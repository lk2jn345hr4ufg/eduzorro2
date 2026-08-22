# Home page: Study tools moved out of the regions section

Tools now live at /{language}/tools, so repeating the link inside every region
card made no sense — the same URL was printed once per region. It is now its own
section, directly under the hero, with one link per language.

Before: a "Study tools" chip inside each region card (duplicated N times).
After:  a standalone "Study tools" section listing each active language once.

The sport chips stay inside the region cards, since those URLs *are*
region-specific.

## Files
- resources/views/home.blade.php  (modified)

## Apply — local
```
unzip -o ~/Downloads/home-tools-section.zip -d /tmp/hometools-unzip
cp -a /tmp/hometools-unzip/home-tools-section/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/hometools-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan view:clear
git add .
git commit -m "Move study tools out of the regions section on the home page"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan view:clear
```

No migration, no controller change.
