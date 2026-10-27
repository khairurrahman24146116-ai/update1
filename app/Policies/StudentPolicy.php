<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isOperator() && $user->hasPermission('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->isOperator() || ! $user->hasPermission('students.view')) {
            return false;
        }

        return $this->withinAccess($user, $student);
    }

    public function create(User $user): bool
    {
        return ! $user->isOperator() && $user->hasPermission('students.create');
    }

    public function import(User $user): bool
    {
        return $user->isAdminUtama() && $user->hasPermission('students.import');
    }

    public function update(User $user, Student $student): bool
    {
        return ! $user->isOperator()
            && $user->hasPermission('students.update')
            && $this->withinAccess($user, $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->isAdminUtama()
            && $user->hasPermission('students.delete')
            && $this->withinAccess($user, $student);
    }

    public function promote(User $user): bool
    {
        return $user->isAdminUtama();
    }

    public function placeManually(User $user): bool
    {
        return $user->isAdminUtama();
    }

    private function withinAccess(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isWaliMurid()) {
            return (int) $student->parent_id === (int) $user->id;
        }

        if ($user->isGuru()) {
            return $user->teachesClassroom($student->classroom_id);
        }

        return $user->canAccessDataUnit($student->unit());
    }
}
