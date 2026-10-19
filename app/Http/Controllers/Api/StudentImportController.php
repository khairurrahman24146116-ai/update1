<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentImportController extends Controller
{
    private const MAX_FILE_KB = 5120;

    private const MAX_ROWS = 1000;

    public function __construct(protected StudentService $service) {}

    public function template(Request $request)
    {
        $gender = $request->query('gender', 'L');
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'NIS');
        $sheet->setCellValue('B1', 'NISN');
        $sheet->setCellValue('C1', 'Nama');
        $sheet->setCellValue('D1', 'Kelas');
        $sheet->setCellValue('E1', 'Wali Email');
        $sheet->setCellValue('A2', '2026001');
        $sheet->setCellValue('B2', '0071234567');
        $sheet->setCellValue('C2', 'Andi Putra');
        $sheet->setCellValue('D2', Classroom::first()?->name ?? 'X-1');
        $sheet->setCellValue('E2', 'wali_murid@madani.test');
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmp);

        return response()->download($tmp, 'template_siswa_'.($gender === 'L' ? 'putra' : 'putri').'.xlsx')->deleteFileAfterSend(true);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel|max:'.self::MAX_FILE_KB,
            'gender' => 'required|in:L,P',
        ]);
        $gender = $request->gender;
        $path = $request->file('file')->getRealPath();
        $rows = $this->readRows($path);
        $this->assertRowLimit($rows);
        $result = $this->validateRows($rows, $gender);

        return response()->json($result);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel|max:'.self::MAX_FILE_KB,
            'gender' => 'required|in:L,P',
        ]);
        $gender = $request->gender;
        $path = $request->file('file')->getRealPath();
        $rows = $this->readRows($path);
        $this->assertRowLimit($rows);
        $result = $this->validateRows($rows, $gender);
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $classroomMap = $this->classroomMap();
        $waliMap = $this->waliMap();
        DB::transaction(function () use ($result, $gender, $classroomMap, $waliMap, &$imported, &$skipped, &$failed, &$errors) {
            foreach ($result['rows'] as $r) {
                if ($r['status'] !== 'PASS') {
                    $failed++;

                    continue;
                }
                $data = $r['data'];
                if (Student::where('nisn', $data['nisn'])->exists() || Student::where('nis', $data['nis'])->exists()) {
                    $skipped++;

                    continue;
                }
                $key = strtolower(trim($data['kelas']));
                $classroom = $classroomMap[$key] ?? null;
                if (! $classroom) {
                    $failed++;
                    $errors[] = "Baris {$r['row']}: Kelas tidak ditemukan";

                    continue;
                }
                $parentId = null;
                if (! empty($data['wali_email'])) {
                    $w = $waliMap[strtolower(trim($data['wali_email']))] ?? null;
                    $parentId = $w?->id;
                }
                Student::create(['nis' => $data['nis'], 'nisn' => $data['nisn'], 'gender' => $gender, 'name' => $data['nama'], 'classroom_id' => $classroom->id, 'parent_id' => $parentId, 'status' => 'aktif']);
                $imported++;
            }
        });

        return response()->json(['imported' => $imported, 'skipped' => $skipped, 'failed' => $failed, 'errors' => $errors, 'preview' => $result]);
    }

    private function assertRowLimit(array $rows): void
    {
        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => ['Jumlah baris melebihi batas '.self::MAX_ROWS.' baris. File berisi '.count($rows).' baris.']]);
        }
    }

    private function classroomMap(): array
    {
        $map = [];
        foreach ($this->scopedClassrooms()->get() as $c) {
            $map[strtolower(trim($c->name))] = $c;
        }

        return $map;
    }

    /**
     * Akun non-admin yang memiliki unit hanya dapat mengimpor ke kelas unitnya.
     */
    private function scopedClassrooms()
    {
        $user = request()->user();
        $unit = $user && ! $user->isAdmin() ? ($user->financeUnit() ?? $user->unit) : null;

        return Classroom::query()->when($unit, fn ($q) => $q->where('unit', $unit));
    }

    private function waliMap(): array
    {
        $map = [];
        foreach (User::where('role', 'wali_murid')->where('is_active', true)->get() as $u) {
            $map[strtolower(trim($u->email))] = $u;
        }

        return $map;
    }

    private function findClassroom(string $name): ?Classroom
    {
        $t = strtolower(trim($name));
        if ($t === '') {
            return null;
        }

        return $this->classroomMap()[$t] ?? null;
    }

    private function availableClassNames(): string
    {
        return $this->scopedClassrooms()->orderBy('name')->pluck('name')->implode(', ') ?: '-';
    }

    private function readRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);
        if (empty($data)) {
            return [];
        }
        $headerRow = $data[1] ?? [];
        $norm = fn ($v) => strtolower(trim((string) $v));
        $headerMap = [];
        $hasHeader = false;
        foreach ($headerRow as $col => $val) {
            $n = $norm($val);
            if (in_array($n, ['nis', 'nisn', 'nama', 'name', 'kelas', 'class', 'classroom', 'wali email', 'wali_email', 'wali', 'email wali', 'parent_email'])) {
                $hasHeader = true;
            }
            if ($n === 'nis') {
                $headerMap['nis'] = $col;
            } elseif ($n === 'nisn') {
                $headerMap['nisn'] = $col;
            } elseif (in_array($n, ['nama', 'name'])) {
                $headerMap['nama'] = $col;
            } elseif (in_array($n, ['kelas', 'class', 'classroom'])) {
                $headerMap['kelas'] = $col;
            } elseif (in_array($n, ['wali email', 'wali_email', 'wali', 'email wali', 'parent_email'])) {
                $headerMap['wali_email'] = $col;
            }
        }
        $rows = [];
        foreach ($data as $idx => $row) {
            if ($idx === 1 && $hasHeader) {
                continue;
            }
            if (! $hasHeader && $idx === 1) {
            } elseif ($idx === 1) {
                continue;
            }
            if (empty(array_filter($row, fn ($v) => trim((string) $v) !== ''))) {
                continue;
            }
            if ($hasHeader && count($headerMap) >= 2) {
                $rows[] = [
                    'row' => $idx,
                    'nis' => trim((string) ($row[$headerMap['nis'] ?? 'A'] ?? '')),
                    'nisn' => trim((string) ($row[$headerMap['nisn'] ?? 'B'] ?? '')),
                    'nama' => trim((string) ($row[$headerMap['nama'] ?? 'C'] ?? '')),
                    'kelas' => trim((string) ($row[$headerMap['kelas'] ?? 'D'] ?? '')),
                    'wali_email' => trim((string) ($row[$headerMap['wali_email'] ?? 'E'] ?? '')),
                ];
            } else {
                $rows[] = ['row' => $idx, 'nis' => trim($row['A'] ?? ''), 'nisn' => trim($row['B'] ?? ''), 'nama' => trim($row['C'] ?? ''), 'kelas' => trim($row['D'] ?? ''), 'wali_email' => trim($row['E'] ?? '')];
            }
        }
        if (! $hasHeader && isset($data[1])) {
            $r1 = $data[1];
            if (! empty(array_filter($r1, fn ($v) => trim((string) $v) !== ''))) {
                array_unshift($rows, ['row' => 1, 'nis' => trim($r1['A'] ?? ''), 'nisn' => trim($r1['B'] ?? ''), 'nama' => trim($r1['C'] ?? ''), 'kelas' => trim($r1['D'] ?? ''), 'wali_email' => trim($r1['E'] ?? '')]);
            }
        }

        return $rows;
    }

    private function validateRows(array $rows, string $gender, ?array $map = null): array
    {
        $valid = 0;
        $invalid = 0;
        $seenNis = [];
        $seenNisn = [];
        $out = [];
        $map = $map ?? $this->classroomMap();
        $waliMap = $this->waliMap();
        $available = $this->availableClassNames();
        $nisList = array_values(array_filter(array_map(fn ($r) => $r['nis'] ?? '', $rows)));
        $nisnList = array_values(array_filter(array_map(fn ($r) => $r['nisn'] ?? '', $rows)));
        $existingNis = $nisList ? Student::whereIn('nis', $nisList)->pluck('nis')->map(fn ($v) => (string) $v)->flip()->toArray() : [];
        $existingNisn = $nisnList ? Student::whereIn('nisn', $nisnList)->pluck('nisn')->map(fn ($v) => (string) $v)->flip()->toArray() : [];
        foreach ($rows as $r) {
            $errs = [];
            if (empty($r['nama'])) {
                $errs[] = 'Nama kosong';
            }
            if (empty($r['nis'])) {
                $errs[] = 'NIS kosong';
            }
            if (empty($r['nisn'])) {
                $errs[] = 'NISN kosong';
            }
            if (empty($r['kelas'])) {
                $errs[] = 'Kelas kosong';
            }
            if (! empty($r['kelas']) && ! isset($map[strtolower(trim($r['kelas']))])) {
                $errs[] = "Kelas '{$r['kelas']}' tidak ditemukan. Kelas tersedia: {$available}";
            }
            if (! empty($r['nis']) && isset($seenNis[$r['nis']])) {
                $errs[] = 'NIS duplicate dalam file';
            }
            if (! empty($r['nisn']) && isset($seenNisn[$r['nisn']])) {
                $errs[] = 'NISN duplicate dalam file';
            }
            if (! empty($r['nis']) && isset($existingNis[$r['nis']])) {
                $errs[] = 'NIS sudah ada di database';
            }
            if (! empty($r['nisn']) && isset($existingNisn[$r['nisn']])) {
                $errs[] = 'NISN sudah ada di database';
            }
            if (! empty($r['wali_email'])) {
                $w = $waliMap[strtolower(trim($r['wali_email']))] ?? null;
                if (! $w) {
                    $errs[] = "Wali Email '{$r['wali_email']}' tidak ditemukan atau bukan wali_murid aktif";
                }
            }
            if (! empty($r['nis'])) {
                $seenNis[$r['nis']] = true;
            }
            if (! empty($r['nisn'])) {
                $seenNisn[$r['nisn']] = true;
            }
            $status = empty($errs) ? 'PASS' : 'ERROR';
            if ($status === 'PASS') {
                $valid++;
            } else {
                $invalid++;
            }
            $out[] = ['row' => $r['row'], 'data' => $r, 'status' => $status, 'errors' => $errs];
        }

        return ['total' => count($rows), 'valid' => $valid, 'invalid' => $invalid, 'rows' => $out];
    }
}
