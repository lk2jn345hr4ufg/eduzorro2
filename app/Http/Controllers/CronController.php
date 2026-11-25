<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Web "cron" for hosts where the system crontab isn't reliable: an external
 * service (e.g. cron-job.org) opens /cron/auto-sync/{token} every few
 * minutes. The page answers immediately; sport:auto-sync runs AFTER the
 * response is sent and itself keeps the 15-minute gap between competitions.
 *
 * The token is FOOTBALL_CRON_TOKEN in .env; without it the URL is a 404.
 */
class CronController extends Controller
{
    public function autoSync(string $token)
    {
        $expected = (string) config('football.auto_sync.cron_token');

        abort_if($expected === '' || ! hash_equals($expected, $token), 404);

        $lock = Cache::lock(\App\Http\Middleware\TriggerAutoSync::LOCK_KEY, 1800);

        if (! $lock->get()) {
            return response('busy', 200)->header('Cache-Control', 'no-store');
        }

        $owner = $lock->owner();

        app()->terminating(function () use ($owner) {
            ignore_user_abort(true);
            @set_time_limit(0);

            try {
                Artisan::call('sport:auto-sync');
            } catch (\Throwable $e) {
                Log::error('[web-cron] auto-sync failed', ['error' => $e->getMessage()]);
            } finally {
                Cache::restoreLock('web-cron:auto-sync', $owner)->release();
            }
        });

        return response('ok', 200)->header('Cache-Control', 'no-store');
    }
}
