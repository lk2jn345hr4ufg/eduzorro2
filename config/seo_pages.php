<?php

/*
|--------------------------------------------------------------------------
| Editable static pages
|--------------------------------------------------------------------------
| Pages whose meta tags come from language files rather than a database
| record. The admin SEO screen builds itself from this list.
|
| "key" becomes the settings key (seo_page_{key}); "sample" is only shown in
| the admin as a hint of which URL is affected; "heading" adds an H1 field.
*/

return [
    'home' => [
        'label'   => 'Home (football dashboard)',
        'sample'  => '/{language}',
        'heading' => true,
    ],
    'sport_news' => [
        'label'  => 'News feed',
        'sample' => '/{language}/news',
    ],
    'tools_index' => [
        'label'  => 'Study tools landing (unlinked)',
        'sample' => '/{language}/tools',
    ],

    /*
     | Competition tabs. Placeholders: {competition}, {country}, {season},
     | {site}, {tab}. A per-competition override beats these templates.
     */
    'competition_dashboard' => [
        'label'   => 'Competition · Dashboard',
        'sample'  => '/{language}/{competition}',
        'heading' => true,
    ],
    'competition_standings' => [
        'label'   => 'Competition · Standings',
        'sample'  => '/{language}/{competition}/standings',
        'heading' => true,
    ],
    'competition_fixtures' => [
        'label'   => 'Competition · Fixtures',
        'sample'  => '/{language}/{competition}/fixtures',
        'heading' => true,
    ],
    'competition_results' => [
        'label'   => 'Competition · Results',
        'sample'  => '/{language}/{competition}/results',
        'heading' => true,
    ],
    'competition_teams' => [
        'label'   => 'Competition · Teams',
        'sample'  => '/{language}/{competition}/teams',
        'heading' => true,
    ],
    'competition_transfers' => [
        'label'   => 'Competition · Transfers',
        'sample'  => '/{language}/{competition}/transfers',
        'heading' => true,
    ],

    /*
     | Team tabs. Placeholders: {team}, {competition}, {country}, {site},
     | {tab}. A per-team override (Teams → SEO per tab) beats these.
     */
    'team_dashboard' => [
        'label'   => 'Team · Dashboard',
        'sample'  => '/{language}/team/{team}',
        'heading' => true,
    ],
    'team_news' => [
        'label'   => 'Team · News',
        'sample'  => '/{language}/team/{team}/news',
        'heading' => true,
    ],
    'team_standings' => [
        'label'   => 'Team · Standings',
        'sample'  => '/{language}/team/{team}/standings',
        'heading' => true,
    ],
    'team_euro_cups' => [
        'label'   => 'Team · European cups',
        'sample'  => '/{language}/team/{team}/euro-cups',
        'heading' => true,
    ],
    'team_transfers' => [
        'label'   => 'Team · Transfers',
        'sample'  => '/{language}/team/{team}/transfers',
        'heading' => true,
    ],
    'team_fixtures' => [
        'label'   => 'Team · Fixtures',
        'sample'  => '/{language}/team/{team}/fixtures',
        'heading' => true,
    ],
    'team_results' => [
        'label'   => 'Team · Results',
        'sample'  => '/{language}/team/{team}/results',
        'heading' => true,
    ],
];
