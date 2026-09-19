# Custom meta tags per team tab

A team's five pages — News, Fixtures, European cups, Transfers, Standings — are
all driven by one `Team` record, so until now they shared a single title and
description. Five URLs with identical tags is exactly what search engines treat
as duplicates. This gives each tab its own.

## Two levels, so you don't edit 100 teams by hand

**1. Global templates — SEO → Meta tags**
Five new tabs: Team · News, Team · Fixtures, Team · European cups,
Team · Transfers, Team · Standings. Each takes a title and description per
language, with placeholders filled in per team:

- `{team}` — team name
- `{country}` — country name
- `{site}` — site name
- `{tab}` — translated tab name

Example for the Fixtures tab (RU):
```
Title:       {team} — расписание матчей {country} | {site}
Description: Календарь игр {team}: ближайшие матчи, результаты и турниры сезона.
```
One entry covers every team.

**2. Per-team override — Teams → edit → "SEO per tab"**
A collapsed section with a tab per page and a language tab inside, for the cases
where one club needs wording of its own.

## Resolution order
per-team tab override → team-wide SEO → global tab template → generated default.
Anything left empty simply falls through, so partial edits are safe.

## Files (extract over project root, keep paths)
- database/migrations/2025_08_06_000015_add_meta_tabs_to_teams.php (new)
- config/seo_pages.php                       (modified: 5 team tab entries)
- app/Support/Seo.php                        (modified: placeholder substitution)
- app/Models/Team.php                        (modified: meta_tabs cast + tabMeta())
- app/Filament/Resources/TeamResource.php    (modified: "SEO per tab" section)
- resources/views/sport/team.blade.php       (modified: resolution chain)

Requires the two earlier SEO packages (seo-meta-editing, seo-meta-page).

## Apply — local
```
unzip -o ~/Downloads/seo-team-tabs.zip -d /tmp/seo3-unzip
cp -a /tmp/seo3-unzip/seo-team-tabs/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/seo3-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan migrate
php artisan optimize:clear
git add .
git commit -m "Add per-tab meta tags for team pages"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan migrate --force
php artisan optimize:clear
```

## Check
Open the five Liverpool URLs and compare `<title>`:
```
curl -s https://eduzorro.com/ukraine/ru/sport/football/england/liverpool | grep -o '<title>[^<]*</title>'
curl -s https://eduzorro.com/ukraine/ru/sport/football/england/liverpool/fixtures | grep -o '<title>[^<]*</title>'
```
They should now differ.

## Note
The same pattern works for any other multi-tab page later: add an entry to
config/seo_pages.php and call
`Seo::pageMeta('key', 'title', $default, ['team' => ..., 'country' => ...])`.
