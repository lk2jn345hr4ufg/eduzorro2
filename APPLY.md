# Custom H1 for the team tab pages

The five team pages (News, Fixtures, European cups, Transfers, Standings) all
showed the same `<h1>` — the team name. The meta tags now differ per tab, so the
visible heading should too: it is the strongest on-page signal of what a URL is
about, and five identical headings undercut the distinct titles.

## Where you edit it

**Global template — SEO → Meta tags**
Each of the five team tabs now has an **H1 heading** field alongside the meta
fields, per language, with the same placeholders:

- `{team}`, `{country}`, `{site}`, `{tab}`

Example for the Standings tab (RU):
```
H1: Турнирная таблица — {team}
```
One entry covers every team.

**Per-team override — Teams → edit → "SEO per tab"**
An H1 field sits above the meta fields in each tab, for clubs that need their
own wording.

## Resolution order
per-team tab H1 → global tab template → the team name (as before).

Nothing changes visually until a field is filled in, so this is safe to deploy
before writing any templates.

## Design note
`tabMeta()` treats a heading differently from the meta fields on purpose: an
empty H1 does **not** fall back to the team's meta title. A title is written for
a search result ("Liverpool — расписание матчей | EduZorro") and would look wrong
as a page heading, so the heading falls through to the plain team name instead.

## Files (extract over project root, keep paths)
- config/seo_pages.php                     (modified: heading flag on the 5 tabs)
- app/Models/Team.php                      (modified: heading-aware tabMeta)
- app/Filament/Pages/SeoMeta.php           (modified: H1 field, prefill, save)
- app/Filament/Resources/TeamResource.php  (modified: per-team H1 field)
- resources/views/sport/team.blade.php     (modified: renders the resolved H1)

Requires the earlier SEO packages (seo-meta-editing, seo-meta-page,
seo-team-tabs). No migration — the value is stored in the JSON columns those
packages already added.

## Apply — local
```
unzip -o ~/Downloads/custom-h1.zip -d /tmp/h1-unzip
cp -a /tmp/h1-unzip/custom-h1/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/h1-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Add editable H1 for team tab pages"
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
curl -s https://eduzorro.com/ru/sport/football/england/liverpool/standings | grep -o '<h1>[^<]*</h1>'
curl -s https://eduzorro.com/ru/sport/football/england/liverpool/fixtures  | grep -o '<h1>[^<]*</h1>'
```
Once templates are filled in, these differ.

## Adding an editable H1 to another page later
Set `'heading' => true` on its entry in `config/seo_pages.php`, then render
`\App\Support\Seo::pageMeta('key', 'heading', $default, $tokens)` in the view.
The field appears on the admin screen automatically.
