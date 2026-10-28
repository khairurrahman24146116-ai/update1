<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Student;
use App\Models\Unit;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class StudentImportService
{
    public const MAX_ROWS = 2000;

    public function __construct(protected StudentImportParser $parser) {}

    public function stage(User $user, UploadedFile $file, int $academicYearId, bool $updateExisting): ImportBatch
    {
        $year = AcademicYear::findOrFail($academicYearId);
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xlsx');

        $parsed = $this->parser->parse($file->getRealPath(), $extension);

        if ($parsed['rows'] === []) {
            throw ValidationException::withMessages([
                'file' => ['File tidak berisi baris data siswa.'],
            ]);
        }

        if (count($parsed['rows']) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'file' => ['Jumlah baris melebihi batas '.self::MAX_ROWS.'. File berisi '.count($parsed['rows']).' baris.'],
            ]);
        }

        $input = [];
        foreach ($parsed['rows'] as $row) {
            $input[] = [
                'nomor_baris' => $row['nomor_baris'],
                'data_mentah' => $row['data_mentah'],
                'data_valid' => $this->normalizeRow($row['data']),
            ];
        }

        $validated = $this->validateRows($input, $year, $updateExisting, false);

        return DB::transaction(function () use ($user, $file, $year, $updateExisting, $validated, $parsed) {
            $errorCount = collect($validated)->where('status', ImportRow::STATUS_ERROR)->count();
            $warningCount = collect($validated)->where('status', ImportRow::STATUS_WARNING)->count();

            $batch = ImportBatch::create([
                'file_name' => $file->getClientOriginalName(),
                'diimport_oleh' => $user->id,
                'tahun_ajaran_id' => $year->id,
                'total_baris' => count($validated),
                'berhasil' => 0,
                'gagal' => $errorCount,
                'peringatan' => $warningCount,
                'update_existing' => $updateExisting,
                'status' => ImportBatch::STATUS_PREVIEW,
            ]);

            foreach (array_chunk($validated, 200) as $chunk) {
                foreach ($chunk as $row) {
                    $batch->rows()->create([
                        'nomor_baris' => $row['nomor_baris'],
                        'data_mentah' => $row['data_mentah'],
                        'data_valid' => $row['data_valid'],
                        'status' => $row['status'],
                        'pesan' => $row['pesan'],
                        'aksi' => $row['aksi'],
                    ]);
                }
            }

            ActivityLogger::log('import.preview', $user, ImportBatch::class, $batch->id, [
                'file_name' => $batch->file_name,
                'tahun_ajaran_id' => $year->id,
                'total_baris' => $batch->total_baris,
                'gagal' => $errorCount,
                'peringatan' => $warningCount,
                'mode' => $updateExisting ? 'update' : 'insert',
                'unknown_headers' => $parsed['unknown_headers'],
            ]);

            return $batch->fresh(['academicYear', 'importer']);
        });
    }

    /**
     * Konfirmasi selalu memvalidasi ulang di dalam transaksi dan mengunci
     * batch agar tidak bisa dikonfirmasi dua kali.
     */
    public function confirm(ImportBatch $batch, User $user): ImportBatch
    {
        try {
            return DB::transaction(function () use ($batch, $user) {
                /** @var ImportBatch $locked */
                $locked = ImportBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();

                if (! $locked->isPreview()) {
                    abort(409, 'Batch import ini sudah dikonfirmasi atau tidak lagi berstatus preview.');
                }

                $year = AcademicYear::findOrFail($locked->tahun_ajaran_id);
                $rows = $locked->rows()->orderBy('nomor_baris')->get();

                if ($rows->isEmpty()) {
                    $locked->update([
                        'status' => ImportBatch::STATUS_CONFIRMED,
                        'confirmed_at' => now(),
                        'hasil' => ['berhasil' => [], 'errors' => [], 'peringatan' => []],
                    ]);

                    return $locked->fresh(['academicYear', 'importer']);
                }

                $input = $rows->map(fn (ImportRow $row) => [
                    'nomor_baris' => $row->nomor_baris,
                    'data_mentah' => $row->data_mentah ?? [],
                    'data_valid' => $row->data_valid ?? [],
                ])->all();

                $validated = $this->validateRows($input, $year, (bool) $locked->update_existing, true);

                $errorRows = collect($validated)->where('status', ImportRow::STATUS_ERROR);

                if ($errorRows->isNotEmpty()) {
                    $numbers = $errorRows->pluck('nomor_baris')->take(10)->implode(', ');
                    $suffix = $errorRows->count() > 10 ? ', ...' : '';

                    abort(422, "Import dibatalkan: {$errorRows->count()} baris masih error (baris {$numbers}{$suffix}). Tidak ada data yang disimpan. Perbaiki file lalu upload ulang.");
                }

                $hasil = ['berhasil' => [], 'errors' => [], 'peringatan' => []];
                $rowByNumber = $rows->keyBy('nomor_baris');

                foreach ($validated as $result) {
                    $row = $rowByNumber->get($result['nomor_baris']);
                    if (! $row) {
                        continue;
                    }

                    $data = $result['data_valid'];

                    if ($result['status'] === ImportRow::STATUS_ERROR) {
                        $row->update([
                            'status' => ImportRow::STATUS_ERROR,
                            'pesan' => $result['pesan'],
                            'aksi' => null,
                        ]);

                        $hasil['errors'][] = $this->resultRow($result);

                        continue;
                    }

                    $student = null;
                    $aksi = 'create';

                    if ($locked->update_existing && ! empty($data['existing_student_id'])) {
                        $student = Student::whereKey($data['existing_student_id'])->lockForUpdate()->first();

                        if (! $student) {
                            $result['status'] = ImportRow::STATUS_ERROR;
                            $result['pesan'][] = [
                                'tingkat' => 'error',
                                'pesan' => 'Data siswa tidak ditemukan saat konfirmasi. Ulangi preview.',
                            ];
                            $row->update([
                                'status' => ImportRow::STATUS_ERROR,
                                'pesan' => $result['pesan'],
                                'aksi' => null,
                            ]);
                            $hasil['errors'][] = $this->resultRow($result);

                            continue;
                        }

                        $this->applyStudentUpdates($student, $data);
                        $aksi = 'update';
                    } else {
                        $student = $this->createStudent($data);
                    }

                    if ($data['will_create_enrollment'] ?? false) {
                        $this->createEnrollments($student, $data, $year);
                    }

                    $row->update([
                        'status' => $result['status'],
                        'pesan' => $result['pesan'],
                        'aksi' => $aksi,
                        'student_id' => $student->id,
                    ]);

                    $success = [
                        'nomor_baris' => $result['nomor_baris'],
                        'student_id' => $student->id,
                        'nis' => $student->nis,
                        'nisn' => $student->nisn,
                        'nama' => $student->name,
                        'kelas' => $data['kelas'] ?? null,
                        'unit' => $data['unit'] ?? null,
                        'aksi' => $aksi,
                    ];

                    $hasil['berhasil'][] = $success;

                    if ($result['status'] === ImportRow::STATUS_WARNING) {
                        $hasil['peringatan'][] = $success + ['pesan' => $result['pesan']];
                    }
                }

                $locked->update([
                    'status' => ImportBatch::STATUS_CONFIRMED,
                    'berhasil' => count($hasil['berhasil']),
                    'gagal' => count($hasil['errors']),
                    'peringatan' => count($hasil['peringatan']),
                    'hasil' => $hasil,
                    'confirmed_at' => now(),
                ]);

                ImportRow::where('batch_id', $locked->id)->delete();

                ActivityLogger::log('import.confirm', $user, ImportBatch::class, $locked->id, [
                    'file_name' => $locked->file_name,
                    'tahun_ajaran_id' => $locked->tahun_ajaran_id,
                    'berhasil' => count($hasil['berhasil']),
                    'gagal' => count($hasil['errors']),
                    'peringatan' => count($hasil['peringatan']),
                    'mode' => $locked->update_existing ? 'update' : 'insert',
                ]);

                return $locked->fresh(['academicYear', 'importer']);
            });
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            ImportBatch::whereKey($batch->id)->update([
                'status' => ImportBatch::STATUS_FAILED,
            ]);

            ActivityLogger::log('import.failed', $user, ImportBatch::class, $batch->id, [
                'file_name' => $batch->file_name,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function previewPayload(ImportBatch $batch, array $filters = []): array
    {
        $perPage = min(200, max(10, (int) ($filters['per_page'] ?? 50)));

        $rows = $batch->rows()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('nomor_baris')
            ->paginate($perPage)
            ->withQueryString();

        $counts = $batch->rows()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'batch' => $batch->load(['academicYear', 'importer']),
            'summary' => [
                'total' => $batch->total_baris,
                'valid' => (int) ($counts[ImportRow::STATUS_VALID] ?? 0),
                'peringatan' => (int) ($counts[ImportRow::STATUS_WARNING] ?? 0),
                'error' => (int) ($counts[ImportRow::STATUS_ERROR] ?? 0),
                'berhasil' => $batch->berhasil,
                'gagal' => $batch->gagal,
            ],
            'rows' => $rows,
        ];
    }

    public function errorCsv(ImportBatch $batch): string
    {
        $errors = $batch->hasil['errors'] ?? null;

        if (! is_array($errors)) {
            $errors = $batch->rows()
                ->where('status', ImportRow::STATUS_ERROR)
                ->orderBy('nomor_baris')
                ->get()
                ->map(fn (ImportRow $row) => $this->resultRow([
                    'nomor_baris' => $row->nomor_baris,
                    'data_valid' => $row->data_valid ?? [],
                    'pesan' => $row->pesan ?? [],
                ]))
                ->all();
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Nomor Baris', 'NIS', 'NISN', 'Nama', 'Kelas', 'Unit', 'Status', 'Pesan']);

        foreach ($errors as $error) {
            fputcsv($handle, [
                $this->csvSafe((string) ($error['nomor_baris'] ?? '')),
                $this->csvSafe((string) ($error['nis'] ?? '')),
                $this->csvSafe((string) ($error['nisn'] ?? '')),
                $this->csvSafe((string) ($error['nama'] ?? '')),
                $this->csvSafe((string) ($error['kelas'] ?? '')),
                $this->csvSafe((string) ($error['unit'] ?? '')),
                $this->csvSafe((string) ($error['status'] ?? 'error')),
                $this->csvSafe($this->messageText($error['pesan'] ?? [])),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    /**
     * @param  array<int, array{nomor_baris: int, data_mentah: array, data_valid: array}>  $rows
     * @return array<int, array{nomor_baris: int, data_mentah: array, data_valid: array, status: string, pesan: array, aksi: ?string}>
     */
    private function validateRows(array $rows, AcademicYear $year, bool $updateExisting, bool $lock): array
    {
        $classes = $this->classesForYear($year, $lock);
        $classById = $classes->keyBy('id');
        $classMap = [];
        foreach ($classes as $class) {
            $classMap[$this->comparable($class->name)][] = $class;
        }

        $normalized = [];

        foreach ($rows as $index => $row) {
            $data = $row['data_valid'];
            $messages = [];
            $class = null;

            if (($data['nama'] ?? '') === '') {
                $this->addMessage($messages, 'error', 'Nama wajib diisi.');
            }

            if (($data['nis'] ?? '') === '') {
                $this->addMessage($messages, 'error', 'NIS wajib diisi.');
            }

            if (($data['nisn'] ?? '') === '') {
                $this->addMessage($messages, 'peringatan', 'NISN kosong.');
            } elseif (preg_match('/^\d{10}$/', (string) $data['nisn']) !== 1) {
                $this->addMessage($messages, 'peringatan', 'Format NISN bukan 10 digit.');
            }

            if (empty($data['jenis_kelamin'])) {
                $this->addMessage($messages, 'error', 'Jenis kelamin harus L atau P.');
            }

            if (empty($data['asal_siswa'])) {
                $this->addMessage($messages, 'error', 'Asal siswa harus internal atau ppdb.');
            }

            if (($data['kelas'] ?? '') === '') {
                $this->addMessage($messages, 'error', 'Kelas wajib diisi.');
            } else {
                $unit = (string) ($data['unit'] ?? '');
                $candidates = $classMap[$this->comparable($data['kelas'])] ?? [];

                if ($unit !== '') {
                    if (! Unit::isEnabledCode($unit)) {
                        $this->addMessage($messages, 'error', "Unit '{$unit}' tidak dikenal. Gunakan LPI, SMA, atau SMP.");
                    }

                    $candidates = array_values(array_filter(
                        $candidates,
                        fn (Classroom $candidate): bool => $this->classUnit($candidate) === $unit,
                    ));
                }

                if (count($candidates) === 1) {
                    $class = $candidates[0];

                    if (empty($class->jalur) || empty($class->jenjang)) {
                        $this->addMessage($messages, 'error', "Kelas '{$class->name}' belum memiliki jalur/jenjang. Lengkapi data kelas terlebih dahulu.");
                    }
                } elseif (count($candidates) > 1) {
                    $this->addMessage($messages, 'error', "Kelas '{$data['kelas']}' ambigu pada tahun ajaran {$year->name}. Isi kolom Unit untuk membedakan.");
                } else {
                    $unitSuffix = $unit !== '' ? " pada unit '{$unit}'" : '';
                    $this->addMessage($messages, 'error', "Kelas '{$data['kelas']}' tidak ditemukan{$unitSuffix} pada tahun ajaran {$year->name}.");
                }
            }

            if (($data['_tanggal_lahir_isi'] ?? false) && ! ($data['_tanggal_lahir_valid'] ?? false)) {
                $this->addMessage($messages, 'peringatan', 'Tanggal lahir tidak dapat dibaca; disimpan kosong.');
            }

            if (($data['_no_hp_isi'] ?? false) && ! ($data['_no_hp_valid'] ?? false)) {
                $this->addMessage($messages, 'peringatan', 'No HP wali tidak valid; disimpan kosong.');
            }

            $data['class_id'] = $class?->id;
            $data['jalur'] = $class?->jalur;
            $data['jenjang'] = $class?->jenjang;
            $data['tingkat'] = $class?->grade;
            $data['lpi_class_id'] = null;
            $data['existing_student_id'] = null;
            $data['will_create_enrollment'] = false;

            $normalized[$index] = [
                'nomor_baris' => $row['nomor_baris'],
                'data_mentah' => $row['data_mentah'],
                'data_valid' => $data,
                'status' => ImportRow::STATUS_VALID,
                'pesan' => $messages,
                'aksi' => null,
            ];
        }

        $nisnList = collect($normalized)->pluck('data_valid.nisn')->filter(fn ($value) => $value !== null && $value !== '')->unique()->values()->all();
        $nisList = collect($normalized)->pluck('data_valid.nis')->filter(fn ($value) => $value !== null && $value !== '')->unique()->values()->all();

        $students = $this->findStudents($nisnList, $nisList, $lock);
        $studentByNisn = $students->whereNotNull('nisn')->keyBy('nisn');
        $studentByNis = $students->keyBy('nis');

        $studentIds = $students->pluck('id')->all();
        $enrollments = $studentIds === []
            ? collect()
            : Enrollment::whereIn('student_id', $studentIds)
                ->where('tahun_ajaran_id', $year->id)
                ->get()
                ->groupBy('student_id');

        $phones = collect($normalized)->pluck('data_valid.no_hp_wali')->filter()->unique()->values()->all();
        $phoneStudents = $phones === []
            ? new EloquentCollection
            : Student::whereIn('no_hp_wali', $phones)->get(['id', 'no_hp_wali', 'nama_ayah', 'nama_ibu', 'name']);

        $classIds = collect($normalized)->pluck('data_valid.class_id')->filter()->unique()->values()->all();
        $classStudents = $classIds === []
            ? new EloquentCollection
            : Student::whereIn('classroom_id', $classIds)->get(['id', 'name', 'classroom_id']);

        $lpiInternal = $this->resolveLpiClass($classes, Classroom::JENJANG_ALIYAH, 10);
        $lpiPpdb = $this->resolveLpiClass($classes, Classroom::JENJANG_TSANAWIYAH, 7);

        foreach ($normalized as &$result) {
            $data = &$result['data_valid'];
            $messages = &$result['pesan'];

            $byNisn = ($data['nisn'] ?? '') !== '' ? $studentByNisn->get($data['nisn']) : null;
            $byNis = ($data['nis'] ?? '') !== '' ? $studentByNis->get($data['nis']) : null;

            if ($byNisn && $byNis && $byNisn->id !== $byNis->id) {
                $this->addMessage($messages, 'error', 'NISN dan NIS merujuk ke siswa yang berbeda.');
            }

            $target = $byNisn ?: $byNis;

            if ($target) {
                if (! $updateExisting) {
                    $this->addMessage($messages, 'error', 'NIS/NISN sudah terdaftar. Gunakan mode update data yang sudah ada.');
                } else {
                    $data['existing_student_id'] = $target->id;
                    $result['aksi'] = 'update';
                }
            } else {
                $result['aksi'] = 'create';
            }

            if ($byNis && $target && $byNis->id !== $target->id) {
                $this->addMessage($messages, 'error', 'NIS sudah dipakai oleh siswa lain.');
            }

            $existingEnrollments = $target ? ($enrollments->get($target->id) ?? collect()) : collect();
            $classJalur = $data['jalur'] ?: null;

            if ($updateExisting && $existingEnrollments->isNotEmpty() && $classJalur) {
                $existingForJalur = $existingEnrollments->where('jalur', $classJalur);

                if ($existingForJalur->isNotEmpty()) {
                    $data['will_create_enrollment'] = false;

                    if ($data['class_id'] && ! $existingForJalur->contains('class_id', $data['class_id'])) {
                        $classNames = $existingForJalur
                            ->map(fn (Enrollment $enrollment) => $classById->get($enrollment->class_id)?->name)
                            ->filter()
                            ->implode(', ');

                        $this->addMessage(
                            $messages,
                            'peringatan',
                            "Mode update tidak mengubah kelas. Kelas existing: {$classNames}; kelas file: {$data['kelas']}.",
                        );
                    }
                } else {
                    $data['will_create_enrollment'] = true;
                }
            } else {
                $data['will_create_enrollment'] = true;
            }

            if ($data['will_create_enrollment'] && $data['class_id']) {
                $class = $classById->get($data['class_id']);

                if ($class?->isFormalSmaGradeTen()) {
                    $lpi = $data['asal_siswa'] === 'internal' ? $lpiInternal : $lpiPpdb;

                    if (! $lpi) {
                        $this->addMessage($messages, 'peringatan', 'Kelas LPI tujuan tidak ditemukan; enrollment LPI otomatis dilewati.');
                    } else {
                        $hasLpi = $existingEnrollments->where('jalur', Enrollment::JALUR_LPI)->isNotEmpty();

                        if (! $hasLpi) {
                            $data['lpi_class_id'] = $lpi->id;
                        }
                    }
                }
            }
        }
        unset($result, $data, $messages);

        $this->markDuplicateIdentifiers($normalized, 'nis', 'NIS kembar dalam file.');
        $this->markDuplicateIdentifiers($normalized, 'nisn', 'NISN kembar dalam file.');
        $this->markSameNameWarnings($normalized, $classStudents);
        $this->markPhoneWarnings($normalized, $phoneStudents);

        foreach ($normalized as &$result) {
            $hasError = collect($result['pesan'])->contains('tingkat', 'error');
            $hasWarning = collect($result['pesan'])->contains('tingkat', 'peringatan');
            $result['status'] = $hasError
                ? ImportRow::STATUS_ERROR
                : ($hasWarning ? ImportRow::STATUS_WARNING : ImportRow::STATUS_VALID);
        }
        unset($result);

        return $normalized;
    }

    /**
     * @param  array<int, array{nomor_baris: int, data_mentah: array, data_valid: array}>  $rows
     */
    private function normalizeRow(array $raw): array
    {
        $date = $this->normalizeDate((string) ($raw['tanggal_lahir'] ?? ''));
        $phoneRaw = trim((string) ($raw['no_hp_wali'] ?? ''));

        return [
            'nis' => $this->cleanIdentifier($raw['nis'] ?? ''),
            'nisn' => $this->normalizeNisn($raw['nisn'] ?? ''),
            'nama' => $this->cleanText($raw['nama'] ?? ''),
            'jenis_kelamin' => $this->normalizeGender($raw['jenis_kelamin'] ?? ''),
            'tanggal_lahir' => $date['value'],
            'kelas' => $this->cleanText($raw['kelas'] ?? ''),
            'unit' => $this->normalizeUnit($raw['unit'] ?? ''),
            'asal_siswa' => $this->normalizeAsal($raw['asal_siswa'] ?? ''),
            'nama_ayah' => $this->cleanText($raw['nama_ayah'] ?? ''),
            'nama_ibu' => $this->cleanText($raw['nama_ibu'] ?? ''),
            'nama_wali' => $this->cleanText($raw['nama_wali'] ?? ''),
            'no_hp_wali' => PhoneNormalizer::normalize($phoneRaw),
            '_tanggal_lahir_isi' => $date['value'] !== null || trim((string) ($raw['tanggal_lahir'] ?? '')) !== '',
            '_tanggal_lahir_valid' => $date['valid'],
            '_no_hp_isi' => $phoneRaw !== '',
            '_no_hp_valid' => PhoneNormalizer::normalize($phoneRaw) !== null,
        ];
    }

    private function normalizeNisn(mixed $value): string
    {
        $value = $this->cleanIdentifier($value);

        if ($value === '') {
            return '';
        }

        if (ctype_digit($value) && strlen($value) < 10) {
            return str_pad($value, 10, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    private function normalizeGender(mixed $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z]+/', ' ', $value) ?? $value;
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        return match ($value) {
            'l', 'laki laki', 'lakilaki', 'pria', 'putra' => 'L',
            'p', 'perempuan', 'wanita', 'putri' => 'P',
            default => null,
        };
    }

    private function normalizeUnit(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function normalizeAsal(mixed $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z]+/', ' ', $value) ?? $value;
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        return match ($value) {
            'internal', 'siswa internal', 'lama', 'siswa lama' => 'internal',
            'ppdb', 'baru', 'siswa baru', 'pendaftar', 'pendaftaran' => 'ppdb',
            default => null,
        };
    }

    /**
     * @return array{value: ?string, valid: bool}
     */
    private function normalizeDate(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return ['value' => null, 'valid' => false];
        }

        if (is_numeric($value) && (float) $value > 20000) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);

                return ['value' => $date->format('Y-m-d'), 'valid' => true];
            } catch (Throwable) {
                return ['value' => null, 'valid' => false];
            }
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd M Y', 'j/n/Y', 'd/m/y'] as $format) {
            if (! Carbon::hasFormat($value, $format)) {
                continue;
            }

            try {
                $date = Carbon::createFromFormat('!'.$format, $value);

                return ['value' => $date->format('Y-m-d'), 'valid' => true];
            } catch (Throwable) {
                continue;
            }
        }

        try {
            return ['value' => Carbon::parse($value)->format('Y-m-d'), 'valid' => true];
        } catch (Throwable) {
            return ['value' => null, 'valid' => false];
        }
    }

    private function cleanText(mixed $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function cleanIdentifier(mixed $value): string
    {
        $value = trim((string) $value);

        return preg_replace('/[\s\'"`]+/u', '', $value) ?? $value;
    }

    private function comparable(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /**
     * @param  list<string>  $nisnList
     * @param  list<string>  $nisList
     */
    private function findStudents(array $nisnList, array $nisList, bool $lock): EloquentCollection
    {
        if ($nisnList === [] && $nisList === []) {
            return new EloquentCollection;
        }

        $query = Student::query()->where(function ($query) use ($nisnList, $nisList) {
            $hasCondition = false;

            if ($nisnList !== []) {
                $query->whereIn('nisn', $nisnList);
                $hasCondition = true;
            }

            if ($nisList !== []) {
                if ($hasCondition) {
                    $query->orWhereIn('nis', $nisList);
                } else {
                    $query->whereIn('nis', $nisList);
                }
            }
        });

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    private function classesForYear(AcademicYear $year, bool $lock): EloquentCollection
    {
        $query = Classroom::query()
            ->where(function ($query) use ($year) {
                $query->where('tahun_ajaran_id', $year->id)
                    ->orWhere(function ($query) use ($year) {
                        $query->whereNull('tahun_ajaran_id')->where('academic_year', $year->name);
                    });
            })
            ->orderBy('name');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    private function classUnit(Classroom $classroom): string
    {
        return strtolower(trim((string) $classroom->unit));
    }

    private function resolveLpiClass(Collection $classes, string $jenjang, int $grade): ?Classroom
    {
        $candidates = $classes->filter(fn (Classroom $class) => $class->jalur === Classroom::JALUR_LPI
            && $class->jenjang === $jenjang
            && (int) $class->grade === $grade);

        if ($candidates->isEmpty()) {
            return null;
        }

        $preferred = $candidates->first(
            fn (Classroom $class) => $this->comparable($class->name) === $this->comparable($jenjang.' 1'),
        );

        return $preferred ?? $candidates->sortBy('name')->first();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function markDuplicateIdentifiers(array &$rows, string $field, string $message): void
    {
        $groups = [];

        foreach ($rows as $index => $row) {
            $value = $row['data_valid'][$field] ?? '';

            if ($value !== null && $value !== '') {
                $groups[$value][] = $index;
            }
        }

        foreach ($groups as $indices) {
            if (count($indices) < 2) {
                continue;
            }

            foreach ($indices as $index) {
                $this->addMessage($rows[$index]['pesan'], 'error', $message);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function markSameNameWarnings(array &$rows, EloquentCollection $classStudents): void
    {
        $fileGroups = [];
        $dbGroups = [];

        foreach ($classStudents as $student) {
            $dbGroups[$student->classroom_id][$this->comparable($student->name)][] = $student->id;
        }

        foreach ($rows as $index => $row) {
            $classId = $row['data_valid']['class_id'] ?? null;
            $name = $this->comparable($row['data_valid']['nama'] ?? '');

            if (! $classId || $name === '') {
                continue;
            }

            $fileGroups[$classId.'|'.$name][] = $index;
        }

        foreach ($fileGroups as $indices) {
            if (count($indices) < 2) {
                continue;
            }

            foreach ($indices as $index) {
                $this->addMessage($rows[$index]['pesan'], 'peringatan', 'Nama sama di kelas yang sama (mungkin kembar nama).');
            }
        }

        foreach ($rows as $index => $row) {
            $classId = $row['data_valid']['class_id'] ?? null;
            $name = $this->comparable($row['data_valid']['nama'] ?? '');
            $existingId = $row['data_valid']['existing_student_id'] ?? null;

            if (! $classId || $name === '') {
                continue;
            }

            $ids = $dbGroups[$classId][$name] ?? [];
            $ids = array_values(array_filter($ids, fn ($id) => (int) $id !== (int) $existingId));

            if ($ids !== []) {
                $this->addMessage($rows[$index]['pesan'], 'peringatan', 'Nama sama dengan siswa lain di kelas yang sama (mungkin kembar nama).');
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function markPhoneWarnings(array &$rows, EloquentCollection $phoneStudents): void
    {
        $fileGroups = [];
        $dbGroups = [];

        foreach ($phoneStudents as $student) {
            $dbGroups[$student->no_hp_wali][] = [
                'id' => $student->id,
                'parent' => $this->parentKey($student->nama_ayah, $student->nama_ibu),
            ];
        }

        foreach ($rows as $index => $row) {
            $phone = $row['data_valid']['no_hp_wali'] ?? null;

            if ($phone) {
                $fileGroups[$phone][] = $index;
            }
        }

        foreach ($fileGroups as $indices) {
            if (count($indices) < 2) {
                continue;
            }

            $parents = [];

            foreach ($indices as $index) {
                $row = $rows[$index];
                $parents[$this->parentKey($row['data_valid']['nama_ayah'] ?? null, $row['data_valid']['nama_ibu'] ?? null)] = true;
            }

            if (count($parents) < 2) {
                continue;
            }

            foreach ($indices as $index) {
                $this->addMessage($rows[$index]['pesan'], 'peringatan', 'No HP sama dengan keluarga lain (nama ayah/ibu berbeda).');
            }
        }

        foreach ($rows as $index => $row) {
            $phone = $row['data_valid']['no_hp_wali'] ?? null;

            if (! $phone) {
                continue;
            }

            $existingId = $row['data_valid']['existing_student_id'] ?? null;
            $parent = $this->parentKey($row['data_valid']['nama_ayah'] ?? null, $row['data_valid']['nama_ibu'] ?? null);

            foreach ($dbGroups[$phone] ?? [] as $existing) {
                if ((int) $existing['id'] === (int) $existingId) {
                    continue;
                }

                if ($existing['parent'] !== $parent) {
                    $this->addMessage($rows[$index]['pesan'], 'peringatan', 'No HP sama dengan keluarga lain (nama ayah/ibu berbeda).');

                    break;
                }
            }
        }
    }

    private function parentKey(?string $ayah, ?string $ibu): string
    {
        return $this->comparable($ayah).'|'.$this->comparable($ibu);
    }

    private function createStudent(array $data): Student
    {
        return Student::create([
            'nis' => $data['nis'],
            'nisn' => ($data['nisn'] ?? '') !== '' ? $data['nisn'] : null,
            'gender' => $data['jenis_kelamin'],
            'name' => $data['nama'],
            'classroom_id' => $data['class_id'],
            'status' => 'aktif',
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'asal_siswa' => $data['asal_siswa'] ?? null,
            'nama_ayah' => ($data['nama_ayah'] ?? '') !== '' ? $data['nama_ayah'] : null,
            'nama_ibu' => ($data['nama_ibu'] ?? '') !== '' ? $data['nama_ibu'] : null,
            'nama_wali' => ($data['nama_wali'] ?? '') !== '' ? $data['nama_wali'] : null,
            'no_hp_wali' => $data['no_hp_wali'] ?? null,
        ]);
    }

    private function applyStudentUpdates(Student $student, array $data): void
    {
        $updates = [];
        $map = [
            'nama' => 'name',
            'jenis_kelamin' => 'gender',
            'tanggal_lahir' => 'tanggal_lahir',
            'asal_siswa' => 'asal_siswa',
            'nama_ayah' => 'nama_ayah',
            'nama_ibu' => 'nama_ibu',
            'nama_wali' => 'nama_wali',
        ];

        foreach ($map as $source => $column) {
            if (($data[$source] ?? null) !== null && $data[$source] !== '') {
                $updates[$column] = $data[$source];
            }
        }

        if (! empty($data['no_hp_wali'])) {
            $updates['no_hp_wali'] = $data['no_hp_wali'];
        }

        if (! empty($data['nis']) && $data['nis'] !== $student->nis) {
            $taken = Student::where('nis', $data['nis'])->whereKeyNot($student->id)->exists();

            if (! $taken) {
                $updates['nis'] = $data['nis'];
            }
        }

        if (($student->nisn === null || $student->nisn === '') && ! empty($data['nisn'])) {
            $taken = Student::where('nisn', $data['nisn'])->whereKeyNot($student->id)->exists();

            if (! $taken) {
                $updates['nisn'] = $data['nisn'];
            }
        }

        if ($updates !== []) {
            $student->update($updates);
        }
    }

    private function createEnrollments(Student $student, array $data, AcademicYear $year): void
    {
        if (empty($data['class_id'])) {
            return;
        }

        $class = Classroom::find($data['class_id']);

        if (! $class) {
            return;
        }

        $primaryExists = Enrollment::where('student_id', $student->id)
            ->where('tahun_ajaran_id', $year->id)
            ->where('jalur', $class->jalur)
            ->exists();

        if (! $primaryExists) {
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'tahun_ajaran_id' => $year->id,
                'jalur' => $class->jalur,
                'status' => Enrollment::STATUS_AKTIF,
            ]);
        }

        if (! empty($data['lpi_class_id'])) {
            $lpiClass = Classroom::find($data['lpi_class_id']);

            if (! $lpiClass) {
                return;
            }

            $lpiExists = Enrollment::where('student_id', $student->id)
                ->where('tahun_ajaran_id', $year->id)
                ->where('jalur', Enrollment::JALUR_LPI)
                ->exists();

            if (! $lpiExists) {
                Enrollment::create([
                    'student_id' => $student->id,
                    'class_id' => $lpiClass->id,
                    'tahun_ajaran_id' => $year->id,
                    'jalur' => Enrollment::JALUR_LPI,
                    'status' => Enrollment::STATUS_AKTIF,
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{tingkat: string, pesan: string}>  $messages
     */
    private function addMessage(array &$messages, string $level, string $text): void
    {
        $messages[] = ['tingkat' => $level, 'pesan' => $text];
    }

    /**
     * @param  array{nomor_baris: int, data_valid: array, pesan: array}  $result
     * @return array<string, mixed>
     */
    private function resultRow(array $result): array
    {
        $data = $result['data_valid'] ?? [];

        return [
            'nomor_baris' => $result['nomor_baris'] ?? null,
            'nis' => $data['nis'] ?? null,
            'nisn' => $data['nisn'] ?? null,
            'nama' => $data['nama'] ?? null,
            'kelas' => $data['kelas'] ?? null,
            'unit' => $data['unit'] ?? null,
            'status' => $result['status'] ?? ImportRow::STATUS_ERROR,
            'pesan' => $result['pesan'] ?? [],
        ];
    }

    /**
     * @param  array<int, array{tingkat: string, pesan: string}>|null  $messages
     */
    private function messageText(?array $messages): string
    {
        if (! is_array($messages)) {
            return '';
        }

        return collect($messages)
            ->map(fn ($message) => $message['pesan'] ?? (string) $message)
            ->implode('; ');
    }

    private function csvSafe(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
