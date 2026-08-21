# Tools move to language-only URLs

Study tools now live at /{language}/tools instead of /{region}/{language}/tools,
because a calculator is identical in every region — one canonical URL per
language instead of one per region/language pair (better for SEO: no duplicate
content across regions).

```
before:  https://eduzorro.com/ukraine/ru/tools
after:   https://eduzorro.com/ru/tools
         https://eduzorro.com/ru/tools/percentage-calculator
```

Old URLs are kept and **301-redirect** to the new ones, so anything already
indexed or linked keeps working.

## Files (extract over project root, keep paths)
- app/Http/Middleware/SetLocale.php        (new) language-only middleware
- bootstrap/app.php                        (modified) registers the `locale` alias
- routes/web.php                           (modified) new routes + legacy 301s
- app/Http/Controllers/ToolController.php  (modified) no region parameter
- resources/views/tools/index.blade.php    (modified) links drop the region
- resources/views/region-language.blade.php, home.blade.php (modified) same

No migration.

## Apply — local
```
unzip -o ~/Downloads/tools-language-url.zip -d /tmp/toolsurl-unzip
cp -a /tmp/toolsurl-unzip/tools-language-url/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/toolsurl-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Move study tools to language-only URLs with 301s from region URLs"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
```

Check:
```
curl -sI https://eduzorro.com/ukraine/ru/tools | head -3   # expect 301 -> /ru/tools
curl -sI https://eduzorro.com/ru/tools | head -3           # expect 200
```

## How it works / notes
- The language-only group is registered BEFORE the region group, so `/ru/tools`
  matches it instead of being read as region=ru, language=tools.
- No slug collision risk: region slugs are words (ukraine, spain, global) while
  language params bind on two-letter codes.
- `SetLocale` deliberately does not share `$currentRegion`. The layout already
  guards with `@isset($currentRegion)`, so on tool pages the brand link points to
  the global home and the region switcher is hidden — nothing breaks.
- hreflang still works: `Seo::hreflangAlternates()` keys off the `language` route
  parameter and falls back to all active languages when there is no region, so
  each tool page advertises one alternate per language.
- If you later want the same treatment for another region-independent section,
  wrap it in the same `Route::prefix('{language:code}')->middleware('locale')` group.
