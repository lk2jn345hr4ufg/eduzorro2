<?php

namespace App\Services\Football;

use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Matches API-Football fixtures (where the odds come from) to our fixtures
 * (which come from football-data.org). The two providers share no ids, so a
 * match is found by kick-off date plus the two teams:
 *
 *   1. by team id, through teams.apisports_id ↔ teams.api_id, when linked;
 *   2. otherwise by normalised team name ("Manchester United FC" and
 *      "Manchester United" both become "manchester united").
 */
class OddsMatcher
{
    /** football-data team id => API-Football team id, for linked teams. */
    protected array $idMap;

    public function __construct()
    {
        $this->idMap = Team::query()
            ->whereNotNull('api_id')
            ->whereNotNull('apisports_id')
            ->pluck('apisports_id', 'api_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @param  Collection<int, Fixture>  $ours  our fixtures for the same day and competition
     * @param  array  $theirs  API-Football fixture: ['home_id','home','away_id','away']
     */
    public function find(Collection $ours, array $theirs): ?Fixture
    {
        foreach ($ours as $fixture) {
            $homeId = $this->idMap[$fixture->home_api_id] ?? null;
            $awayId = $this->idMap[$fixture->away_api_id] ?? null;

            if ($homeId && $awayId
                && $homeId === (int) $theirs['home_id']
                && $awayId === (int) $theirs['away_id']) {
                return $fixture;
            }
        }

        foreach ($ours as $fixture) {
            if ($this->sameTeam($fixture->home_name, $theirs['home'])
                && $this->sameTeam($fixture->away_name, $theirs['away'])) {
                return $fixture;
            }
        }

        return null;
    }

    public function sameTeam(?string $a, ?string $b): bool
    {
        $a = $this->normalise($a);
        $b = $this->normalise($b);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        // "inter" vs "internazionale milano" style differences: accept when
        // the shorter name is a whole-word prefix of the longer one.
        [$short, $long] = strlen($a) <= strlen($b) ? [$a, $b] : [$b, $a];

        return strlen($short) >= 4 && str_starts_with($long.' ', $short.' ');
    }

    public function normalise(?string $name): string
    {
        $name = Str::lower(Str::ascii((string) $name));
        $name = preg_replace('/\b(fc|cf|afc|sc|ac|as|ss|ssc|bk|if|sk|vfl|vfb|tsv|fsv|rc|cd|ud|sd|club|de)\b/', ' ', $name);
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }
}
