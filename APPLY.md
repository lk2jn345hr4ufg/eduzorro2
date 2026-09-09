# Transfers via api-sports (hybrid setup)

football-data.org has no transfers endpoint, so transfers — and only transfers —
now come from API-Football (api-sports.io). Teams, fixtures and standings stay
on football-data.org.

## How the two providers are joined
The two APIs use different team ids. Each team now stores both:
- `teams.api_id` — football-data id (fixtures, standings)
- `teams.apisports_id` — api-sports id (transfers), resolved once by club name
  and cached on the row.

The first run of the sync spends one lookup call per team to fill
`apisports_id`; after that it goes straight to `/transfers`. Transfer rows
already imported before the provider switch are keyed to those same api-sports
ids, so they light up again automatically once a team is linked.

## Files (extract over project root, keep paths)
- config/apisports.php                                   (new)
- app/Services/Football/ApiSportsTransfersClient.php     (new)
- app/Console/Commands/SyncTransfers.php                 (new)
- database/migrations/..._add_apisports_id_to_teams.php  (new)
- app/Http/Controllers/TeamController.php                (modified: reads by apisports_id)
- resources/views/sport/partials/transfers.blade.php     (modified: direction by apisports_id)
- app/Filament/Pages/Settings.php                        (modified: transfers key field)
- app/Filament/Pages/DataSync.php                        (modified: transfers sync button)
- app/Providers/SettingsServiceProvider.php              (modified: maps the key)
- app/Console/Commands/SyncFootballStats.php             (modified: points at the new command)

## Apply — local
```
unzip -o ~/Downloads/transfers-apisports.zip -d /tmp/tr-unzip
cp -a /tmp/tr-unzip/transfers-apisports/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/tr-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan migrate
php artisan optimize:clear
git add .
git commit -m "Add api-sports transfers alongside football-data.org"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan migrate --force
php artisan optimize:clear
```

## Configure and load
1. Admin → Sport → Settings → **Transfers (api-sports.io)** → paste your existing
   API-Football key → Save. (Or keep `API_FOOTBALL_KEY` in .env — the admin value
   wins when both are set.)
2. Link teams and pull transfers, a country at a time:
```
php artisan sport:sync-transfers --country=england --limit=15 --sleep=1
```
Or use the admin: **Data sync → Sync transfers (api-sports)**.

3. Verify a team got linked:
```
php artisan tinker --execute="\$t=App\Models\Team::where('slug','manchester-united')->first(); echo \$t->api_id.' fd / '.\$t->apisports_id.' as'.PHP_EOL;"
```
Expect something like `66 fd / 33 as`.

## Notes
- `/transfers` takes no season parameter, which is why it kept working on your
  free api-sports plan even when fixtures there were stuck on 2023.
- Name matching strips club suffixes ("Manchester United FC" → "Manchester
  United"). If a team links to the wrong club, fix `apisports_id` in the admin or
  re-run with `--relink`.
- The command stops after five consecutive empty responses, so an exhausted
  api-sports quota fails loudly instead of silently writing nothing.
- Ukrainian teams have no football-data coverage but their transfers still work,
  since that lookup is name-based and independent of football-data.
