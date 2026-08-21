# Study tools section (/tools)

Adds a localized study-tools section with two working calculators, built so new
tools are one view + one admin row.

## URLs
```
/{region}/{lang}/tools                   list of tools, grouped by category
/{region}/{lang}/tools/gpa-calculator    weighted GPA calculator (4.0 scale)
/{region}/{lang}/tools/grade-converter   % -> US letter / GPA / UK / ECTS / UA-12 / 5-point
```

Links added on the localized home ("Study tools" section) and on each region card
of the global home.

## Files (extract over project root, keep paths)
- database/migrations/2025_08_06_000010_create_tools_table.php  (new)
- database/seeders/ToolSeeder.php                               (new)
- app/Models/Tool.php                                           (new)
- app/Http/Controllers/ToolController.php                       (new)
- app/Filament/Resources/ToolResource.php + Pages/              (new)
- routes/web.php                                                (modified: /tools before the {industry} wildcard)
- resources/views/tools/**                                      (new)
- resources/views/region-language.blade.php, home.blade.php     (modified: nav links)
- public/css/tools.css                                          (new)
- lang/{en,uk,ru,es}/tools.php                                  (new)

## Apply - local
```
unzip -o ~/Downloads/tools-module.zip -d /tmp/tools-unzip
cp -a /tmp/tools-unzip/tools-module/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/tools-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\ToolSeeder
php artisan optimize:clear
git add .
git commit -m "Add study tools section with GPA calculator and grade converter"
git push
```

## Apply - server
```
cd ~/laravel-app
git pull origin main
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\ToolSeeder --force
php artisan optimize:clear
```

## Adding a new tool later
1. Create resources/views/tools/partials/{slug}.blade.php with the tool markup.
2. Admin -> Tools -> Study tools -> New, set slug to the same {slug},
   fill the translated name/description, pick a category, save.
That is all - it appears in the list and gets its own indexable URL.

## Notes
- Both tools run entirely in the browser (no API calls, no quota, instant results),
  and work without JS frameworks.
- Names/descriptions are translatable JSON like the rest of the site, so pages are
  localized per region/language and fit the existing SEO setup.
- Grade mappings are indicative and the UI says so - scales vary by institution.
- Tools are toggled on/off and reordered from the admin (is_active, sort_order).
