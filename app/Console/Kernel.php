<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run futures trading bots every minute
        $schedule->command('futures:run')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Run regular trading bots every minute
        $schedule->command('trading:run --all')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Log scheduler activity
        $schedule->call(function () {
            \Log::info('Scheduler running - ' . now()->format('Y-m-d H:i:s'));
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

