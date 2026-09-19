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
];
