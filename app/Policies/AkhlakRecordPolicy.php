<?php

namespace App\Policies;

use App\Models\AkhlakRecord;
use App\Models\User;
use App\Services\StudentWelfareService;

class AkhlakRecordPolicy
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

    public function view(User $user, AkhlakRecord $record): bool
    {
        return $user->canManageAkhlak()
            && $record->student !== null
            && $this->welfare->canViewStudent($user, $record->student);
    }

    public function create(User $user): bool
    {
        return $user->canManageAkhlak();
    }

    public function update(User $user, AkhlakRecord $record): bool
    {
        return $user->canManageAkhlak()
            && $record->student !== null
            && $this->welfare->canEditAkhlak($user, $record);
    }

    public function cancel(User $user, AkhlakRecord $record): bool
    {
        return $this->update($user, $record);
    }

    public function delete(User $user, AkhlakRecord $record): bool
    {
        return false;
    }
}
