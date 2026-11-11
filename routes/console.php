<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('import:prune-staging')->daily()->withoutOverlapping();
Schedule::command('welfare:detect-permit-returns')->dailyAt('07:00')->withoutOverlapping();
