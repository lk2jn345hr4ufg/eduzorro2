# Sport module (/sport) — install guide

Adds a localized `/{region}/{lang}/sport` section:

```
/{region}/{lang}/sport                                   list of sports
/{region}/{lang}/sport/football                          countries
/{region}/{lang}/sport/football/{country}                teams
/{region}/{lang}/sport/football/{country}/{team}         team hub → News
        …/{team}/fixtures   …/euro-cups   …/transfers   …/standings
```

Football data (fixtures, standings, transfers, euro cups) comes from
**API-Football** (api-sports.io), read live and cached. The taxonomy
(countries, teams) is imported into your DB so URLs/slugs are stable and
indexable. **News is stored locally** and edited in the admin — API-Football
has no news endpoint (see Caveats).

## 1. Files
Unzip over the project root, keeping paths. New/changed:

- `config/football.php`  *(new)* — API key, season, leagues, euro cups, cache TTLs
- `routes/web.php`  *(modified)* — sport routes added before the `/{industry}` wildcard
- `app/Models/{Sport,SportCountry,Team,TeamNews}.php`  *(new)*
- `app/Services/Football/ApiFootballClient.php`  *(new)*
- `app/Console/Commands/SyncFootball.php`  *(new)*
- `app/Http/Controllers/{Sport,Football,Team}Controller.php`  *(new)*
- `app/Filament/Resources/{Sport,SportCountry,Team,TeamNews}Resource.php` + Pages  *(new)*
- `database/migrations/2025_08_06_00000{1..4}_*.php`  *(new)*
- `database/seeders/SportSeeder.php`  *(new)*
- `lang/{en,uk,ru,es}/sport.php`  *(new)*
- `resources/views/sport/**`  *(new)*
- `public/css/sport.css`  *(new)* — loaded by the views via @push('head')

## 2. Environment
Add to `.env` (get a key at dashboard.api-football.com):

```
API_FOOTBALL_KEY=your_key_here
API_FOOTBALL_SEASON=2024
# RapidAPI users only:
# API_FOOTBALL_BASE_URL=https://api-football-v1.p.rapidapi.com/v3
# API_FOOTBALL_HOST=api-football-v1.p.rapidapi.com
```

Tune the leagues to import in `config/football.php` (`leagues` array). Keep the
list small on the free plan — it's rate-limited.

## 3. Run
```
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\SportSeeder
php artisan sport:sync-football           # imports countries + teams
php artisan cache:clear
```
Re-run `sport:sync-football` whenever you change the leagues list or the season.
On production add `--force` to migrate.

If OPcache is on, reload PHP-FPM so new classes load:
```
sudo systemctl reload php8.3-fpm
```

## 4. Add a nav link (optional)
In `resources/views/layouts/app.blade.php`, inside the localized nav:
```blade
@if (isset($currentRegion))
    <a href="{{ route('sport.index', [$currentRegion, $currentLanguage]) }}">{{ __('sport.sports') }}</a>
@endif
```

## 5. Sitemap (optional, recommended for SEO)
In `SitemapController`, add sport URLs per region/language:
`sport.index`, `sport.football.countries`, one `sport.football.country` per
active `SportCountry`, and one `sport.team` per active `Team` — mirroring how
industries/categories are already emitted.

## Caveats
- **News**: API-Football has no news feed, so `team_news` is manual (admin →
  Sport → Team news). To automate later, add a NewsProvider (e.g. GNews /
  NewsAPI) and feed the same table — the view already reads from it.
- **Euro cups** = fixtures whose competition id is in
  `config('football.euro_competitions')` (Champions/Europa/Conference). Adjust
  the ids if you want different competitions.
- **Standings** uses each team's `primary_league_api_id` (set during sync from
  the first league it was found in). Fix it per team in the admin if needed.
- **Rate limits**: tabs are cached (fixtures 15 min, standings 1 h, transfers
  24 h). The sync command does one `/teams` call per configured league.
- **Graceful degradation**: if the API key is missing or upstream fails, tabs
  show a "temporarily unavailable" notice instead of erroring.
