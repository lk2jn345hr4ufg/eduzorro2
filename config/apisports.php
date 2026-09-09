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

    'cache' => [
        'transfers' => (int) env('API_FOOTBALL_TTL_TRANSFERS', 86400),
        'lookup'    => (int) env('API_FOOTBALL_TTL_LOOKUP', 604800), // team-id lookups rarely change
    ],
];
