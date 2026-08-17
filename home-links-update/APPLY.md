# Home page: Sport link + categories

Adds discovery links for the new /sport section and surfaces categories on the
home pages. Depends on the sport module (routes sport.index / sport.football.*)
being installed first.

## Files (extract over project root, keep paths)
- app/Http/Controllers/HomeController.php   (modified: eager-load industry categories)
- resources/views/home.blade.php            (global "/" : Sport chips per region + category links under industries)
- resources/views/region-language.blade.php (localized home: new Sport section; categories already present)

## Apply
1. Unzip over the project root.
2. php artisan view:clear
   (and cache:clear if you cache config/views)

No migration needed. If OPcache is on, reload php-fpm so the controller change loads.

## Notes
- Global home category links use each region + its first active language, matching
  how industry shortcuts were already built there.
- Sport links use each region linkLanguage (its first active language).
- If you only wanted the Sport link (not category trees) on the global home, delete
  the inner <ul class="category-links"> block in home.blade.php.
