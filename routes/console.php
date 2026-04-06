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

Schedule::command('backup:email-daily-database --no-interaction')
    ->dailyAt('01:00')
    ->timezone((string) config('database_backup.schedule_timezone'))
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Email the latest daily database backup to configured recipients');
