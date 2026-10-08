<?php

namespace App\Support;

use App\Models\Team;

/**
 * Resolves which football-data team ids have a page on this site, so match
 * lists and tables can link a club only when its page exists (a Champions
 * League opponent from a league we don't cover simply renders as text).
 */
class TeamLinks
{
    /** @return array<int, string> football-data team id => team slug */
    public static function forIds(iterable $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Team::query()
            ->active()
            ->whereIn('api_id', $ids)
            ->pluck('slug', 'api_id')
            ->mapWithKeys(fn ($slug, $id) => [(int) $id => $slug])
            ->all();
    }

    /** Collect both sides of a set of Fixture models. */
    public static function forFixtures(iterable $fixtures): array
    {
        $ids = [];

        foreach ($fixtures as $fixture) {
            $ids[] = $fixture->home_api_id;
            $ids[] = $fixture->away_api_id;
        }

        return self::forIds($ids);
    }
}
