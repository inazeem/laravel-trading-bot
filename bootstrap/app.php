<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
           // \App\Http\Middleware\HandleInertiaRequests::class,
           // \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Run all active trading bots every 5 minutes
        // $schedule->command('trading:run --all')
        //     ->everyFifteenMinutes()
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/trading-bot-scheduler.log'));

        $schedule->command('futures:run --all')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/trading-bot-scheduler.log'));
        
        // Alternative scheduling options (uncomment the one you prefer):
        
        // Run every minute (for high-frequency trading)
        // $schedule->command('trading:run --all')->everyMinute()->withoutOverlapping();
        
        // Run every 10 minutes (for medium-frequency trading)
        // $schedule->command('trading:run --all')->everyTenMinutes()->withoutOverlapping();
        
        // Run every 15 minutes (for lower-frequency trading)
        // $schedule->command('trading:run --all')->everyFifteenMinutes()->withoutOverlapping();
        
        // Run every hour (for daily trading)
        // $schedule->command('trading:run --all')->hourly()->withoutOverlapping();
        
        // Run at specific times (e.g., every 4 hours)
        // $schedule->command('trading:run --all')->cron('0 */4 * * *')->withoutOverlapping();
        
        // Run only during market hours (9 AM to 5 PM UTC)
        // $schedule->command('trading:run --all')->weekdays()->between('09:00', '17:00')->everyFiveMinutes()->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
