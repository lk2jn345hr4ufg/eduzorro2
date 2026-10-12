<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Odds source for sport:sync-odds
     |--------------------------------------------------------------------------
     | "oddsapi"   — The Odds API (the-odds-api.com). Free plan: 500 credits a
     |               month; one request per competition costs 1 credit
     |               (1 market × 1 region) and requests with no events are free.
     | "apisports" — API-Football /odds. The free plan does not cover the
     |               current season.
     | Overridable in the admin: Settings → Odds.
     */
    'provider' => env('ODDS_PROVIDER', 'oddsapi'),

    'oddsapi' => [
        'key'      => env('ODDS_API_KEY'),
        'base_url' => rtrim(env('ODDS_API_BASE_URL', 'https://api.the-odds-api.com'), '/'),
        'timeout'  => (int) env('ODDS_API_TIMEOUT', 15),

        // Bookmaker region(s). Keep ONE region: every extra one multiplies
        // the credit cost of each request.
        'regions' => env('ODDS_API_REGIONS', 'eu'),

        // Preferred bookmakers, best first (keys as in the API, comma
        // separated). The first one offering the match is shown; if none of
        // them do, the first bookmaker returned is used.
        'bookmakers' => env('ODDS_API_BOOKMAKERS', 'pinnacle,betfair_ex_eu,unibet_eu,williamhill,marathonbet,onexbet,betsson'),
    ],
];
