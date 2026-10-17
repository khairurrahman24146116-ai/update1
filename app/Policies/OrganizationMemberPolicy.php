<?php

namespace App\Policies;

use App\Models\OrganizationMember;
use App\Models\User;

class OrganizationMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(User $user, OrganizationMember $member): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, OrganizationMember $member): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, OrganizationMember $member): bool
    {
        return $user->role === 'admin';
    }
}
