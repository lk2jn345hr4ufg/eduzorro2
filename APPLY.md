# Store fixtures, standings & transfers in the database

Until now fixtures, standings and transfers were fetched live from API-Football
on every page view (cached only). Now they are stored in your database and
synced on demand from the admin, so pages render from local data.

## New admin button
Admin -> Sport -> Data sync -> **Sync fixtures, standings & transfers**, with:
- What to sync: Everything / Fixtures / Standings / Transfers
- Country (dropdown), Team slug, Season, Limit, Sleep

CLI equivalent:
```
php artisan sport:sync-stats                                  # everything, all teams
php artisan sport:sync-stats --country=ukraine --limit=20
php artisan sport:sync-stats --type=standings --season=2023
php artisan sport:sync-stats --type=transfers --team=dynamo-kyiv
```

## Files (extract over project root, keep paths)
- database/migrations/2025_08_06_000007_create_fixtures_table.php   (new)
- database/migrations/2025_08_06_000008_create_standings_table.php  (new)
- database/migrations/2025_08_06_000009_create_transfers_table.php  (new)
- app/Models/Fixture.php, Standing.php, Transfer.php                (new)
- app/Console/Commands/SyncFootballStats.php                        (new)
- app/Http/Controllers/TeamController.php                           (modified: reads DB)
- app/Filament/Pages/DataSync.php                                   (modified: new button)
- resources/views/filament/pages/data-sync.blade.php                (modified: docs)

## Apply — local
```
unzip -o ~/Downloads/store-football-stats.zip -d /tmp/stats-unzip
cp -a /tmp/stats-unzip/store-football-stats/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/stats-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan migrate
php artisan optimize:clear
git add .
git commit -m "Store fixtures, standings and transfers in DB with admin sync"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan migrate --force
php artisan optimize:clear
```
Then: admin -> Sport -> Data sync -> Sync fixtures, standings & transfers.

## Notes
- **Views are unchanged.** DB rows are re-shaped into the same payload the
  templates already expect, so the transfers layout and the other tabs keep working.
- **Graceful fallback**: a team with nothing synced yet still falls back to a live
  API read, so no page breaks before the first sync.
- **Standings are per league**, not per team, so each league is fetched once per
  run no matter how many of its teams you sync.
- **Quota safety**: the command stops after 5 consecutive empty API responses
  (dead key, wrong season, or exhausted quota) instead of burning the rest.
- **Season matters**: standings/fixtures are stored per season. Make sure
  Settings -> Season matches a season your API plan covers (2023 on the free tier).
- Refresh cadence: fixtures after each matchday, standings weekly, transfers during
  the transfer windows.
