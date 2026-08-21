# Fix #2: Language::tools() still thrown on tool pages

The previous fix added `->withoutScopedBindings()` to the tools group, but the
error persisted. Root cause is the explicit route key: writing `{tool:slug}`
after another bound parameter is what switches Laravel into implicit *child
scoping*, and it then looks for a `tools()` relationship on the parent model
(`Language`).

Fix: drop the explicit key and use `{tool}`. The `Tool` model already declares
`getRouteKeyName() = 'slug'`, so binding by slug is unchanged — the URLs stay
exactly the same — but the scoping behaviour is never triggered.

`->withoutScopedBindings()` is kept on the group as a second line of defence.

## Files
- routes/web.php  (modified: `{tool:slug}` -> `{tool}` in both the new route and
  the legacy 301 route)

## Apply — local
```
unzip -o ~/Downloads/tools-binding-fix-2.zip -d /tmp/fix2-unzip
cp -a /tmp/fix2-unzip/tools-binding-fix-2/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/fix2-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Bind tool by model route key to avoid implicit scoping"
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
curl -sI https://eduzorro.com/ru/tools/gpa-calculator | head -3        # 200
curl -sI https://eduzorro.com/ukraine/ru/tools/gpa-calculator | head -3 # 301
```

If it still 500s, confirm the deployed file actually changed:
```
grep -n "tools/{tool" routes/web.php
```
It must show `{tool}` and not `{tool:slug}`. Also make sure no route cache is
stale — `optimize:clear` covers that.
