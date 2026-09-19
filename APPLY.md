# Editable meta tags in the admin

Adds an optional per-page **SEO** section to the admin, where you can override
the `<title>` and `<meta name="description">` for individual records, in every
site language.

## Where it appears
A collapsed "SEO" section at the bottom of the edit form for:

- Regions
- Industries
- Categories
- Sports
- Countries (sport)
- Teams
- Study tools
- Team news

Each has a tab per active language for **Meta title** and **Meta description**.

## How it behaves
- Both fields are **optional overrides**. Left empty, the page keeps the title
  and description it already generates from its own content — so installing this
  changes nothing visible until someone fills a field in.
- Overrides are per language: filling only the Russian meta title leaves the
  other languages on their generated values.
- The override also feeds the Open Graph tags, since `og:title` and
  `og:description` reuse the same sections in the layout.

## Files (extract over project root, keep paths)
- database/migrations/2025_08_06_000014_add_seo_meta_columns.php (new)
- app/Support/HasSeoMeta.php                    (new: metaTitle/metaDescription)
- app/Filament/Support/SeoFields.php            (new: the reusable form section)
- app/Models/{Region,Industry,Category,Sport,SportCountry,Team,Tool,TeamNews}.php (modified: trait + casts)
- app/Filament/Resources/{...the 8 matching resources}.php (modified: SEO section)
- resources/views/{industry,category,region-language}.blade.php (modified)
- resources/views/tools/show.blade.php          (modified)
- resources/views/sport/{show,teams,team}.blade.php (modified)
- resources/views/sport/news/show.blade.php     (modified)

## Apply — local
```
unzip -o ~/Downloads/seo-meta-editing.zip -d /tmp/seo-unzip
cp -a /tmp/seo-unzip/seo-meta-editing/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/seo-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan migrate
php artisan optimize:clear
git add .
git commit -m "Add editable meta title and description per page"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan migrate --force
php artisan optimize:clear
```

## Notes
- The migration is written to skip any table that already has the columns, so it
  is safe to re-run.
- Length guidance shown in the form: ~50–60 characters for the title, ~140–160
  for the description. Nothing is truncated automatically — search engines cut
  the display themselves, and a slightly long tag is not an error.
- Not covered by this package: the global home page and the tools landing page,
  whose text comes from the language files (`messages.site_name`,
  `messages.tagline`, `tools.tagline`). Say the word and I'll add those to the
  admin Settings page as global defaults.
- Adding the section to another resource later is one line:
  `SeoFields::make(),` at the end of its form schema, plus `use HasSeoMeta` and
  the two casts on the model.
