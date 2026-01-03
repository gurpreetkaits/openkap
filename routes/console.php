<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process queue jobs every minute
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Auto-complete stale upload sessions (5 min inactive = auto-complete, 1 hour = cleanup)
Schedule::command('uploads:process-stale --timeout=300 --cleanup=3600')
    ->everyFiveMinutes()
    ->withoutOverlapping();
