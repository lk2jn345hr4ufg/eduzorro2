# Football section rebuilt as a match-day hub

`/{language}/sport/football` was a plain list of countries. It now works like a
live-score hub: the matches of one day, grouped by competition, with a strip of
nearby days and the country navigation alongside.

This is an original implementation on top of your own `fixtures` table — layout,
markup and wording are ours, not copied from any site.

## What the page does
- **Day strip**: three days either side of the selected one. Today is labelled
  as such; each day shows how many matches it has, so an empty day is visible
  before you click it. Selecting a day is a plain link (`?date=YYYY-MM-DD`), so
  every day is crawlable and shareable.
- **Matches grouped by competition**, ordered so the competitions configured in
  `config/football.php` come first and anything else follows.
- **Per match**: kick-off time, or the score for finished games, or a Live
  marker with a red dot for games in progress. Crests shown where available.
- **Sidebar**: every country with its team count (the old page's content, kept
  and still linking to the same country URLs), plus a link to the news feed.
- **Zero API calls** — it reads the fixtures already synced into the database.

## Files (extract over project root, keep paths)
- app/Http/Controllers/FootballController.php  (modified: day resolution, grouping, counts)
- resources/views/sport/countries.blade.php    (rewritten as the hub)
- public/css/sport.css                         (modified: hub styles)
- lang/{en,uk,ru,es}/sport.php                 (modified: 6 new strings)

No migration. The route and its name are unchanged, so existing links and the
SEO templates for this page keep working.

## Apply — local
```
unzip -o ~/Downloads/football-hub.zip -d /tmp/hub-unzip
cp -a /tmp/hub-unzip/football-hub/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/hub-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Rebuild the football section as a match-day hub"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
```

## If the page looks empty
That means no fixtures fall on the selected day, which is likely right now: the
only matches in your database are the English clubs you synced. Check what you
have and which days are populated:

```
php artisan tinker --execute="echo App\Models\Fixture::count().' fixtures'.PHP_EOL; foreach (App\Models\Fixture::selectRaw('date(kickoff_at) d, count(*) c')->groupBy('d')->orderByDesc('d')->limit(10)->get() as \$r) { echo \$r->d.': '.\$r->c.PHP_EOL; }"
```

Then load more competitions (each is one API call plus the teams call):
```
php artisan sport:sync-football --competition=PD --create-countries
php artisan sport:sync-stats --type=fixtures --country=spain --limit=10 --sleep=7
```

## Note on live scores
Matches show as Live only while the stored `status_short` says so, and that
value is only as fresh as your last sync. For genuinely live updates the
fixtures for today would need re-syncing every few minutes — worth a scheduled
command if you want that, but it will consume the football-data rate limit.
