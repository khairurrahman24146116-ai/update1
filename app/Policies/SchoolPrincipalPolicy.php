<?php

namespace App\Policies;

use App\Models\SchoolPrincipal;
use App\Models\User;

class SchoolPrincipalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('content.principal.view');
    }

    public function view(User $user, SchoolPrincipal $principal): bool
    {
        return $user->hasPermission('content.principal.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('content.principal.create');
    }

    public function update(User $user, SchoolPrincipal $principal): bool
    {
        return $user->hasPermission('content.principal.update');
    }

    public function delete(User $user, SchoolPrincipal $principal): bool
    {
        return $user->hasPermission('content.principal.delete');
    }
}
