<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Odd;
use App\Services\Football\ApiSportsTransfersClient;
use App\Services\Football\OddsMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Pulls 1X2 odds from API-Football for upcoming matches and attaches them to
 * our fixtures.
 *
 * Cost per competition per day: one /odds call, plus one /fixtures call only
 * when odds came back (it maps their fixture ids to team names). With the
 * free plan's 100 requests/day, keep --days small and run it daily.
 */
class SyncOdds extends Command
{
    protected $signature = 'sport:sync-odds
                            {codes?* : Competition codes, e.g. PL CL (default: all active with an API-Football league id)}
                            {--days=2 : How many days ahead, starting today}
                            {--season= : API-Football season (start year); defaults to the site season}';

    protected $description = 'Import 1X2 odds from API-Football for upcoming matches';

    public function handle(ApiSportsTransfersClient $api): int
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
                        $price + ['apisports_fixture_id' => $theirId, 'fetched_at' => now()]
                    );

                    $matched++;
                }

                $saved += $matched;
                $this->info("   {$date}: ".count($odds)." with odds, {$matched} matched to our fixtures");
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
