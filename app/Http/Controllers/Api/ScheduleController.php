<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Scopes\BerdasarkanUnit;
use App\Models\Subject;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function __construct(protected ScheduleService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Schedule::class);

        /** @var User $user */
        $user = $request->user();
        $teacherId = $request->query('teacher_id') ? (int) $request->query('teacher_id') : null;

        if ($user->isGuru()) {
            $teacherId = $user->id;
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return $this->service->list(
            $request->query('search'),
            $request->query('classroom_id') ? (int) $request->query('classroom_id') : null,
            $teacherId,
            $request->query('day'),
            $perPage,
        );
    }

    public function store(StoreScheduleRequest $request)
    {
        $this->authorize('create', Schedule::class);
        $data = $request->validated();
        $classroom = $this->ensureEnabledClassroom((int) $data['classroom_id']);
        $this->ensureSubjectAvailable((int) $data['subject_id'], $classroom);
        $schedule = $this->service->create($data);

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
        $data = $request->validated();
        $classroom = $this->ensureEnabledClassroom((int) $data['classroom_id']);
        $this->ensureSubjectAvailable((int) $data['subject_id'], $classroom);
        $updated = $this->service->update($s, $data);

        ActivityLogger::log('schedule.update', $request->user(), Schedule::class, $updated->id, [
            'changes' => array_keys($data),
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

    private function ensureEnabledClassroom(int $classroomId): Classroom
    {
        $classroom = Classroom::query()
            ->withoutGlobalScope(BerdasarkanUnit::class)
            ->find($classroomId);

        if ($classroom === null || ! Unit::isClassroomEnabled($classroom)) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Kelas tidak aktif.',
            ]);
        }

        return $classroom;
    }

    private function ensureSubjectAvailable(int $subjectId, Classroom $classroom): void
    {
        $subject = Subject::query()->with('scopes')->find($subjectId);

        if ($subject === null || ! $subject->is_active || ! $subject->isAvailableFor($classroom)) {
            throw ValidationException::withMessages([
                'subject_id' => 'Mapel tidak aktif atau tidak sesuai unit/jenjang kelas.',
            ]);
        }
    }
}
