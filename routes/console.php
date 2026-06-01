<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('truck-owners:reset-declarations --apply --no-interaction')
    ->yearlyOn(4, 1, '00:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Reset all truck owner declarations to "not given" and recalculate TDS at the start of financial year');

Schedule::command('database:backup-to-s3 --no-interaction')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Backup database to S3 and prune backups older than 24 hours');

// Schedule::command('invoice:send-due-reminders --days=3,1 --due-days=7 --no-interaction')
//     ->dailyAt('08:00')
//     ->timezone('Asia/Kolkata')
//     ->withoutOverlapping()
//     ->onOneServer()
//     ->description('Send invoice due reminders (3d, 1d before due date) to customers');

// Schedule::command('eway:send-expiry-reminders --days=1 --no-interaction')
//     ->dailyAt('08:10')
//     ->timezone('Asia/Kolkata')
//     ->withoutOverlapping()
//     ->onOneServer()
//     ->description('Send E-Way expiry reminders to office roles');

// Schedule::command('company:send-plan-lifecycle-reminders --expiring-days=30,15,10 --no-interaction')
//     ->dailyAt('08:20')
//     ->timezone('Asia/Kolkata')
//     ->withoutOverlapping()
//     ->onOneServer()
//     ->description('Send company plan expiring/expired lifecycle reminders');

// Schedule::command('company:send-credit-low-reminders --thresholds=100,50,25 --no-interaction')
//     ->dailyAt('08:30')
//     ->timezone('Asia/Kolkata')
//     ->withoutOverlapping()
//     ->onOneServer()
//     ->description('Send low LR credit reminders to company owners and admins');
