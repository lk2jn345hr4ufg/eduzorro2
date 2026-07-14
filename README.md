# Eduzorro — multi-region, multi-language education directory

A Laravel + MySQL starter for a SEO-friendly directory of education companies (local & digital),
organised by **region** and **language**, with ratings, reviews, quick search, maps, and full SEO
plumbing (clean URLs, hreflang, canonical, JSON-LD, XML sitemap).

## URL structure

```
/                                             Global home — pick a region & language
/{region}/{lang}                              Localized home — quick search + all industries/categories
/{region}/{lang}/{industry}                   Industry page — its categories
/{region}/{lang}/{industry}/{category}        Category listing — companies (rating, sort, pagination)
/{region}/{lang}/company/{company}            Company profile — description, map, contacts, reviews
/{region}/{lang}/search                       Search results
/{region}/{lang}/search/suggest               JSON autocomplete endpoint
/sitemap.xml                                  Multi-region/-language sitemap with hreflang alternates
```

`region` is matched by **slug**, `language` by **ISO code** (e.g. `/spain/es`). The
`SetRegionAndLocale` middleware resolves both, sets `app()->setLocale()`, and shares
`$currentRegion`, `$currentLanguage`, `$activeRegions`, `$activeLanguages` with every view.

## Data model

| Table            | Purpose                                                            |
|------------------|-------------------------------------------------------------------|
| `regions`        | Self-referencing hierarchy; translatable `name` (JSON)            |
| `languages`      | `code`, `native_name`, `direction`                                |
| `industries`     | Top-level grouping; translatable `name`/`description`            |
| `categories`     | Belong to an industry; translatable; globally-unique slug        |
| `companies`      | One primary category; `type` = local/digital; geo + contacts     |
| `company_region` | Pivot — lets **digital** companies appear in many regions        |
| `reviews`        | 1–5 rating, moderation flag (`is_approved`)                       |

Translations are stored as JSON columns (`{"en": "...", "es": "..."}`) and read with
`$model->translate('name')` via the `App\Support\HasTranslations` trait — no external package.
Swap for `spatie/laravel-translatable` later if you prefer.

Ratings are computed from **approved** reviews via the `withRatingSummary()` scope, which adds
`average_rating` and `reviews_count` to each company row.

## SEO features

- Clean, localized, keyword-rich URLs.
- `<link rel="canonical">` + `hreflang` alternates on every localized page (`App\Support\Seo`).
- JSON-LD: `BreadcrumbList`, `ItemList` (category pages), `LocalBusiness`/`Organization` +
  `AggregateRating` (company pages).
- `robots: noindex` on search results.
- `sitemap.xml` emitting every region × language × page, with `xhtml:link` alternates.
- OpenGraph tags.

## Setup

This is a set of application files meant to drop into a fresh Laravel app. From scratch:

```bash
# 1. Create a Laravel app (Laravel 11/12)
composer create-project laravel/laravel eduzorro
cd eduzorro

# 2. Copy the files from this package over the top, keeping the same paths:
#    app/, bootstrap/app.php, database/, lang/, public/, resources/, routes/

# 3. Configure MySQL in .env
#    DB_CONNECTION=mysql  DB_DATABASE=eduzorro  DB_USERNAME=...  DB_PASSWORD=...

# 4. Migrate + seed sample data (3 regions, 2 languages, 4 industries, 10 companies)
php artisan migrate --seed

# 5. Serve
php artisan serve
```

Then visit:

- `http://localhost:8000/` — global home
- `http://localhost:8000/spain/es` — localized home (Spanish, Spain)
- `http://localhost:8000/united-states/en/language-learning/language-schools` — a category listing
- `http://localhost:8000/global/en/company/fluentloop` — a company profile
- `http://localhost:8000/sitemap.xml`

> `bootstrap/app.php` here already registers the `region.locale` middleware alias. If you keep your
> own `bootstrap/app.php`, just add the alias in `->withMiddleware()`.

## Notes / next steps

- Models use `protected $guarded = []` for brevity — tighten to explicit `$fillable` before production.
- Maps use Leaflet + OpenStreetMap tiles (no API key). Swap tiles/provider as needed.
- Add an admin panel (e.g. Filament) to manage listings and moderate the review queue
  (`reviews.is_approved`).
- For a large catalogue, split `sitemap.xml` into a sitemap **index** with paginated child sitemaps
  (50k URLs / 50MB cap per file).
- Consider full-text search (MySQL FULLTEXT, Meilisearch, or Laravel Scout) as the catalogue grows.
- Add caching (region/language/industry trees change rarely) and rate-limiting on `search/suggest`.
