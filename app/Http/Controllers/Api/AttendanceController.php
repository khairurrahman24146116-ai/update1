<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertAttendanceRequest;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Services\ActivityLogger;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

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
        $this->service->upsertMany($scheduleId, $date, $rows);

        ActivityLogger::log('attendance.store', $request->user(), Attendance::class, null, [
            'schedule_id' => $scheduleId,
            'date' => $date,
            'rows' => count($rows),
        ]);

        return response()->noContent();
    }
}
