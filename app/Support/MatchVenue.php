<?php

namespace App\Support;

use App\Models\Fixture;
use App\Models\Team;

/**
 * Where a match is played: the stadium football-data gives for the match
 * (covers neutral venues and finals), otherwise the home club's stadium,
 * plus the club's city or country.
 */
class MatchVenue
{
    /** @var array<int, array{stadium: ?string, place: ?string}>|null */
    protected static ?array $teams = null;

    /** @return array{stadium: ?string, place: ?string}|null */
    public static function for(Fixture $fixture): ?array
    {
        self::load();

        $home    = self::$teams[(int) $fixture->home_api_id] ?? null;
        $stadium = trim((string) ($fixture->venue ?: ($home['stadium'] ?? '')));

        if ($stadium === '') {
            return null;
        }

        // The club's city/country only describes its OWN stadium, not a
        // neutral venue given by the API.
        $place = (! $fixture->venue || $fixture->venue === ($home['stadium'] ?? null)) ? ($home['place'] ?? null) : null;

        return ['stadium' => $stadium, 'place' => $place];
    }

    public static function label(Fixture $fixture): ?string
    {
        $v = self::for($fixture);

        return $v ? ($v['place'] ? $v['stadium'].', '.$v['place'] : $v['stadium']) : null;
    }

    protected static function load(): void
    {
        if (self::$teams !== null) {
            return;
        }

        self::$teams = Team::query()
            ->whereNotNull('api_id')
            ->with('country')
            ->get(['id', 'api_id', 'stadium', 'city', 'sport_country_id'])
            ->mapWithKeys(fn ($t) => [(int) $t->api_id => [
                'stadium' => $t->stadium ?: null,
                'place'   => $t->city ?: $t->country?->translate('name'),
            ]])
            ->all();
    }
}
