# Switch from api-sports to football-data.org

Replaces API-Football (api-sports.io) with football-data.org (v4) as the source
for teams, fixtures and standings.

## Read this first — two things you lose

1. **No Ukrainian Premier League.** football-data.org covers 12 competitions
   (PL, PD, SA, BL1, FL1, DED, PPL, BSA, ELC, CL, EC, WC). The UPL is not among
   them, so your 18 Ukrainian clubs will get no new fixtures or standings.
   Their already-synced data stays in the database and keeps rendering.
2. **No transfers endpoint.** football-data.org does not expose transfers at
   all. The transfers tab keeps showing the ~3,100 rows already imported from
   api-sports, but they will no longer refresh. `sport:sync-stats --type=transfers`
   now prints a notice and skips.

What you gain: the **current season** instead of being stuck on 2023.

## What changed
- `config/football.php` — token, base URL, competitions keyed by code, euro cups.
- `ApiFootballClient` — rewritten against football-data.org, but it **normalises
  responses into the same internal shape** the app already used, so the views,
  the fixtures calendar and the controllers needed almost no changes.
- `sport:sync-football` — imports teams per competition code, with `--competition`
  and a `--sleep` default of 7s (free tier allows ~10 requests/minute).
- `sport:sync-stats` — fixtures and standings via the new API; standings are
  fetched by competition code.
- New columns: `teams.primary_league_code`, `fixtures.league_code`,
  `standings.league_code` — the API addresses competitions by code while our
  tables join on numeric ids, so both are stored.
- Admin Settings — the API-Football section became **football-data.org** with an
  `X-Auth-Token` field.

## Apply — local
```
unzip -o ~/Downloads/football-data-migration.zip -d /tmp/fd-unzip
cp -a /tmp/fd-unzip/football-data-migration/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/fd-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan migrate
php artisan optimize:clear
git add .
git commit -m "Switch football data source to football-data.org"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan migrate --force
php artisan optimize:clear
```

## Configure and load data
1. Get a free token: https://www.football-data.org/client/register
2. Admin → Sport → Settings → **football-data.org** → paste the token, leave
   Season empty (or set the current start year, e.g. 2025). Save.
   Or in .env: `FOOTBALL_DATA_TOKEN=...` then `php artisan config:clear`.
3. Import teams (one competition at a time is gentlest on the rate limit):
```
php artisan sport:sync-football --competition=PL --create-countries
php artisan sport:sync-football --competition=PD --create-countries
```
4. Load fixtures and standings:
```
php artisan sport:sync-stats --type=fixtures --country=england --limit=10 --sleep=7
php artisan sport:sync-stats --type=standings --country=england --limit=1
```

## Notes
- Team `api_id` values are football-data ids, which differ from api-sports ids.
  Re-running `sport:sync-football` updates existing rows (matched on country +
  slug), so teams keep their URLs but point at the new ids. Old fixtures keyed to
  api-sports ids stay in the table but stop matching — clear them if you want a
  clean slate: `php artisan tinker --execute="App\Models\Fixture::truncate();"`
- Rate limit is ~10 requests/minute: always use `--sleep` and a small `--limit`.
- The season fallback added earlier still applies: if the configured season has
  no rows, the fixtures tab shows the newest season that does.
