<?php

namespace App\Policies;

use App\Models\TeacherSubject;
use App\Models\User;

class TeacherSubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('subjects.view');
    }

    public function view(User $user, TeacherSubject $ts): bool
    {
        return $user->hasPermission('subjects.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('subjects.update');
    }

    public function delete(User $user, TeacherSubject $ts): bool
    {
        return $user->hasPermission('subjects.update');
    }
}
