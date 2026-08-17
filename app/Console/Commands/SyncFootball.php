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
                            {--season= : Override the configured season (start year, e.g. 2024)}
                            {--create-countries : Also create countries that do not exist yet (default: only import teams for countries already created in the admin)}';

    protected $description = 'Import football teams from API-Football, only for countries already created in the admin';

    public function handle(ApiFootballClient $api): int
    {
        $season  = (int) ($this->option('season') ?: config('football.season'));
        $leagues = config('football.leagues', []);
        $create  = (bool) $this->option('create-countries');

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

        if (! $create && SportCountry::where('sport_id', $sport->id)->count() === 0) {
            $this->error('No countries created yet. Add the countries you want in the admin (Sport → Countries), set their "API name" (e.g. England), then run the import. Or pass --create-countries to auto-create them.');
            return self::FAILURE;
        }

        $mode = $create ? 'creating missing countries' : 'existing countries only';
        $this->info("Syncing season {$season} across ".count($leagues)." league(s) [{$mode}]...");

        $teamCount = 0;
        $skipped   = []; // country name => skipped team count

        foreach ($leagues as $leagueId => $leagueName) {
            $this->line("→ League {$leagueId} ({$leagueName})");
            $rows = $api->teamsByLeague((int) $leagueId, $season);

            if (empty($rows)) {
                $this->warn('  no teams returned (quota, wrong id, or off-season?)');
                continue;
            }

            $imported = 0;

            foreach ($rows as $row) {
                $t = $row['team'] ?? [];
                $v = $row['venue'] ?? [];

                if (empty($t['id']) || empty($t['name'])) {
                    continue;
                }

                $countryName = $t['country'] ?? 'World';
                $country     = $this->resolveCountry($sport, $countryName, $create);

                // Country not created in the admin — skip its teams.
                if (! $country) {
                    $skipped[$countryName] = ($skipped[$countryName] ?? 0) + 1;
                    continue;
                }

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
                $imported++;
            }

            $this->info("  imported {$imported} team(s)");
        }

        $this->newLine();
        $this->info("Done. {$teamCount} team row(s) upserted.");

        if (! empty($skipped)) {
            $this->newLine();
            $this->warn('Skipped teams for countries not created in the admin:');
            foreach ($skipped as $name => $n) {
                $this->line("  - {$name}: {$n} team(s)");
            }
            $this->line('Create these under Sport → Countries (set "API name" to match) and re-run, or use --create-countries.');
        }

        return self::SUCCESS;
    }

    /**
     * Find the already-created country for this API country name.
     * Matches on api_name first, then slug. Creates it only when allowed.
     */
    protected function resolveCountry(Sport $sport, string $name, bool $create): ?SportCountry
    {
        $slug = Str::slug($name);

        $country = SportCountry::where('sport_id', $sport->id)
            ->where(function ($q) use ($name, $slug) {
                $q->where('api_name', $name)->orWhere('slug', $slug);
            })
            ->first();

        if ($country || ! $create) {
            return $country;
        }

        return SportCountry::create([
            'sport_id'  => $sport->id,
            'slug'      => $slug,
            'name'      => ['en' => $name],
            'api_name'  => $name,
            'is_active' => true,
        ]);
    }
}
