<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('schedules.view');
    }

    public function view(User $user, Schedule $s): bool
    {
        return $user->hasPermission('schedules.view');
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
