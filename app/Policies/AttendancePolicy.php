<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;
use App\Services\TeacherAssignmentService;

class AttendancePolicy
{
    public function __construct(protected TeacherAssignmentService $assignments) {}

    public function view(User $user, Schedule $schedule): bool
    {
        if (! $user->hasPermission('attendance.view') || ! $this->scheduleIsEnabled($schedule)) {
            return false;
        }

        if ($user->isAdmin() || $user->role !== User::ROLE_GURU) {
            return true;
        }

        return $this->assignments->canAccessSchedule($user, $schedule);
    }

    public function store(User $user, Schedule $schedule): bool
    {
        if (! $user->hasPermission('attendance.create') || ! $this->scheduleIsEnabled($schedule)) {
            return false;
        }

        if ($user->isAdmin() || $user->role !== User::ROLE_GURU) {
            return true;
        }

        return $this->assignments->canAccessSchedule($user, $schedule);
    }

    private function scheduleIsEnabled(Schedule $schedule): bool
    {
        $schedule->loadMissing('classroom');

        return $schedule->classroom !== null
            && $this->assignments->isClassroomEnabled($schedule->classroom);
    }
}
