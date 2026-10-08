<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Services\Football\ApiFootballClient;
use App\Services\Football\FootballStore;
use Illuminate\Console\Command;

/**
 * Loads a whole competition — every match of the season and its table — in
 * two football-data requests, instead of one request per team. This is what
 * feeds the championship and tournament pages, and it is also the cheapest
 * way to refresh the team pages, since their fixtures come from the same rows.
 */
class SyncCompetition extends Command
{
    protected $signature = 'sport:sync-competition
                            {codes?* : Competition codes, e.g. PL CL (default: all active with football-data coverage)}
                            {--season= : Season start year (omit for the current season)}
                            {--sleep=7 : Seconds between competitions (free tier allows ~10 requests/minute)}';

    protected $description = 'Sync matches and standings for whole competitions from football-data.org';

    public function handle(ApiFootballClient $api, FootballStore $store): int
    {
        if (! $api->isConfigured()) {
            $this->error('football-data token is not set (Settings → API, or FOOTBALL_DATA_TOKEN).');
            return self::FAILURE;
        }

        $query = Competition::active()->ordered()->whereNotNull('api_id');

        if ($codes = array_map('strtoupper', (array) $this->argument('codes'))) {
            $query->whereIn('code', $codes);
        }

        $competitions = $query->get();

        if ($competitions->isEmpty()) {
            $this->warn('No matching competitions with football-data coverage. Seed them first: php artisan db:seed --class=Database\\Seeders\\CompetitionSeeder');
            return self::SUCCESS;
        }

        $season = $this->option('season') ? (int) $this->option('season') : null;
        $sleep  = (int) $this->option('sleep');

        foreach ($competitions as $i => $competition) {
            $this->line("→ {$competition->code} ".$competition->translate('name'));

            $result = $api->competitionMatches($competition->code, $season);
            $fx     = $store->fixtures($result['matches'], $result['season'] ?: (int) config('football.season'));

            $effectiveSeason = $result['season'] ?: (int) config('football.season');

            if ($sleep > 0) {
                sleep($sleep);
            }

            $rows = $api->standingsAll($competition->code, $season);
            $st   = $store->standings($rows, $competition->code, $effectiveSeason);

            if ($result['emblem'] && $result['emblem'] !== $competition->emblem_url) {
                $competition->update(['emblem_url' => $result['emblem']]);
            }

            $this->info("   season {$effectiveSeason}: {$fx} matches, {$st} table rows");

            if ($fx === 0 && $st === 0) {
                $this->warn('   nothing returned — rate limit, token, or this competition is not in your plan (see storage/logs/laravel.log)');
            }

            if ($sleep > 0 && $i < $competitions->count() - 1) {
                sleep($sleep);
            }
        }

        return self::SUCCESS;
    }
}
