<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertAttendanceRequest;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Student;
use App\Services\ActivityLogger;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceService $service) {}

    public function index(int $scheduleId, Request $request)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $this->authorize('viewAttendance', $schedule);

        return $this->service->listBySchedule($scheduleId, $request->query('date'));
    }

    public function sheet(int $scheduleId, Request $request)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $this->authorize('viewAttendance', $schedule);

        return $this->service->sheet($scheduleId, $request->query('date', date('Y-m-d')));
    }

    public function store(int $scheduleId, UpsertAttendanceRequest $request)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $this->authorize('storeAttendance', $schedule);
        $date = $request->query('date', date('Y-m-d'));
        $rows = $request->validated()['rows'];
        $this->ensureStudentsBelongToSchedule($schedule, $rows);
        $this->service->upsertMany($scheduleId, $date, $rows);

        ActivityLogger::log('attendance.store', $request->user(), Attendance::class, null, [
            'schedule_id' => $scheduleId,
            'date' => $date,
            'rows' => count($rows),
        ]);

        return response()->noContent();
    }

    /**
     * @param  array<int, array{student_id: int, status: string, notes?: string|null}>  $rows
     */
    private function ensureStudentsBelongToSchedule(Schedule $schedule, array $rows): void
    {
        $studentIds = collect($rows)->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return;
        }

        $validCount = Student::query()
            ->withoutGlobalScope(BerdasarkanUnit::class)
            ->whereIn('id', $studentIds)
            ->where('classroom_id', $schedule->classroom_id)
            ->count();

        if ($validCount !== $studentIds->count()) {
            throw ValidationException::withMessages([
                'rows' => 'Terdapat siswa yang bukan anggota kelas jadwal ini.',
            ]);
        }
    }
}
