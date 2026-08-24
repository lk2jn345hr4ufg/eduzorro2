# Fix: categories not editable / only some shown, in English

Two separate causes, both fixed here.

## 1. No Edit button on Categories
`CategoryResource`'s table never declared row actions, and Filament 3 does not
add them automatically — so there was no way into the edit screen from the list.
Added Edit + Delete row actions, bulk delete, and 50-per-page pagination.
The Name column is now searchable inside the JSON translations too, so searching
a Ukrainian or Russian name finds the row (previously only the slug matched).

## 2. "Only part of the categories, in English"
The categories that actually drive the directory are **not** in the `categories`
table — they come from the WordPress import and live in `taxonomy_terms`
(city / section / business / direction / industry / category). There was no
Filament resource for them at all, so the admin only ever showed the small
curated `categories` set. And `taxonomy_terms.name` is a plain string, not a
translatable JSON column, which is why those names render in the imported
language everywhere.

Fixes:
- New admin section **Directory -> Imported categories** (TaxonomyTermResource):
  list, search, filter by taxonomy, listing counts, edit, delete, create.
- New nullable JSON column `taxonomy_terms.name_i18n` for per-language names.
  The original `name` is left untouched, so re-imports keep matching on it and
  nothing breaks if a translation is missing.
- `TaxonomyTerm::label()` returns the current locale's name, falling back to any
  filled translation, then the imported name.
- Directory pages (listing pages, breadcrumbs, category chips, related
  categories, business tags) now render `label()` instead of the raw name.
- The list shows a "Translated" badge with the language codes already filled in,
  so you can see at a glance what still needs work.

## Files (extract over project root, keep paths)
- database/migrations/2025_08_06_000011_add_translations_to_taxonomy_terms.php (new)
- app/Models/TaxonomyTerm.php                        (modified: casts + label())
- app/Filament/Resources/TaxonomyTermResource.php    (new) + Pages/ (new)
- app/Filament/Resources/CategoryResource.php        (modified: row actions, search, pagination)
- app/Http/Controllers/DirectoryController.php       (modified: breadcrumb label)
- resources/views/directory/{index,show,business}.blade.php (modified: label())

## Apply - local
```
unzip -o ~/Downloads/categories-admin-fix.zip -d /tmp/cat-unzip
cp -a /tmp/cat-unzip/categories-admin-fix/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/cat-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan migrate
php artisan optimize:clear
git add .
git commit -m "Make imported categories editable and translatable in the admin"
git push
```

## Apply - server
```
cd ~/laravel-app
git pull origin main
php artisan migrate --force
php artisan optimize:clear
```

## Notes
- Nothing is renamed or deleted: existing terms keep their imported `name` and
  keep rendering exactly as before until you add a translation.
- Two admin sections now exist on purpose: **Categories** (curated, used by the
  industry pages) and **Imported categories** (WordPress terms, used by the
  directory). They are different tables serving different parts of the site.
- Changing a term's `slug` changes its public URL - add a redirect if you do.
