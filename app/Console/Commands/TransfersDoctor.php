<?php

namespace App\Console\Commands;

use App\Services\Football\ApiSportsTransfersClient;
use Illuminate\Console\Command;

/**
 * Prints exactly what the transfers client is configured with and what the API
 * answers, so a misconfigured host/key is obvious instead of showing up as
 * "no api-sports match" for every team.
 */
class TransfersDoctor extends Command
{
    protected $signature = 'sport:transfers-doctor {--team=Manchester United : Club name to test the lookup with}';

    protected $description = 'Diagnose the api-sports transfers connection';

    public function handle(ApiSportsTransfersClient $api): int
    {
        $key  = (string) config('apisports.key');
        $host = config('apisports.host');

        $this->line('base_url : '.config('apisports.base_url'));
        $this->line('key      : '.($key ? substr($key, 0, 6).'… ('.strlen($key).' chars)' : 'NOT SET'));
        $this->line('host     : '.($host ?: 'not set (direct api-sports — correct for v3.football.api-sports.io)'));
        $this->line('throttle : '.config('apisports.min_interval').'s between calls, '
            .config('apisports.retries').' retries on 429');

        if ($host && ! str_contains(strtolower($host), 'rapidapi')) {
            $this->warn('host is set but is not a RapidAPI host — remove API_FOOTBALL_HOST from .env');
        }

        $this->newLine();
        $this->line('Checking /status ...');
        $status = $api->get('/status');

        if (empty($status)) {
            $this->error('No answer from /status. Check storage/logs/laravel.log:');
            $this->line('  status 429 → rate limited. The free plan allows ~10 requests/minute');
            $this->line('              and 100/day; wait a minute (or until the daily reset) and retry.');
            $this->line('  other      → key or headers are wrong.');
            return self::FAILURE;
        }

        $this->info(sprintf('plan: %s | requests today: %s/%s',
            data_get($status, 'subscription.plan'),
            data_get($status, 'requests.current'),
            data_get($status, 'requests.limit_day')
        ));

        $name = (string) $this->option('team');
        $this->newLine();
        $this->line("Looking up \"{$name}\" ...");

        $id = $api->resolveTeamId($name, null, fn (string $l) => $this->line('  '.$l));

        if (! $id) {
            $this->error('Lookup failed.');
            return self::FAILURE;
        }

        $this->info("Resolved to api-sports id {$id}");
        $this->line('transfers rows available: '.collect($api->transfers($id))->count());

        return self::SUCCESS;
    }
}
