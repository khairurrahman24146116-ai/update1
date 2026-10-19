<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendanceService
{
    public function listBySchedule(int $scheduleId, ?string $date = null, int $perPage = 50): LengthAwarePaginator
    {
        return Attendance::with(['student', 'schedule'])
            ->where('schedule_id', $scheduleId)
            ->when($date, fn ($q) => $q->where('date', $date))
            ->orderBy('date', 'desc')->paginate($perPage);
    }

    public function sheet(int $scheduleId, string $date): Collection
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $students = Student::where('classroom_id', $schedule->classroom_id)->where('status', 'aktif')->orderBy('name')->get();
        $records = Attendance::where('schedule_id', $scheduleId)->where('date', $date)->get()->keyBy('student_id');

        return $students->map(fn ($s) => ['student' => $s, 'attendance' => $records->get($s->id)]);
    }

    public function upsertMany(int $scheduleId, string $date, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $values = [];

        foreach ($rows as $row) {
            $values[] = [
                'schedule_id' => $scheduleId,
                'student_id' => $row['student_id'],
                'date' => $date,
                'status' => $row['status'],
                'notes' => $row['notes'] ?? null,
            ];
        }

        Attendance::upsert(
            $values,
            ['schedule_id', 'student_id', 'date'],
            ['status', 'notes'],
        );
    }

    public function update(Attendance $attendance, array $data): Attendance
    {
        $attendance->update($data);

        return $attendance->fresh(['student', 'schedule']);
    }
}
