# Fix: transfers lookup returned 0 while the API works

Calling api-sports by hand returned 5 results for "Manchester United", but the
client got nothing. The cause is the auth headers: if `API_FOOTBALL_HOST` is set
in .env (a leftover from the original RapidAPI-capable config), the client sends
`x-rapidapi-key` / `x-rapidapi-host` to the DIRECT api-sports domain. That domain
ignores those headers and answers HTTP 200 with an empty response and no error -
which looked exactly like "team not found".

## Changes
- The client only uses RapidAPI headers when the configured host actually is a
  RapidAPI host; otherwise it always uses `x-apisports-key`.
- An empty-but-error-free response is now logged with the path and query, so this
  class of misconfiguration is visible in the log instead of silent.
- New `sport:transfers-doctor` command prints the effective base URL, key, host,
  plan and daily quota, then runs a real lookup - one command that answers "is it
  my key, my quota, or my code".

## Files
- app/Services/Football/ApiSportsTransfersClient.php  (modified)
- app/Console/Commands/TransfersDoctor.php            (new)

## Apply - local
```
unzip -o ~/Downloads/transfers-host-fix.zip -d /tmp/hf-unzip
cp -a /tmp/hf-unzip/transfers-host-fix/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/hf-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Only use RapidAPI headers for RapidAPI hosts; add transfers doctor"
git push
```

## Apply - server
```
cd ~/laravel-app
git pull origin main

# check whether the stale variable is there, and drop it if so
grep -n "API_FOOTBALL_HOST" .env
# (comment it out or delete the line, then:)
php artisan config:clear
php artisan cache:clear

php artisan sport:transfers-doctor
```

Expect: plan Free, a quota line, then
`search "Manchester United" → 5 candidate(s): Manchester United #33 ...`
and `Resolved to api-sports id 33`.

Then load the data:
```
php artisan sport:sync-transfers --country=england --limit=15 --sleep=1
```

Note: the fix works even if you leave API_FOOTBALL_HOST in .env, since the host
is now ignored unless it is a RapidAPI host - but removing the stale line is
still the cleaner outcome.
