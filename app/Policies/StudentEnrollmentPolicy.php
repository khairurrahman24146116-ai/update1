<?php

namespace App\Policies;

use App\Models\StudentEnrollment;
use App\Models\User;

class StudentEnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isOperator() && $user->hasPermission('students.view');
    }

    public function view(User $user, StudentEnrollment $enrollment): bool
    {
        if ($user->isOperator() || ! $user->hasPermission('students.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGuru()) {
            return $user->teachesClassroom($enrollment->class_id);
        }

        return $user->canAccessDataUnit($enrollment->unit?->kode);
    }

    public function create(User $user): bool
    {
        return $user->isAdminUtama() || ($user->isKepalaSekolah() && $user->hasPermission('students.create'));
    }

    public function update(User $user, StudentEnrollment $enrollment): bool
    {
        return $user->isAdminUtama()
            || ($user->isKepalaSekolah()
                && $user->hasPermission('students.update')
                && $user->canAccessDataUnit($enrollment->unit?->kode));
    }

    public function delete(User $user, StudentEnrollment $enrollment): bool
    {
        return $user->isAdminUtama();
    }
}
