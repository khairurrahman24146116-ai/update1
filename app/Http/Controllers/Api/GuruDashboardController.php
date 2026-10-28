<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ScheduleService;
use App\Services\TeacherAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuruDashboardController extends Controller
{
    public function __construct(
        protected ScheduleService $schedules,
        protected TeacherAssignmentService $assignments,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $day = $this->dayCode(now()->dayOfWeekIso);
        $weeklySchedules = $this->schedules->forGuru($user->id);
        $todaySchedules = $weeklySchedules
            ->where('day_of_week', $day)
            ->sortBy('start_time')
            ->values();
        $assignments = $this->assignments->assignmentsFor($user);

        $unitLabels = $assignments
            ->pluck('classroom_label')
            ->merge($weeklySchedules->map(fn (Schedule $schedule): string => $schedule->classroom?->unitLabel() ?? '-'))
            ->filter(fn (string $label): bool => $label !== '' && $label !== '-')
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'date' => now()->toDateString(),
            'day' => $day,
            'day_label' => $this->dayLabel($day),
            'today_schedules' => $todaySchedules->map(fn (Schedule $schedule): array => $this->schedulePayload($schedule))->all(),
            'weekly_schedules' => $weeklySchedules->map(fn (Schedule $schedule): array => $this->schedulePayload($schedule))->all(),
            'assignments' => $assignments->values()->all(),
            'summary' => [
                'today_sessions' => $todaySchedules->count(),
                'weekly_sessions' => $weeklySchedules->count(),
                'assigned_pairs' => $assignments->count(),
                'unit_labels' => $unitLabels,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function schedulePayload(Schedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'classroom_id' => $schedule->classroom_id,
            'classroom_name' => $schedule->classroom?->name,
            'classroom_label' => $schedule->classroom?->unitLabel() ?? '-',
            'subject_id' => $schedule->subject_id,
            'subject_name' => $schedule->subject?->name,
            'subject_jenis' => $schedule->subject?->jenis,
            'subject_is_active' => (bool) $schedule->subject?->is_active,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => substr((string) $schedule->start_time, 0, 5),
            'end_time' => substr((string) $schedule->end_time, 0, 5),
        ];
    }

    private function dayCode(int $dayOfWeekIso): string
    {
        return ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'][$dayOfWeekIso - 1] ?? 'mon';
    }

    private function dayLabel(string $day): string
    {
        return [
            'mon' => 'Senin',
            'tue' => 'Selasa',
            'wed' => 'Rabu',
            'thu' => 'Kamis',
            'fri' => 'Jumat',
            'sat' => 'Sabtu',
            'sun' => 'Minggu',
        ][$day] ?? $day;
    }
}
