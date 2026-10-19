<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\TeacherSubject;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        if (! $user->hasPermission('students.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isFinanceRole()) {
            return $student->unit() === $user->financeUnit();
        }

        if ($user->role === User::ROLE_WALI_MURID) {
            return (int) $student->parent_id === (int) $user->id;
        }

        if ($user->role === User::ROLE_GURU) {
            return TeacherSubject::where('teacher_id', $user->id)
                ->where('classroom_id', $student->classroom_id)
                ->exists();
        }

        $unit = $user->unit;

        return $unit === null || $student->unit() === $unit;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('students.create');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasPermission('students.update') && $this->withinUnit($user, $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasPermission('students.delete') && $this->withinUnit($user, $student);
    }

    private function withinUnit(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $unit = $user->financeUnit() ?? $user->unit;

        return $unit === null || $student->unit() === $unit;
    }
}
