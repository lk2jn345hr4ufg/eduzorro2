# Fix: 429 rate limit on transfers lookups

The log showed 18 requests inside the same second. That is not the daily quota
(which was 22/100 a minute earlier) - it is the api-sports free plan's
~10 requests/MINUTE cap. `--sleep` only paused between teams, while each
unmatched team fired up to three lookups back to back with no gap.

## Changes
- The client now throttles itself: a minimum gap between every outgoing request
  (default 6.5s, so ~9/minute), applied inside `get()` so no caller can burst.
- A 429 is retried with a growing pause (default 3 attempts, 20s x attempt)
  instead of being counted as "team not found".
- `--sleep` on the sync command now defaults to 0, since throttling is handled
  centrally; it stays available if you want extra spacing.
- `sport:transfers-doctor` prints the throttle settings and, when /status fails,
  explains how to tell a rate limit apart from a bad key.

Tunable in .env if you upgrade the plan:
```
API_FOOTBALL_MIN_INTERVAL=6.5
API_FOOTBALL_RETRIES=3
API_FOOTBALL_RETRY_WAIT=20
```

## Files
- app/Services/Football/ApiSportsTransfersClient.php  (modified)
- app/Console/Commands/SyncTransfers.php             (modified)
- app/Console/Commands/TransfersDoctor.php           (modified)
- config/apisports.php                               (modified)

## Apply - local
```
unzip -o ~/Downloads/transfers-throttle.zip -d /tmp/th-unzip
cp -a /tmp/th-unzip/transfers-throttle/. /Users/olegmishyn/HERD/eduzorro/
rm -rf /tmp/th-unzip
cd /Users/olegmishyn/HERD/eduzorro
php artisan optimize:clear
git add .
git commit -m "Throttle api-sports requests and retry on 429"
git push
```

## Apply - server
```
cd ~/laravel-app
git pull origin main
php artisan optimize:clear

# wait a minute for the per-minute window to clear, then:
php artisan sport:transfers-doctor
```

Expect the lookup to resolve Manchester United to id 33. Then load in small
batches - each team costs 1 lookup (first time only) + 1 transfers call, and the
daily cap is 100:
```
php artisan sport:sync-transfers --country=england --limit=10
```
At ~6.5s per call that batch takes a couple of minutes; that is the throttle
doing its job, not a hang.

## Note on the daily quota
100 requests/day means roughly 45-50 teams per day on the first pass (lookup +
transfers each), and about 100 on later passes since ids are cached on the team
row. Spread countries across days, or run it from the scheduler.
