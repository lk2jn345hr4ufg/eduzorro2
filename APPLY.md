# Team news list: show the title in EN and RU

The list only had a single "Title" column bound to title.en. Since the Gemini
rewrite writes translations into the active site languages (ru, uk), rewritten
articles often have no English title at all — so those rows rendered blank.

Now there are two columns, Title (EN) and Title (RU), each falling back to a
dash when that language is missing. Both search the whole title JSON, so typing
a Russian word finds the row even though the column is a computed value.

## Files
- app/Filament/Resources/TeamNewsResource.php  (modified: table columns)

## Apply - local
```
unzip -o ~/Downloads/team-news-titles.zip -d /tmp/tn-unzip
cp -a /tmp/tn-unzip/team-news-titles/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/tn-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Show EN and RU titles in the team news admin list"
git push
```

## Apply - server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
```

No migration. To add Ukrainian too, copy either column block and swap 'ru' for 'uk'.
