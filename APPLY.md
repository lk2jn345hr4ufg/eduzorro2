# Import only for countries already created

Changes the football import so it imports teams ONLY for countries that already
exist in the admin. Countries are no longer auto-created by default.

## Behaviour
- sport:sync-football now looks up each team's country in sport_countries
  (matched by "API name", then slug). If the country was not created in the
  admin, its teams are skipped. At the end it lists the skipped countries so you
  know which to create.
- If NO countries exist yet, the command stops with a hint (nothing to import).
- Opt back into the old behaviour with --create-countries (CLI) or the new
  "Create missing countries" toggle on the Data sync button.

## Workflow
1. Admin -> Sport -> Countries -> create the countries you want. Set each one's
   "API name" to the API-Football country name (e.g. England, Spain, Ukraine).
2. Admin -> Sport -> Data sync -> "Import teams & countries" (toggle OFF).
   Only teams for your created countries are imported.

## Files (extract over project root, keep paths)
- app/Console/Commands/SyncFootball.php   (modified)
- app/Filament/Pages/DataSync.php         (modified: create-countries toggle)

No migration. Apply:
1. Unzip over project root (overwrite).
2. php artisan optimize:clear
