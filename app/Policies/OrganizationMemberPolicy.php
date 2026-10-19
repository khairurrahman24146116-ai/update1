<?php

namespace App\Policies;

use App\Models\OrganizationMember;
use App\Models\User;

class OrganizationMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('content.structure.view');
    }

    public function view(User $user, OrganizationMember $member): bool
    {
        return $user->hasPermission('content.structure.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('content.structure.create');
    }

    public function update(User $user, OrganizationMember $member): bool
    {
        return $user->hasPermission('content.structure.update');
    }

    public function delete(User $user, OrganizationMember $member): bool
    {
        return $user->hasPermission('content.structure.delete');
    }
}
