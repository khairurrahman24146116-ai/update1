<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('import:prune-staging {--days=7 : Batas umur batch preview dalam hari}')]
#[Description('Menghapus batch import berstatus preview beserta baris staging yang lebih lama dari batas umur.')]
class PruneImportStaging extends Command
{
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = ImportBatch::query()
            ->where('status', ImportBatch::STATUS_PREVIEW)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Menghapus {$deleted} batch import preview lebih lama dari {$days} hari.");

        return self::SUCCESS;
    }
}
