<?php

return [

    /*
     | Automatic sync (sport:auto-sync, every 15 minutes): each run refreshes
     | ONE competition — the one updated longest ago — so championships are
     | done one by one with a 15-minute break, and each once a day.
     | Overridable in the admin: Settings → Auto sync.
     */
    'auto_sync' => [
        'enabled'         => (bool) env('FOOTBALL_AUTO_SYNC', true),
        'every_hours'     => (int) env('FOOTBALL_AUTO_SYNC_HOURS', 24),   // how often each competition
        'weekly_days'     => 7,     // squads & transfers: at most this often per championship
        'transfers_limit' => 10,    // teams per run (API-Football free plan: 100 requests a day)
        'squads_limit'    => 25,    // teams per run (football-data: ~10 requests a minute)
    ],

    /*
     | Team news on the site (feed, article pages, news tab and blocks).
     | Off: everything news-related is hidden and /news URLs redirect home.
     | Overridable in the admin: Settings → News API → "Show news on the site".
     */
    'news_enabled' => (bool) env('FOOTBALL_NEWS_ENABLED', false),


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
        'EL' => 'UEFA Europa League',
    ],

    'cache' => [
        'fixtures'  => (int) env('FOOTBALL_TTL_FIXTURES', 900),
        'standings' => (int) env('FOOTBALL_TTL_STANDINGS', 3600),
        'transfers' => (int) env('FOOTBALL_TTL_TRANSFERS', 86400),
    ],
];
