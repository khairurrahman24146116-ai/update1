<?php

namespace App\Services;

use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Student;
use App\Models\StudentPermit;
use App\Models\StudentWelfareAlert;
use App\Support\WelfarePeriod;
use Illuminate\Support\Collection;

class PermitReturnMonitor
{
    public function syncAll(): int
    {
        $permits = StudentPermit::query()
            ->where('status', StudentPermit::STATUS_APPROVED)
            ->whereNull('returned_at')
            ->whereDate('end_date', '<=', now()->toDateString())
            ->orderBy('end_date')
            ->get();

        $studentIds = $permits->pluck('student_id')->unique()->values();

        foreach ($studentIds as $studentId) {
            $student = Student::query()
                ->withoutGlobalScope(BerdasarkanUnit::class)
                ->with(['classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class)])
                ->find($studentId);

            if ($student !== null) {
                $this->syncStudent($student);
            }
        }

        $stale = StudentWelfareAlert::query()
            ->where('type', StudentWelfareAlert::TYPE_PERMIT_RETURN)
            ->whereNull('resolved_at');

        if ($studentIds->isNotEmpty()) {
            $stale->whereNotIn('student_id', $studentIds->all());
        }

        $stale->update(['resolved_at' => now()]);

        return $studentIds->count();
    }

    public function syncStudent(Student $student): void
    {
        $student->loadMissing(['classroom' => fn ($query) => $query->withoutGlobalScope(BerdasarkanUnit::class)]);

        $permits = StudentPermit::query()
            ->where('student_id', $student->id)
            ->where('status', StudentPermit::STATUS_APPROVED)
            ->whereNull('returned_at')
            ->whereDate('end_date', '<=', now()->toDateString())
            ->orderBy('end_date')
            ->get();

        $today = now()->startOfDay();
        $overdue = $permits->filter(fn (StudentPermit $permit): bool => $permit->end_date->lt($today));
        $dueToday = $permits->filter(fn (StudentPermit $permit): bool => $permit->end_date->equalTo($today));

        $alerts = StudentWelfareAlert::query()
            ->where('student_id', $student->id)
            ->where('type', StudentWelfareAlert::TYPE_PERMIT_RETURN)
            ->whereNull('resolved_at');

        if ($overdue->isEmpty() && $dueToday->isEmpty()) {
            $alerts->update(['resolved_at' => now()]);

            return;
        }

        $period = WelfarePeriod::academicYearFor($student->classroom);

        if ($overdue->isNotEmpty()) {
            $severity = 'danger';
            $message = $this->overdueMessage($overdue);
        } else {
            $severity = 'warning';
            $message = $dueToday->count() === 1
                ? 'Izin berakhir hari ini dan santri belum dicatat kembali.'
                : "{$dueToday->count()} izin berakhir hari ini dan belum dicatat kembali.";
        }

        StudentWelfareAlert::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'type' => StudentWelfareAlert::TYPE_PERMIT_RETURN,
                'period' => $period,
            ],
            [
                'severity' => $severity,
                'message' => $message,
                'resolved_at' => null,
            ],
        );

        $alerts->where('period', '!=', $period)->update(['resolved_at' => now()]);
    }

    /**
     * @param  Collection<int, StudentPermit>  $overdue
     */
    private function overdueMessage(Collection $overdue): string
    {
        $oldest = $overdue->first();
        $daysLate = max(0, (int) $oldest->end_date->copy()->startOfDay()->diffInDays(now()->startOfDay(), true));

        if ($overdue->count() === 1) {
            return "Izin berakhir {$oldest->end_date->toDateString()} dan santri belum dicatat kembali (terlambat {$daysLate} hari).";
        }

        return "{$overdue->count()} izin belum dicatat kembali; terlama berakhir {$oldest->end_date->toDateString()} (terlambat {$daysLate} hari).";
    }
}
