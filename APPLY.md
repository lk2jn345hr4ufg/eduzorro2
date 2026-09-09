# Fix: "no api-sports match" for every team

Every team was skipped at the lookup step. Two problems, both mine:

1. **Failed lookups were cached for a week.** If the very first search failed
   (quota, a bad country filter, a network hiccup), the miss was stored and every
   later run answered "not found" without ever calling the API again. Only
   successful lookups are cached now.
2. **Only one search attempt.** It searched the full club name filtered by
   country and gave up. It now tries progressively: name + country, then name
   alone, then the first word — which is what actually finds clubs whose local
   name differs from the api-sports spelling.

Also added `--debug`, which prints each search and the candidates it returned,
so a miss is diagnosable instead of silent.

## Files
- app/Services/Football/ApiSportsTransfersClient.php  (modified)
- app/Console/Commands/SyncTransfers.php              (modified: --debug)

## Apply - local
```
unzip -o ~/Downloads/transfers-lookup-fix.zip -d /tmp/lk-unzip
cp -a /tmp/lk-unzip/transfers-lookup-fix/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/lk-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Fix api-sports team lookup: don't cache misses, add fallbacks"
git push
```

## Apply - server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear

# clear the poisoned lookup cache from the failed run
php artisan cache:clear

# now watch what the API actually returns
php artisan sport:sync-transfers --country=england --limit=3 --sleep=1 --debug
```

You should see lines like:
```
   search "Manchester United" in England → 1 candidate(s): Manchester United #33
→ Manchester United: linked to api-sports id 33
   transfers: 263
```

If instead you see `→ 0 candidate(s)` on every attempt, the key or quota is the
problem, not the name - check `tail -n 30 storage/logs/laravel.log`.
Once a few teams link correctly, run the rest:
```
php artisan sport:sync-transfers --country=england --limit=15 --sleep=1
```
