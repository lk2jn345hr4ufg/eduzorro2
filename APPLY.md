# 34 math calculators for /tools (fixed)

Fix: the previous package had a comment in config/math_tools.php containing the
sequence `*​/` (inside a path), which closed the PHP block comment early and
caused: `syntax error, unexpected identifier "—"`. The comment is reworded; the
tools themselves are unchanged.

Adds a Mathematics category to the study-tools section with 34 calculators,
built on a config-driven engine: one config entry per tool, one shared view.
No AI, no API calls — everything runs in the browser.

Requires the tools module (/tools) to be installed first.

## The tools
Basics: percentage, percentage change, fractions, ratio, proportion
Statistics: average (mean/median/range), standard deviation
Equations: quadratic, linear, 2x2 system
Number theory: GCD & LCM, prime checker, factorial, combinations & permutations
Powers: exponent, root, logarithm
Formatting: rounding, scientific notation, number base, Roman numerals
Geometry: circle, triangle (Heron), Pythagoras, rectangle, trapezoid, sphere,
cylinder, cone
Coordinates: distance between points, slope, midpoint
Applied: simple interest, compound interest

Each gets its own indexable URL, e.g.
/{region}/{lang}/tools/quadratic-equation-solver

## Files (extract over project root, keep paths)
- config/math_tools.php                          (new, FIXED) fields + formulas
- resources/views/tools/partials/math.blade.php  (new) generic engine view
- database/seeders/MathToolSeeder.php            (new) registers all 34 tools
- app/Models/Tool.php                            (modified) falls back to engine view
- app/Filament/Resources/ToolResource.php        (modified) Mathematics category
- lang/{en,uk,ru,es}/math.php                    (new) shared field labels
- lang/{en,uk,ru,es}/tools.php                   (modified) Mathematics category name
- public/css/tools.css                           (modified) math field grid

## Apply — local
```
unzip -o ~/Downloads/math-tools.zip -d /tmp/math-unzip
cp -a /tmp/math-unzip/math-tools/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/math-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
php artisan db:seed --class=Database\\Seeders\\MathToolSeeder
git add .
git commit -m "Add 34 math calculators to the study tools section"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
php artisan db:seed --class=Database\\Seeders\\MathToolSeeder --force
```

No migration — reuses the existing `tools` table.

## Adding another calculator later
1. Add an entry to config/math_tools.php: fields, outputs, and a `js` body that
   returns an object keyed by output id.
2. Add its labels to the math.php language files (most already exist, shared).
3. Add a row in the admin (Tools -> Study tools) with the same slug, or extend
   MathToolSeeder and re-run it.
No new Blade file is needed — the engine view renders it.

## Notes
- Run optimize:clear BEFORE seeding: the broken config may be sitting in the
  cached config from the failed run.
- Field labels are shared across tools, so the language files stay small.
- Results recalculate as you type; invalid input shows "—" rather than an error.
- Formulas live in config, not the database, so nothing user-supplied is ever
  evaluated as code.
