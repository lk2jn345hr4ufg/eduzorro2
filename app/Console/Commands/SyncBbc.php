<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\SyncLog;
use App\Models\Team;
use App\Services\Football\BbcScoresClient;
use App\Services\Football\OddsMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Fixtures and results from BBC Sport for competitions with a BBC slug
 * (Admin → Championships & tournaments → "BBC slug"), e.g. the Europa League,
 * which football-data's free plan doesn't include.
 *
 * One page per month. By default: last month (results), this month and next
 * month. BBC team names are matched to our clubs so links, time zones,
 * stadiums and the clubs' "European cups" tab work; clubs we don't have are
 * shown as plain text.
 */
class SyncBbc extends Command
{
    protected $signature = 'sport:sync-bbc
                            {codes?* : Competition codes, e.g. EL (default: all with a BBC slug)}
                            {--months=* : Months to fetch, YYYY-MM (default: previous, current, next)}
                            {--from= : First month YYYY-MM (with --to: a range)}
                            {--to= : Last month YYYY-MM}
                            {--delay=2 : Seconds between page requests}';

    protected $description = 'Import fixtures & results from BBC Sport scores-fixtures pages';

    /** Synthetic id ranges, far above football-data ids. */
    protected const MATCH_ID_BASE = 9_000_000_000_000;
    protected const TEAM_ID_BASE  = 8_000_000_000_000;

    /** @var array<int, array{id: int, names: array<int, string>, logo: ?string}> */
    protected array $teams = [];

    protected OddsMatcher $matcher;

