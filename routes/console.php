<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Football data refresh. Runs only if the server cron calls the scheduler:
 *   * * * * * cd ~/laravel-app && php artisan schedule:run >> /dev/null 2>&1
 *
 * football-data free tier: ~10 requests/minute, 2 per competition per run.
 * The Odds API free tier: 500 credits/month. One run = 1 credit per
 * competition that has matches in the next 3 days (~8–11 a day ≈ 300/month).
 */
Schedule::command('sport:sync-competition --sleep=7')->everyThreeHours()->withoutOverlapping();
Schedule::command('sport:sync-odds --days=3')->dailyAt('07:10')->withoutOverlapping();

// Squads change slowly: weekly, only the first 60 teams per run (~7 min).
Schedule::command('sport:sync-squads --limit=60 --sleep=7')->weeklyOn(1, '04:20')->withoutOverlapping();

// Competitions football-data's free plan lacks (Europa League) from BBC Sport:
// 3 pages per competition (last/this/next month), a few seconds apart.
Schedule::command('sport:sync-bbc --delay=3')->everyThreeHours()->withoutOverlapping();
