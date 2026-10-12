<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One sync run for one scope (see the admin dashboard → Data freshness). */
class SyncLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'items'       => 'integer',
    ];

    public const TYPES = [
        'teams'     => 'Teams import',
        'matches'   => 'Matches & tables',
        'squads'    => 'Squads',
        'transfers' => 'Transfers',
        'odds'      => 'Odds',
    ];

    /** Record a finished run. Never lets logging break a sync. */
    public static function record(string $type, ?string $scope, string $status, int $items = 0, ?string $message = null, $startedAt = null): void
    {
        try {
            static::create([
                'type'        => $type,
                'scope'       => $scope ? mb_substr($scope, 0, 80) : null,
                'status'      => $status,
                'items'       => max(0, $items),
                'message'     => $message ? mb_substr($message, 0, 500) : null,
                'started_at'  => $startedAt ?: now(),
                'finished_at' => now(),
            ]);
        } catch (\Throwable) {
            // sync_logs missing (not migrated yet) — ignore.
        }
    }
}
