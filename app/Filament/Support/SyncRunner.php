<?php

namespace App\Filament\Support;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Runs sport:* sync commands from admin buttons.
 *
 *  - now():        short jobs (1–3 API requests) — runs in the request and
 *                  shows the command output in a notification.
 *  - background(): long jobs (many teams / competitions, throttled to the
 *                  APIs' per-minute limits) — starts AFTER the response is
 *                  sent, so neither the browser nor Cloudflare's 100 s limit
 *                  is involved. Results land in the Sync log on the dashboard.
 *
 * A cache lock per command+parameters stops the same job being started twice.
 */
class SyncRunner
{
    public static function now(string $title, string $command, array $params = []): void
    {
        $lock = Cache::lock(self::key($command, $params), 600);

        if (! $lock->get()) {
            self::busy($title);
            return;
        }

        try {
            @set_time_limit(0);
            $code   = Artisan::call($command, $params);
            $output = trim(Artisan::output());

            Notification::make()
                ->title($title)
                ->body(Str::limit($output, 900) ?: 'Done.')
                ->{$code === 0 ? 'success' : 'danger'}()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Log::error("[admin sync] {$command} failed", ['params' => $params, 'error' => $e->getMessage()]);
            Notification::make()->title($title)->body('Failed: '.$e->getMessage())->danger()->persistent()->send();
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<int, array{0: string, 1: array}>  $runs  one or more [command, params] run in order
     */
    public static function background(string $title, array $runs, int $pauseBetween = 0): void
    {
        $key  = self::key($title, $runs);
        $lock = Cache::lock($key, 3 * 3600);

        if (! $lock->get()) {
            self::busy($title);
            return;
        }

        $owner = $lock->owner();

        app()->terminating(function () use ($runs, $pauseBetween, $key, $owner, $title) {
            ignore_user_abort(true);
            @set_time_limit(0);

            try {
                foreach (array_values($runs) as $i => [$command, $params]) {
                    if ($i > 0 && $pauseBetween > 0) {
                        sleep($pauseBetween);
                    }

                    Artisan::call($command, $params);
                    Log::info("[admin sync] {$title}: {$command}", ['params' => $params, 'output' => Str::limit(Artisan::output(), 2000)]);
                }
            } catch (\Throwable $e) {
                Log::error("[admin sync] {$title} failed", ['error' => $e->getMessage()]);
            } finally {
                Cache::restoreLock($key, $owner)->release();
            }
        });

        Notification::make()
            ->title($title.' — started')
            ->body('Running in the background. Refresh the dashboard in a few minutes: results appear in the Sync log and the dates above.')
            ->info()
            ->persistent()
            ->send();
    }

    protected static function busy(string $title): void
    {
        Notification::make()
            ->title($title)
            ->body('This sync is already running. Wait for it to finish (see the Sync log).')
            ->warning()
            ->send();
    }

    protected static function key(string $name, array $params): string
    {
        return 'admin-sync:'.md5($name.json_encode($params));
    }
}
