<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherAttendance;
use App\Services\ActivityLogger;
use App\Services\TeacherAttendanceService;
use Illuminate\Http\Request;

class TeacherAttendanceController extends Controller
{
    public function __construct(protected TeacherAttendanceService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', TeacherAttendance::class);

        return $this->service->list($request->query('date'), $request->user()->isAdmin() ? $request->query('teacher_id') : $request->user()->id);
    }

    public function checkIn(Request $request)
    {
        $this->authorize('checkIn', TeacherAttendance::class);
        $record = $this->service->checkIn($request->user()->id, date('Y-m-d'), date('H:i:s'));

        ActivityLogger::log('attendance.teacher.checkin', $request->user(), TeacherAttendance::class, $record->id, [
            'date' => $record->date,
            'check_in' => $record->check_in,
        ]);

        return response()->json($record, 201);
    }

    public function checkOut(Request $request)
    {
        $this->authorize('checkOut', TeacherAttendance::class);
        $record = $this->service->checkOut($request->user()->id, date('Y-m-d'), date('H:i:s'));

        ActivityLogger::log('attendance.teacher.checkout', $request->user(), TeacherAttendance::class, $record->id, [
            'date' => $record->date,
            'check_out' => $record->check_out,
        ]);

        return $record;
    }
}
