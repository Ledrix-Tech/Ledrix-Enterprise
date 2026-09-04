<?php

use Illuminate\Support\Facades\Schedule;

// Ledrix scheduler — register ONE cron job on the server.
// VPS crontab (every minute): * * * * * ... artisan schedule:run
// cPanel / Namecheap: do not use every-minute; they reject Minute=*
// Use every 5 minutes: Minute=*/5  Hour=*  Day=*  Month=*  Weekday=*
// See scripts/cron.example

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

Schedule::command('tenants:process-jazzcash-renewals')
    ->dailyAt('02:00');

Schedule::command('queue:prune-failed', ['--hours' => 168])
    ->weeklyOn(0, '03:30');

Schedule::command('tenants:purge-data-exports')
    ->dailyAt('04:00');

Schedule::command('tenants:process-storage-alerts')
    ->dailyAt('05:00');
