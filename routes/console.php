<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote');


// ── Weekly reviewer reminder ───────────────────────────────────────────────
// Every Friday at 6:00 PM Nigeria time (WAT = UTC+1).
// Emails all active reviewers who have pending/under-review submissions.
Schedule::command('reviewer:send-weekly-reminders')
    ->weeklyOn(5, '18:00')          // 5 = Friday
    ->timezone('Africa/Lagos')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
