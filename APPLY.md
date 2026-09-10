# Tools landing page + home page link

The tools index is now a proper landing page, and the home page points at it
with a single promo block instead of a row of language chips.

## What changed

**Landing page (/{language}/tools)**
- Hero now shows how many tools there are in total (pluralised per language).
- Anchor navigation across the categories, each with its own count — with ~90
  tools the page is long, so visitors can jump straight to Mathematics, Grades
  or Video & lectures.
- Each category renders as a section with an id, so category links are shareable
  (e.g. /ru/tools#cat-math).
- Footnote explaining that the tools are free and run in the browser.

**Home page**
- The list of language chips is replaced by one promo block: title, tagline and
  a primary "Browse all tools" button pointing at the landing page in the first
  active language.
- The other languages stay as small links underneath, so every language version
  of the landing page is still crawlable and one click away.

## Files (extract over project root, keep paths)
- resources/views/tools/index.blade.php  (rewritten: landing page)
- resources/views/home.blade.php         (modified: promo block)
- public/css/tools.css                   (modified: landing + promo styles)
- lang/{en,uk,ru,es}/tools.php           (modified: browse_all, footnote, count)

No migration, no controller change — the landing page uses the data the tools
index already received.

## Apply — local
```
unzip -o ~/Downloads/tools-landing.zip -d /tmp/land-unzip
cp -a /tmp/land-unzip/tools-landing/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/land-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Turn the tools index into a landing page and link it from home"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
```

## Note
The `count` string uses Laravel's `trans_choice` plural rules, so Ukrainian and
Russian decline correctly (1 інструмент / 3 інструменти / 90 інструментів).

The promo block and the anchor nav are styled in tools.css, which on the server
still needs to be reachable at /css/tools.css — until that symlink exists the
block will render unstyled.
