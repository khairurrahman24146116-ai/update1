<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class AttendancePolicy
{
    public function view(User $user, Schedule $schedule): bool
    {
        if (! $user->hasPermission('attendance.view')) {
            return false;
        }

        if ($user->isAdmin() || $user->role !== User::ROLE_GURU) {
            return true;
        }

        return (int) $schedule->teacher_id === (int) $user->id;
    }

    public function store(User $user, Schedule $schedule): bool
    {
        if (! $user->hasPermission('attendance.create')) {
            return false;
        }

        if ($user->isAdmin() || $user->role !== User::ROLE_GURU) {
            return true;
        }

        return (int) $schedule->teacher_id === (int) $user->id;
    }
}
