<?php

namespace App\Policies;

use App\Models\User;

class KeuanganLpiPolicy
{
    public function view(User $user): bool
    {
        return $user->canAccessLpiFinance();
    }

    public function manage(User $user): bool
    {
        return $user->canAccessLpiFinance();
    }
}
