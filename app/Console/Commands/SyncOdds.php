<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Odd;
use App\Services\Football\ApiSportsTransfersClient;
use App\Services\Football\OddsMatcher;
use App\Services\Odds\OddsApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Pulls 1X2 odds for upcoming matches and attaches them to our fixtures.
 *
 * Providers (config odds.provider, or --provider):
 *  - oddsapi   (default) The Odds API: ONE request per competition for the
 *              whole --days window, 1 credit each; skipped entirely when we
 *              have no matches in the window.
 *  - apisports API-Football: one /odds call per competition per day, plus one
 *              /fixtures call when odds came back. Free plan: old seasons only.
 */
class SyncOdds extends Command
{
    protected $signature = 'sport:sync-odds
                            {codes?* : Competition codes, e.g. PL CL (default: all active with an API-Football league id)}
                            {--days=2 : How many days ahead, starting today}
                            {--season= : API-Football season (start year); defaults to the site season}
                            {--provider= : oddsapi or apisports (default: Settings → Odds)}';

    protected $description = 'Import 1X2 odds for upcoming matches (The Odds API or API-Football)';

    public function handle(ApiSportsTransfersClient $api, OddsApiClient $oddsApi): int
    {
        $provider = strtolower((string) ($this->option('provider') ?: config('odds.provider', 'oddsapi')));

        if ($provider === 'oddsapi') {
            return $this->viaOddsApi($oddsApi);
        }

        return $this->viaApiSports($api);
    }

    /** The Odds API: one request per competition for the whole window. */
    protected function viaOddsApi(OddsApiClient $api): int
    {
        if (! $api->isConfigured()) {
            $this->error('The Odds API key is not set (Settings → Odds, or ODDS_API_KEY).');
            return self::FAILURE;
        }

        $query = Competition::active()->ordered()->whereNotNull('oddsapi_sport_key');

        if ($codes = array_map('strtoupper', (array) $this->argument('codes'))) {
            $query->whereIn('code', $codes);
        }

        $days    = max(1, (int) $this->option('days'));
        $from    = Carbon::now()->subHours(2);
        $to      = Carbon::today()->addDays($days);
        $matcher = new OddsMatcher();
        $saved   = 0;

        foreach ($query->get() as $competition) {
            $this->line("→ {$competition->code} ".$competition->translate('name')." ({$competition->oddsapi_sport_key})");

            $ours = Fixture::where('league_code', $competition->code)
                ->upcoming()
                ->whereBetween('kickoff_at', [$from, $to])
                ->get();

            if ($ours->isEmpty()) {
                $this->line('   no matches of ours in the window — skipped (no request made)');
                continue;
            }

            $startedAt = now();
            $events    = $api->odds($competition->oddsapi_sport_key, $from, $to);

            if ($error = $api->lastError()) {
                $this->error('   The Odds API: '.$error);
                \App\Models\SyncLog::record('odds', $competition->code, 'failed', 0, 'The Odds API: '.$error, $startedAt);

                // 401 (bad key / quota used up) repeats for every call: stop.
                if (str_starts_with($error, 'HTTP 401') || str_starts_with($error, 'HTTP 429')) {
                    return self::FAILURE;
                }

                continue;
            }

            $matched = 0;

            foreach ($events as $event) {
                $price = OddsApiClient::prices($event);

                if (! $price || empty($event['commence_time'])) {
                    continue;
                }

                $fixture = $matcher->findNear(
                    $ours,
                    (string) ($event['home_team'] ?? ''),
                    (string) ($event['away_team'] ?? ''),
                    Carbon::parse($event['commence_time'])
                );

                if (! $fixture) {
                    $this->line('   ? not matched: '.($event['home_team'] ?? '').' – '.($event['away_team'] ?? ''), null, 'v');
                    continue;
                }

                Odd::updateOrCreate(
                    ['fixture_id' => $fixture->id],
                    $price + [
                        'provider'             => 'oddsapi',
                        'event_id'             => (string) ($event['id'] ?? ''),
                        'apisports_fixture_id' => null,
                        'fetched_at'           => now(),
                    ]
                );

                $matched++;
            }

            $saved += $matched;
            $this->info('   '.count($events)." event(s) with odds, {$matched} matched to our {$ours->count()} fixture(s)");

            $competition->forceFill(['odds_synced_at' => now()])->save();
            \App\Models\SyncLog::record(
                'odds', $competition->code,
                $matched > 0 ? 'ok' : (count($events) ? 'partial' : 'skipped'),
                $matched,
                count($events)." events, {$matched}/{$ours->count()} matched"
                    .($api->remaining() !== null ? ", credits left {$api->remaining()}" : ''),
                $startedAt
            );
        }

        $this->newLine();
        $this->info("Done. {$saved} match(es) with odds saved."
            .($api->remaining() !== null ? " Credits left this month: {$api->remaining()} (used {$api->used()})." : ''));

        return self::SUCCESS;
    }

