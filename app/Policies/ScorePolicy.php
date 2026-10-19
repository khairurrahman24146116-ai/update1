<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Models\User;

class ScorePolicy
{
    public function viewAny(User $user, ?Classroom $class = null, ?Subject $subj = null): bool
    {
        if (! $user->hasPermission('scores.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role === User::ROLE_GURU) {
            return TeacherSubject::where('teacher_id', $user->id)->exists()
                || Schedule::where('teacher_id', $user->id)->exists();
        }

        return true;
    }

    public function store(User $user, Classroom $class, Subject $subj): bool
    {
        if (! $user->hasPermission('scores.update')) {
            return false;
        }

        if ($user->isAdmin() || $user->role !== User::ROLE_GURU) {
            return true;
        }

        return TeacherSubject::where('teacher_id', $user->id)
            ->where('classroom_id', $class->id)
            ->where('subject_id', $subj->id)
            ->exists()
            || Schedule::where('teacher_id', $user->id)
                ->where('classroom_id', $class->id)
                ->where('subject_id', $subj->id)
                ->exists();
    }
}
