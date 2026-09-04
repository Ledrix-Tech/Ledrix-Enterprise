<?php

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;

// Ledrix scheduler — register ONE cron job on the server.
// VPS crontab (every minute): * * * * * ... artisan schedule:run
// cPanel / Namecheap: do not use every-minute; they reject Minute=*
// Use every 5 minutes: Minute=*/5  Hour=*  Day=*  Month=*  Weekday=*
// See scripts/cron.example

Event::listen(CommandStarting::class, function (CommandStarting $event) {
    if (! in_array($event->command, ['schedule:run', 'schedule:work'], true)) {
        return;
    }

    $path = storage_path('logs/scheduler.log');
    $dir = dirname($path);
    if (! is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    @file_put_contents(
        $path,
        '['.now()->toDateTimeString().'] '.$event->command.PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
});

$queueConnection = config('queue.default', 'database');

// Drain queued mail/jobs. Sized for a 5-minute cron (Namecheap minimum).
Schedule::command('queue:work', [
    $queueConnection,
    '--stop-when-empty',
    '--max-time=270',
    '--tries=3',
    '--sleep=3',
])
    ->everyFiveMinutes()
    ->withoutOverlapping(300)
    ->name('process-queue');

Schedule::command('predict:churn')
    ->dailyAt('00:00');

Schedule::command('leads:auto-reply')
    ->hourly();

Schedule::command('tickets:deadline-check')
    ->everyFifteenMinutes();

Schedule::command('tenants:process-trials')
    ->dailyAt('01:00');

Schedule::command('tenants:process-subscriptions')
    ->dailyAt('01:30');

Schedule::command('queue:prune-failed', ['--hours' => 168])
    ->weeklyOn(0, '03:30');

Schedule::command('tenants:purge-data-exports')
    ->dailyAt('04:00');

Schedule::command('tenants:process-storage-alerts')
    ->dailyAt('05:00');
