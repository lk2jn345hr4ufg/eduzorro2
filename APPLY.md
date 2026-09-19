# Sport moves to language-only URLs

The sport section no longer carries the visitor's region. A club belongs to its
own country, which is already a segment of the URL, so the region added nothing
but a duplicate copy of identical content for every region.

```
before:  https://eduzorro.com/ukraine/ru/sport/football/england/liverpool/standings
after:   https://eduzorro.com/ru/sport/football/england/liverpool/standings
```

Every old URL 301-redirects to the new one, so indexed links and any external
links keep working.

## Why this matters beyond tidiness
With N regions, each team page existed N times over with the same content. Those
were competing duplicates in search. Now there is exactly one canonical URL per
language, and the hreflang alternates still work because they key off the
`{language}` parameter.

## What changed
- **routes/web.php** — the eight sport routes moved into the language-only
  group (`/{language}/sport/...`), which already hosts the tools. Eight legacy
  routes in the region group now issue 301s.
  Parameters are written without an explicit key (`{team}`, not `{team:slug}`):
  the models declare `getRouteKeyName() = 'slug'`, and an explicit key after
  another bound parameter makes Laravel try to scope the child through a
  relationship that does not exist — the exact crash the tools pages hit.
- **Controllers** — `SportController`, `FootballController`, `SportNewsController`
  and `TeamController` no longer take a `Region`; breadcrumbs point at the global
  home instead of the region home.
- **Views** — every `route('sport.*', [$currentRegion, ...])` call dropped the
  region argument.
- **sport/index.blade.php** — its title used the region name, which is no longer
  shared on these pages; it now uses a new `sport.tagline` string.
- **home.blade.php** — the three sport chips moved out of the region cards into
  one standalone section, for the same reason the tools link did: the URL is the
  same for every region, so repeating it per card was noise.

## Files (extract over project root, keep paths)
- routes/web.php
- app/Http/Controllers/{Sport,Football,SportNews,Team}Controller.php
- resources/views/sport/**  (index, show, countries, teams, team, news/*, partials/*)
- resources/views/home.blade.php, region-language.blade.php
- lang/{en,uk,ru,es}/sport.php  (new `tagline` string)

No migration.

## Apply — local
```
unzip -o ~/Downloads/sport-language-url.zip -d /tmp/sporturl-unzip
cp -a /tmp/sporturl-unzip/sport-language-url/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/sporturl-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Move sport section to language-only URLs with 301s"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
```

## Check
```
curl -sI https://eduzorro.com/ukraine/ru/sport/football/england/liverpool/standings | head -3
# expect 301 -> /ru/sport/football/england/liverpool/standings

curl -sI https://eduzorro.com/ru/sport/football/england/liverpool/standings | head -3
# expect 200
```

## Note
The per-tab meta templates from the previous package are unaffected — they key
off the tab name, not the region. Worth re-checking the five Liverpool titles
after this deploy to confirm they are still distinct.
