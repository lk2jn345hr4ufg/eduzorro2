# Fixtures: calendar + automatic season fallback

Two changes to the Fixtures tab.

## 1. Why it was empty (fixed)
The page asks for the season set in Settings (2025), but the matches were synced
under 2023 — so the query found nothing, and the live-API fallback also returned
nothing for 2025.

Now the controller checks which seasons actually exist in the database for that
team and, if the configured season has no data, falls back to the newest season
that does. The page stops depending on the setting being in sync.

You should still set Settings -> Season = 2023 so future syncs and the standings
tab agree, but the fixtures page no longer breaks if they drift.

## 2. Calendar
The tab now shows a month calendar next to the match list:
- days with matches are highlighted (upcoming vs played get different shading),
  today is outlined, and a dot marks match days;
- click a day to filter the list to it, click again to clear;
- month arrows navigate; the calendar opens on the month of the next upcoming
  match, or the last played one if the season is over;
- filter buttons: All matches / Upcoming / Results;
- each row shows date, H/A badge, teams, score (or kick-off time) and competition;
- weekday names and month titles are localised from the page language, Monday-first.

Everything runs client-side on data already loaded — no extra API calls.

## Files (extract over project root, keep paths)
- app/Http/Controllers/TeamController.php            (modified: season fallback + payload)
- resources/views/sport/partials/fixtures.blade.php  (rewritten: calendar UI)
- public/css/sport.css                               (modified: calendar styles)
- lang/{en,uk,ru,es}/sport.php                       (modified: 3 new keys)

## Apply - local
```
unzip -o ~/Downloads/fixtures-calendar.zip -d /tmp/fx-unzip
cp -a /tmp/fx-unzip/fixtures-calendar/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/fx-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Add fixtures calendar and fall back to a season with data"
git push
```

## Apply - server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
```

Then hard-refresh the page (sport.css is browser-cached), and remember the
stylesheet still needs to be reachable at /css/sport.css - if it 404s, the
calendar will render unstyled.

## Notes
- No migration; reads the fixtures already in your database.
- Teams with no synced fixtures still show the "no fixtures" message - sync them:
  `php artisan sport:sync-stats --type=fixtures --country=england --season=2023 --limit=10 --sleep=1`
