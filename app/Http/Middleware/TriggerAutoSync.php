<?php

namespace App\Http\Middleware;

use App\Models\Competition;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * "Visitor cron": runs the rotating data sync after a normal page view has
 * been answered, so no system cron or external pinger is needed (the host
 * resets crontabs and Cloudflare blocks cron services).
 *
 * Cost per request: one cache read. Only when the next sync is due does one
 * request — after its response is sent — take a lock and run
 * sport:auto-sync, which itself keeps the 15-minute gap and the daily cycle.
 */
class TriggerAutoSync
{
    public const NEXT_KEY = 'auto-sync:next-check-at';
    public const LOCK_KEY = 'web-cron:auto-sync';   // shared with CronController

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        if (! config('football.auto_sync.enabled') || ! config('football.auto_sync.on_visits', true)) {
            return;
        }

        // Only ordinary GET page views; never admin, Livewire, assets, cron.
        if (! $request->isMethod('GET') || $request->is('admin*', 'livewire*', 'filament*', 'cron/*', 'up', 'storage/*', 'build/*')) {
            return;
        }

        try {
            $nextAt = Cache::get(self::NEXT_KEY);

            if ($nextAt && now()->timestamp < (int) $nextAt) {
                return; // cheap path for almost every request
            }

            $lock = Cache::lock(self::LOCK_KEY, 1800);

            if (! $lock->get()) {
                return; // another request is already syncing
            }

            try {
                ignore_user_abort(true);
                @set_time_limit(0);

                Artisan::call('sport:auto-sync');
            } finally {
                $lock->release();
            }

            Cache::put(self::NEXT_KEY, self::nextCheckAt(), now()->addDay());
        } catch (\Throwable $e) {
            // Never let the sync affect the site; try again in 5 minutes.
            Cache::put(self::NEXT_KEY, now()->addMinutes(5)->timestamp, now()->addHour());
            Log::warning('[visit-cron] auto-sync failed', ['error' => $e->getMessage()]);
        }
    }

    /** When it is worth checking again: 15 min after the last run, or in 5 min. */
    public static function nextCheckAt(): int
    {
        $gap  = max(1, (int) config('football.auto_sync.gap_minutes', 15));
        $last = Competition::max('auto_synced_at');

        $at = $last ? Carbon::parse($last)->addMinutes($gap) : now();

        return max($at->timestamp, now()->addMinutes(5)->timestamp);
    }
}
