<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Sport;
use App\Models\SportCountry;
use App\Models\Team;
use Illuminate\Database\Seeder;

/**
 * Championships (one per country) and tournaments, with football-data codes
 * and API-Football league ids (the latter are used only for odds).
 *
 * Safe to re-run: rows are matched on their code and only updated. After the
 * competitions exist, existing teams are linked to their league and a default
 * set of well-known clubs is flagged as popular (only if none is flagged yet,
 * so it never overwrites choices made in the admin).
 */
class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $sport = Sport::firstOrCreate(
            ['slug' => 'football'],
            ['name' => ['en' => 'Football', 'uk' => 'Футбол', 'ru' => 'Футбол', 'es' => 'Fútbol'], 'is_active' => true]
        );

        $order = 0;

        foreach ($this->leagues() as $code => $l) {
            $country = $this->country($sport, $l['country'], $l['country_name']);

            Competition::updateOrCreate(['code' => $code], [
                'type'                => Competition::LEAGUE,
                'api_id'              => $l['api_id'],
                'apisports_league_id' => $l['apisports'],
                'sport_country_id'    => $country->id,
                'slug'                => $l['slug'],
                'name'                => $l['name'],
                'emblem_url'          => $l['api_id'] ? "https://crests.football-data.org/{$code}.png" : null,
                'flag'                => $l['flag'],
                'is_featured'         => true,
                'sort_order'          => $order++,
                'is_active'           => true,
            ]);
        }

        foreach ($this->cups() as $code => $c) {
            Competition::updateOrCreate(['code' => $code], [
                'type'                => Competition::CUP,
                'api_id'              => $c['api_id'],
                'apisports_league_id' => $c['apisports'],
                'sport_country_id'    => null,
                'slug'                => $c['slug'],
                'name'                => $c['name'],
                'emblem_url'          => "https://crests.football-data.org/{$code}.png",
                'flag'                => $c['flag'],
                'is_featured'         => true,
                'sort_order'          => 100 + $order++,
                'is_active'           => true,
            ]);
        }

        $this->linkTeams();
        $this->flagPopularTeams();
    }

    /** Attach every team to its country's league (by code first, then by country). */
    protected function linkTeams(): void
    {
        foreach (Competition::leagues()->get() as $league) {
            Team::where('primary_league_code', $league->code)
                ->update(['competition_id' => $league->id]);

            // Teams without a football-data code (e.g. Ukrainian clubs) are
            // linked through their country, which has exactly one league.
            Team::whereNull('competition_id')
                ->whereNull('primary_league_code')
                ->where('sport_country_id', $league->sport_country_id)
                ->update(['competition_id' => $league->id]);
        }
    }

    protected function flagPopularTeams(): void
    {
        if (Team::where('is_popular', true)->exists()) {
            return;
        }

        $slugs = [
            'real-madrid', 'barcelona', 'manchester-city', 'liverpool', 'arsenal',
            'manchester-united', 'chelsea', 'bayern-munchen', 'bayern-munich',
            'paris-saint-germain', 'internazionale-milano', 'inter', 'milan',
            'juventus', 'borussia-dortmund', 'atletico-madrid', 'club-atletico-de-madrid',
            'shakhtar-donetsk', 'dynamo-kyiv',
        ];

        foreach ($slugs as $i => $slug) {
            Team::where('slug', $slug)->update(['is_popular' => true, 'popular_order' => $i]);
        }
    }

    protected function country(Sport $sport, string $slug, array $name): SportCountry
    {
        return SportCountry::firstOrCreate(
            ['sport_id' => $sport->id, 'slug' => $slug],
            ['name' => $name, 'api_name' => $name['en'], 'is_active' => true]
        );
    }

    protected function leagues(): array
    {
        $n = fn (string $en, string $uk, string $ru, string $es) => compact('en', 'uk', 'ru', 'es');

        return [
            'PL' => [
                'slug' => 'premier-league', 'api_id' => 2021, 'apisports' => 39, 'flag' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿',
                'country' => 'england', 'country_name' => $n('England', 'Англія', 'Англия', 'Inglaterra'),
                'name' => $n('Premier League', 'Прем\'єр-ліга', 'Премьер-лига', 'Premier League'),
            ],
            'PD' => [
                'slug' => 'la-liga', 'api_id' => 2014, 'apisports' => 140, 'flag' => '🇪🇸',
                'country' => 'spain', 'country_name' => $n('Spain', 'Іспанія', 'Испания', 'España'),
                'name' => $n('La Liga', 'Ла Ліга', 'Ла Лига', 'LaLiga'),
            ],
            'SA' => [
                'slug' => 'serie-a', 'api_id' => 2019, 'apisports' => 135, 'flag' => '🇮🇹',
                'country' => 'italy', 'country_name' => $n('Italy', 'Італія', 'Италия', 'Italia'),
                'name' => $n('Serie A', 'Серія A', 'Серия A', 'Serie A'),
            ],
            'BL1' => [
                'slug' => 'bundesliga', 'api_id' => 2002, 'apisports' => 78, 'flag' => '🇩🇪',
                'country' => 'germany', 'country_name' => $n('Germany', 'Німеччина', 'Германия', 'Alemania'),
                'name' => $n('Bundesliga', 'Бундесліга', 'Бундеслига', 'Bundesliga'),
            ],
            'FL1' => [
                'slug' => 'ligue-1', 'api_id' => 2015, 'apisports' => 61, 'flag' => '🇫🇷',
                'country' => 'france', 'country_name' => $n('France', 'Франція', 'Франция', 'Francia'),
                'name' => $n('Ligue 1', 'Ліга 1', 'Лига 1', 'Ligue 1'),
            ],
            'DED' => [
                'slug' => 'eredivisie', 'api_id' => 2003, 'apisports' => 88, 'flag' => '🇳🇱',
                'country' => 'netherlands', 'country_name' => $n('Netherlands', 'Нідерланди', 'Нидерланды', 'Países Bajos'),
                'name' => $n('Eredivisie', 'Ередивізі', 'Эредивизи', 'Eredivisie'),
            ],
            'PPL' => [
                'slug' => 'primeira-liga', 'api_id' => 2017, 'apisports' => 94, 'flag' => '🇵🇹',
                'country' => 'portugal', 'country_name' => $n('Portugal', 'Португалія', 'Португалия', 'Portugal'),
                'name' => $n('Primeira Liga', 'Прімейра-ліга', 'Примейра-лига', 'Primeira Liga'),
            ],
            'BSA' => [
                'slug' => 'brasileirao', 'api_id' => 2013, 'apisports' => 71, 'flag' => '🇧🇷',
                'country' => 'brazil', 'country_name' => $n('Brazil', 'Бразилія', 'Бразилия', 'Brasil'),
                'name' => $n('Brasileirão Série A', 'Бразильська Серія A', 'Бразильская Серия A', 'Brasileirão Serie A'),
            ],
            // Not covered by football-data.org: no fixtures/standings until a
            // source is added, but the clubs, news and transfers still work.
            'UPL' => [
                'slug' => 'ukrainian-premier-league', 'api_id' => null, 'apisports' => 333, 'flag' => '🇺🇦',
                'country' => 'ukraine', 'country_name' => $n('Ukraine', 'Україна', 'Украина', 'Ucrania'),
                'name' => $n('Ukrainian Premier League', 'Українська Прем\'єр-ліга', 'Украинская Премьер-лига', 'Premier League de Ucrania'),
            ],
        ];
    }

    protected function cups(): array
    {
        $n = fn (string $en, string $uk, string $ru, string $es) => compact('en', 'uk', 'ru', 'es');

        return [
            'CL' => [
                'slug' => 'champions-league', 'api_id' => 2001, 'apisports' => 2, 'flag' => '🇪🇺',
                'name' => $n('UEFA Champions League', 'Ліга чемпіонів УЄФА', 'Лига чемпионов УЕФА', 'Liga de Campeones'),
            ],
            'EC' => [
                'slug' => 'euro', 'api_id' => 2018, 'apisports' => 4, 'flag' => '🇪🇺',
                'name' => $n('European Championship', 'Чемпіонат Європи', 'Чемпионат Европы', 'Eurocopa'),
            ],
            'WC' => [
                'slug' => 'world-cup', 'api_id' => 2000, 'apisports' => 1, 'flag' => '🌍',
                'name' => $n('FIFA World Cup', 'Чемпіонат світу', 'Чемпионат мира', 'Copa Mundial'),
            ],
        ];
    }
}
