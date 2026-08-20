<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('embeddings:build')->dailyAt('03:00');
        $schedule->command('recommendations:build')->dailyAt('04:00');
        $schedule->command('analytics:trends')->hourly();
        $schedule->command('vendors:score')->dailyAt('05:00');
        $schedule->command('ota:release --auto')->dailyAt('02:00');
        $schedule->command('agentic:loop --background')->everyFiveMinutes();
        $schedule->command('queue:restart')->hourly();
        $schedule->command('media:transcode-webm')->hourly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}