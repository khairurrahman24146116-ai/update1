<?php

namespace Tests\Feature;

use App\Models\AkhlakRecord;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentPermit;
use App\Models\StudentWelfareAlert;
use App\Models\StudentWelfareSetting;
use App\Models\Subject;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentWelfareTest extends TestCase
{
    use RefreshDatabase;

    public function test_kabag_pengajian_can_manage_all_lpi_students_without_class_assignment(): void
    {
        $kabag = $this->kabagPengajian();
        $tsanawiyah = $this->lpiClassroom(Classroom::JENJANG_TSANAWIYAH, 'VII-A');
        $aliyah = $this->lpiClassroom(Classroom::JENJANG_ALIYAH, 'X-B');
        $firstStudent = $this->student($tsanawiyah, 'Santri Satu');
        $secondStudent = $this->student($aliyah, 'Santri Dua');

        $meta = $this->actingAs($kabag)->getJson('/api/welfare/meta')->assertOk();

        $meta->assertJsonPath('can_manage_akhlak', true)
            ->assertJsonPath('has_lpi', true)
            ->assertJsonPath('permissions.manage', true)
            ->assertJsonPath('permissions.approve', true)
            ->assertJsonPath('permissions.settings', true);

        $classroomIds = collect($meta->json('classrooms'))->pluck('id')->all();
        $this->assertContains($tsanawiyah->id, $classroomIds);
        $this->assertContains($aliyah->id, $classroomIds);

        $this->actingAs($kabag)->postJson('/api/welfare/akhlak', [
            'student_id' => $firstStudent->id,
            'type' => AkhlakRecord::TYPE_PELANGGARAN,
            'severity' => AkhlakRecord::SEVERITY_SEDANG,
            'category' => 'Kedisiplinan',
            'description' => 'Terlambat masuk kelas.',
            'occurred_at' => now()->toDateString(),
            'internal_note' => 'Sudah ditegur wali kelas.',
        ])->assertCreated()
            ->assertJsonPath('student_id', $firstStudent->id)
            ->assertJsonPath('points', 15);

        $this->assertDatabaseHas('akhlak_records', [
            'student_id' => $firstStudent->id,
            'recorded_by' => $kabag->id,
        ]);

        $permit = StudentPermit::create([
            'student_id' => $secondStudent->id,
            'classroom_id' => $aliyah->id,
            'requested_by' => $kabag->id,
            'type' => StudentPermit::TYPE_IZIN,
            'reason' => 'Keperluan keluarga.',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'status' => StudentPermit::STATUS_PENDING,
            'academic_year' => $this->academicYear(),
            'semester' => 'ganjil',
        ]);

        $this->actingAs($kabag)
            ->postJson("/api/welfare/permits/{$permit->id}/approve", [])
            ->assertOk()
            ->assertJsonPath('status', StudentPermit::STATUS_APPROVED);

        $this->assertDatabaseHas('student_permits', [
            'id' => $permit->id,
            'status' => StudentPermit::STATUS_APPROVED,
            'decided_by' => $kabag->id,
        ]);
    }

    public function test_guru_lpi_without_kabag_jabatan_is_denied_menu_and_feature_endpoints(): void
    {
        $guru = $this->guruLpiBiasa();
        $classroom = $this->lpiClassroom(Classroom::JENJANG_TSANAWIYAH, 'VII-B');
        $student = $this->student($classroom, 'Santri Tiga');

        $this->actingAs($guru)->getJson('/api/welfare/meta')->assertForbidden();
        $this->actingAs($guru)->getJson('/api/welfare/akhlak')->assertForbidden();
        $this->actingAs($guru)->getJson('/api/welfare/settings')->assertForbidden();

        $this->actingAs($guru)->postJson('/api/welfare/akhlak', [
            'student_id' => $student->id,
            'type' => AkhlakRecord::TYPE_PELANGGARAN,
            'severity' => AkhlakRecord::SEVERITY_RINGAN,
            'description' => 'Tidak boleh tercatat.',
            'occurred_at' => now()->toDateString(),
        ])->assertForbidden();

        $this->actingAs($guru)->putJson('/api/welfare/settings', [])->assertForbidden();

        $this->assertDatabaseCount('akhlak_records', 0);
    }

    public function test_guru_sma_with_kabag_jabatan_is_denied(): void
    {
        $guru = $this->userWithUnit(User::ROLE_GURU, 'SMA', User::JABATAN_KABAG_PENGAJIAN);
        $this->smaClassroom('X-SMA');

        $this->actingAs($guru)->getJson('/api/welfare/meta')->assertForbidden();
        $this->actingAs($guru)->getJson('/api/welfare/akhlak')->assertForbidden();
    }

    public function test_kabag_pengajian_cannot_access_sma_or_smp_students(): void
    {
        $kabag = $this->kabagPengajian();
        $lpi = $this->lpiClassroom(Classroom::JENJANG_TSANAWIYAH, 'VII-C');
        $sma = $this->smaClassroom('X-SMA');
        $smp = $this->smpClassroom('VII-SMP');
        $smaStudent = $this->student($sma, 'Santri SMA');
        $smpStudent = $this->student($smp, 'Santri SMP');

        $meta = $this->actingAs($kabag)->getJson('/api/welfare/meta')->assertOk();
        $classroomIds = collect($meta->json('classrooms'))->pluck('id')->all();

        $this->assertContains($lpi->id, $classroomIds);
        $this->assertNotContains($sma->id, $classroomIds);
        $this->assertNotContains($smp->id, $classroomIds);

        $this->actingAs($kabag)
            ->getJson("/api/welfare/students/{$smaStudent->id}/eligibility")
            ->assertForbidden();
        $this->actingAs($kabag)
            ->getJson("/api/welfare/students/{$smpStudent->id}/eligibility")
            ->assertForbidden();

        $this->actingAs($kabag)->postJson('/api/welfare/akhlak', [
            'student_id' => $smaStudent->id,
            'type' => AkhlakRecord::TYPE_PELANGGARAN,
            'severity' => AkhlakRecord::SEVERITY_RINGAN,
            'description' => 'Di luar unit LPI.',
            'occurred_at' => now()->toDateString(),
        ])->assertForbidden();

        $this->actingAs($kabag)
            ->getJson('/api/welfare/akhlak?student_id='.$smaStudent->id)
            ->assertForbidden();

        $this->assertDatabaseCount('akhlak_records', 0);
    }

    public function test_admin_utama_can_manage_all_lpi_but_not_sma_or_smp(): void
    {
        $admin = $this->admin();
        $lpi = $this->lpiClassroom(Classroom::JENJANG_ALIYAH, 'X-C');
        $sma = $this->smaClassroom('XI-SMA');
        $smp = $this->smpClassroom('VIII-SMP');
        $lpiStudent = $this->student($lpi, 'Santri LPI');
        $smaStudent = $this->student($sma, 'Santri SMA');
        $smpStudent = $this->student($smp, 'Santri SMP');

        $meta = $this->actingAs($admin)->getJson('/api/welfare/meta')->assertOk();
        $meta->assertJsonPath('can_manage_akhlak', true);
        $classroomIds = collect($meta->json('classrooms'))->pluck('id')->all();

        $this->assertContains($lpi->id, $classroomIds);
        $this->assertNotContains($sma->id, $classroomIds);
        $this->assertNotContains($smp->id, $classroomIds);

        $this->actingAs($admin)->postJson('/api/welfare/akhlak', [
            'student_id' => $lpiStudent->id,
            'type' => AkhlakRecord::TYPE_APRESIASI,
            'category' => 'Kepemimpinan',
            'description' => 'Membantu kegiatan sekolah.',
            'occurred_at' => now()->toDateString(),
        ])->assertCreated();

        $this->actingAs($admin)
            ->getJson("/api/welfare/students/{$lpiStudent->id}/eligibility")
            ->assertOk();
        $this->actingAs($admin)
            ->getJson("/api/welfare/students/{$smaStudent->id}/eligibility")
            ->assertForbidden();
        $this->actingAs($admin)
            ->getJson("/api/welfare/students/{$smpStudent->id}/eligibility")
            ->assertForbidden();
    }

    public function test_changing_jabatan_immediately_changes_access_and_options_include_kabag(): void
    {
        $admin = $this->admin();
        $guru = $this->guruLpiBiasa();

        $this->actingAs($guru)->getJson('/api/welfare/meta')->assertForbidden();

        $options = $this->actingAs($admin)->getJson('/api/accounts/options')->assertOk();
        $this->assertContains(User::JABATAN_KABAG_PENGAJIAN, $options->json('jabatan_options'));

        $this->actingAs($admin)->putJson("/api/accounts/{$guru->id}", [
            'units' => [
                ['code' => 'LPI', 'jabatan' => User::JABATAN_KABAG_PENGAJIAN],
            ],
        ])->assertOk();

        $guru = User::query()->findOrFail($guru->id);
        $this->actingAs($guru)
            ->getJson('/api/welfare/meta')
            ->assertOk()
            ->assertJsonPath('can_manage_akhlak', true);

        $this->actingAs($admin)->putJson("/api/accounts/{$guru->id}", [
            'units' => [
                ['code' => 'LPI', 'jabatan' => 'Guru Mata Pelajaran'],
            ],
        ])->assertOk();

        $guru = User::query()->findOrFail($guru->id);
        $this->actingAs($guru)->getJson('/api/welfare/meta')->assertForbidden();
    }

    public function test_eligibility_reports_warning_and_syncs_alerts_without_duplicates(): void
    {
        $kabag = $this->kabagPengajian();
        $classroom = $this->lpiClassroom(Classroom::JENJANG_ALIYAH, 'X-A');
        $student = $this->student($classroom, 'Santri Empat');
        $subject = $this->subject();
        $schedule = $this->schedule($kabag, $subject, $classroom);

        foreach (['hadir', 'hadir', 'hadir', 'alfa'] as $index => $status) {
            Attendance::create([
                'schedule_id' => $schedule->id,
                'student_id' => $student->id,
                'date' => now()->startOfMonth()->addDays($index)->toDateString(),
                'status' => $status,
            ]);
        }

        $this->actingAs($kabag)->postJson('/api/welfare/akhlak', [
            'student_id' => $student->id,
            'type' => AkhlakRecord::TYPE_PELANGGARAN,
            'severity' => AkhlakRecord::SEVERITY_BERAT,
            'description' => 'Pelanggaran berat.',
            'occurred_at' => now()->toDateString(),
        ])->assertCreated();

        $response = $this->actingAs($kabag)
            ->getJson("/api/welfare/students/{$student->id}/eligibility")
            ->assertOk();

        $response->assertJsonPath('attendance.percent', 75)
            ->assertJsonPath('violations.points', 30)
            ->assertJsonPath('status', 'perlu_perhatian')
            ->assertJsonPath('status_label', 'Perlu Perhatian');

        $this->assertNotEmpty($response->json('reasons'));
        $this->assertDatabaseCount('student_welfare_alerts', 1);

        $this->actingAs($kabag)
            ->getJson("/api/welfare/students/{$student->id}/eligibility")
            ->assertOk();

        $this->assertDatabaseCount('student_welfare_alerts', 1);
        $this->assertDatabaseHas('student_welfare_alerts', [
            'student_id' => $student->id,
            'type' => StudentWelfareAlert::TYPE_VIOLATION,
        ]);
    }

    public function test_admin_can_override_permit_limit_and_reason_is_recorded(): void
    {
        $admin = $this->admin();
        $kabag = $this->kabagPengajian();
        $classroom = $this->lpiClassroom(Classroom::JENJANG_TSANAWIYAH, 'VII-C');
        $student = $this->student($classroom, 'Santri Lima');

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        for ($i = 0; $i < 3; $i++) {
            StudentPermit::create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'requested_by' => $kabag->id,
                'decided_by' => $admin->id,
                'type' => StudentPermit::TYPE_IZIN,
                'reason' => 'Izin lama '.$i,
                'start_date' => $monthStart,
                'end_date' => $monthEnd,
                'status' => StudentPermit::STATUS_APPROVED,
                'decided_at' => now(),
                'academic_year' => $this->academicYear(),
                'semester' => 'ganjil',
            ]);
        }

        $permitId = $this->actingAs($kabag)->postJson('/api/welfare/permits', [
            'student_id' => $student->id,
            'type' => StudentPermit::TYPE_IZIN,
            'reason' => 'Izin keempat.',
            'start_date' => $monthStart,
            'end_date' => $monthEnd,
        ])->assertCreated()->json('id');

        $this->actingAs($admin)
            ->postJson("/api/welfare/permits/{$permitId}/approve", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('override_reason');

        $response = $this->actingAs($admin)
            ->postJson("/api/welfare/permits/{$permitId}/approve", [
                'override_reason' => 'Kebutuhan keluarga mendesak.',
                'decision_note' => 'Disetujui dengan catatan.',
            ])
            ->assertOk();

        $response->assertJsonPath('status', StudentPermit::STATUS_APPROVED)
            ->assertJsonPath('is_override', true)
            ->assertJsonPath('override_reason', 'Kebutuhan keluarga mendesak.');

        $this->assertDatabaseHas('student_permits', [
            'id' => $permitId,
            'status' => StudentPermit::STATUS_APPROVED,
            'is_override' => true,
            'override_reason' => 'Kebutuhan keluarga mendesak.',
            'decided_by' => $admin->id,
        ]);
    }

    public function test_wali_only_sees_own_child_and_internal_fields_are_hidden(): void
    {
        $kabag = $this->kabagPengajian();
        $parent = User::factory()->create(['role' => User::ROLE_WALI_MURID]);
        $classroom = $this->lpiClassroom(Classroom::JENJANG_ALIYAH, 'X-D');
        $ownChild = $this->student($classroom, 'Anak Sendiri');
        $otherChild = $this->student($classroom, 'Anak Lain');
        $ownChild->update(['parent_id' => $parent->id]);

        $this->actingAs($kabag)->postJson('/api/welfare/akhlak', [
            'student_id' => $ownChild->id,
            'type' => AkhlakRecord::TYPE_PELANGGARAN,
            'severity' => AkhlakRecord::SEVERITY_RINGAN,
            'description' => 'Catatan untuk wali.',
            'occurred_at' => now()->toDateString(),
            'internal_note' => 'Catatan internal guru.',
        ])->assertCreated();

        StudentPermit::create([
            'student_id' => $ownChild->id,
            'classroom_id' => $classroom->id,
            'requested_by' => $kabag->id,
            'type' => StudentPermit::TYPE_IZIN,
            'reason' => 'Izin keluarga.',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'status' => StudentPermit::STATUS_APPROVED,
            'decision_note' => 'Catatan keputusan internal.',
            'is_override' => true,
            'override_reason' => 'Alasan override internal.',
            'academic_year' => $this->academicYear(),
            'semester' => 'ganjil',
        ]);

        $response = $this->actingAs($parent)
            ->getJson('/api/welfare/wali/overview')
            ->assertOk();

        $this->assertSame([$ownChild->id], collect($response->json('students'))->pluck('id')->all());
        $this->assertSame($ownChild->id, $response->json('selected_student_id'));
        $this->assertSame('Catatan untuk wali.', $response->json('akhlak.0.description'));
        $this->assertArrayNotHasKey('internal_note', $response->json('akhlak.0'));
        $this->assertArrayNotHasKey('recorded_by', $response->json('akhlak.0'));
        $this->assertArrayNotHasKey('decision_note', $response->json('permits.0'));
        $this->assertArrayNotHasKey('override_reason', $response->json('permits.0'));
        $this->assertArrayNotHasKey('requested_by', $response->json('permits.0'));

        $meta = $this->actingAs($parent)->getJson('/api/welfare/meta')->assertOk();
        $meta->assertJsonPath('can_manage_akhlak', false)
            ->assertJsonPath('permissions.manage', false)
            ->assertJsonPath('settings', null);

        $this->actingAs($parent)->getJson('/api/welfare/akhlak')->assertForbidden();

        $this->actingAs($parent)
            ->getJson("/api/welfare/students/{$otherChild->id}/eligibility")
            ->assertForbidden();

        $this->actingAs($parent)
            ->getJson('/api/welfare/wali/overview?student_id='.$otherChild->id)
            ->assertForbidden();
    }

    public function test_admin_and_kabag_can_update_thresholds_but_ordinary_guru_cannot(): void
    {
        $admin = $this->admin();
        $kabag = $this->kabagPengajian();
        $guru = $this->guruLpiBiasa();
        $this->lpiClassroom(Classroom::JENJANG_TSANAWIYAH, 'VII-F');

        $payload = [
            'attendance_min_percent' => 80,
            'permit_max_per_month' => 2,
            'permit_max_per_semester' => 10,
            'violation_warning_points' => 15,
            'violation_max_points' => 40,
            'point_ringan' => 4,
            'point_sedang' => 12,
            'point_berat' => 25,
            'allow_permit_override' => true,
        ];

        $this->actingAs($kabag)->getJson('/api/welfare/settings')
            ->assertOk()
            ->assertJsonPath('attendance_min_percent', 75);

        $this->actingAs($kabag)->putJson('/api/welfare/settings', $payload)
            ->assertOk()
            ->assertJsonPath('attendance_min_percent', 80)
            ->assertJsonPath('permit_max_per_month', 2);

        $this->actingAs($admin)->putJson('/api/welfare/settings', $payload)
            ->assertOk();

        $this->actingAs($guru)->putJson('/api/welfare/settings', $payload)->assertForbidden();

        $this->assertSame(1, StudentWelfareSetting::query()->count());
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);
    }

    private function kabagPengajian(string $code = 'LPI'): User
    {
        return $this->userWithUnit(User::ROLE_GURU, $code, User::JABATAN_KABAG_PENGAJIAN);
    }

    private function guruLpiBiasa(): User
    {
        return $this->userWithUnit(User::ROLE_GURU, 'LPI', 'Guru Mata Pelajaran');
    }

    private function userWithUnit(string $role, string $code, string $jabatan): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'unit' => strtolower($code),
        ]);

        $user->units()->attach($this->unitId($code), ['jabatan' => $jabatan]);

        return $user;
    }

    private function unitId(string $code): int
    {
        return (int) Unit::query()->where('kode', $code)->value('id');
    }

    private function lpiClassroom(string $jenjang, string $name): Classroom
    {
        return Classroom::factory()->lpi($jenjang, 7)->create([
            'name' => $name,
            'academic_year' => $this->academicYear(),
        ]);
    }

    private function smaClassroom(string $name): Classroom
    {
        return Classroom::factory()->create([
            'name' => $name,
            'academic_year' => $this->academicYear(),
        ]);
    }

    private function smpClassroom(string $name): Classroom
    {
        return Classroom::factory()->smp()->create([
            'name' => $name,
            'academic_year' => $this->academicYear(),
        ]);
    }

    private function student(Classroom $classroom, string $name): Student
    {
        return Student::factory()->create([
            'classroom_id' => $classroom->id,
            'name' => $name,
        ]);
    }

    private function subject(): Subject
    {
        return Subject::create(['name' => 'Matematika', 'jenis' => Subject::JENIS_FORMAL]);
    }

    private function schedule(User $teacher, Subject $subject, Classroom $classroom): Schedule
    {
        return Schedule::create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 'mon',
            'start_time' => '07:00',
            'end_time' => '08:30',
            'academic_year' => $this->academicYear(),
        ]);
    }

    private function academicYear(): string
    {
        return now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;
    }
}
