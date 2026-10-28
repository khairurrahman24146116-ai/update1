<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_only_own_enabled_lpi_and_sma_schedules_and_assignments(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject('Matematika', Subject::JENIS_FORMAL);
        $tsanawiyah = $this->classroom('lpi', Classroom::JENJANG_TSANAWIYAH, 'VII-TSAN');
        $aliyah = $this->classroom('lpi', Classroom::JENJANG_ALIYAH, 'X-ALIYAH');
        $sma = $this->classroom('sma', Classroom::JENJANG_SMA, 'X-SMA');
        $smp = $this->classroom('smp', Classroom::JENJANG_SMP, 'VII-SMP');
        $day = $this->today();

        $this->schedule($guru, $subject, $tsanawiyah, $day, '07:00', '08:30');
        $this->schedule($guru, $subject, $sma, $day, '09:00', '10:30');
        $this->schedule($guru, $subject, $smp, $day, '11:00', '12:30');
        $this->assign($guru, $subject, $tsanawiyah);
        $this->assign($guru, $subject, $aliyah);
        $this->assign($guru, $subject, $sma);
        $this->assign($guru, $subject, $smp);

        $response = $this->actingAs($guru)->getJson('/api/guru/dashboard')->assertOk();

        $this->assertSame(2, $response->json('summary.today_sessions'));
        $this->assertSame(2, $response->json('summary.weekly_sessions'));
        $this->assertSame(3, $response->json('summary.assigned_pairs'));
        $this->assertEqualsCanonicalizing(
            ['LPI - Tsanawiyah', 'LPI - Aliyah', 'SMA'],
            $response->json('summary.unit_labels'),
        );
        $this->assertEqualsCanonicalizing(
            ['LPI - Tsanawiyah', 'SMA'],
            collect($response->json('today_schedules'))->pluck('classroom_label')->all(),
        );
        $this->assertNotContains('VII-SMP', collect($response->json('weekly_schedules'))->pluck('classroom_name'));
        $this->assertSame('07:00', $response->json('today_schedules.0.start_time'));
        $this->assertSame(Subject::JENIS_FORMAL, $response->json('today_schedules.0.subject_jenis'));
    }

    public function test_dashboard_hides_lpi_when_lpi_unit_is_disabled(): void
    {
        config(['units.enabled' => ['sma']]);

        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $subject = $this->subject('Fisika', Subject::JENIS_FORMAL);
        $sma = $this->classroom('sma', Classroom::JENJANG_SMA, 'X-SMA');
        $lpi = $this->classroom('lpi', Classroom::JENJANG_ALIYAH, 'X-LPI');

        $this->schedule($guru, $subject, $sma, $this->today(), '07:00', '08:30');
        $this->schedule($guru, $subject, $lpi, $this->today(), '09:00', '10:30');
        $this->assign($guru, $subject, $sma);
        $this->assign($guru, $subject, $lpi);

        $response = $this->actingAs($guru)->getJson('/api/guru/dashboard')->assertOk();

        $this->assertSame(1, $response->json('summary.today_sessions'));
        $this->assertSame(1, $response->json('summary.assigned_pairs'));
        $this->assertSame(['SMA'], $response->json('summary.unit_labels'));
    }

    public function test_dashboard_is_only_available_to_guru(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_UTAMA]);

        $this->actingAs($admin)->getJson('/api/guru/dashboard')->assertForbidden();
    }

    private function today(): string
    {
        return ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'][now()->dayOfWeekIso - 1];
    }

    private function classroom(string $unit, string $jenjang, string $name): Classroom
    {
        return Classroom::create([
            'name' => $name,
            'grade' => in_array($jenjang, [Classroom::JENJANG_ALIYAH, Classroom::JENJANG_SMA], true) ? 10 : 7,
            'academic_year' => '2026/2027',
            'unit' => $unit,
            'jalur' => $unit === 'lpi' ? Classroom::JALUR_LPI : Classroom::JALUR_FORMAL,
            'jenjang' => $jenjang,
        ]);
    }

    private function subject(string $name, string $jenis): Subject
    {
        return Subject::create(['name' => $name, 'jenis' => $jenis]);
    }

    private function schedule(User $teacher, Subject $subject, Classroom $classroom, string $day, string $start, string $end): Schedule
    {
        return Schedule::create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'academic_year' => '2026/2027',
        ]);
    }

    private function assign(User $teacher, Subject $subject, Classroom $classroom): TeacherSubject
    {
        return TeacherSubject::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'academic_year' => '2026/2027',
        ]);
    }
}
