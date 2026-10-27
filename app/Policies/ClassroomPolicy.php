<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isOperator() && $user->hasPermission('classrooms.view');
    }

    public function view(User $user, Classroom $classroom): bool
    {
        if ($user->isOperator() || ! $user->hasPermission('classrooms.view')) {
            return false;
        }

        if ($user->isGuru()) {
            return $user->teachesClassroom($classroom->id);
        }

        return $user->canAccessDataUnit($classroom->unit);
    }

    public function create(User $user): bool
    {
        return ! $user->isOperator() && $user->hasPermission('classrooms.create');
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return ! $user->isOperator()
            && $user->hasPermission('classrooms.update')
            && $this->withinAccess($user, $classroom);
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->isAdminUtama()
            && $user->hasPermission('classrooms.delete')
            && $this->withinAccess($user, $classroom);
    }

    public function assignWaliKelas(User $user, Classroom $classroom): bool
    {
        return ! $user->isOperator()
            && $user->hasPermission('classrooms.update')
            && $this->withinAccess($user, $classroom);
    }

    private function withinAccess(User $user, Classroom $classroom): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isGuru()) {
            return $user->teachesClassroom($classroom->id);
        }

        return $user->canAccessDataUnit($classroom->unit);
    }
}
