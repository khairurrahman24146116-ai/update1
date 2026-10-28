<?php

namespace App\Policies;

use App\Models\StudentPermit;
use App\Models\User;
use App\Services\StudentWelfareService;

class StudentPermitPolicy
{
    public function __construct(protected StudentWelfareService $welfare) {}

    public function viewAny(User $user): bool
    {
        return $user->canManageAkhlak();
    }

    public function viewAnyForWali(User $user): bool
    {
        return $user->isWaliMurid();
    }

    public function view(User $user, StudentPermit $permit): bool
    {
        return $user->canManageAkhlak()
            && $permit->student !== null
            && $this->welfare->canViewStudent($user, $permit->student);
    }

    public function create(User $user): bool
    {
        return $user->canManageAkhlak();
    }

    public function approve(User $user, StudentPermit $permit): bool
    {
        return $user->canManageAkhlak()
            && $permit->student !== null
            && $this->welfare->canViewStudent($user, $permit->student);
    }

    public function reject(User $user, StudentPermit $permit): bool
    {
        return $this->approve($user, $permit);
    }

    public function cancel(User $user, StudentPermit $permit): bool
    {
        return $user->canManageAkhlak()
            && $permit->student !== null
            && $this->welfare->canViewStudent($user, $permit->student);
    }

    public function delete(User $user, StudentPermit $permit): bool
    {
        return false;
    }
}
