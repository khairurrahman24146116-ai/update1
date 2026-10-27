<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('teachers.view');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        if (! $user->hasPermission('teachers.view')) {
            return false;
        }

        if ($user->isAdmin() || $user->isOperator()) {
            return true;
        }

        if ($user->isGuru()) {
            return (int) $teacher->user_id === (int) $user->id;
        }

        if ($user->isWaliMurid()) {
            return Teacher::query()->whereKey($teacher->getKey())->exists();
        }

        return $this->withinUnit($user, $teacher);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('teachers.create') && ($user->isAdmin() || $user->isOperator());
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->hasPermission('teachers.update')
            && ($user->isAdmin() || $user->isOperator())
            && $this->withinUnit($user, $teacher);
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->hasPermission('teachers.delete')
            && ($user->isAdmin() || $user->isOperator())
            && $this->withinUnit($user, $teacher);
    }

    public function manageUnits(User $user, Teacher $teacher): bool
    {
        return $user->isAdminUtama() && $user->hasPermission('teachers.update');
    }

    public function manageAssignments(User $user, Teacher $teacher): bool
    {
        if (! $user->hasPermission('teachers.assignments')) {
            return false;
        }

        return $user->isAdmin() || ($user->isKepalaSekolah() && $this->withinUnit($user, $teacher));
    }

    public function manageAttendance(User $user, Teacher $teacher): bool
    {
        if (! $user->hasPermission('attendance.teacher.manage')) {
            return false;
        }

        return $user->isAdmin() || ($user->isKepalaSekolah() && $this->withinUnit($user, $teacher));
    }

    private function withinUnit(User $user, Teacher $teacher): bool
    {
        $codes = $teacher->units()->pluck('units.kode')
            ->filter()
            ->map(fn (string $code): string => strtolower($code));

        foreach ($codes as $code) {
            if ($user->canAccessDataUnit($code)) {
                return true;
            }
        }

        return false;
    }
}
