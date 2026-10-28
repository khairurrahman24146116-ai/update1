<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_formal_and_non_formal_subjects_with_multiple_scopes(): void
    {
        $admin = $this->admin();

        $formal = $this->actingAs($admin)->postJson('/api/subjects', [
            'name' => 'Matematika',
            'code' => 'mtk',
            'jenis' => Subject::JENIS_FORMAL,
            'placements' => [
                ['unit' => 'lpi', 'jenjang' => Classroom::JENJANG_TSANAWIYAH],
                ['unit' => 'sma', 'jenjang' => Classroom::JENJANG_SMA],
            ],
        ])->assertCreated()->assertJsonPath('jenis', Subject::JENIS_FORMAL);

        $this->assertEqualsCanonicalizing(
            ['lpi:Tsanawiyah', 'sma:SMA'],
            collect($formal->json('scopes'))->map(fn (array $scope): string => $scope['unit'].':'.$scope['jenjang'])->all(),
        );
        $this->assertSame('MTK', $formal->json('code'));

        $this->actingAs($admin)->postJson('/api/subjects', [
            'name' => 'Tahfiz',
            'jenis' => Subject::JENIS_NON_FORMAL,
            'placements' => [['unit' => 'lpi', 'jenjang' => Classroom::JENJANG_ALIYAH]],
        ])->assertCreated()->assertJsonPath('jenis', Subject::JENIS_NON_FORMAL);

        $this->assertDatabaseHas('subject_scopes', [
            'subject_id' => $formal->json('id'),
            'unit' => 'lpi',
            'jenjang' => Classroom::JENJANG_TSANAWIYAH,
        ]);
    }

    public function test_subject_name_must_be_unique_per_unit_and_jenjang(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/subjects', [
            'name' => 'Matematika',
            'jenis' => Subject::JENIS_FORMAL,
            'placements' => [['unit' => 'lpi', 'jenjang' => Classroom::JENJANG_TSANAWIYAH]],
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/api/subjects', [
            'name' => 'matematika',
            'jenis' => Subject::JENIS_FORMAL,
            'placements' => [['unit' => 'lpi', 'jenjang' => Classroom::JENJANG_TSANAWIYAH]],
        ])->assertUnprocessable()->assertJsonValidationErrors('name');

        $this->actingAs($admin)->postJson('/api/subjects', [
            'name' => 'Matematika',
            'jenis' => Subject::JENIS_FORMAL,
            'placements' => [['unit' => 'lpi', 'jenjang' => Classroom::JENJANG_ALIYAH]],
        ])->assertCreated();
    }

    public function test_used_subject_cannot_be_deleted_but_can_be_deactivated(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject('Biologi', [['unit' => 'sma', 'jenjang' => Classroom::JENJANG_SMA]]);
        $classroom = $this->classroom('sma', Classroom::JENJANG_SMA, 'X-SMA');

        Schedule::create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $guru->id,
            'day_of_week' => 'mon',
            'start_time' => '07:00',
            'end_time' => '08:30',
            'academic_year' => '2026/2027',
        ]);

        $this->actingAs($admin)->deleteJson("/api/subjects/{$subject->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Nonaktifkan'));

        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);

        $this->actingAs($admin)->putJson("/api/subjects/{$subject->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('is_active', false);
    }

    public function test_subject_list_filters_by_unit_jenjang_type_and_status(): void
    {
        $admin = $this->admin();
        $tsanawiyah = $this->subject('Fikih', [['unit' => 'lpi', 'jenjang' => Classroom::JENJANG_TSANAWIYAH]]);
        $sma = $this->subject('Fisika', [['unit' => 'sma', 'jenjang' => Classroom::JENJANG_SMA]], Subject::JENIS_NON_FORMAL);
        $inactive = $this->subject('Bahasa Arab', [['unit' => 'lpi', 'jenjang' => Classroom::JENJANG_ALIYAH]]);
        $inactive->update(['is_active' => false]);

        $response = $this->actingAs($admin)
            ->getJson('/api/subjects?unit=lpi&jenjang=Tsanawiyah&status=aktif&per_page=100')
            ->assertOk();

        $this->assertSame([$tsanawiyah->id], collect($response->json('data'))->pluck('id')->all());

        $response = $this->actingAs($admin)
            ->getJson('/api/subjects?jenis=non_formal&per_page=100')
            ->assertOk();

        $this->assertSame([$sma->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_schedule_rejects_subject_outside_classroom_scope(): void
    {
        $admin = $this->admin();
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject('Matematika', [['unit' => 'lpi', 'jenjang' => Classroom::JENJANG_TSANAWIYAH]]);
        $sma = $this->classroom('sma', Classroom::JENJANG_SMA, 'X-SMA');

        $this->actingAs($admin)->postJson('/api/schedules', [
            'classroom_id' => $sma->id,
            'subject_id' => $subject->id,
            'teacher_id' => $guru->id,
            'day_of_week' => 'mon',
            'start_time' => '07:00',
            'end_time' => '08:30',
            'academic_year' => '2026/2027',
        ])->assertUnprocessable()->assertJsonValidationErrors('subject_id');
    }

    public function test_updating_subject_keeps_scopes_from_disabled_units(): void
    {
        config(['units.enabled' => ['sma']]);

        $admin = $this->admin();
        $subject = $this->subject('Sejarah', [
            ['unit' => 'sma', 'jenjang' => Classroom::JENJANG_SMA],
            ['unit' => 'smp', 'jenjang' => Classroom::JENJANG_SMP],
        ]);

        $this->actingAs($admin)->putJson("/api/subjects/{$subject->id}", [
            'name' => 'Sejarah Indonesia',
            'placements' => [['unit' => 'sma', 'jenjang' => Classroom::JENJANG_SMA]],
        ])->assertOk();

        $this->assertDatabaseHas('subject_scopes', [
            'subject_id' => $subject->id,
            'unit' => 'smp',
            'jenjang' => Classroom::JENJANG_SMP,
        ]);
    }

    public function test_teacher_assignment_options_only_include_active_subjects(): void
    {
        $admin = $this->admin();
        $active = $this->subject('Aktif', [['unit' => 'sma', 'jenjang' => Classroom::JENJANG_SMA]]);
        $inactive = $this->subject('Nonaktif', [['unit' => 'sma', 'jenjang' => Classroom::JENJANG_SMA]]);
        $inactive->update(['is_active' => false]);

        $response = $this->actingAs($admin)->getJson('/api/teacher-directory/options')->assertOk();
        $ids = collect($response->json('subjects'))->pluck('id');

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);
    }

    private function classroom(string $unit, string $jenjang, string $name): Classroom
    {
        return Classroom::create([
            'name' => $name,
            'grade' => str_contains(strtolower($jenjang), 'aliyah') || str_contains($jenjang, 'SMA') ? 10 : 7,
            'academic_year' => '2026/2027',
            'unit' => $unit,
            'jalur' => $unit === 'lpi' ? Classroom::JALUR_LPI : Classroom::JALUR_FORMAL,
            'jenjang' => $jenjang,
        ]);
    }

    /**
     * @param  list<array{unit: string, jenjang: string}>  $scopes
     */
    private function subject(string $name, array $scopes, string $jenis = Subject::JENIS_FORMAL): Subject
    {
        $subject = Subject::create(['name' => $name, 'jenis' => $jenis]);
        $subject->scopes()->createMany($scopes);

        return $subject;
    }
}
