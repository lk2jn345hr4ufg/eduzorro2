# SEO screen in the admin: meta tags for static pages

Adds a dedicated **SEO → Meta tags** screen where you edit the title and
description of pages that have no database record of their own — the ones the
previous package could not cover because their text came from language files.

## Pages covered here
- Home page (global `/`)
- Region home (`/{region}/{language}`)
- Study tools landing (`/{language}/tools`)
- Sport section (`/{region}/{language}/sport`)
- Sports news feed (`/{region}/{language}/sport/news`)
- Football countries (`/{region}/{language}/sport/football`)

Each page gets a tab, and inside it a tab per active language with Meta title
and Meta description.

## How it fits with the per-record SEO section
Two places, no overlap:
- **SEO → Meta tags** (this screen): pages without a record.
- **SEO section inside a resource form**: regions, industries, categories,
  sports, countries, teams, study tools, team news.

On the region home both apply, in a sensible order: the region's own override
wins, and this screen's value is the default for regions that have none.

## Behaviour
- Empty field = keep the generated tag. Nothing changes until you type something.
- Values are stored in the existing `settings` table as JSON per page, so there
  is **no migration** and saving takes effect immediately.
- Saving strips blank locales rather than storing empty strings, so a partially
  filled page never ends up with a blank `<title>`.
- Overrides feed the Open Graph tags too, since `og:title` / `og:description`
  reuse the same Blade sections.

## Files (extract over project root, keep paths)
- config/seo_pages.php                              (new: the page registry)
- app/Filament/Pages/SeoMeta.php                    (new: the admin screen)
- resources/views/filament/pages/seo-meta.blade.php (new)
- app/Support/Seo.php                               (modified: pageMeta() helper)
- resources/views/home.blade.php                    (modified)
- resources/views/region-language.blade.php         (modified)
- resources/views/tools/index.blade.php             (modified)
- resources/views/sport/index.blade.php             (modified)
- resources/views/sport/countries.blade.php         (modified)
- resources/views/sport/news/index.blade.php        (modified)

Requires the previous package (seo-meta-editing) for the region override on the
region home; everything else here works standalone.

## Apply — local
```
unzip -o ~/Downloads/seo-meta-page.zip -d /tmp/seo2-unzip
cp -a /tmp/seo2-unzip/seo-meta-page/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/seo2-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Add admin screen for editing meta tags of static pages"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
```

Then open /admin → SEO → Meta tags. The page is auto-discovered, nothing to
register.

## Adding another page later
Add an entry to `config/seo_pages.php` and use it in the view:
```blade
@section('title', \App\Support\Seo::pageMeta('my_key', 'title', 'generated default'))
```
It appears on the admin screen automatically.
