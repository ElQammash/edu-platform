<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Production runs on shared hosting (Hostinger) with a single cron entry and
| no long-running processes:
|
|   * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
|
| So the queue is processed from here: every minute a worker drains the
| queue and exits (--stop-when-empty), capped below a minute (--max-time) so
| runs never pile up, and withoutOverlapping() keeps a single worker even if
| a run is slow. Tasks run in the order below, so reminders queued in a run
| are sent by the worker in that same run.
|
*/

Schedule::command('lessons:send-live-reminders')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->description('Queue 24h and 1h live lesson reminders');

Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3 --backoff=30')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->description('Process queued notifications and emails');

Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->description('Forget failed jobs older than a week');