    /** API-Football: per day, /odds then /fixtures for team names. */
    protected function viaApiSports(ApiSportsTransfersClient $api): int
    {
        if (! $api->isConfigured()) {
            $this->error('API-Football key is not set (Settings → Transfers, or API_FOOTBALL_KEY).');
            return self::FAILURE;
        }

        $query = Competition::active()->ordered()->whereNotNull('apisports_league_id');

        if ($codes = array_map('strtoupper', (array) $this->argument('codes'))) {
            $query->whereIn('code', $codes);
        }

        $competitions = $query->get();
        $days         = max(1, (int) $this->option('days'));
        $season       = (int) ($this->option('season') ?: config('apisports.odds.season') ?: config('football.season'));
        $bookmaker    = config('apisports.odds.bookmaker');
        $matcher      = new OddsMatcher();

        $saved = 0;

        foreach ($competitions as $competition) {
            $this->line("→ {$competition->code} ".$competition->translate('name'));

            for ($d = 0; $d < $days; $d++) {
                $date = Carbon::today()->addDays($d)->toDateString();

                $ours = Fixture::where('league_code', $competition->code)
                    ->whereDate('kickoff_at', $date)
                    ->get();

                if ($ours->isEmpty()) {
                    $this->line("   {$date}: no matches of ours — skipped (no request made)");
                    continue;
                }

                $odds = $api->get('/odds', array_filter([
                    'league'    => $competition->apisports_league_id,
                    'season'    => $season,
                    'date'      => $date,
                    'bet'       => 1,
                    'bookmaker' => $bookmaker,
                ]));

                if ($errors = $api->lastErrors()) {
                    $this->error('   API-Football: '.implode(' ', array_map('strval', $errors)));
                    \App\Models\SyncLog::record('odds', $competition->code, 'failed', 0, 'API-Football: '.implode(' ', array_map('strval', $errors)));

                    // A plan or quota error will repeat for every remaining
                    // call, so stop instead of burning the daily allowance.
                    return self::FAILURE;
                }

                if (empty($odds)) {
                    $this->line("   {$date}: no odds published yet");
                    continue;
                }

                $fixtures = collect($api->get('/fixtures', [
                    'league' => $competition->apisports_league_id,
                    'season' => $season,
                    'date'   => $date,
                ]))->keyBy(fn ($f) => (int) data_get($f, 'fixture.id'));

                $matched = 0;

                foreach ($odds as $row) {
                    $theirId = (int) data_get($row, 'fixture.id');
                    $info    = $fixtures->get($theirId);

                    if (! $info) {
                        continue;
                    }

                    $fixture = $matcher->find($ours, [
                        'home_id' => data_get($info, 'teams.home.id'),
                        'home'    => data_get($info, 'teams.home.name'),
                        'away_id' => data_get($info, 'teams.away.id'),
                        'away'    => data_get($info, 'teams.away.name'),
                    ]);

                    $price = $this->matchWinner($row);

                    if (! $fixture || ! $price) {
                        continue;
                    }

                    Odd::updateOrCreate(
                        ['fixture_id' => $fixture->id],
                        $price + ['provider' => 'apisports', 'apisports_fixture_id' => $theirId, 'fetched_at' => now()]
                    );

                    $matched++;
                }

                $saved += $matched;
                $this->info("   {$date}: ".count($odds)." with odds, {$matched} matched to our fixtures");

                if ($matched > 0) {
                    $competition->forceFill(['odds_synced_at' => now()])->save();
                    \App\Models\SyncLog::record('odds', $competition->code, 'ok', $matched, "{$date}: {$matched} matched (API-Football)");
                }
            }
        }

        $this->newLine();
        $this->info("Done. {$saved} match(es) with odds saved.");

        return self::SUCCESS;
    }

    /** Home / draw / away prices from the first bookmaker offering "Match Winner". */
    protected function matchWinner(array $row): ?array
    {
        foreach ((array) data_get($row, 'bookmakers', []) as $bookmaker) {
            foreach ((array) data_get($bookmaker, 'bets', []) as $bet) {
                if ((int) data_get($bet, 'id') !== 1) {
                    continue;
                }

                $values = collect(data_get($bet, 'values', []))
                    ->mapWithKeys(fn ($v) => [strtolower((string) data_get($v, 'value')) => (float) data_get($v, 'odd')]);

                if (! $values->has('home') || ! $values->has('away')) {
                    continue;
                }

                return [
                    'bookmaker' => data_get($bookmaker, 'name'),
                    'home'      => $values->get('home'),
                    'draw'      => $values->get('draw'),
                    'away'      => $values->get('away'),
                ];
            }
        }

        return null;
    }
}
