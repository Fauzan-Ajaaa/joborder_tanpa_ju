<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SyncCoaData::class,
        \App\Console\Commands\ResetAllTransactions::class,
        \App\Console\Commands\CleanAllDataCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Posting jurnal penyesuaian penyusutan otomatis setiap tanggal 1 jam 00:05
        $schedule->command('depreciation:post')->monthlyOn(1, '00:05');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
