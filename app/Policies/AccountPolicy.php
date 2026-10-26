<?php

namespace App\Policies;

use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAccountManager() && $actor->hasPermission('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        if (! $actor->hasPermission('users.view')) {
            return false;
        }

        if ($actor->isAdmin()) {
            return true;
        }

        return ! $target->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAccountManager() && $actor->hasPermission('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        if (! $actor->hasPermission('users.update')) {
            return false;
        }

        if ($target->is_protected && ! $actor->isProtectedAdmin()) {
            return false;
        }

        if ($actor->isAdmin()) {
            return true;
        }

        if ($actor->isOperator()) {
            return (int) $target->id !== (int) $actor->id
                && ! $target->isOperator()
                && ! $target->isAdmin();
        }

        return false;
    }

    public function delete(User $actor, User $target): bool
    {
        if (! $actor->hasPermission('users.delete')) {
            return false;
        }

        if ($target->is_protected) {
            return false;
        }

        if ($actor->isAdmin()) {
            return (int) $target->id !== (int) $actor->id;
        }

        if ($actor->isOperator()) {
            return (int) $target->id !== (int) $actor->id
                && ! $target->isOperator()
                && ! $target->isAdmin();
        }

        return false;
    }
}
