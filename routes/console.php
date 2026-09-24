<?php

use Illuminate\Support\Facades\Schedule;

// NOTE: queue processing is NOT scheduled here. A persistent worker runs
// under supervisor (program:openkap-worker, `queue:work database`), and
// adding a scheduled worker on top of it would put two workers on a
// single-core box competing for the same jobs.

// Auto-complete stale upload sessions (5 min inactive = auto-complete, 1 hour = cleanup)
Schedule::command('uploads:process-stale --timeout=300 --cleanup=3600')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Clean up stale ClipForge temp files every 10 minutes
Schedule::command('clipforge:cleanup')
    ->everyTenMinutes()
    ->withoutOverlapping();

// Clean up expired MP4 download files hourly
Schedule::command('mp4-downloads:cleanup --max-age=24')
    ->hourly()
    ->withoutOverlapping();
