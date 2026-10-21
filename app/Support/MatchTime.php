<?php

namespace App\Support;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Support\Carbon;

/**
 * Kick-off time in the match's own time zone.
 *
 * Zone of a match: the home club's championship zone (so a Champions League
 * game in Madrid shows Madrid time), else the competition's own zone, else
 * UTC. The visitor's own time is added in the browser (see the layout).
 */
class MatchTime
{
    /** @var array<int, string>|null football-data team id => zone */
    protected static ?array $teamZones = null;

    /** @var array<string, string|null>|null competition code => zone */
    protected static ?array $competitionZones = null;

    public static function zone(Fixture $fixture): string
    {
        self::load();

        $zone = self::$teamZones[(int) $fixture->home_api_id]
            ?? self::$competitionZones[$fixture->league_code]
            ?? null;

        return $zone && in_array($zone, timezone_identifiers_list(), true) ? $zone : 'UTC';
    }

    public static function local(Fixture $fixture): ?Carbon
    {
        return $fixture->kickoff_at?->copy()->setTimezone(self::zone($fixture));
    }

    /** Short zone label: "BST", "CEST", or "UTC−3" where no abbreviation exists. */
    public static function abbr(Carbon $local): string
    {
        $abbr = $local->format('T');

        if (preg_match('/^[+-]\d+$/', $abbr) || $abbr === 'Z') {
            $offset = $local->format('P');                         // +03:00
            $offset = preg_replace('/:00$/', '', $offset);          // +03
            $offset = preg_replace('/^([+-])0/', '$1', $offset);    // +3

            return $offset === '+0' ? 'UTC' : 'UTC'.str_replace('-', '−', $offset);
        }

        return $abbr;
    }

    /** "London" from "Europe/London". */
    public static function city(string $zone): string
    {
        return $zone === 'UTC' ? 'UTC' : str_replace('_', ' ', substr($zone, strrpos($zone, '/') + 1));
    }

    protected static function load(): void
    {
        if (self::$teamZones !== null) {
            return;
        }

        self::$competitionZones = Competition::query()->pluck('timezone', 'code')->all();

        self::$teamZones = Team::query()
            ->join('competitions', 'competitions.id', '=', 'teams.competition_id')
            ->whereNotNull('teams.api_id')
            ->whereNotNull('competitions.timezone')
            ->pluck('competitions.timezone', 'teams.api_id')
            ->mapWithKeys(fn ($zone, $id) => [(int) $id => $zone])
            ->all();
    }
}
