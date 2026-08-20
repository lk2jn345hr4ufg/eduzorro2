<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Transfer;
use App\Services\Football\ApiFootballClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncFootballStats extends Command
{
    protected $signature = 'sport:sync-stats
                            {--type=all : What to sync: all|fixtures|standings|transfers}
                            {--team= : Limit to a single team slug}
                            {--country= : Limit to one country (slug, e.g. ukraine)}
                            {--season= : Override the configured season}
                            {--limit=0 : Max teams to process this run (0 = all)}
                            {--sleep=0 : Seconds to wait between teams (API rate limits)}';

    protected $description = 'Store fixtures, standings and transfers from API-Football in the database';

    public function handle(ApiFootballClient $api): int
    {
        if (empty(config('football.api.key'))) {
            $this->error('API-Football key is not set (Settings → API-Football, or API_FOOTBALL_KEY).');
            return self::FAILURE;
        }

        $season = (int) ($this->option('season') ?: config('football.season'));
        $type   = $this->option('type');
        $sleep  = (int) $this->option('sleep');

        $query = Team::query()->active()->ordered()->whereNotNull('api_id');

        if ($slug = $this->option('team')) {
            $query->where('slug', $slug);
        }

        if ($countrySlug = $this->option('country')) {
            $query->whereHas('country', fn ($q) => $q->where('slug', $countrySlug));
        }

        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $teams = $query->get();

        if ($teams->isEmpty()) {
            $this->warn('No matching teams. Import teams first (sport:sync-football), or check the slug.');
            return self::SUCCESS;
        }

        $this->info("Syncing [{$type}] season {$season} for {$teams->count()} team(s)...");

        $fx = $st = $tr = 0;
        $failures = 0;           // consecutive empty API responses
        $doneLeagues = [];       // standings are per league, not per team

        foreach ($teams as $team) {
            $name = $team->translate('name');
            $this->line("→ {$name}");

            if (in_array($type, ['all', 'fixtures'], true)) {
                $rows = $api->teamFixtures($team->api_id, $season);
                $fx  += $this->storeFixtures($rows, $season);
                $this->line('   fixtures: '.count($rows));
                $failures = empty($rows) ? $failures + 1 : 0;
            }

            if (in_array($type, ['all', 'transfers'], true)) {
                $rows = $api->transfers($team->api_id);
                $tr  += $this->storeTransfers($rows, $team->api_id);
                $this->line('   transfers: '.count($rows));
            }

            if (in_array($type, ['all', 'standings'], true)) {
                $league = (int) $team->primary_league_api_id;

                if (! $league) {
                    $this->warn('   no primary league set — standings skipped');
                } elseif (! in_array($league, $doneLeagues, true)) {
                    $rows = $api->standings($league, $season);
                    $st  += $this->storeStandings($rows, $league, $season);
                    $doneLeagues[] = $league;
                    $this->line("   standings (league {$league}): ".count($rows));
                }
            }

            // Bail out early instead of burning the quota on a dead key/season.
            if ($failures >= 5) {
                $this->error('Five empty responses in a row — stopping. Check the season, quota or key.');
                break;
            }

            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        $this->newLine();
        $this->info("Done. fixtures: {$fx}, standings rows: {$st}, transfers: {$tr}.");

        return self::SUCCESS;
    }

    protected function storeFixtures(array $rows, int $season): int
    {
        $n = 0;

        foreach ($rows as $row) {
            $id = data_get($row, 'fixture.id');
            if (! $id) {
                continue;
            }

            Fixture::updateOrCreate(
                ['api_id' => $id],
                [
                    'league_api_id' => data_get($row, 'league.id'),
                    'league_name'   => data_get($row, 'league.name'),
                    'league_round'  => data_get($row, 'league.round'),
                    'season'        => $season,
                    'home_api_id'   => data_get($row, 'teams.home.id'),
                    'home_name'     => data_get($row, 'teams.home.name'),
                    'home_logo'     => data_get($row, 'teams.home.logo'),
                    'away_api_id'   => data_get($row, 'teams.away.id'),
                    'away_name'     => data_get($row, 'teams.away.name'),
                    'away_logo'     => data_get($row, 'teams.away.logo'),
                    'goals_home'    => data_get($row, 'goals.home'),
                    'goals_away'    => data_get($row, 'goals.away'),
                    'status_short'  => data_get($row, 'fixture.status.short'),
                    'kickoff_at'    => $this->date(data_get($row, 'fixture.date')),
                ]
            );

            $n++;
        }

        return $n;
    }

    protected function storeStandings(array $rows, int $league, int $season): int
    {
        $n = 0;

        foreach ($rows as $row) {
            $teamId = data_get($row, 'team.id');
            if (! $teamId) {
                continue;
            }

            Standing::updateOrCreate(
                ['league_api_id' => $league, 'season' => $season, 'team_api_id' => $teamId],
                [
                    'rank'          => data_get($row, 'rank'),
                    'team_name'     => data_get($row, 'team.name'),
                    'team_logo'     => data_get($row, 'team.logo'),
                    'group_label'   => data_get($row, 'group'),
                    'form'          => data_get($row, 'form'),
                    'played'        => (int) data_get($row, 'all.played', 0),
                    'win'           => (int) data_get($row, 'all.win', 0),
                    'draw'          => (int) data_get($row, 'all.draw', 0),
                    'lose'          => (int) data_get($row, 'all.lose', 0),
                    'goals_for'     => (int) data_get($row, 'all.goals.for', 0),
                    'goals_against' => (int) data_get($row, 'all.goals.against', 0),
                    'points'        => (int) data_get($row, 'points', 0),
                ]
            );

            $n++;
        }

        return $n;
    }

    protected function storeTransfers(array $rows, int $teamApiId): int
    {
        $n = 0;

        foreach ($rows as $row) {
            $player = data_get($row, 'player.name');

            foreach ((array) data_get($row, 'transfers', []) as $t) {
                $date  = data_get($t, 'date');
                $inId  = data_get($t, 'teams.in.id');
                $outId = data_get($t, 'teams.out.id');

                if (! $player || ! $date) {
                    continue;
                }

                $fingerprint = sha1("{$teamApiId}|{$player}|{$date}|{$inId}|{$outId}");

                Transfer::updateOrCreate(
                    ['fingerprint' => $fingerprint],
                    [
                        'team_api_id'   => $teamApiId,
                        'player_name'   => $player,
                        'transfer_date' => $this->date($date),
                        'type'          => data_get($t, 'type'),
                        'in_api_id'     => $inId,
                        'in_name'       => data_get($t, 'teams.in.name'),
                        'in_logo'       => data_get($t, 'teams.in.logo'),
                        'out_api_id'    => $outId,
                        'out_name'      => data_get($t, 'teams.out.name'),
                        'out_logo'      => data_get($t, 'teams.out.logo'),
                    ]
                );

                $n++;
            }
        }

        return $n;
    }

    protected function date(?string $raw): ?Carbon
    {
        if (! $raw) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
