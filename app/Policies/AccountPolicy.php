<?php

namespace App\Policies;

use App\Models\User;

class AccountPolicy
{
    private const OPERATOR_MANAGEABLE_ROLES = [
        User::ROLE_GURU,
        User::ROLE_PENDING,
    ];

    public function viewAny(User $actor): bool
    {
        return $actor->isAccountManager() && $actor->hasPermission('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->hasPermission('users.view') && $this->canSeeTarget($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $actor->isAccountManager() && $actor->hasPermission('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->hasPermission('users.update') && $this->canEditTarget($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return false;
    }

    private function canSeeTarget(User $actor, User $target): bool
    {
        if ($actor->isAdminUtama()) {
            return true;
        }

        return $this->isOperatorManageable($actor, $target);
    }

    private function canEditTarget(User $actor, User $target): bool
    {
        if ((int) $target->id === (int) $actor->id) {
            return false;
        }

        if ($target->isAdminUtama()) {
            return false;
        }

        if ($actor->isAdminUtama()) {
            return true;
        }

        return $this->isOperatorManageable($actor, $target);
    }

    private function isOperatorManageable(User $actor, User $target): bool
    {
        return $actor->isOperator()
            && (int) $target->id !== (int) $actor->id
            && in_array($target->role, self::OPERATOR_MANAGEABLE_ROLES, true);
    }
}
