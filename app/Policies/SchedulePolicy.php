<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;
use App\Services\TeacherAssignmentService;

class SchedulePolicy
{
    public function __construct(protected TeacherAssignmentService $assignments) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('schedules.view');
    }

    public function view(User $user, Schedule $s): bool
    {
        if (! $user->hasPermission('schedules.view')) {
            return false;
        }

        $s->loadMissing('classroom');

        if ($s->classroom === null || ! $this->assignments->isClassroomEnabled($s->classroom)) {
            return false;
        }

        if ($user->role === User::ROLE_GURU) {
            return $this->assignments->canAccessSchedule($user, $s);
        }

        return true;
    }

    /**
     * Otorisasi baca lembar absensi didelegasikan ke AttendancePolicy.
     */
    public function viewAttendance(User $user, Schedule $schedule): bool
    {
        return app(AttendancePolicy::class)->view($user, $schedule);
    }

    /**
     * Otorisasi simpan absensi didelegasikan ke AttendancePolicy.
     */
    public function storeAttendance(User $user, Schedule $schedule): bool
    {
        return app(AttendancePolicy::class)->store($user, $schedule);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('schedules.create');
    }

    public function update(User $user, Schedule $s): bool
    {
        return $user->hasPermission('schedules.update');
    }

    public function delete(User $user, Schedule $s): bool
    {
        return $user->hasPermission('schedules.delete');
    }
}
