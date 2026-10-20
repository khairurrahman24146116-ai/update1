<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;

class ImportBatchPolicy
{
    public function view(User $user, ImportBatch $batch): bool
    {
        return $this->ownsOrAdmin($user, $batch);
    }

    public function confirm(User $user, ImportBatch $batch): bool
    {
        return $this->ownsOrAdmin($user, $batch);
    }

    public function delete(User $user, ImportBatch $batch): bool
    {
        return $this->ownsOrAdmin($user, $batch);
    }

    private function ownsOrAdmin(User $user, ImportBatch $batch): bool
    {
        if (! $user->hasPermission('students.import')) {
            return false;
        }

        return $user->isAdmin() || (int) $batch->diimport_oleh === (int) $user->id;
    }
}
