<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeacherSubject;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherDirectoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kepala_sekolah_only_lists_teachers_in_own_unit_and_cannot_open_other_unit(): void
    {
        $kepala = $this->userForUnit('kepala_sekolah', 'SMA');
        $smaTeacher = $this->teacherFor($this->userForUnit('guru', 'SMA', ['name' => 'Guru SMA']), ['SMA' => 'Guru Mapel']);
        $smpTeacher = $this->teacherFor($this->userForUnit('guru', 'SMP', ['name' => 'Guru SMP']), ['SMP' => 'Guru Mapel']);

        $response = $this->actingAs($kepala)->getJson('/api/teacher-directory')->assertOk();

        $this->assertSame(['Guru SMA'], collect($response->json('data'))->pluck('name')->all());
        $this->assertSame(['SMA'], collect($response->json('tabs'))->pluck('code')->all());

        $this->actingAs($kepala)
            ->getJson("/api/teacher-directory/{$smaTeacher->id}")
            ->assertOk();

        $this->actingAs($kepala)
            ->getJson("/api/teacher-directory/{$smpTeacher->id}")
            ->assertNotFound();
    }

    public function test_kepala_sekolah_options_and_assignments_are_limited_to_own_unit(): void
    {
        $kepala = $this->userForUnit('kepala_sekolah', 'SMA');
        $teacherUser = $this->userForUnit('guru', 'SMA');
        $teacher = $this->teacherFor($teacherUser, ['SMA' => 'Guru Mapel']);
        $smaClassroom = $this->classroom('SMA', 10, 'X-1');
        $smpClassroom = $this->classroom('SMP', 7, 'VII-1');
        $subject = Subject::create(['name' => 'Fisika', 'code' => 'FIS']);

        $options = $this->actingAs($kepala)
            ->getJson('/api/teacher-directory/options')
            ->assertOk();

        $this->assertSame(['SMA'], collect($options->json('units'))->pluck('code')->all());
        $this->assertSame(['SMA'], collect($options->json('classrooms'))->pluck('unit')->unique()->values()->all());

        $this->actingAs($kepala)->putJson("/api/teacher-directory/{$teacher->id}/assignments", [
            'academic_year' => '2026/2027',
            'assignments' => [
                ['subject_id' => $subject->id, 'classroom_id' => $smpClassroom->id],
            ],
            'wali_classroom_ids' => [$smpClassroom->id],
        ])->assertForbidden();

        $this->assertDatabaseMissing('teacher_subjects', [
            'teacher_id' => $teacherUser->id,
            'classroom_id' => $smpClassroom->id,
        ]);
        $this->assertDatabaseMissing('classrooms', [
            'id' => $smpClassroom->id,
            'wali_kelas_id' => $teacher->id,
        ]);
        $this->assertDatabaseMissing('teacher_subjects', [
            'teacher_id' => $teacherUser->id,
            'classroom_id' => $smaClassroom->id,
        ]);
    }

    public function test_kepala_sekolah_can_manage_assignments_and_wali_kelas_in_own_unit_but_not_profile_data(): void
    {
        $kepala = $this->userForUnit('kepala_sekolah', 'SMA');
        $teacherUser = $this->userForUnit('guru', 'SMA');
        $teacher = $this->teacherFor($teacherUser, ['SMA' => 'Guru Mapel']);
        $classroom = $this->classroom('SMA', 10, 'X-1');
        $subject = Subject::create(['name' => 'Matematika', 'code' => 'MTK']);

        $this->actingAs($kepala)->putJson("/api/teacher-directory/{$teacher->id}/assignments", [
            'academic_year' => '2026/2027',
            'assignments' => [
                ['subject_id' => $subject->id, 'classroom_id' => $classroom->id],
            ],
            'wali_classroom_ids' => [$classroom->id],
        ])->assertOk()
            ->assertJsonPath('class_count', 1)
            ->assertJsonPath('wali_classrooms.0.id', $classroom->id);

        $this->assertDatabaseHas('teacher_subjects', [
            'teacher_id' => $teacherUser->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
        ]);
        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'wali_kelas_id' => $teacher->id,
        ]);

        $this->actingAs($kepala)
            ->putJson("/api/teacher-directory/{$teacher->id}", ['name' => 'Nama Tidak Boleh'])
            ->assertForbidden();

        $this->actingAs($kepala)
            ->putJson("/api/teacher-directory/{$teacher->id}/units", [
                'units' => [['code' => 'SMA', 'jabatan' => 'Kepala']],
            ])
            ->assertForbidden();
    }

    public function test_operator_creates_pending_teacher_profile_without_role_or_units(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)->postJson('/api/teacher-directory', [
            'name' => 'Guru Baru',
            'email' => 'guru-baru@madani.test',
            'password' => 'password123',
            'phone' => '081234567890',
            'nip' => 'NIP-BARU',
            'is_active' => true,
        ])->assertCreated()
            ->assertJsonPath('name', 'Guru Baru')
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('role', User::ROLE_PENDING)
            ->assertJsonCount(0, 'units');

        $account = User::where('email', 'guru-baru@madani.test')->firstOrFail();

        $this->assertDatabaseHas('teachers', [
            'user_id' => $account->id,
            'nama' => 'Guru Baru',
            'nip' => 'NIP-BARU',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'teacher.create',
            'target_id' => $response->json('id'),
        ]);
    }

    public function test_operator_cannot_assign_role_or_units_through_teacher_directory(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->postJson('/api/teacher-directory', [
            'name' => 'Guru Multi Unit',
            'email' => 'guru-multi@madani.test',
            'password' => 'password123',
            'role' => 'guru',
            'units' => [['code' => 'SMA', 'jabatan' => 'Guru']],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'guru-multi@madani.test']);
    }

    public function test_operator_cannot_access_attendance_or_student_data_from_teacher_page(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $teacher = $this->teacherFor($this->userForUnit('guru', 'SMA'), ['SMA' => 'Guru']);

        $this->actingAs($operator)
            ->getJson("/api/teacher-directory/{$teacher->id}")
            ->assertOk()
            ->assertJsonMissingPath('students');

        $this->actingAs($operator)
            ->getJson("/api/teacher-directory/{$teacher->id}/attendance")
            ->assertForbidden();

        $this->actingAs($operator)
            ->getJson('/api/students')
            ->assertForbidden();
    }

    public function test_multi_unit_teacher_appears_in_each_unit_filter_and_class_count_is_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama', 'is_protected' => false]);
        $teacher = $this->teacherFor($this->userForUnit('guru', 'SMA', ['name' => 'Guru Multi']), [
            'SMA' => 'Guru Mapel',
            'LPI' => 'Pembina',
        ]);

        $smaClass = $this->classroom('SMA', 10, 'X-1');
        $smaClassTwo = $this->classroom('SMA', 10, 'X-2');
        $subject = Subject::create(['name' => 'Bahasa Arab', 'code' => 'BA']);

        TeacherSubject::create([
            'teacher_id' => $teacher->user_id,
            'subject_id' => $subject->id,
            'classroom_id' => $smaClass->id,
            'academic_year' => '2026/2027',
        ]);
        TeacherSubject::create([
            'teacher_id' => $teacher->user_id,
            'subject_id' => $subject->id,
            'classroom_id' => $smaClassTwo->id,
            'academic_year' => '2026/2027',
        ]);
        $smaClass->update(['wali_kelas_id' => $teacher->id]);

        foreach (['SMA', 'LPI'] as $unit) {
            $this->actingAs($admin)
                ->getJson("/api/teacher-directory?unit={$unit}")
                ->assertOk()
                ->assertJsonPath('data.0.name', 'Guru Multi');
        }

        $this->actingAs($admin)
            ->getJson('/api/teacher-directory?unit=SMA')
            ->assertOk()
            ->assertJsonPath('data.0.class_count', 2);
    }

    public function test_admin_utama_updates_units_and_keeps_teacher_units_in_sync(): void
    {
        $admin = User::factory()->create(['role' => 'admin_utama', 'is_protected' => false]);
        $teacherUser = $this->userForUnit('guru', 'SMA');
        $teacher = $this->teacherFor($teacherUser, ['SMA' => 'Guru']);

        $this->actingAs($admin)->putJson("/api/teacher-directory/{$teacher->id}/units", [
            'units' => [
                ['code' => 'LPI', 'jabatan' => 'Wali Kelas'],
                ['code' => 'SMA', 'jabatan' => 'Guru Mapel'],
            ],
        ])->assertOk()
            ->assertJsonCount(2, 'units');

        $this->assertDatabaseHas('teacher_unit', [
            'teacher_id' => $teacher->id,
            'unit_id' => $this->unitId('LPI'),
            'jabatan' => 'Wali Kelas',
        ]);
        $this->assertDatabaseHas('user_unit', [
            'user_id' => $teacherUser->id,
            'unit_id' => $this->unitId('LPI'),
            'jabatan' => 'Wali Kelas',
        ]);
        $this->assertSame('lpi', $teacherUser->fresh()->unit);
    }

    public function test_kepala_sekolah_can_update_monthly_teacher_attendance_in_own_unit(): void
    {
        $kepala = $this->userForUnit('kepala_sekolah', 'SMA');
        $teacherUser = $this->userForUnit('guru', 'SMA');
        $teacher = $this->teacherFor($teacherUser, ['SMA' => 'Guru']);
        $date = now()->format('Y-m-d');

        $record = TeacherAttendance::create([
            'teacher_id' => $teacherUser->id,
            'date' => $date,
            'check_in' => '07:30:00',
            'status' => 'hadir',
        ]);

        $this->actingAs($kepala)
            ->getJson("/api/teacher-directory/{$teacher->id}/attendance?month=".now()->format('Y-m'))
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('records.0.date', $date)
            ->assertJsonPath('records.0.check_in', '07:30:00');

        $this->actingAs($kepala)->putJson(
            "/api/teacher-directory/{$teacher->id}/attendance/{$record->id}",
            [
                'date' => $date,
                'check_in' => '08:15',
                'check_out' => '14:00',
                'status' => 'izin',
                'notes' => 'Keperluan keluarga',
            ],
        )->assertOk()
            ->assertJsonPath('status', 'izin')
            ->assertJsonPath('check_in', '08:15:00');

        $this->assertDatabaseHas('teacher_attendances', [
            'id' => $record->id,
            'check_in' => '08:15:00',
            'check_out' => '14:00:00',
            'status' => 'izin',
        ]);
    }

    private function userForUnit(string $role, string $code, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => $role,
            'unit' => null,
        ], $attributes));

        $user->units()->attach($this->unitId($code));

        return $user;
    }

    /**
     * @param  array<string, string>  $units
     */
    private function teacherFor(User $user, array $units = []): Teacher
    {
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'nama' => $user->name,
        ]);

        foreach ($units as $code => $jabatan) {
            $teacher->units()->attach($this->unitId($code), ['jabatan' => $jabatan]);
        }

        return $teacher;
    }

    private function classroom(string $code, int $grade, string $name): Classroom
    {
        return Classroom::create([
            'name' => $name,
            'grade' => $grade,
            'academic_year' => '2026/2027',
            'unit' => strtolower($code),
            'unit_id' => $this->unitId($code),
        ]);
    }

    private function unitId(string $code): int
    {
        return (int) Unit::where('kode', $code)->value('id');
    }
}
