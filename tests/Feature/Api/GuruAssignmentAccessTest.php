<?php

namespace Tests\Feature\Api;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruAssignmentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignments_endpoint_only_returns_enabled_assigned_pairs(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject();

        $sma = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-SMA');
        $tsanawiyah = $this->classroom('LPI', Classroom::JENJANG_TSANAWIYAH, 7, 'VII-TSAN');
        $aliyah = $this->classroom('LPI', Classroom::JENJANG_ALIYAH, 10, 'X-ALIYAH');
        $smp = $this->classroom('SMP', Classroom::JENJANG_SMP, 7, 'VII-SMP');
        $unassigned = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-UNASSIGNED');

        foreach ([$sma, $tsanawiyah, $aliyah, $smp] as $classroom) {
            $this->assign($guru, $classroom, $subject);
        }

        $response = $this->actingAs($guru)->getJson('/api/guru/assignments')->assertOk();

        $classroomIds = collect($response->json('assignments'))->pluck('classroom_id');

        $this->assertCount(3, $classroomIds);
        $this->assertEqualsCanonicalizing([$sma->id, $tsanawiyah->id, $aliyah->id], $classroomIds->all());
        $this->assertNotContains($smp->id, $classroomIds);
        $this->assertNotContains($unassigned->id, $classroomIds);
    }

    public function test_guru_can_save_scores_for_cross_unit_and_lpi_assignments(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject();
        $classrooms = [
            $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-SMA'),
            $this->classroom('LPI', Classroom::JENJANG_TSANAWIYAH, 7, 'VII-TSAN'),
            $this->classroom('LPI', Classroom::JENJANG_ALIYAH, 10, 'X-ALIYAH'),
        ];

        foreach ($classrooms as $index => $classroom) {
            $this->assign($guru, $classroom, $subject);
            $student = $this->student($classroom, 'CROSS'.$index);

            $this->actingAs($guru)->postJson('/api/scores', [
                'classroom_id' => $classroom->id,
                'subject_id' => $subject->id,
                'semester' => 'ganjil',
                'academic_year' => '2026/2027',
                'rows' => [['student_id' => $student->id, 'value' => 80]],
            ])->assertNoContent();
        }

        $this->assertDatabaseCount('scores', 3);
    }

    public function test_guru_cannot_save_scores_for_unassigned_or_disabled_classroom(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);
        $subject = $this->subject();
        $unassigned = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-UNASSIGNED');
        $smp = $this->classroom('SMP', Classroom::JENJANG_SMP, 7, 'VII-SMP');
        $unassignedStudent = $this->student($unassigned, 'UNASSIGNED');
        $smpStudent = $this->student($smp, 'SMP');
        $this->assign($guru, $smp, $subject);

        $payload = fn (Classroom $classroom, Student $student): array => [
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'rows' => [['student_id' => $student->id, 'value' => 80]],
        ];

        $this->actingAs($guru)->postJson('/api/scores', $payload($unassigned, $unassignedStudent))->assertForbidden();
        $this->actingAs($guru)->postJson('/api/scores', $payload($smp, $smpStudent))->assertForbidden();
        $this->actingAs($admin)->postJson('/api/scores', $payload($smp, $smpStudent))->assertForbidden();

        $this->assertDatabaseCount('scores', 0);
    }

    public function test_score_rows_must_belong_to_the_selected_classroom(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject();
        $assigned = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-ASSIGNED');
        $other = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-OTHER');
        $otherStudent = $this->student($other, 'OTHER');
        $this->assign($guru, $assigned, $subject);

        $this->actingAs($guru)->postJson('/api/scores', [
            'classroom_id' => $assigned->id,
            'subject_id' => $subject->id,
            'rows' => [['student_id' => $otherStudent->id, 'value' => 80]],
        ])->assertUnprocessable()->assertJsonValidationErrors('rows');

        $this->assertDatabaseCount('scores', 0);
    }

    public function test_wali_cannot_read_other_class_scores_or_other_students(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_WALI_MURID]);
        $subject = $this->subject();
        $own = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-OWN');
        $other = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-OTHER');
        $ownChild = $this->student($own, 'CHILD');
        $ownChild->update(['parent_id' => $parent->id]);
        $classmate = $this->student($own, 'CLASSMATE');

        $this->actingAs($parent)->getJson("/api/scores/sheet?classroom_id={$other->id}&subject_id={$subject->id}")
            ->assertForbidden();

        $response = $this->actingAs($parent)->getJson("/api/scores/sheet?classroom_id={$own->id}&subject_id={$subject->id}")
            ->assertOk();

        $this->assertSame([$ownChild->id], collect($response->json())->pluck('student.id')->all());
        $this->assertNotContains($classmate->id, collect($response->json())->pluck('student.id'));
    }

    public function test_attendance_sheet_requires_own_enabled_schedule(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $otherGuru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject();
        $sma = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-SMA');
        $lpi = $this->classroom('LPI', Classroom::JENJANG_ALIYAH, 10, 'X-LPI');
        $smp = $this->classroom('SMP', Classroom::JENJANG_SMP, 7, 'VII-SMP');
        $ownSma = $this->schedule($sma, $subject, $guru);
        $ownLpi = $this->schedule($lpi, $subject, $guru);
        $ownSmp = $this->schedule($smp, $subject, $guru);
        $otherSchedule = $this->schedule($sma, $subject, $otherGuru);

        $this->actingAs($guru)->getJson("/api/schedules/{$ownSma->id}/sheet?date=2026-10-28")->assertOk();
        $this->actingAs($guru)->getJson("/api/schedules/{$ownLpi->id}/sheet?date=2026-10-28")->assertOk();
        $this->actingAs($guru)->getJson("/api/schedules/{$ownSmp->id}/sheet?date=2026-10-28")->assertForbidden();
        $this->actingAs($guru)->getJson("/api/schedules/{$otherSchedule->id}/sheet?date=2026-10-28")->assertForbidden();
    }

    public function test_attendance_rows_must_belong_to_schedule_classroom(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject();
        $classroom = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-SMA');
        $other = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-OTHER');
        $schedule = $this->schedule($classroom, $subject, $guru);
        $validStudent = $this->student($classroom, 'VALID');
        $otherStudent = $this->student($other, 'OTHER');

        $this->actingAs($guru)->postJson("/api/schedules/{$schedule->id}/attendances?date=2026-10-28", [
            'rows' => [['student_id' => $otherStudent->id, 'status' => 'hadir']],
        ])->assertUnprocessable()->assertJsonValidationErrors('rows');

        $this->actingAs($guru)->postJson("/api/schedules/{$schedule->id}/attendances?date=2026-10-28", [
            'rows' => [['student_id' => $validStudent->id, 'status' => 'hadir']],
        ])->assertNoContent();

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', ['student_id' => $validStudent->id]);
        $this->assertDatabaseMissing('attendances', ['student_id' => $otherStudent->id]);
    }

    public function test_schedule_list_is_limited_to_own_enabled_schedules(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $otherGuru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject();
        $sma = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-SMA');
        $lpi = $this->classroom('LPI', Classroom::JENJANG_ALIYAH, 10, 'X-LPI');
        $smp = $this->classroom('SMP', Classroom::JENJANG_SMP, 7, 'VII-SMP');
        $ownSma = $this->schedule($sma, $subject, $guru);
        $ownLpi = $this->schedule($lpi, $subject, $guru);
        $this->schedule($smp, $subject, $guru);
        $otherSchedule = $this->schedule($sma, $subject, $otherGuru);

        $response = $this->actingAs($guru)
            ->getJson("/api/schedules?teacher_id={$otherGuru->id}&per_page=100")
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertEqualsCanonicalizing([$ownSma->id, $ownLpi->id], $ids->all());
        $this->assertNotContains($otherSchedule->id, $ids);
    }

    public function test_admin_dropdowns_hide_disabled_smp_unit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);
        $this->classroom('SMP', Classroom::JENJANG_SMP, 7, 'VII-SMP');

        $options = $this->actingAs($admin)->getJson('/api/teacher-directory/options')->assertOk();
        $this->assertSame(['LPI', 'SMA'], collect($options->json('units'))->pluck('code')->sort()->values()->all());
        $this->assertNotContains('SMP', collect($options->json('classrooms'))->pluck('unit'));

        $classrooms = $this->actingAs($admin)->getJson('/api/classrooms?enabled_units=1&per_page=100')->assertOk();
        $this->assertNotContains('VII-SMP', collect($classrooms->json('data'))->pluck('name'));

        $directory = $this->actingAs($admin)->getJson('/api/teacher-directory')->assertOk();
        $this->assertNotContains('SMP', collect($directory->json('tabs'))->pluck('code'));
    }

    public function test_account_management_rejects_disabled_smp_unit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);

        $this->actingAs($admin)->postJson('/api/accounts', [
            'name' => 'Guru SMP',
            'email' => 'guru-smp@madani.test',
            'password' => 'password123',
            'role' => User::ROLE_GURU,
            'units' => [['code' => 'SMP', 'jabatan' => 'Guru Mapel']],
        ])->assertUnprocessable()->assertJsonValidationErrors('units.0.code');
    }

    public function test_assignment_update_rejects_disabled_classroom(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);
        $teacherUser = User::factory()->create(['role' => User::ROLE_GURU]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'nama' => $teacherUser->name]);
        $subject = $this->subject();
        $smp = $this->classroom('SMP', Classroom::JENJANG_SMP, 7, 'VII-SMP');

        $this->actingAs($admin)->putJson("/api/teacher-directory/{$teacher->id}/assignments", [
            'academic_year' => '2026/2027',
            'assignments' => [['subject_id' => $subject->id, 'classroom_id' => $smp->id]],
            'wali_classroom_ids' => [],
        ])->assertForbidden();

        $this->assertDatabaseMissing('teacher_subjects', [
            'teacher_id' => $teacherUser->id,
            'classroom_id' => $smp->id,
        ]);
    }

    public function test_disabling_unit_blocks_access_and_hides_assignments(): void
    {
        config(['units.enabled' => ['sma']]);

        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject();
        $sma = $this->classroom('SMA', Classroom::JENJANG_SMA, 10, 'X-SMA');
        $lpi = $this->classroom('LPI', Classroom::JENJANG_ALIYAH, 10, 'X-LPI');
        $smaStudent = $this->student($sma, 'SMA');
        $lpiStudent = $this->student($lpi, 'LPI');
        $this->assign($guru, $sma, $subject);
        $this->assign($guru, $lpi, $subject);

        $this->actingAs($guru)->postJson('/api/scores', [
            'classroom_id' => $sma->id,
            'subject_id' => $subject->id,
            'rows' => [['student_id' => $smaStudent->id, 'value' => 80]],
        ])->assertNoContent();

        $this->actingAs($guru)->postJson('/api/scores', [
            'classroom_id' => $lpi->id,
            'subject_id' => $subject->id,
            'rows' => [['student_id' => $lpiStudent->id, 'value' => 80]],
        ])->assertForbidden();

        $assignments = $this->actingAs($guru)->getJson('/api/guru/assignments')->assertOk();
        $this->assertCount(1, $assignments->json('assignments'));
        $this->assertSame($sma->id, $assignments->json('assignments.0.classroom_id'));
    }

    public function test_auth_payload_includes_enabled_units(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->actingAs($guru)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('enabled_units', ['lpi', 'sma']);
    }

    private function classroom(string $unit, string $jenjang, int $grade, string $name): Classroom
    {
        return Classroom::create([
            'name' => $name,
            'grade' => $grade,
            'academic_year' => '2026/2027',
            'unit' => strtolower($unit),
            'jalur' => strtolower($unit) === 'lpi' ? Classroom::JALUR_LPI : Classroom::JALUR_FORMAL,
            'jenjang' => $jenjang,
        ]);
    }

    private function subject(): Subject
    {
        return Subject::create(['name' => 'Fisika', 'code' => 'FIS']);
    }

    private function student(Classroom $classroom, string $suffix): Student
    {
        return Student::create([
            'nis' => 'NIS-'.$suffix,
            'nisn' => '0070'.str_pad((string) (crc32($suffix) % 1000000), 6, '0', STR_PAD_LEFT),
            'name' => 'Siswa '.$suffix,
            'gender' => 'L',
            'classroom_id' => $classroom->id,
            'status' => 'aktif',
        ]);
    }

    private function schedule(Classroom $classroom, Subject $subject, User $teacher): Schedule
    {
        return Schedule::create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 'wed',
            'start_time' => '07:00',
            'end_time' => '08:30',
            'academic_year' => '2026/2027',
        ]);
    }

    private function assign(User $teacher, Classroom $classroom, Subject $subject): TeacherSubject
    {
        return TeacherSubject::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'academic_year' => '2026/2027',
        ]);
    }
}
