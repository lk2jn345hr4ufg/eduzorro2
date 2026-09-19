<?php

/*
|--------------------------------------------------------------------------
| Editable static pages
|--------------------------------------------------------------------------
| Pages whose meta tags come from language files rather than a database
| record, so they cannot be edited through a resource form. The admin SEO
| screen builds itself from this list: add an entry here and the page shows
| up there automatically.
|
| "key" becomes the settings key (seo_page_{key}); "sample" is only shown in
| the admin as a hint of which URL is affected.
*/

return [
    'home' => [
        'label'  => 'Home page (global)',
        'sample' => '/',
    ],
    'region_home' => [
        'label'  => 'Region home',
        'sample' => '/{region}/{language}',
    ],
    'tools_index' => [
        'label'  => 'Study tools landing',
        'sample' => '/{language}/tools',
    ],
    'sport_index' => [
        'label'  => 'Sport section',
        'sample' => '/{region}/{language}/sport',
    ],
    'sport_news' => [
        'label'  => 'Sports news feed',
        'sample' => '/{region}/{language}/sport/news',
    ],
    'football_countries' => [
        'label'  => 'Football countries',
        'sample' => '/{region}/{language}/sport/football',
    ],
    /*
     | Team tabs. One Team record powers five URLs, so without separate
     | templates all five would share one title. Placeholders: {team},
     | {country}, {site}. A per-team override (Teams → SEO per tab) beats
     | these templates when it is filled in.
     */
    'team_news' => [
        'label'  => 'Team · News',
        'sample' => '/…/sport/football/{country}/{team}',
    ],
    'team_fixtures' => [
        'label'  => 'Team · Fixtures',
        'sample' => '/…/{team}/fixtures',
    ],
    'team_euro_cups' => [
        'label'  => 'Team · European cups',
        'sample' => '/…/{team}/euro-cups',
    ],
    'team_transfers' => [
        'label'  => 'Team · Transfers',
        'sample' => '/…/{team}/transfers',
    ],
    'team_standings' => [
        'label'  => 'Team · Standings',
        'sample' => '/…/{team}/standings',
    ],
];
