<?php

namespace App\Console\Commands;

use App\Models\Sport;
use App\Models\SportCountry;
use App\Models\Team;
use App\Services\Football\ApiFootballClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncFootball extends Command
{
    protected $signature = 'sport:sync-football
                            {--season= : Override the configured season (start year, e.g. 2024)}';

    protected $description = 'Import football countries & teams from API-Football into the local taxonomy';

    public function handle(ApiFootballClient $api): int
    {
        $season  = (int) ($this->option('season') ?: config('football.season'));
        $leagues = config('football.leagues', []);

        if (empty(config('football.api.key'))) {
            $this->error('API_FOOTBALL_KEY is not set. Add it to your .env first.');
            return self::FAILURE;
        }

        // Ensure the "football" sport exists (it owns the deep hierarchy).
        $sport = Sport::firstOrCreate(
            ['slug' => 'football'],
            [
                'name'             => ['en' => 'Football', 'uk' => 'Футбол', 'ru' => 'Футбол'],
                'has_competitions' => true,
                'is_active'        => true,
                'sort_order'       => 0,
                'icon'             => 'football',
            ]
        );

        $this->info("Syncing season {$season} across ".count($leagues).' league(s)...');

        $teamCount = 0;

        foreach ($leagues as $leagueId => $leagueName) {
            $this->line("→ League {$leagueId} ({$leagueName})");
            $rows = $api->teamsByLeague((int) $leagueId, $season);

            if (empty($rows)) {
                $this->warn("  no teams returned (quota, wrong id, or off-season?)");
                continue;
            }

            foreach ($rows as $row) {
                $t = $row['team'] ?? [];
                $v = $row['venue'] ?? [];

                if (empty($t['id']) || empty($t['name'])) {
                    continue;
                }

                $countryName = $t['country'] ?? 'World';
                $country = SportCountry::firstOrCreate(
                    ['sport_id' => $sport->id, 'slug' => Str::slug($countryName)],
                    [
                        'name'     => ['en' => $countryName],
                        'api_name' => $countryName,
                        'flag_url' => $t['logo'] ?? null, // real flag set later; placeholder
                        'is_active'=> true,
                    ]
                );

                Team::updateOrCreate(
                    ['sport_country_id' => $country->id, 'slug' => Str::slug($t['name'])],
                    [
                        'sport_id'              => $sport->id,
                        'api_id'                => $t['id'],
                        'primary_league_api_id' => (int) $leagueId,
                        'name'                  => ['en' => $t['name']],
                        'short_name'            => $t['code'] ?? null,
                        'logo_url'              => $t['logo'] ?? null,
                        'founded'               => $t['founded'] ?? null,
                        'stadium'               => $v['name'] ?? null,
                        'city'                  => $v['city'] ?? null,
                        'is_active'             => true,
                    ]
                );

                $teamCount++;
            }

            $this->info('  imported '.count($rows).' teams');
        }

        $this->newLine();
        $this->info("Done. {$teamCount} team rows upserted.");

        return self::SUCCESS;
    }
}
