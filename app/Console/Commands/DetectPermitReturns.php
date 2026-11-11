<?php

namespace App\Console\Commands;

use App\Services\PermitReturnMonitor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('welfare:detect-permit-returns')]
#[Description('Membuat pengingat dan menandai izin santri yang terlambat kembali.')]
class DetectPermitReturns extends Command
{
    public function __construct(protected PermitReturnMonitor $monitor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $students = $this->monitor->syncAll();

        $this->info("Memproses {$students} santri dengan izin berakhir atau terlambat kembali.");

        return self::SUCCESS;
    }
}
