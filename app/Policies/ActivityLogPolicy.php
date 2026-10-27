<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $this->allowed($user);
    }

    public function export(User $user): bool
    {
        return $this->allowed($user);
    }

    public function print(User $user): bool
    {
        return $this->allowed($user);
    }

    private function allowed(User $user): bool
    {
        return $user->isAdminUtama() && $user->hasPermission('logs.view');
    }
}
