<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks (cron: * * * * * php artisan schedule:run)
|--------------------------------------------------------------------------
*/

Schedule::command('cv:prune-files')->hourly()->withoutOverlapping();
Schedule::command('cv:expire-subscriptions')->hourly()->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->daily();

// Shared hosting (cPanel): process the queue from cron instead of a supervised worker.
if (config('cv.queue_via_scheduler')) {
    Schedule::command('queue:work --queue=exports,imports,default --stop-when-empty --max-time=50 --tries=2 --timeout=45')
        ->everyMinute()
        ->withoutOverlapping(2);
}
