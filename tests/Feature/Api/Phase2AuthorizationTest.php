<?php

namespace Tests\Feature\Api;

use App\Models\Classroom;
use App\Models\SiteSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function userForUnit(string $role, string $code, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => $role,
            'unit' => null,
        ], $attributes));

        $user->units()->attach(Unit::where('kode', $code)->value('id'));

        return $user;
    }

    private function classroom(string $code, int $grade, string $name): Classroom
    {
        return Classroom::create([
            'name' => $name,
            'grade' => $grade,
            'academic_year' => '2026/2027',
            'unit' => strtolower($code),
            'unit_id' => Unit::where('kode', $code)->value('id'),
        ]);
    }

    private function student(Classroom $classroom, string $name): Student
    {
        return Student::create([
            'nis' => fake()->unique()->numerify('NIS####'),
            'nisn' => fake()->unique()->numerify('##########'),
            'gender' => 'L',
            'name' => $name,
            'classroom_id' => $classroom->id,
            'status' => 'aktif',
        ]);
    }

    public function test_kepala_sekolah_can_update_student_in_own_unit_but_not_other_unit(): void
    {
        $kepala = $this->userForUnit('kepala_sekolah', 'SMA');
        $smaClass = $this->classroom('SMA', 10, 'X-SMA');
        $smpClass = $this->classroom('SMP', 7, 'VII-SMP');

        $smaStudent = $this->student($smaClass, 'Siswa SMA');
        $smpStudent = $this->student($smpClass, 'Siswa SMP');

        $this->actingAs($kepala)
            ->putJson("/api/students/{$smaStudent->id}", ['name' => 'Siswa SMA Baru'])
            ->assertOk()
            ->assertJsonPath('name', 'Siswa SMA Baru');

        $this->actingAs($kepala)
            ->putJson("/api/students/{$smpStudent->id}", ['name' => 'Tidak Boleh'])
            ->assertStatus(403);
    }

    public function test_kepala_sekolah_cannot_access_account_management(): void
    {
        $kepala = $this->userForUnit('kepala_sekolah', 'SMA');

        $this->actingAs($kepala)->getJson('/api/users')->assertStatus(403);
        $this->actingAs($kepala)->postJson('/api/users', [
            'name' => 'Akun Baru',
            'email' => 'akun-baru@madani.test',
            'password' => 'password123',
            'role' => 'guru',
        ])->assertStatus(403);
    }

    public function test_operator_cannot_access_lpi_finance_while_admin_utama_can(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $adminUtama = User::factory()->create(['role' => 'admin_utama', 'is_protected' => true]);

        $this->actingAs($operator)
            ->getJson('/api/finance/transactions')
            ->assertStatus(403);

        $this->actingAs($adminUtama)
            ->getJson('/api/finance/transactions?unit=lpi')
            ->assertOk();
    }

    public function test_operator_cannot_edit_self_or_another_operator(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $otherOperator = User::factory()->create(['role' => 'operator']);
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($operator)
            ->putJson("/api/users/{$operator->id}", ['name' => 'Ubah Sendiri'])
            ->assertStatus(403);

        $this->actingAs($operator)
            ->putJson("/api/users/{$otherOperator->id}", ['name' => 'Ubah Operator'])
            ->assertStatus(403);

        $this->actingAs($operator)
            ->putJson("/api/users/{$guru->id}", ['name' => 'Ubah Guru'])
            ->assertOk();
    }

    public function test_guru_cannot_open_student_outside_taught_class(): void
    {
        $guru = $this->userForUnit('guru', 'SMA');
        $subject = Subject::create(['name' => 'Matematika', 'code' => 'MTK']);

        $taughtClass = $this->classroom('SMA', 10, 'X-1');
        $otherClass = $this->classroom('SMA', 10, 'X-2');

        TeacherSubject::create([
            'teacher_id' => $guru->id,
            'subject_id' => $subject->id,
            'classroom_id' => $taughtClass->id,
            'academic_year' => '2026/2027',
        ]);

        $taughtStudent = $this->student($taughtClass, 'Siswa Diampu');
        $otherStudent = $this->student($otherClass, 'Siswa Lain');

        $this->actingAs($guru)
            ->getJson("/api/students/{$taughtStudent->id}")
            ->assertOk();

        $this->actingAs($guru)
            ->getJson("/api/students/{$otherStudent->id}")
            ->assertStatus(403);
    }

    public function test_unit_scope_filters_students_and_classrooms_for_kepala_sekolah(): void
    {
        $kepala = $this->userForUnit('kepala_sekolah', 'SMA');
        $smaClass = $this->classroom('SMA', 10, 'X-SMA');
        $smpClass = $this->classroom('SMP', 7, 'VII-SMP');

        $this->student($smaClass, 'Siswa SMA');
        $this->student($smpClass, 'Siswa SMP');

        $students = $this->actingAs($kepala)->getJson('/api/students')->assertOk();
        $this->assertSame(['Siswa SMA'], collect($students->json('data'))->pluck('name')->all());

        $classrooms = $this->actingAs($kepala)->getJson('/api/classrooms')->assertOk();
        $this->assertSame(['X-SMA'], collect($classrooms->json('data'))->pluck('name')->all());
    }

    public function test_kepala_sekolah_without_user_unit_sees_no_data(): void
    {
        $kepala = User::factory()->create(['role' => 'kepala_sekolah', 'unit' => 'sma']);
        $smaClass = $this->classroom('SMA', 10, 'X-SMA');
        $smpClass = $this->classroom('SMP', 7, 'VII-SMP');

        $this->student($smaClass, 'Siswa SMA');
        $this->student($smpClass, 'Siswa SMP');

        $students = $this->actingAs($kepala)->getJson('/api/students')->assertOk();
        $this->assertSame([], $students->json('data'));

        $classrooms = $this->actingAs($kepala)->getJson('/api/classrooms')->assertOk();
        $this->assertSame([], $classrooms->json('data'));
    }

    public function test_account_with_two_user_units_sees_only_those_units(): void
    {
        $kepala = User::factory()->create(['role' => 'kepala_sekolah', 'unit' => null]);
        $kepala->units()->attach([
            Unit::where('kode', 'LPI')->value('id'),
            Unit::where('kode', 'SMA')->value('id'),
        ]);

        $lpiClass = $this->classroom('LPI', 7, 'VII-LPI');
        $smaClass = $this->classroom('SMA', 10, 'X-SMA');
        $smpClass = $this->classroom('SMP', 7, 'VII-SMP');

        $this->student($lpiClass, 'Siswa LPI');
        $this->student($smaClass, 'Siswa SMA');
        $this->student($smpClass, 'Siswa SMP');

        $students = $this->actingAs($kepala)->getJson('/api/students')->assertOk();
        $this->assertSame(
            ['Siswa LPI', 'Siswa SMA'],
            collect($students->json('data'))->pluck('name')->sort()->values()->all(),
        );

        $classrooms = $this->actingAs($kepala)->getJson('/api/classrooms')->assertOk();
        $this->assertSame(
            ['VII-LPI', 'X-SMA'],
            collect($classrooms->json('data'))->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_wali_murid_only_sees_teachers_of_child_class_without_personal_data(): void
    {
        $parent = User::factory()->create(['role' => 'wali_murid']);
        $childClass = $this->classroom('SMA', 10, 'X-Wali');
        $otherClass = $this->classroom('SMA', 10, 'X-Lain');

        $child = $this->student($childClass, 'Anak Wali');
        $child->update(['parent_id' => $parent->id]);

        $teachingTeacher = User::factory()->create([
            'role' => 'guru',
            'name' => 'Ustadz Pengajar',
            'email' => 'pengajar@madani.test',
            'phone' => '081234567890',
        ]);
        $otherTeacher = User::factory()->create(['role' => 'guru', 'name' => 'Ustadz Lain']);

        $subject = Subject::create(['name' => 'Matematika', 'code' => 'MTK']);

        TeacherSubject::create([
            'teacher_id' => $teachingTeacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $childClass->id,
            'academic_year' => '2026/2027',
        ]);

        TeacherSubject::create([
            'teacher_id' => $otherTeacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $otherClass->id,
            'academic_year' => '2026/2027',
        ]);

        SiteSetting::create([
            'app_name' => 'Madani',
            'phone' => '0751-123456',
        ]);

        $teachers = $this->actingAs($parent)->getJson('/api/teachers')->assertOk();
        $payload = $teachers->json();

        $this->assertCount(1, $payload);
        $this->assertSame(['name', 'subjects', 'school_contact'], array_keys($payload[0]));
        $this->assertSame('Ustadz Pengajar', $payload[0]['name']);
        $this->assertSame(['Matematika'], $payload[0]['subjects']);
        $this->assertSame('0751-123456', $payload[0]['school_contact']);
    }

    public function test_bendahara_sma_cannot_open_lpi_finance(): void
    {
        $bendahara = $this->userForUnit('bendahara_sma', 'SMA');

        $this->actingAs($bendahara)
            ->getJson('/api/finance/transactions?unit=lpi')
            ->assertStatus(403);
    }
}
