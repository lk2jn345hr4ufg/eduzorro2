<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    protected $guarded = [];

    protected $casts = [
        'kickoff_at' => 'datetime',
    ];

    public const FINISHED = ['FT', 'AET', 'PEN'];
    public const LIVE     = ['IN_PLAY', 'PAUSED', 'LIVE'];

    public function odd()
    {
        return $this->hasOne(Odd::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class, 'league_code', 'code');
    }

    public function isPlayed(): bool
    {
        return in_array($this->status_short, self::FINISHED, true);
    }

    public function isLive(): bool
    {
        return in_array($this->status_short, self::LIVE, true);
    }

    public function scopePlayed($query)
    {
        return $query->whereIn('status_short', self::FINISHED);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereNotIn('status_short', self::FINISHED)
            ->whereNotIn('status_short', ['CANCELLED', 'POSTPONED', 'SUSPENDED']);
    }

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
                'code'  => $this->league_code,
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
