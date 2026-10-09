<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A squad member (see sport:sync-squads). */
class Player extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
        'shirt_number'  => 'integer',
    ];

    /** Display order of the lines on the squad tab. */
    public const LINES = ['GK', 'DEF', 'MID', 'FWD', 'OTH'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Map a football-data position to a line. Older data uses the coarse
     * "Defence/Midfield/Offence", newer data detailed roles ("Centre-Back",
     * "Right Winger", "Defensive Midfield"…).
     */
    public static function lineFor(?string $position): string
    {
        $p = strtolower((string) $position);

        return match (true) {
            $p === ''                                                    => 'OTH',
            str_contains($p, 'goal')                                      => 'GK',
            str_contains($p, 'midfield')                                  => 'MID',
            str_contains($p, 'back') || str_contains($p, 'defen')         => 'DEF',
            str_contains($p, 'forward') || str_contains($p, 'wing')
                || str_contains($p, 'offen') || str_contains($p, 'attack')
                || str_contains($p, 'striker')                            => 'FWD',
            default                                                       => 'OTH',
        };
    }
}
