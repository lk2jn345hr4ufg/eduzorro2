<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Football data refresh. Runs only if the server cron calls the scheduler:
 *   * * * * * cd ~/laravel-app && php artisan schedule:run >> /dev/null 2>&1
 *
 * One rotating job replaces the per-source schedules: every 15 minutes it
 * refreshes ONE competition (matches & table, top scorers, odds, and weekly
 * squads & transfers) — the one updated longest ago — so each competition is
 * synced once a day, one at a time, 15 minutes apart. See sport:auto-sync and
 * Settings → Auto sync.
 */
//
// Called every minute; the command itself waits until 15 minutes have passed
// since the previous competition, so it works even when the host's cron
// fires irregularly or every few minutes.
Schedule::command('sport:auto-sync')->everyMinute()->withoutOverlapping(60);
