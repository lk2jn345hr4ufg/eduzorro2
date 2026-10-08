<?php

return [

    /*
     |--------------------------------------------------------------------------
     | API-Football (api-sports.io) — transfers only
     |--------------------------------------------------------------------------
     | football-data.org has no transfers endpoint, so this provider is kept
     | solely for /transfers. Everything else (teams, fixtures, standings) comes
     | from football-data.org — see config/football.php.
     |
     | The /transfers endpoint takes no season parameter, which is why it keeps
     | working on the free plan even though fixtures there are limited to old
     | seasons.
     */
    'key'      => env('API_FOOTBALL_KEY'),
    'base_url' => rtrim(env('API_FOOTBALL_BASE_URL', 'https://v3.football.api-sports.io'), '/'),
    'host'     => env('API_FOOTBALL_HOST'), // set only when going through RapidAPI
    'timeout'  => (int) env('API_FOOTBALL_TIMEOUT', 12),

    /*
     | Free plan is ~10 requests/minute. The client spaces its own calls out by
     | this many seconds and retries a 429 after a growing pause, so a burst of
     | lookups can no longer trip the limit.
     */
    'min_interval' => (float) env('API_FOOTBALL_MIN_INTERVAL', 6.5),
    'retries'      => (int) env('API_FOOTBALL_RETRIES', 3),
    'retry_wait'   => (int) env('API_FOOTBALL_RETRY_WAIT', 20),

    /*
     | Odds (sport:sync-odds). Only the 1X2 "Match Winner" market (bet id 1) is
     | requested. Set a bookmaker id to always use the same one; leave empty to
     | take the first bookmaker that offers the market.
     | Season: API-Football keys seasons by start year. Leave empty to use the
     | site season (config/football.php). Free plans only open past seasons —
     | the sync command reports the exact plan error when that happens.
     */
    'odds' => [
        'bookmaker' => env('API_FOOTBALL_ODDS_BOOKMAKER'),
        'season'    => env('API_FOOTBALL_ODDS_SEASON'),
    ],

    'cache' => [
        'transfers' => (int) env('API_FOOTBALL_TTL_TRANSFERS', 86400),
        'lookup'    => (int) env('API_FOOTBALL_TTL_LOOKUP', 604800), // team-id lookups rarely change
    ],
];
