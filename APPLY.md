# Pattern-based 301s from the old region URLs

The sport and tools sections moved out of `/{region}/{language}/…`. The route
level already redirects the common case, but it has a gap worth closing.

## The gap
Those legacy routes live *inside* the region group, so they only fire when the
region segment still resolves to a live `Region` model. An old link pointing at
a region that was since renamed, deactivated or deleted never reaches them — it
404s and the link is lost. Exactly the URLs most likely to exist in search
results and on other sites.

This adds a middleware that runs **before routing** and works on the raw path,
so the redirect happens regardless of what became of the region.

## Rules
```
/{anything}/{xx}/sport/...  ->  /{xx}/sport/...
/{anything}/{xx}/tools/...  ->  /{xx}/tools/...
```

Verified behaviour:

| Old URL | Result |
|---|---|
| `/ukraine/ru/sport/football/england/liverpool/standings` | 301 → `/ru/sport/football/england/liverpool/standings` |
| `/spain/es/sport/news/some-article` | 301 → `/es/sport/news/some-article` |
| `/deleted-region/uk/sport/football` | 301 → `/uk/sport/football` |
| `/ukraine/ru/tools/gpa-calculator` | 301 → `/ru/tools/gpa-calculator` |
| `/ru/sport/football/england/liverpool` | untouched (no loop) |
| `/ukraine/ru/businesses` | untouched |
| `/ukraine/ru/directory/schools` | untouched |

The language group is two letters, so a path that already starts with a language
can never match — no redirect loops. Query strings are preserved.

## Ordering
`HandleRedirects` (the admin-managed redirects table) still runs first, so a
rule you create in the admin always beats these generic patterns. The existing
route-level legacy redirects are left in place as a fallback; they simply stop
being reached.

## Files (extract over project root, keep paths)
- app/Http/Middleware/RedirectLegacyPaths.php  (new)
- bootstrap/app.php                            (modified: registers it)

No migration.

## Apply — local
```
unzip -o ~/Downloads/legacy-redirects.zip -d /tmp/legacy-unzip
cp -a /tmp/legacy-unzip/legacy-redirects/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/legacy-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Add pattern-based 301s from old region-scoped sport and tools URLs"
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
curl -sI https://eduzorro.com/nonexistent-region/ru/sport | head -3
curl -sI https://eduzorro.com/ru/sport | head -3
```
First two: `301` with the new `location`. Third: `200`, unchanged.

## Adding another moved section later
Add one line to `RULES` in the middleware — it covers every region and language
at once, which is why this lives in code rather than as thousands of rows in the
redirects table.
