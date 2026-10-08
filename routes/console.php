<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Football data refresh. Runs only if the server cron calls the scheduler:
 *   * * * * * cd ~/laravel-app && php artisan schedule:run >> /dev/null 2>&1
 *
 * football-data free tier: ~10 requests/minute, 2 per competition per run.
 * API-Football free tier: 100 requests/day — odds once a day is enough.
 */
Schedule::command('sport:sync-competition --sleep=7')->everyThreeHours()->withoutOverlapping();
Schedule::command('sport:sync-odds --days=2')->dailyAt('07:10')->withoutOverlapping();