    public function handle(BbcScoresClient $bbc): int
    {
        $query = Competition::query()->whereNotNull('bbc_slug')->where('bbc_slug', '!=', '')->ordered();

        if ($codes = array_map('strtoupper', (array) $this->argument('codes'))) {
            $query->whereIn('code', $codes);
        }

        $competitions = $query->get();

        if ($competitions->isEmpty()) {
            $this->warn('No competition has a BBC slug (Admin → Championships & tournaments).');
            return self::SUCCESS;
        }

        $months        = $this->months();
        $delay         = max(0, (int) $this->option('delay'));
        $this->matcher = new OddsMatcher();
        $this->loadTeams();

        $failed = false;

        foreach ($competitions as $competition) {
            $this->line("→ {$competition->code} ".$competition->translate('name')." (bbc: {$competition->bbc_slug})");

            $startedAt = now();
            $saved     = 0;
            $unmatched = [];
            $errors    = [];
            $seen      = [];

            foreach ($months as $i => $month) {
                if ($i > 0 && $delay) {
                    sleep($delay);
                }

                $rows = $bbc->month($competition->bbc_slug, $month);

                if ($rows === null) {
                    $errors[] = "{$month}: ".$bbc->lastError();
                    $this->error("   {$month}: ".$bbc->lastError());
                    continue;
                }

                foreach ($rows as $row) {
                    if (isset($seen[$row['event_id']])) {
                        continue; // a match listed on two month pages
                    }

                    $seen[$row['event_id']] = true;

                    if ($this->store($competition, $row, $unmatched)) {
                        $saved++;
                    }
                }

                $this->line("   {$month}: ".count($rows).' match(es)');
            }

            $unmatched = array_values(array_unique($unmatched));

            if ($unmatched && $this->output->isVerbose()) {
                $this->line('   not our clubs (shown as text): '.implode(', ', $unmatched));
            }

            $status = $errors ? ($saved ? 'partial' : 'failed') : 'ok';

            if ($saved) {
                $competition->forceFill(['matches_synced_at' => now()])->save();
            }

            SyncLog::record('matches', $competition->code, $status, $saved,
                'BBC '.implode(', ', $months).": {$saved} matches"
                    .($unmatched ? ', '.count($unmatched).' clubs not on the site' : '')
                    .($errors ? ' · '.implode(' · ', $errors) : ''),
                $startedAt
            );

            $this->info("   saved {$saved} match(es)");
            $failed = $failed || $status === 'failed';
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    protected function store(Competition $competition, array $row, array &$unmatched): bool
    {
        if (! $row['kickoff_utc']) {
            return false;
        }

        try {
            $kickoff = Carbon::parse($row['kickoff_utc'])->utc();
        } catch (\Throwable) {
            return false;
        }

        $home = $this->team($row['home'], $unmatched);
        $away = $this->team($row['away'], $unmatched);

        $status = $this->status($row);
        $played = in_array($status, Fixture::FINISHED, true) || in_array($status, Fixture::LIVE, true);

        Fixture::updateOrCreate(
            ['api_id' => $this->matchId($row['event_id'])],
            [
                'source'        => 'bbc',
                'league_api_id' => (int) ($competition->api_id ?? 0),
                'league_code'   => $competition->code,
                'league_name'   => $competition->translate('name', 'en'),
                'league_round'  => $row['stage'] ? mb_substr($row['stage'], 0, 190) : null,
                'season'        => $kickoff->month >= 7 ? $kickoff->year : $kickoff->year - 1,
                'home_api_id'   => $home['id'],
                'home_name'     => $row['home'],
                'home_logo'     => $home['logo'],
                'away_api_id'   => $away['id'],
                'away_name'     => $row['away'],
                'away_logo'     => $away['logo'],
                'goals_home'    => $played ? $row['home_score'] : null,
                'goals_away'    => $played ? $row['away_score'] : null,
                'status_short'  => $status,
                'kickoff_at'    => $kickoff,
            ]
        );

        return true;
    }

    /** BBC status/period → our status codes (see Fixture::FINISHED / LIVE). */
    protected function status(array $row): string
    {
        $period  = strtoupper($row['period']);
        $comment = strtolower($row['status_comment'].' '.$row['period']);

        if (str_contains($comment, 'postpon')) {
            return 'POSTPONED';
        }

        if (str_contains($comment, 'cancel') || str_contains($comment, 'abandon')) {
            return 'CANCELLED';
        }

        return match ($row['status']) {
            'PostEvent' => match (true) {
                str_contains($period, 'PEN') => 'PEN',
                str_contains($period, 'AET') || str_contains($period, 'EXTRA') => 'AET',
                default => 'FT',
            },
            'MidEvent' => $period === 'HT' ? 'PAUSED' : 'IN_PLAY',
            default    => 'TIMED',
        };
    }

    /**
     * Our club for a BBC name, or a stable synthetic id for clubs we don't
     * have (so the same club keeps the same id across syncs).
     *
     * @return array{id: int, logo: ?string}
     */
    protected function team(string $name, array &$unmatched): array
    {
        $wanted = $this->matcher->normalise($name);

        // Exact (normalised) names first, so "Real Betis" can never fall
        // through to a looser prefix match with another club.
        foreach ([true, false] as $exact) {
            foreach ($this->teams as $team) {
                foreach ($team['names'] as $candidate) {
                    $hit = $exact
                        ? $this->matcher->normalise($candidate) === $wanted
                        : $this->matcher->sameTeam($candidate, $name);

                    if ($hit) {
                        return ['id' => $team['id'], 'logo' => $team['logo']];
                    }
                }
            }
        }

        $unmatched[] = $name;

        return ['id' => self::TEAM_ID_BASE + crc32(mb_strtolower($name)), 'logo' => null];
    }

    protected function matchId(string $eventId): int
    {
        $digits = preg_replace('/\D+/', '', $eventId);

        return self::MATCH_ID_BASE + ($digits !== '' && strlen($digits) <= 12 ? (int) $digits : crc32($eventId));
    }

    protected function loadTeams(): void
    {
        $this->teams = Team::query()->whereNotNull('api_id')->where('is_active', true)->get()
            ->map(function (Team $t) {
                $names = json_decode((string) $t->getRawOriginal('name'), true);
                $names = is_array($names) ? array_values($names) : [(string) $t->getRawOriginal('name')];

                return [
                    'id'    => (int) $t->api_id,
                    'logo'  => $t->logo_url,
                    'names' => collect($names)->push($t->short_name)->filter()->unique()->values()->all(),
                ];
            })
            ->all();
    }

    /** @return array<int, string> YYYY-MM */
    protected function months(): array
    {
        if ($list = array_filter((array) $this->option('months'))) {
            return array_values($list);
        }

        if ($from = $this->option('from')) {
            $start = Carbon::createFromFormat('Y-m-d', $from.'-01')->startOfMonth();
            $end   = Carbon::createFromFormat('Y-m-d', ($this->option('to') ?: $from).'-01')->startOfMonth();
            $out   = [];

            for ($m = $start->copy(); $m->lte($end) && count($out) < 24; $m->addMonth()) {
                $out[] = $m->format('Y-m');
            }

            return $out;
        }

        $now = now()->startOfMonth();

        return [$now->copy()->subMonth()->format('Y-m'), $now->format('Y-m'), $now->copy()->addMonth()->format('Y-m')];
    }
}
