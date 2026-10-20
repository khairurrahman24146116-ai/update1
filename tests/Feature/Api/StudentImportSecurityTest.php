<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportSecurityTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $year;

    private Classroom $formalClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->year = AcademicYear::firstOrCreate(['name' => '2026/2027'], ['is_active' => true]);
        $this->formalClass = $this->classroom('X-1', 10, Classroom::JALUR_FORMAL, Classroom::JENJANG_SMA);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function classroom(string $name, int $grade, string $jalur, string $jenjang, string $unit = 'sma'): Classroom
    {
        return Classroom::factory()->forYear($this->year)->create([
            'name' => $name,
            'grade' => $grade,
            'jalur' => $jalur,
            'jenjang' => $jenjang,
            'unit' => $unit,
            'academic_year' => $this->year->name,
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  list<string>|null  $headers
     */
    private function csv(array $rows, ?array $headers = null): UploadedFile
    {
        $headers ??= ['NIS', 'NISN', 'Nama', 'Jenis Kelamin', 'Kelas', 'Asal Siswa', 'No HP Wali', 'Nama Ayah', 'Nama Ibu'];
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return UploadedFile::fake()->createWithContent('import-siswa.csv', $content);
    }

    private function preview(User $user, UploadedFile $file, bool $update = false)
    {
        return $this->post('/api/import-siswa/preview', [
            'file' => $file,
            'tahun_ajaran_id' => $this->year->id,
            'update_existing' => $update ? '1' : '0',
        ], ['Accept' => 'application/json']);
    }

    public function test_preview_maps_headers_by_name_and_does_not_write_students(): void
    {
        $this->classroom('Aliyah 1', 10, Classroom::JALUR_LPI, Classroom::JENJANG_ALIYAH, 'lpi');
        $headers = ['Kelas', 'Nama', 'NISN', 'NIS', 'Jenis Kelamin', 'No HP Wali', 'Asal Siswa', 'Catatan'];

        $response = $this->actingAs($this->admin())->preview($this->admin(), $this->csv([
            [
                'Kelas' => 'X-1',
                'Nama' => 'Andi Putra',
                'NISN' => '0071234567',
                'NIS' => '2026001',
                'Jenis Kelamin' => 'L',
                'No HP Wali' => '+62 812-3456-7890',
                'Asal Siswa' => 'internal',
                'Catatan' => 'diabaikan',
            ],
        ], $headers));

        $response->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.valid', 1)
            ->assertJsonPath('batch.status', ImportBatch::STATUS_PREVIEW);

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('import_batches', 1);
        $this->assertDatabaseCount('import_rows', 1);

        $row = ImportRow::first();
        $this->assertSame('081234567890', $row->data_valid['no_hp_wali']);
        $this->assertSame('2026001', $row->data_valid['nis']);
    }

    public function test_empty_nisn_is_warning_and_confirm_creates_enrollment(): void
    {
        $admin = $this->admin();
        $file = $this->csv([[
            'NIS' => '2026002',
            'NISN' => '',
            'Nama' => 'Budi',
            'Jenis Kelamin' => 'L',
            'Kelas' => 'X-1',
            'Asal Siswa' => 'internal',
        ]]);

        $preview = $this->actingAs($admin)->preview($admin, $file);
        $preview->assertOk()
            ->assertJsonPath('summary.peringatan', 1)
            ->assertJsonPath('summary.valid', 0)
            ->assertJsonPath('summary.error', 0);

        $batchId = $preview->json('batch.id');

        $this->post("/api/import-siswa/batches/{$batchId}/confirm", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('batch.berhasil', 1);

        $student = Student::where('nis', '2026002')->firstOrFail();
        $this->assertNull($student->nisn);
        $this->assertSame($this->formalClass->id, $student->classroom_id);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $this->formalClass->id,
            'tahun_ajaran_id' => $this->year->id,
            'jalur' => Enrollment::JALUR_FORMAL,
        ]);
        $this->assertDatabaseCount('import_rows', 0);
    }

    public function test_update_mode_does_not_change_class_and_second_confirm_returns_409(): void
    {
        $admin = $this->admin();
        $other = $this->classroom('X-2', 10, Classroom::JALUR_FORMAL, Classroom::JENJANG_SMA);
        $student = Student::factory()->create([
            'nis' => '2026003',
            'nisn' => '0071234567',
            'name' => 'Nama Lama',
            'classroom_id' => $this->formalClass->id,
        ]);

        $file = $this->csv([[
            'NIS' => '2026003',
            'NISN' => '0071234567',
            'Nama' => 'Nama Baru',
            'Jenis Kelamin' => 'L',
            'Kelas' => 'X-2',
            'Asal Siswa' => 'internal',
        ]]);

        $preview = $this->actingAs($admin)->preview($admin, $file, true);
        $preview->assertOk()->assertJsonPath('summary.error', 0);
        $batchId = $preview->json('batch.id');

        $this->post("/api/import-siswa/batches/{$batchId}/confirm", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('batch.berhasil', 1);

        $student->refresh();
        $this->assertSame('Nama Baru', $student->name);
        $this->assertSame($this->formalClass->id, $student->classroom_id);
        $this->assertNotSame($other->id, $student->classroom_id);

        $this->post("/api/import-siswa/batches/{$batchId}/confirm", [], ['Accept' => 'application/json'])
            ->assertStatus(409);
    }

    public function test_automatic_lpi_enrollment_uses_asal_siswa(): void
    {
        $aliyah = $this->classroom('Aliyah 1', 10, Classroom::JALUR_LPI, Classroom::JENJANG_ALIYAH, 'lpi');
        $tsanawiyah = $this->classroom('Tsanawiyah 1', 7, Classroom::JALUR_LPI, Classroom::JENJANG_TSANAWIYAH, 'lpi');
        $admin = $this->admin();

        $file = $this->csv([
            [
                'NIS' => '2026010',
                'NISN' => '0070000010',
                'Nama' => 'Internal',
                'Jenis Kelamin' => 'L',
                'Kelas' => 'X-1',
                'Asal Siswa' => 'internal',
            ],
            [
                'NIS' => '2026011',
                'NISN' => '0070000011',
                'Nama' => 'Ppdb',
                'Jenis Kelamin' => 'P',
                'Kelas' => 'X-1',
                'Asal Siswa' => 'ppdb',
            ],
        ]);

        $preview = $this->actingAs($admin)->preview($admin, $file);
        $preview->assertOk()->assertJsonPath('summary.error', 0);
        $batchId = $preview->json('batch.id');

        $this->post("/api/import-siswa/batches/{$batchId}/confirm", [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('batch.berhasil', 2);

        $internal = Student::where('nis', '2026010')->firstOrFail();
        $ppdb = Student::where('nis', '2026011')->firstOrFail();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $internal->id,
            'class_id' => $aliyah->id,
            'tahun_ajaran_id' => $this->year->id,
            'jalur' => Enrollment::JALUR_LPI,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $ppdb->id,
            'class_id' => $tsanawiyah->id,
            'tahun_ajaran_id' => $this->year->id,
            'jalur' => Enrollment::JALUR_LPI,
        ]);
    }

    public function test_update_without_current_enrollment_creates_formal_and_lpi_but_existing_enrollment_does_not(): void
    {
        $this->classroom('Aliyah 1', 10, Classroom::JALUR_LPI, Classroom::JENJANG_ALIYAH, 'lpi');
        $admin = $this->admin();

        $withoutEnrollment = Student::factory()->create([
            'nis' => '2026020',
            'nisn' => '0070000020',
            'classroom_id' => $this->formalClass->id,
        ]);

        $withEnrollment = Student::factory()->create([
            'nis' => '2026021',
            'nisn' => '0070000021',
            'classroom_id' => $this->formalClass->id,
        ]);
        $existing = Enrollment::factory()->create([
            'student_id' => $withEnrollment->id,
            'class_id' => $this->formalClass->id,
            'tahun_ajaran_id' => $this->year->id,
            'jalur' => Enrollment::JALUR_FORMAL,
        ]);

        $file = $this->csv([
            [
                'NIS' => '2026020',
                'NISN' => '0070000020',
                'Nama' => $withoutEnrollment->name,
                'Jenis Kelamin' => 'L',
                'Kelas' => 'X-1',
                'Asal Siswa' => 'internal',
            ],
            [
                'NIS' => '2026021',
                'NISN' => '0070000021',
                'Nama' => $withEnrollment->name,
                'Jenis Kelamin' => 'L',
                'Kelas' => 'X-1',
                'Asal Siswa' => 'internal',
            ],
        ]);

        $preview = $this->actingAs($admin)->preview($admin, $file, true);
        $preview->assertOk()->assertJsonPath('summary.error', 0);
        $batchId = $preview->json('batch.id');

        $this->post("/api/import-siswa/batches/{$batchId}/confirm", [], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $withoutEnrollment->id,
            'tahun_ajaran_id' => $this->year->id,
            'jalur' => Enrollment::JALUR_FORMAL,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $withoutEnrollment->id,
            'tahun_ajaran_id' => $this->year->id,
            'jalur' => Enrollment::JALUR_LPI,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'id' => $existing->id,
            'student_id' => $withEnrollment->id,
            'class_id' => $this->formalClass->id,
        ]);
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $withEnrollment->id,
            'tahun_ajaran_id' => $this->year->id,
            'jalur' => Enrollment::JALUR_LPI,
        ]);
    }

    public function test_existing_nisn_is_error_in_insert_mode_but_updated_in_update_mode(): void
    {
        $admin = $this->admin();
        $existing = Student::factory()->create([
            'nis' => '2026030',
            'nisn' => '0070000030',
            'name' => 'Lama',
            'classroom_id' => $this->formalClass->id,
        ]);

        $file = $this->csv([[
            'NIS' => '2026030',
            'NISN' => '0070000030',
            'Nama' => 'Baru',
            'Jenis Kelamin' => 'L',
            'Kelas' => 'X-1',
            'Asal Siswa' => 'internal',
        ]]);

        $this->actingAs($admin)->preview($admin, $file)
            ->assertOk()
            ->assertJsonPath('summary.error', 1);

        $preview = $this->actingAs($admin)->preview($admin, $file, true);
        $preview->assertOk()->assertJsonPath('summary.error', 0);
        $batchId = $preview->json('batch.id');

        $this->post("/api/import-siswa/batches/{$batchId}/confirm", [], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame('Baru', $existing->fresh()->name);
    }

    public function test_invalid_phone_is_stored_null_and_reported(): void
    {
        $admin = $this->admin();
        $file = $this->csv([[
            'NIS' => '2026040',
            'NISN' => '0070000040',
            'Nama' => 'Tanpa HP',
            'Jenis Kelamin' => 'P',
            'Kelas' => 'X-1',
            'Asal Siswa' => 'ppdb',
            'No HP Wali' => '123',
        ]]);

        $preview = $this->actingAs($admin)->preview($admin, $file);
        $preview->assertOk()->assertJsonPath('summary.peringatan', 1);
        $batchId = $preview->json('batch.id');

        $this->post("/api/import-siswa/batches/{$batchId}/confirm", [], ['Accept' => 'application/json'])
            ->assertOk();

        $student = Student::where('nis', '2026040')->firstOrFail();
        $this->assertNull($student->no_hp_wali);

        $this->actingAs($admin)->getJson('/api/students?without_valid_phone=1')
            ->assertOk()
            ->assertJsonPath('data.0.nis', '2026040');
    }

    public function test_missing_required_header_and_invalid_class_are_rejected(): void
    {
        $admin = $this->admin();

        $missing = $this->csv([[
            'NIS' => '1',
            'Nama' => 'A',
            'Kelas' => 'X-1',
        ]], ['NIS', 'Nama', 'Kelas']);

        $this->actingAs($admin)->preview($admin, $missing)
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $invalidClass = $this->csv([[
            'NIS' => '2026050',
            'NISN' => '0070000050',
            'Nama' => 'Kelas Salah',
            'Jenis Kelamin' => 'L',
            'Kelas' => 'X-99',
            'Asal Siswa' => 'internal',
        ]]);

        $this->actingAs($admin)->preview($admin, $invalidClass)
            ->assertOk()
            ->assertJsonPath('summary.error', 1);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_non_admin_roles_cannot_import_or_download_template(): void
    {
        $file = $this->csv([[
            'NIS' => '1',
            'NISN' => '0070000001',
            'Nama' => 'A',
            'Jenis Kelamin' => 'L',
            'Kelas' => 'X-1',
            'Asal Siswa' => 'internal',
        ]]);

        foreach ([User::ROLE_GURU, User::ROLE_WALI_MURID, User::ROLE_BENDAHARA_SMA] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->preview($user, $file)->assertStatus(403);
            $this->actingAs($user)->getJson('/api/import-siswa/template?tahun_ajaran_id='.$this->year->id)->assertStatus(403);
        }
    }

    public function test_file_size_extension_and_row_limit_are_enforced(): void
    {
        $admin = $this->admin();

        $large = UploadedFile::fake()->create('besar.csv', 6000, 'text/csv');
        $this->actingAs($admin)->preview($admin, $large)->assertStatus(422);

        $wrong = UploadedFile::fake()->create('data.xls', 10, 'application/vnd.ms-excel');
        $this->actingAs($admin)->preview($admin, $wrong)->assertStatus(422);

        $rows = [];
        for ($i = 1; $i <= 2001; $i++) {
            $rows[] = [
                'NIS' => 'N'.$i,
                'NISN' => str_pad((string) $i, 10, '0', STR_PAD_LEFT),
                'Nama' => 'Siswa '.$i,
                'Jenis Kelamin' => 'L',
                'Kelas' => 'X-1',
                'Asal Siswa' => 'internal',
            ];
        }

        $this->actingAs($admin)->preview($admin, $this->csv($rows))->assertStatus(422);
        $this->assertDatabaseCount('import_batches', 0);
    }

    public function test_error_csv_is_safe_and_activity_log_does_not_store_phone(): void
    {
        $admin = $this->admin();
        $file = $this->csv([
            [
                'NIS' => '2026060',
                'NISN' => '0070000060',
                'Nama' => '=SUM(1,1)',
                'Jenis Kelamin' => 'L',
                'Kelas' => 'X-99',
                'Asal Siswa' => 'internal',
            ],
            [
                'NIS' => '2026061',
                'NISN' => '0070000061',
                'Nama' => 'Valid',
                'Jenis Kelamin' => 'P',
                'Kelas' => 'X-1',
                'Asal Siswa' => 'internal',
                'No HP Wali' => '081234567890',
            ],
        ]);

        $preview = $this->actingAs($admin)->preview($admin, $file);
        $preview->assertOk();
        $batchId = $preview->json('batch.id');

        $response = $this->actingAs($admin)->get("/api/import-siswa/batches/{$batchId}/errors");
        $response->assertOk();
        $csv = (string) $response->getContent();

        $this->assertStringContainsString("'=SUM(1,1)", $csv);

        $this->post("/api/import-siswa/batches/{$batchId}/confirm", [], ['Accept' => 'application/json'])
            ->assertOk();

        $payloads = ActivityLog::whereIn('action', ['import.preview', 'import.confirm'])->pluck('metadata')->toJson();
        $this->assertStringNotContainsString('081234567890', $payloads);
    }

    public function test_template_download_contains_expected_headers(): void
    {
        $response = $this->actingAs($this->admin())
            ->get('/api/import-siswa/template?tahun_ajaran_id='.$this->year->id);

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }
}
