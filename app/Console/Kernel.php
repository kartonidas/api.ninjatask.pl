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
        $schedule->command('auth:clear-resets')->everyFifteenMinutes();
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
        $schedule->command('app:clear-invitations-token')->daily();
        $schedule->command('app:clear-registers-token')->daily();
        $schedule->command('app:clear-unused-access-token')->daily();
        $schedule->command('app:remove-deleted-tasks')->daily();
        $schedule->command('app:expire-subscriptions')->everyFiveMinutes();
        $schedule->command('app:expiration-subscription')->everyFifteenMinutes();
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
