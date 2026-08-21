# Fix: "Call to undefined method App\Models\Language::tools()"

`/ru/tools` worked but `/ru/tools/fraction-calculator` threw a 500.

Cause: when a route has a parameter with a custom key (`{tool:slug}`) after
another bound parameter (`{language:code}`), Laravel automatically **scopes**
the child binding through a guessed relationship on the parent — here
`Language::tools()`, which doesn't exist. Tools are global, not children of a
language.

Fix: add `->withoutScopedBindings()` to the language-only route group — exactly
what the region group already does for the same reason.

## Files
- routes/web.php  (modified: one line added to the tools group)

## Apply — local
```
unzip -o ~/Downloads/tools-scoped-binding-fix.zip -d /tmp/fix-unzip
cp -a /tmp/fix-unzip/tools-scoped-binding-fix/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/fix-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Fix scoped binding on language-only tool routes"
git push
```

## Apply — server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear
```

Check: https://eduzorro.com/ru/tools/fraction-calculator should render.
