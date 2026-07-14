# WordPress migration (eduzorro.com)

This imports the real data from the old WordPress site's WXR export
(`Tools → Export → All content`, 142MB / 18,723 items) into this Laravel app,
and sets up 301 redirects from every old URL to its new location.

## What was actually on the old site

The export turned out to be structurally different from this project's
original brief — not a single "companies" list, but **six WordPress custom
post types**, each with its own taxonomy:

| Old post type | Count | What it is | New home |
|---|---|---|---|
| `courses` | 250 (248 published) | In-person/local courses | `listings` (vertical: `course`) |
| `online_courses` | 331 | Online courses | `listings` (vertical: `online_course`) |
| `universities` | 435 (434 published) | Universities by field of study | `listings` (vertical: `university`) |
| `online_business` | 776 (773 published) | Reviewed online services | `listings` (vertical: `online_business`) |
| `affiliate-networks` | 276 | Affiliate programs | `listings` (vertical: `affiliate_network`) |
| `websites` | 12,570 | Ukrainian business registry (EDRPOU-based) | `businesses` |

The first five shared an identical ACF field structure (name, description,
specialization, an editorial 0–5 rating, contacts, website, logo, optional
address/price repeaters) and real **user reviews as WordPress comments**
(9,291 of them, each with a 1–5 `rating` meta value) — genuinely valuable
data, migrated as-is.

`websites` is a completely different animal: a bulk import of Ukrainian
government business-registry records (EDRPOU number, director, registration
date, KVED industry codes, lat/long) — no reviews, no editorial content. It
got its own table (`businesses`) rather than being forced into the same
shape as the reviewed listings.

Also present in the export but **not imported**: `attachment` (3,720 media
items — logos are linked by URL back to eduzorro.com, not re-hosted, see
below), `acf-field`/`acf-field-group` (form-builder config, not content),
`nav_menu_item` (old site nav), `blog`/`blog_ru` (60 blog posts), `page`/`post`
(20 static pages — mostly empty), `kz-business`/`instagramers`/`youtubers`
(tiny niche post types, a few dozen items total). All of these are still in
the WXR file if you want them later; they just weren't in scope for this
pass.

## New schema

- **`taxonomy_terms`** — every term from every old taxonomy (city, section,
  business, direction, industry, category, categories), 4,307 total, shared
  by both listings and businesses via pivot tables.
- **`listings`** + `listing_addresses` + `listing_prices` +
  `listing_pros_cons` + `listing_reviews` + `listing_taxonomy_term` — the
  five reviewed verticals.
- **`businesses`** + `business_taxonomy_term` — the registry data.

See `database/migrations/2025_01_01_000009_create_wordpress_import_tables.php`
for exact columns.

## Data files

The parsing was already done — `database/import/*.csv` contains the fully
transformed data, ready to load:

```
taxonomy_terms.csv       4,307 rows
listings.csv              2,062 rows
listing_addresses.csv
listing_prices.csv
listing_pros_cons.csv
listing_reviews.csv       9,291 rows
listing_categories.csv
businesses.csv           12,570 rows
business_categories.csv
redirects.csv            14,614 rows
```

These were generated from the WXR export by a one-off Python script (not
included in the app — it did its job once against the real export). If the
export is ever re-run and you need to regenerate these CSVs, ask for the
transform script again; it's a straightforward WXR-to-CSV pass.

## Running the import

```bash
cd ~/Herd/eduzorro
php artisan migrate            # creates the new tables
php artisan import:wordpress   # loads listings, businesses, reviews, terms
php artisan import:redirects   # loads the 301 redirects
php artisan import:wordpress-regions   # creates Ukraine/Kazakhstan + ru/uk, links everything to them
```

`import:wordpress-regions` creates real `Region` rows for Ukraine and
Kazakhstan and `Language` rows for Russian and Ukrainian, constrains each
region to the languages it actually offers (Ukraine: Russian + Ukrainian,
Kazakhstan: Russian only — via a `language_region` pivot, editable per-region
in the admin panel), deactivates the starter scaffold's demo regions/
languages (hidden, not deleted), and links every imported business to its
region using the old `country` taxonomy (11,321 Ukraine, 1,249 Kazakhstan —
that's real source data). **Listings** (the five review-driven verticals)
were never tagged by country on the old site, so they're all linked to
Ukraine by default — a deliberate inference (the site is Ukraine-market
content throughout: Ukrainian cities, Ukrainian institutions), not something
in the source data. Worth knowing if you later get real per-listing country
data.

This command is safe to re-run — every step uses `updateOrCreate`/`sync`.

Once this runs, the home page's region cards for Ukraine/Kazakhstan show
real links into the migrated verticals and business registry (previously
they'd have shown nothing, since no `Region` existed for them yet). Public
browsing pages now exist for all of it:

```
/{region}/{language}/directory/{vertical}              listing index (paginated, sortable)
/{region}/{language}/directory/{vertical}/{slug}        listing profile (reviews, prices, pros/cons)
/{region}/{language}/businesses                         business registry (searchable)
/{region}/{language}/businesses/{slug}                  business detail
```

where `{vertical}` is one of `courses`, `online-courses`, `universities`,
`online-business`, `affiliate-networks`.

Both commands are safe to re-run — they use chunked **upserts** keyed on
`wp_post_id` (or `from_path` for redirects), so running them again updates
existing rows instead of duplicating them. Use `php artisan import:wordpress
--fresh` to wipe and reimport from scratch instead.

**Why not the admin panel's CSV importer?** The Redirects section already
has a browser-based "Import CSV" button (built earlier) — it's the right
tool for adding a few dozen redirects by hand, but not for 14,614 rows in a
single HTTP request (real risk of a timeout). These two Artisan commands do
the same job with chunked, transaction-safe bulk upserts instead.

Expect `import:wordpress` to take a minute or two (mostly the 12,570
business rows) and `import:redirects` a few seconds.

## Verifying it worked

```bash
php artisan tinker --execute="
echo App\Models\Listing::count() . ' listings\n';
echo App\Models\ListingReview::count() . ' reviews\n';
echo App\Models\Business::count() . ' businesses\n';
echo App\Models\Redirect::count() . ' redirects\n';
"
```

Then spot-check a redirect in the browser — visit an old URL like
`/courses/all-reviews-of-it-education-academy` and confirm it 301s.

## What's built

Public pages exist for all six verticals (see the route table above), with
breadcrumbs, sorting, pagination, and — for listings — full review display,
reused from the site's existing design system (same card/stamp/breadcrumb
components as the rest of the app). Not included: a category/section-archive
browsing page for the 4,307 taxonomy terms (city, section, business,
direction, industry) — right now the redirects for those old archive URLs
point at new URLs that don't have a page behind them yet
(`/courses/category/{slug}` etc.). That's the natural next piece if you want
it — filtering the directory/business indexes by taxonomy term.

## Images

Every `logo_url` still points at `eduzorro.com` (e.g.
`https://eduzorro.com/wp-content/uploads/2021/03/aivix.jpg`) — the WXR
export only references image URLs, it doesn't include the files. This works
as long as the old site (or at least its `/wp-content/uploads/` path) stays
reachable. Downloading and re-hosting all 3,720 attachments locally is
straightforward to add later if you'd rather not depend on the old host.
