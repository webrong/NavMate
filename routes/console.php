<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Clean up click_logs older than 90 days (see ClickLog::prunable())
Schedule::command('model:prune', ['--model' => \App\Models\ClickLog::class])
    ->daily()
    ->at('03:00')
    ->withoutOverlapping();

