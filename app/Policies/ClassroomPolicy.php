<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('classrooms.view');
    }

    public function view(User $user, Classroom $c): bool
    {
        if (! $user->hasPermission('classrooms.view')) {
            return false;
        }

        return $this->withinUnit($user, $c->unit);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('classrooms.create');
    }

    public function update(User $user, Classroom $c): bool
    {
        return $user->hasPermission('classrooms.update') && $this->withinUnit($user, $c->unit);
    }

    public function delete(User $user, Classroom $c): bool
    {
        return $user->hasPermission('classrooms.delete') && $this->withinUnit($user, $c->unit);
    }

    /**
     * Admin lintas unit. Akun yang memiliki unit hanya boleh menyentuh unitnya.
     * Akun tanpa unit (mis. guru) tidak dibatasi oleh policy.
     */
    private function withinUnit(User $user, string $unit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $restricted = $user->financeUnit() ?? $user->unit;

        return $restricted === null || $restricted === $unit;
    }
}
