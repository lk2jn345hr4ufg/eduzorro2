<?php

return [

    /*
     |--------------------------------------------------------------------------
     | API-Football (api-sports.io)
     |--------------------------------------------------------------------------
     | Direct api-sports.io host uses the "x-apisports-key" header.
     | If you subscribe through RapidAPI instead, set FOOTBALL_API_HOST to the
     | RapidAPI host and the client will send the x-rapidapi-* headers.
     */
    'api' => [
        'key'      => env('API_FOOTBALL_KEY'),
        'base_url' => rtrim(env('API_FOOTBALL_BASE_URL', 'https://v3.football.api-sports.io'), '/'),
        'host'     => env('API_FOOTBALL_HOST'), // set only for RapidAPI, e.g. api-football-v1.p.rapidapi.com
        'timeout'  => (int) env('API_FOOTBALL_TIMEOUT', 12),
    ],

    /*
     | The season synced/queried. API-Football keys seasons by their start year
     | (e.g. the 2024/25 season is "2024").
     */
    'season' => (int) env('API_FOOTBALL_SEASON', 2024),

    /*
     |--------------------------------------------------------------------------
     | Leagues to build the taxonomy from
     |--------------------------------------------------------------------------
     | The sync command walks these league IDs and imports their countries and
     | teams. Keep this list bounded — the free API plan is rate-limited, and
     | importing "every league" would exhaust the daily quota. IDs are
     | API-Football league IDs. A team's first league here becomes its primary
     | (domestic) league, used for the standings tab.
     */
    'leagues' => [
        39  => 'Premier League',   // England
        140 => 'La Liga',          // Spain
        135 => 'Serie A',          // Italy
        78  => 'Bundesliga',       // Germany
        61  => 'Ligue 1',          // France
        333 => 'Premier League',   // Ukraine
    ],

    /*
     | European cups — used for the team "euro-cups" tab (fixtures + standings
     | filtered to these competitions). API-Football league IDs.
     */
    'euro_competitions' => [
        2   => 'UEFA Champions League',
        3   => 'UEFA Europa League',
        848 => 'UEFA Europa Conference League',
    ],

    /*
     | Cache TTLs (seconds) for on-demand API reads. Taxonomy (countries/teams)
     | is persisted in the DB by the sync command; the tabs below are fetched
     | live and cached.
     */
    'cache' => [
        'fixtures'  => (int) env('API_FOOTBALL_TTL_FIXTURES', 900),    // 15 min
        'standings' => (int) env('API_FOOTBALL_TTL_STANDINGS', 3600),  // 1 h
        'transfers' => (int) env('API_FOOTBALL_TTL_TRANSFERS', 86400), // 24 h
    ],
];
