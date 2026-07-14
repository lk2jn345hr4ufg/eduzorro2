# Eduzorro admin panel (Filament)

Adds a full admin panel at `/admin` for managing regions, languages, industries,
categories, companies, and — most importantly — the review moderation queue.

## What's included

| Resource   | What you can do                                                          |
|------------|---------------------------------------------------------------------------|
| Regions    | CRUD, drag-to-reorder, translatable name, parent/child hierarchy         |
| Languages  | CRUD, drag-to-reorder, direction (ltr/rtl)                               |
| Industries | CRUD, translatable name/description                                      |
| Categories | CRUD, translatable name/description, industry picker                    |
| Companies  | CRUD, category + multi-region picker, translatable description, verified/active toggles |
| Reviews    | **Moderation queue** — approve/unapprove per row or in bulk, sidebar badge showing pending count, filter by approval status |
| Redirects  | 301/302 SEO redirects — single, bulk-paste, and CSV import; hit-count tracking |
| Listings   | The 5 WordPress-imported verticals (courses, online courses, universities, online business, affiliate networks) |
| Businesses | Ukrainian business-registry data imported from the old `websites` post type |
| Listing Reviews | Moderation queue for the 9,291 real reviews migrated from WordPress comments |

The dashboard also shows a small stats overview (active companies, active
regions, pending reviews).

Translatable fields (`name`, `description`) render as a tab per active
language — no translation package involved, it's just the existing JSON
columns addressed with dot-notation field names (`name.en`, `name.es`, …),
which Laravel's array cast handles natively.

## Install

Run these from your project root (`~/Herd/eduzorro` if you're using Herd):

```bash
# 1. Install Filament's panel builder (v3, which added Laravel 13 support)
composer require filament/filament:"^3.0-stable" -W

# 2. Scaffold the panel — creates app/Providers/Filament/AdminPanelProvider.php
#    and registers it in bootstrap/providers.php
php artisan filament:install --panels
#   When prompted for a panel ID, accept the default: admin
#   When asked to create a user, you can skip it — the seeder makes one (see below)
```

Then overlay this package's files (same as any other update — see the main
README/setup guide for the full `cp -R` sequence). This adds/replaces:

```
app/Filament/...                     (all resources, widgets, the TranslatableTabs helper)
app/Providers/Filament/AdminPanelProvider.php   (replaces the generated one — same class/path)
app/Models/User.php                  (adds the FilamentUser contract)
database/seeders/DatabaseSeeder.php  (adds a default admin user)
```

> If you'd already customized `app/Models/User.php`, diff it against ours
> first — we only added the `FilamentUser` interface and a `canAccessPanel()`
> method; everything else is stock Laravel.

Finally, migrate/seed and clear caches:

```bash
php artisan migrate:fresh --seed
php artisan optimize:clear
```

## Log in

```
URL:      http://localhost:8000/admin   (or https://eduzorro.test/admin on Herd)
Email:    [email protected]
Password: password
```

**Change that password before deploying anywhere public.**

## Access control

`User::canAccessPanel()` currently returns `true` for anyone with an account —
fine for local development, not fine for production. Before deploying, tighten
it, e.g.:

```php
public function canAccessPanel(Panel $panel): bool
{
    return str_ends_with($this->email, '@yourdomain.com');
}
```

or add an `is_admin` boolean column and check that instead.

## Notes

- Digital companies are auto-attached to every active region on save (in
  addition to whatever you pick manually in the Regions field), matching the
  public site's rule that digital companies appear everywhere.
- The Reviews sidebar badge counts unapproved reviews — it's the fastest way
  to see if anything needs moderating.
- Adding a new language in **Languages** automatically adds a new tab to every
  translatable field across Regions/Industries/Categories/Companies — no code
  changes needed.

## Redirects (SEO)

Under **SEO → Redirects** you get three ways to create 301s:

1. **Single** — the normal Create/Edit form: `from_path`, `to_path`, status
   code (301/302/307/308), match type, active toggle, notes.
2. **Bulk add** — a header button opens a modal with one big textarea. Paste
   many lines at once, one redirect per line:
   ```
   /old-page -> /new-page
   /old-region/en -> /global/en
   /retired-category, /global/en/new-category
   ```
   Both `->` and a plain comma work as the separator. One shared match type /
   status code / active toggle applies to the whole batch.
3. **Import CSV** — a header button opens a modal with a file upload. The
   first row must be a header; required columns are `from_path` and
   `to_path`, optional columns are `status_code`, `match_type`, `is_active`,
   `notes`. Example:
   ```csv
   from_path,to_path,status_code,match_type,is_active,notes
   /old-page,/new-page,301,exact,1,renamed June 2026
   /old-region,/global,301,prefix,1,region retired
   ```
   Re-importing the same CSV updates existing rows (matched by `from_path`)
   rather than creating duplicates.

**Match types:**
- `exact` — the incoming path must match `from_path` exactly.
- `prefix` — matches anything starting with `from_path`, and appends
  whatever comes after it onto `to_path`. Use this to retire a whole
  region/category at once, e.g. `from_path=/old-region`,
  `to_path=/global` sends `/old-region/en/x` to `/global/en/x`.

**How it works:** `HandleRedirects` middleware runs globally, before routing
(registered in `bootstrap/app.php`), so it also catches URLs that no longer
match any route at all — the main real-world case for a redirects module.
Active redirects are cached for 5 minutes and the cache is busted
automatically on any create/update/delete, so admin changes apply
immediately without needing `optimize:clear`. Each redirect tracks a hit
count and last-hit timestamp, visible in the table, so you can spot rules
nobody's actually using anymore.
