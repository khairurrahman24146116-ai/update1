<?php

namespace App\Services;

use App\Models\AkhlakRecord;
use App\Models\Attendance;
use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Student;
use App\Models\StudentPermit;
use App\Models\StudentWelfareAlert;
use App\Models\StudentWelfareSetting;
use App\Support\WelfarePeriod;

class EligibilityService
{
    /**
     * @return array<string, mixed>
     */
    public function evaluate(Student $student, ?StudentWelfareSetting $settings = null): array
    {
        $student->loadMissing(['classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class)]);
        $settings ??= StudentWelfareSetting::query()->firstOrCreate([], StudentWelfareSetting::defaults());

        $academicYear = WelfarePeriod::academicYearFor($student->classroom);
        $semester = WelfarePeriod::semesterFor(now());
        [$start, $end] = WelfarePeriod::academicYearRange($academicYear);
        [$semesterStart, $semesterEnd] = WelfarePeriod::semesterRange($academicYear, $semester);

        $attendanceRows = Attendance::query()
            ->where('student_id', $student->id)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('LOWER(status) as status, COUNT(*) as total')
            ->groupByRaw('LOWER(status)')
            ->pluck('total', 'status');

        $totalAttendance = (int) $attendanceRows->sum();
        $present = (int) ($attendanceRows['hadir'] ?? 0);
        $attendancePercent = $totalAttendance > 0
            ? round(($present / $totalAttendance) * 100, 1)
            : 100.0;

        $permitBase = StudentPermit::query()
            ->where('student_id', $student->id)
            ->where('status', StudentPermit::STATUS_APPROVED);

        $monthCount = (clone $permitBase)
            ->where('start_date', '<=', WelfarePeriod::monthRange(now())[1])
            ->where('end_date', '>=', WelfarePeriod::monthRange(now())[0])
            ->count();

        $semesterCount = (clone $permitBase)
            ->where('start_date', '<=', $semesterEnd)
            ->where('end_date', '>=', $semesterStart)
            ->count();

        $overrideCount = (clone $permitBase)
            ->where('is_override', true)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->count();

        $violationBase = AkhlakRecord::query()
            ->where('student_id', $student->id)
            ->where('type', AkhlakRecord::TYPE_PELANGGARAN)
            ->where('status', AkhlakRecord::STATUS_ACTIVE)
            ->whereBetween('occurred_at', [$start, $end]);

        $violationPoints = (int) (clone $violationBase)->sum('points');
        $violationCount = (clone $violationBase)->count();

        $apresiasiCount = AkhlakRecord::query()
            ->where('student_id', $student->id)
            ->where('type', AkhlakRecord::TYPE_APRESIASI)
            ->where('status', AkhlakRecord::STATUS_ACTIVE)
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        $minimumAttendance = (float) $settings->attendance_min_percent;
        $warningPoints = (int) $settings->violation_warning_points;
        $maxPoints = (int) $settings->violation_max_points;
        $monthLimit = (int) $settings->permit_max_per_month;
        $semesterLimit = (int) $settings->permit_max_per_semester;

        $reasons = [];
        $fails = false;
        $warnings = false;

        if ($attendancePercent < $minimumAttendance) {
            $fails = true;
            $reasons[] = [
                'code' => 'attendance_below_min',
                'label' => "Kehadiran {$attendancePercent}% di bawah batas {$minimumAttendance}%.",
                'severity' => 'danger',
            ];
        } elseif ($attendancePercent < min(100, $minimumAttendance + 5)) {
            $warnings = true;
            $reasons[] = [
                'code' => 'attendance_near_min',
                'label' => "Kehadiran {$attendancePercent}% mendekati batas minimum {$minimumAttendance}%.",
                'severity' => 'warning',
            ];
        }

        if ($violationPoints >= $maxPoints) {
            $fails = true;
            $reasons[] = [
                'code' => 'violation_points_max',
                'label' => "Poin pelanggaran {$violationPoints} mencapai batas maksimum {$maxPoints}.",
                'severity' => 'danger',
            ];
        } elseif ($violationPoints >= $warningPoints) {
            $warnings = true;
            $reasons[] = [
                'code' => 'violation_points_warning',
                'label' => "Poin pelanggaran {$violationPoints} mencapai batas peringatan {$warningPoints}.",
                'severity' => 'warning',
            ];
        }

        if ($monthCount > $monthLimit) {
            $fails = true;
            $reasons[] = [
                'code' => 'permit_monthly_exceeded',
                'label' => "Izin bulan ini {$monthCount} melebihi batas {$monthLimit}.",
                'severity' => 'danger',
            ];
        } elseif ($monthCount === $monthLimit) {
            $warnings = true;
            $reasons[] = [
                'code' => 'permit_monthly_limit',
                'label' => "Izin bulan ini sudah mencapai batas {$monthLimit}.",
                'severity' => 'warning',
            ];
        }

        if ($semesterCount > $semesterLimit) {
            $fails = true;
            $reasons[] = [
                'code' => 'permit_semester_exceeded',
                'label' => "Izin semester ini {$semesterCount} melebihi batas {$semesterLimit}.",
                'severity' => 'danger',
            ];
        } elseif ($semesterCount === $semesterLimit) {
            $warnings = true;
            $reasons[] = [
                'code' => 'permit_semester_limit',
                'label' => "Izin semester ini sudah mencapai batas {$semesterLimit}.",
                'severity' => 'warning',
            ];
        }

        if ($overrideCount > 0) {
            $warnings = true;
            $reasons[] = [
                'code' => 'permit_override',
                'label' => "Terdapat {$overrideCount} izin override pada tahun ajaran ini.",
                'severity' => 'warning',
            ];
        }

        $status = $fails ? 'tidak_memenuhi' : ($warnings ? 'perlu_perhatian' : 'layak');

        $alertPayloads = [];

        if ($attendancePercent < $minimumAttendance) {
            $alertPayloads[] = [
                'type' => StudentWelfareAlert::TYPE_ATTENDANCE,
                'severity' => 'danger',
                'message' => "Kehadiran {$attendancePercent}% di bawah batas minimum {$minimumAttendance}%.",
            ];
        }

        if ($violationPoints >= $warningPoints) {
            $alertPayloads[] = [
                'type' => StudentWelfareAlert::TYPE_VIOLATION,
                'severity' => $violationPoints >= $maxPoints ? 'danger' : 'warning',
                'message' => "Poin pelanggaran mencapai {$violationPoints} dari batas peringatan {$warningPoints}.",
            ];
        }

        if ($monthCount > $monthLimit) {
            $alertPayloads[] = [
                'type' => StudentWelfareAlert::TYPE_PERMIT_MONTHLY,
                'severity' => 'danger',
                'message' => "Jumlah izin bulan ini {$monthCount} melebihi batas {$monthLimit}.",
            ];
        }

        if ($semesterCount > $semesterLimit) {
            $alertPayloads[] = [
                'type' => StudentWelfareAlert::TYPE_PERMIT_SEMESTER,
                'severity' => 'danger',
                'message' => "Jumlah izin semester ini {$semesterCount} melebihi batas {$semesterLimit}.",
            ];
        }

        if ($overrideCount > 0) {
            $alertPayloads[] = [
                'type' => StudentWelfareAlert::TYPE_PERMIT_OVERRIDE,
                'severity' => 'warning',
                'message' => "Terdapat {$overrideCount} izin override pada tahun ajaran {$academicYear}.",
            ];
        }

        $this->syncAlerts($student->id, $academicYear, $alertPayloads);

        $alerts = StudentWelfareAlert::query()
            ->where('student_id', $student->id)
            ->where('period', $academicYear)
            ->whereNull('resolved_at')
            ->orderBy('id')
            ->get()
            ->map(fn (StudentWelfareAlert $alert): array => [
                'id' => $alert->id,
                'type' => $alert->type,
                'severity' => $alert->severity,
                'message' => $alert->message,
                'resolved_at' => $alert->resolved_at?->toDateTimeString(),
            ])
            ->values()
            ->all();

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'classroom_id' => $student->classroom_id,
                'classroom_name' => (string) $student->classroom?->name,
                'classroom_label' => $student->classroom?->unitLabel(),
            ],
            'period' => [
                'academic_year' => $academicYear,
                'semester' => $semester,
                'start' => $start,
                'end' => $end,
            ],
            'attendance' => [
                'hadir' => $present,
                'total' => $totalAttendance,
                'percent' => $attendancePercent,
                'minimum_percent' => $minimumAttendance,
            ],
            'permits' => [
                'month' => $monthCount,
                'semester' => $semesterCount,
                'override' => $overrideCount,
                'month_limit' => $monthLimit,
                'semester_limit' => $semesterLimit,
            ],
            'violations' => [
                'points' => $violationPoints,
                'count' => $violationCount,
                'warning_points' => $warningPoints,
                'max_points' => $maxPoints,
            ],
            'apresiasi' => [
                'count' => $apresiasiCount,
            ],
            'status' => $status,
            'status_label' => match ($status) {
                'tidak_memenuhi' => 'Tidak Memenuhi Syarat',
                'perlu_perhatian' => 'Perlu Perhatian',
                default => 'Layak',
            },
            'status_class' => match ($status) {
                'tidak_memenuhi' => 'danger',
                'perlu_perhatian' => 'warning',
                default => 'success',
            },
            'reasons' => $reasons,
            'alerts' => $alerts,
        ];
    }

    /**
     * @param  list<array{type: string, severity: string, message: string}>  $alerts
     */
    private function syncAlerts(int $studentId, string $period, array $alerts): void
    {
        $activeTypes = [];

        foreach ($alerts as $alert) {
            $activeTypes[] = $alert['type'];

            StudentWelfareAlert::query()->updateOrCreate(
                [
                    'student_id' => $studentId,
                    'type' => $alert['type'],
                    'period' => $period,
                ],
                [
                    'severity' => $alert['severity'],
                    'message' => $alert['message'],
                    'resolved_at' => null,
                ],
            );
        }

        $resolved = StudentWelfareAlert::query()
            ->where('student_id', $studentId)
            ->where('period', $period)
            ->whereNull('resolved_at');

        if ($activeTypes === []) {
            $resolved->update(['resolved_at' => now()]);

            return;
        }

        $resolved->whereNotIn('type', $activeTypes)->update(['resolved_at' => now()]);
    }
}
