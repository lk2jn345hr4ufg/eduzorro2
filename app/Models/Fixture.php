<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    protected $guarded = [];

    protected $casts = [
        'kickoff_at' => 'datetime',
    ];

    /** Matches involving a given API team id. */
    public function scopeForTeam($query, int $teamApiId)
    {
        return $query->where(function ($q) use ($teamApiId) {
            $q->where('home_api_id', $teamApiId)->orWhere('away_api_id', $teamApiId);
        });
    }

    public function scopeSeason($query, int $season)
    {
        return $query->where('season', $season);
    }

    /** Re-shape a DB row into the API-Football payload the views expect. */
    public function toApiShape(): array
    {
        return [
            'fixture' => [
                'id'     => $this->api_id,
                'date'   => optional($this->kickoff_at)->toIso8601String(),
                'status' => ['short' => $this->status_short],
            ],
            'league' => [
                'id'    => $this->league_api_id,
                'name'  => $this->league_name,
                'round' => $this->league_round,
            ],
            'teams' => [
                'home' => ['id' => $this->home_api_id, 'name' => $this->home_name, 'logo' => $this->home_logo],
                'away' => ['id' => $this->away_api_id, 'name' => $this->away_name, 'logo' => $this->away_logo],
            ],
            'goals' => ['home' => $this->goals_home, 'away' => $this->goals_away],
        ];
    }
}
