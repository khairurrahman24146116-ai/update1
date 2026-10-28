<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use App\Services\TeacherAssignmentService;

class ScorePolicy
{
    public function __construct(protected TeacherAssignmentService $assignments) {}

    public function viewAny(User $user, ?Classroom $class = null, ?Subject $subj = null): bool
    {
        if (! $user->hasPermission('scores.view') || $class === null || $subj === null) {
            return false;
        }

        if (! $this->assignments->isClassroomEnabled($class)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role === User::ROLE_GURU) {
            return $this->assignments->canTeach($user, $class, $subj->id);
        }

        if ($user->isWaliMurid()) {
            return $class->students()->where('parent_id', $user->id)->exists();
        }

        return $user->canAccessDataUnit($class->unit);
    }

    public function store(User $user, Classroom $class, Subject $subj): bool
    {
        if (! $user->hasPermission('scores.update') || ! $this->assignments->isClassroomEnabled($class)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role === User::ROLE_GURU) {
            return $this->assignments->canTeach($user, $class, $subj->id);
        }

        return $user->canAccessDataUnit($class->unit);
    }
}
