# 50 more math calculators (total: 84)

Extends the Mathematics category with 50 additional calculators. Same
config-driven engine — no new Blade files, no migration, no API calls.

Requires the tools module and the first math batch to be installed.

## What's new
Plane geometry: square, parallelogram, rhombus, regular polygon, ellipse,
circle sector
Solids: cube, cuboid, square pyramid, prism, hemisphere, torus
Sequences: arithmetic progression, geometric progression, Fibonacci
Statistics & probability: weighted average, mode & range, z-score, probability,
binomial probability, exponential growth, half-life
Everyday math: percent error, discount, VAT, tip, markup & margin, unit price,
speed-distance-time
Fractions & numbers: mixed to improper, decimal to fraction, fraction to percent,
modulo, division with remainder, significant figures, divisors
Algebra & trigonometry: parabola vertex, line through two points, triangle angles
(law of cosines), law of sines, sin/cos/tan, inverse trig, degrees to radians
Vectors & matrices: vector magnitude, dot product, cross product, 2x2 matrix
(determinant/trace/inverse), 3x3 determinant
Converters: length units, mass units

Each gets its own indexable URL, e.g.
/{region}/{lang}/tools/binomial-probability-calculator

## Files (extract over project root, keep paths)
- config/math_tools.php                        (replaced: now 84 tools)
- database/seeders/MathToolExtraSeeder.php     (new: registers the 50)
- lang/{en,uk,ru,es}/math.php                  (replaced: +137 field labels)

## Apply — local
```
unzip -o ~/Downloads/math-tools-50.zip -d /tmp/math50-unzip
cp -a /tmp/math50-unzip/math-tools-50/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/math50-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
php artisan db:seed --class=Database\\Seeders\\MathToolExtraSeeder
git add .
git commit -m "Add 50 more math calculators (84 total)"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
php artisan db:seed --class=Database\\Seeders\\MathToolExtraSeeder --force
```

## Notes
- config/math_tools.php REPLACES the previous file — it contains all 84 tools
  (the original 34 are unchanged).
- The language files are replaced too: they keep the existing labels and add 137
  new ones. Labels are shared across tools, so the files stay compact.
- The first batch's seeder (MathToolSeeder) does not need re-running; the two
  seeders cover different slugs and never overlap.
- All calculations run in the browser; invalid input shows "—" instead of an error.
- Ellipse perimeter uses the Ramanujan approximation (accurate to ~1e-5 for
  typical shapes), since there is no closed form.
