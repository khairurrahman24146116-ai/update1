<?php

namespace App\Policies;

use App\Models\User;

class TeacherAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.teacher.view');
    }

    public function checkIn(User $user): bool
    {
        return $user->hasPermission('attendance.teacher.checkin');
    }

    public function checkOut(User $user): bool
    {
        return $user->hasPermission('attendance.teacher.checkin');
    }
}
