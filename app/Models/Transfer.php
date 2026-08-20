<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    /**
     * Re-shape DB rows into the grouped API-Football payload the view expects:
     * [ ['player' => [...], 'transfers' => [ [...], ... ]], ... ]
     */
    public static function toApiShapeCollection($rows): array
    {
        return $rows->groupBy('player_name')->map(fn ($group, $player) => [
            'player'    => ['name' => $player],
            'transfers' => $group->map(fn ($r) => [
                'date'  => optional($r->transfer_date)->toDateString(),
                'type'  => $r->type,
                'teams' => [
                    'in'  => ['id' => $r->in_api_id,  'name' => $r->in_name,  'logo' => $r->in_logo],
                    'out' => ['id' => $r->out_api_id, 'name' => $r->out_name, 'logo' => $r->out_logo],
                ],
            ])->values()->all(),
        ])->values()->all();
    }
}
