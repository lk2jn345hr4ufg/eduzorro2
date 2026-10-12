<?php

namespace App\Filament\Support;

use Illuminate\Support\Carbon;

/**
 * How old each kind of data may get before the dashboard flags it.
 * [fresh until, stale after] in hours: green below the first, amber
 * between, red above the second (or never synced).
 */
class Freshness
{
    public const LIMITS = [
        'matches'   => [12, 72],
        'odds'      => [36, 96],
        'teams'     => [24 * 30, 24 * 90],
        'squads'    => [24 * 14, 24 * 45],
        'transfers' => [24 * 14, 24 * 45],
    ];

    public static function color(string $type, $date): string
    {
        if (! $date) {
            return 'danger';
        }

        [$fresh, $stale] = self::LIMITS[$type] ?? [24, 72];
        $hours = Carbon::parse($date)->diffInHours(now());

        return match (true) {
            $hours <= $fresh => 'success',
            $hours <= $stale => 'warning',
            default          => 'danger',
        };
    }
}
