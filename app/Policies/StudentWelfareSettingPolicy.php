<?php

namespace App\Policies;

use App\Models\User;

class StudentWelfareSettingPolicy
{
    public function view(User $user, mixed $model = null): bool
    {
        return $user->canManageAkhlak();
    }

    public function update(User $user, mixed $model = null): bool
    {
        return $user->canManageAkhlak();
    }
}
