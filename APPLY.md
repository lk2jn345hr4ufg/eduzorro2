# Fix: duplicate teams after the provider switch

Symptom: `sport:sync-football --competition=PL` reported "20 team(s) upserted",
but Manchester United still had `api_id: 33 | code: none`, and fixtures kept
failing with 403 on /teams/33/matches.

Cause: football-data spells clubs with a suffix ("Manchester United FC") while
the rows imported from api-sports did not ("Manchester United"). The importer
matched on the exact slug, so instead of updating the existing rows it created
20 near-duplicates (manchester-united-fc, arsenal-fc, ...). The stats sync then
picked the OLD rows, which still carried api-sports ids - hence the 403s, since
those ids point at restricted competitions on football-data.

## What this package changes
- `sport:sync-football` now matches an existing team on a suffix-stripped slug,
  in both prefix directions (old "newcastle" <-> new "newcastle-united"), and
  updates that row while KEEPING its slug - so URLs, sort order and attached
  news survive. No more duplicates on future syncs.
- New `sport:merge-teams` command cleans up the duplicates already created: for
  each new row it finds the matching old one, copies the new api_id, competition
  code, crest, venue and founded year into it, moves any news across, and deletes
  the duplicate.

## Apply - local
```
unzip -o ~/Downloads/football-data-team-merge.zip -d /tmp/merge-unzip
cp -a /tmp/merge-unzip/football-data-team-merge/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/merge-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Match teams on normalised slug and merge provider duplicates"
git push
```

## Apply - server, then clean up and load data
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear

# see what would be merged, without touching anything
php artisan sport:merge-teams --dry-run

# do it
php artisan sport:merge-teams

# check a team now carries football-data ids
php artisan tinker --execute="\$t=App\Models\Team::where('slug','manchester-united')->first(); echo \$t->api_id.' / '.\$t->primary_league_code.PHP_EOL;"
```
Manchester United should read `66 / PL`.

Then drop the stale fixtures/standings keyed to the old ids and reload:
```
php artisan tinker --execute="App\Models\Fixture::truncate(); App\Models\Standing::truncate(); echo 'cleared';"
php artisan sport:sync-stats --type=fixtures --country=england --limit=10 --sleep=7
php artisan sport:sync-stats --type=standings --country=england --limit=1
```

## Notes
- Leave Settings -> Season EMPTY. The free tier only serves the current season,
  and passing an explicit season often returns 403.
- Transfers are untouched: they are still keyed to the old api-sports ids, but
  that data has no other source now, so the merge deliberately leaves it alone.
- Ukrainian teams have no competition code and are not affected by the merge.
