<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use App\Services\ActivityLogService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('activity-log:prune {--months= : Jumlah bulan yang disimpan} {--force : Jalankan tanpa konfirmasi}')]
#[Description('Menghapus log aktivitas yang lebih lama dari masa simpan.')]
class PruneActivityLogs extends Command
{
    public function handle(ActivityLogService $service): int
    {
        $months = $service->clampMonths((int) ($this->option('months') ?: $service->retentionMonths()));
        $preview = $service->retentionPreview($months);

        if ($preview['count'] === 0) {
            $this->info("Tidak ada log lebih lama dari {$preview['cutoff']}.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Hapus {$preview['count']} log sebelum {$preview['cutoff']}?")) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        $deleted = $service->prune($months);

        ActivityLogger::log('activity_log.prune', null, null, null, [
            'months' => $months,
            'cutoff' => $preview['cutoff'],
            'deleted' => $deleted,
            'via' => 'command',
        ]);

        $this->info("Menghapus {$deleted} log sebelum {$preview['cutoff']}.");

        return self::SUCCESS;
    }
}
