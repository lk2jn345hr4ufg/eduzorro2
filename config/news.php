<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Team news provider
     |--------------------------------------------------------------------------
     | API-Football has no news feed, so team news is pulled from a keyword
     | news API (searched by team name) and stored in the `team_news` table by
     | the `sport:sync-news` command. Supported providers: "gnews" (gnews.io)
     | and "newsapi" (newsapi.org).
     */
    'provider' => env('NEWS_PROVIDER', 'gnews'),
    'key'      => env('NEWS_API_KEY'),

    // Language of the fetched articles. Stored as this locale key in the JSON
    // title/excerpt/body, so it should match one of the site locales (en/uk/ru/es).
    'language' => env('NEWS_LANGUAGE', 'en'),

    // Appended to the team name to focus the search, e.g. "Arsenal football".
    'query_suffix' => env('NEWS_QUERY_SUFFIX', ' football'),

    // How many articles to keep per team per run.
    'per_team' => (int) env('NEWS_PER_TEAM', 6),

    'timeout' => (int) env('NEWS_TIMEOUT', 12),

    'base_url' => [
        'gnews'   => 'https://gnews.io/api/v4',
        'newsapi' => 'https://newsapi.org/v2',
    ],
];
