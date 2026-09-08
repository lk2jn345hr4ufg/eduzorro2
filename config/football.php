<?php

return [

    /*
     |--------------------------------------------------------------------------
     | football-data.org (v4)
     |--------------------------------------------------------------------------
     | Auth is a single header, X-Auth-Token. The free tier covers the 12
     | competitions listed below and the CURRENT season; it is rate limited to
     | ~10 requests/minute, so syncs use --sleep and per-run limits.
     */
    'api' => [
        'token'    => env('FOOTBALL_DATA_TOKEN'),
        'base_url' => rtrim(env('FOOTBALL_DATA_BASE_URL', 'https://api.football-data.org/v4'), '/'),
        'timeout'  => (int) env('FOOTBALL_DATA_TIMEOUT', 15),
    ],

    /*
     | Season = starting year. Leave FOOTBALL_DATA_SEASON empty to let the API
     | use the competition's current season, which is what the free tier allows.
     */
    'season' => (int) env('FOOTBALL_DATA_SEASON', (int) date('Y') - (date('n') < 7 ? 1 : 0)),

    /*
     | Domestic competitions to import teams from, keyed by football-data code.
     | NOTE: the Ukrainian Premier League is NOT available on football-data.org.
     */
    'leagues' => [
        'PL'  => 'Premier League (England)',
        'PD'  => 'La Liga (Spain)',
        'SA'  => 'Serie A (Italy)',
        'BL1' => 'Bundesliga (Germany)',
        'FL1' => 'Ligue 1 (France)',
        'DED' => 'Eredivisie (Netherlands)',
        'PPL' => 'Primeira Liga (Portugal)',
    ],

    /*
     | European competitions — the "euro cups" tab filters fixtures to these codes.
     */
    'euro_competitions' => [
        'CL' => 'UEFA Champions League',
    ],

    'cache' => [
        'fixtures'  => (int) env('FOOTBALL_TTL_FIXTURES', 900),
        'standings' => (int) env('FOOTBALL_TTL_STANDINGS', 3600),
        'transfers' => (int) env('FOOTBALL_TTL_TRANSFERS', 86400),
    ],
];
