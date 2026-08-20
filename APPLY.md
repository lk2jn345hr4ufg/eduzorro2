# Sync team news by country

Adds a Country filter to the news tools, so you can sync (or rewrite) news for
just one country's teams instead of all of them.

## What changed
- Admin -> Sport -> Data sync -> **Sync team news**: new **Country** dropdown
  (searchable, lists your existing countries; leave empty for all countries).
- Same Country dropdown added to **Rewrite & translate news (Gemini)**.
- CLI gains a matching option on both commands:
    php artisan sport:sync-news --country=ukraine
    php artisan sport:sync-news --country=england --limit=10 --sleep=1
    php artisan sport:rewrite-news --country=ukraine --limit=20
- Country is matched on the country **slug** (as shown in Sport -> Countries).
- Filters combine: country + team + limit + sleep all apply together.
- The "no teams" warning now hints at the likely cause (wrong slug, or teams
  not imported yet).

## Files (extract over project root, keep paths)
- app/Console/Commands/SyncTeamNews.php   (modified: --country)
- app/Console/Commands/RewriteNews.php    (modified: --country)
- app/Filament/Pages/DataSync.php         (modified: Country selects)

No migration.

## Apply
1. Unzip over the project root (overwrite).
2. php artisan optimize:clear

## Why this helps
News API free tiers are rate-limited, so syncing 118 teams in one go burns the
quota. Running country by country (e.g. ukraine today, england tomorrow) keeps
each run small and predictable.
