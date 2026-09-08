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
                            {--season= : Season start year (omit to use the API current season)}
                            {--competition= : Limit to one competition code, e.g. PL}
                            {--create-countries : Also create countries that do not exist yet}
                            {--sleep=7 : Seconds between competitions (free tier allows ~10 req/min)}';

    protected $description = 'Import football teams from football-data.org into the local taxonomy';

    public function handle(ApiFootballClient $api): int
    {
        if (! $api->isConfigured()) {
            $this->error('FOOTBALL_DATA_TOKEN is not set (Settings → API, or .env).');
            return self::FAILURE;
        }

        $season  = $this->option('season') ? (int) $this->option('season') : null;
        $create  = (bool) $this->option('create-countries');
        $sleep   = (int) $this->option('sleep');
        $leagues = config('football.leagues', []);

        if ($only = $this->option('competition')) {
            $leagues = array_intersect_key($leagues, [$only => true]);
            if (empty($leagues)) {
                $this->error("Unknown competition code: {$only}");
                return self::FAILURE;
            }
        }

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
            $this->error('No countries created yet. Add them in the admin (Sport → Countries) or pass --create-countries.');
            return self::FAILURE;
        }

        $this->info('Importing '.count($leagues).' competition(s) from football-data.org'.($season ? " (season {$season})" : ' (current season)').'...');

        $total   = 0;
        $skipped = [];

        foreach ($leagues as $code => $label) {
            $this->line("→ {$code} ({$label})");
            $teams = $api->teamsByCompetition($code, $season);

            if (empty($teams)) {
                $this->warn('  nothing returned (rate limit, token, or competition not in your plan)');
                continue;
            }

            foreach ($teams as $t) {
                $countryName = $t['area'] ?: 'World';
                $country     = $this->resolveCountry($sport, $countryName, $create);

                if (! $country) {
                    $skipped[$countryName] = ($skipped[$countryName] ?? 0) + 1;
                    continue;
                }

                // football-data spells clubs "Manchester United FC" while our
                // rows came from api-sports as "Manchester United". Match on a
                // suffix-stripped slug and keep the existing row (and its URL,
                // news and sort order) instead of creating a near-duplicate.
                $slug = $this->slugFor($t['name']);

                $existing = Team::where('sport_country_id', $country->id)
                    ->where(fn ($q) => $q
                        ->where('slug', $slug)
                        ->orWhere('slug', 'like', $slug.'%')
                        ->orWhereRaw('? like concat(slug, \'%\')', [$slug]))
                    ->orderByRaw('char_length(slug) desc')
                    ->first();

                Team::updateOrCreate(
                    ['id' => $existing?->id],
                    [
                        'sport_country_id'      => $country->id,
                        'slug'                  => $existing?->slug ?: $slug,
                        'sport_id'              => $sport->id,
                        'api_id'                => $t['id'],
                        'primary_league_api_id' => $t['competition_id'],
                        'primary_league_code'   => $t['competition_code'],
                        'name'                  => ['en' => $t['name']],
                        'short_name'            => $t['code'],
                        'logo_url'              => $t['crest'],
                        'founded'               => $t['founded'],
                        'stadium'               => $t['venue'],
                        'is_active'             => true,
                    ]
                );

                $total++;
            }

            $this->info('  imported '.count($teams).' team(s)');

            if ($sleep > 0 && count($leagues) > 1) {
                sleep($sleep);
            }
        }

        $this->newLine();
        $this->info("Done. {$total} team row(s) upserted.");

        if ($skipped) {
            $this->newLine();
            $this->warn('Skipped teams for countries not created in the admin:');
            foreach ($skipped as $name => $n) {
                $this->line("  - {$name}: {$n}");
            }
            $this->line('Create them under Sport → Countries (API name = the country as returned above), or use --create-countries.');
        }

        return self::SUCCESS;
    }

    /**
     * Slug from a club name with the usual legal/club suffixes removed, so
     * "Manchester United FC", "Manchester United" and "FC Manchester United"
     * all collapse to the same slug.
     */
    protected function slugFor(string $name): string
    {
        $clean = preg_replace(
            '/\b(fc|cf|afc|sc|ac|as|ss|ssc|bk|if|sk|vfl|vfb|tsv|fsv|rc|cd|ud|sd)\b/iu',
            ' ',
            $name
        );

        return Str::slug(trim(preg_replace('/\s+/u', ' ', $clean)));
    }

    protected function resolveCountry(Sport $sport, string $name, bool $create): ?SportCountry
    {
        $slug = Str::slug($name);

        $country = SportCountry::where('sport_id', $sport->id)
            ->where(fn ($q) => $q->where('api_name', $name)->orWhere('slug', $slug))
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
