<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Schedule;
use App\Services\ActivityLogger;
use App\Services\ScheduleService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(protected ScheduleService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Schedule::class);

        return $this->service->list(
            $request->query('search'),
            $request->query('classroom_id') ? (int) $request->query('classroom_id') : null,
            $request->query('teacher_id') ? (int) $request->query('teacher_id') : null,
            $request->query('day')
        );
    }

    public function store(StoreScheduleRequest $request)
    {
        $this->authorize('create', Schedule::class);
        $schedule = $this->service->create($request->validated());

        ActivityLogger::log('schedule.create', $request->user(), Schedule::class, $schedule->id, [
            'classroom_id' => $schedule->classroom_id,
            'subject_id' => $schedule->subject_id,
            'teacher_id' => $schedule->teacher_id,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
        ]);

        return response()->json($schedule, 201);
    }

    public function show(int $id)
    {
        $s = $this->service->find($id);
        $this->authorize('view', $s);

        return $s;
    }

    public function update(StoreScheduleRequest $request, int $id)
    {
        $s = $this->service->find($id);
        $this->authorize('update', $s);
        $updated = $this->service->update($s, $request->validated());

        ActivityLogger::log('schedule.update', $request->user(), Schedule::class, $updated->id, [
            'changes' => array_keys($request->validated()),
        ]);

        return $updated;
    }

    public function destroy(int $id)
    {
        $s = $this->service->find($id);
        $this->authorize('delete', $s);
        $this->service->delete($s);

        ActivityLogger::log('schedule.delete', request()->user(), Schedule::class, $id);

        return response()->noContent();
    }
}
