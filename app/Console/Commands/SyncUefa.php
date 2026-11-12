<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Standing;
use App\Models\SyncLog;
use App\Services\Football\StandingsCalculator;
use App\Services\Football\TeamNameMatcher;
use App\Services\Football\UefaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fixtures, results and OFFICIAL tables from uefa.com's JSON services, for
 * competitions with a UEFA competition id (Admin → Championships &
 * tournaments): Nations League (2014), Europa League (14)…
 *
 * Per competition: matches (100 per request, paged) + 1 standings request.
 */
class SyncUefa extends Command
{
    protected $signature = 'sport:sync-uefa
                            {codes?* : Competition codes, e.g. UNL EL (default: all with a UEFA id)}
                            {--season-year= : UEFA season year = the year the season ENDS (2026/27 → 2027); default: current}
                            {--delay=2 : Seconds between competitions}';

    protected $description = 'Import fixtures, results and tables from uefa.com';

    protected const MATCH_ID_BASE = 6_000_000_000_000;
    protected const TEAM_ID_BASE  = 5_000_000_000_000;

    protected TeamNameMatcher $teams;

    /** National-team competitions never match clubs ("Austria" ≠ Austria Wien). */
    protected bool $national = false;

    public function handle(UefaClient $uefa): int
    {
        $query = Competition::query()->whereNotNull('uefa_competition_id')->ordered();

        if ($codes = array_map('strtoupper', (array) $this->argument('codes'))) {
            $query->whereIn('code', $codes);
        }

        $competitions = $query->get();

        if ($competitions->isEmpty()) {
            $this->warn('No competition has a UEFA competition id (Admin → Championships & tournaments).');
            return self::SUCCESS;
        }

        $seasonYear  = (int) ($this->option('season-year') ?: (now()->month >= 7 ? now()->year + 1 : now()->year));
        $season      = $seasonYear - 1; // our seasons are keyed by start year
        $delay       = max(0, (int) $this->option('delay'));
        $this->teams = new TeamNameMatcher();
        $failed      = false;

        foreach ($competitions as $i => $competition) {
            if ($i > 0 && $delay) {
                sleep($delay);
            }

            $this->national = (bool) $competition->is_national;
            $this->line("→ {$competition->code} ".$competition->translate('name')." (uefa {$competition->uefa_competition_id}, {$seasonYear})");
            $startedAt = now();

            // ---- matches -------------------------------------------------
            $matches = $uefa->matches((int) $competition->uefa_competition_id, $seasonYear);

            if ($matches === null) {
                $this->error('   matches: '.$uefa->lastError());
                SyncLog::record('matches', $competition->code, 'failed', 0, 'UEFA matches: '.$uefa->lastError(), $startedAt);
                $failed = true;
                continue;
            }

            $phase   = $competition->uefa_phase;
            $kept    = array_values(array_filter($matches, fn ($m) => ! $phase || strtoupper((string) data_get($m, 'round.phase')) === strtoupper($phase)));
            $saved   = 0;

            foreach ($kept as $m) {
                if ($this->storeMatch($competition, $m, $season)) {
                    $saved++;
                }
            }

            $this->line('   matches: '.count($matches).' from UEFA, '.count($kept).($phase ? " in phase {$phase}" : '').", {$saved} saved");

            // ---- tables --------------------------------------------------
            $groups    = $uefa->standings((int) $competition->uefa_competition_id, $seasonYear);
            $tableRows = 0;
            $tableNote = '';

            if ($groups) {
                $tableRows = $this->storeStandings($competition, $groups, $season);
                $tableNote = "{$tableRows} official table rows";
            }

            if (! $tableRows) {
                // No official table (yet): compute one from the results.
                $tableRows = app(StandingsCalculator::class)->rebuild($competition, $season);
                $tableNote = "{$tableRows} computed table rows".($groups === null ? ' (UEFA standings: '.$uefa->lastError().')' : '');
            }

            $this->line("   tables: {$tableNote}");

            if ($saved) {
                $competition->forceFill(['matches_synced_at' => now()])->save();
            }

            SyncLog::record('matches', $competition->code, $saved ? 'ok' : 'skipped', $saved,
                "UEFA {$seasonYear}: {$saved} matches, {$tableNote}", $startedAt);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    protected function storeMatch(Competition $competition, array $m, int $season): bool
    {
        $id      = data_get($m, 'id');
        $kickoff = data_get($m, 'kickOffTime.dateTime');
        $home    = $this->team((array) data_get($m, 'homeTeam', []));
        $away    = $this->team((array) data_get($m, 'awayTeam', []));

        if (! $id || ! $kickoff || ! $home || ! $away) {
            return false;
        }

        $status = $this->status($m);
        $scored = in_array($status, Fixture::FINISHED, true) || in_array($status, Fixture::LIVE, true);

        Fixture::updateOrCreate(
            ['api_id' => self::MATCH_ID_BASE + (int) preg_replace('/\D+/', '', (string) $id)],
            [
                'source'        => 'uefa',
                'league_api_id' => StandingsCalculator::leagueId($competition),
                'league_code'   => $competition->code,
                'league_name'   => $competition->translate('name', 'en'),
                'league_round'  => mb_substr($this->roundLabel($m), 0, 190) ?: null,
                'season'        => $season,
                'home_api_id'   => $home['id'],
                'home_name'     => $home['name'],
                'home_logo'     => $home['logo'],
                'away_api_id'   => $away['id'],
                'away_name'     => $away['name'],
                'away_logo'     => $away['logo'],
                'goals_home'    => $scored ? self::int(data_get($m, 'score.total.home')) : null,
                'goals_away'    => $scored ? self::int(data_get($m, 'score.total.away')) : null,
                'status_short'  => $status,
                'kickoff_at'    => Carbon::parse($kickoff)->utc(),
                'venue'         => $this->venue($m),
            ]
        );

        return true;
    }

    /** Group name for group stages ("League A - Group 1"), else the round name. */
    protected function roundLabel(array $m): string
    {
        $group = data_get($m, 'group');

        if (is_string($group) && $group !== '') {
            return $group;
        }

        return (string) (data_get($m, 'group.metaData.groupName')
            ?? data_get($m, 'group.translations.name.EN')
            ?? data_get($m, 'round.metaData.name')
            ?? data_get($m, 'round.translations.name.EN')
            ?? '');
    }

    protected function status(array $m): string
    {
        $status = strtoupper((string) data_get($m, 'status'));
        $reason = strtoupper((string) data_get($m, 'winner.match.reason'));

        return match (true) {
            $status === 'FINISHED' && str_contains($reason, 'PENALT') => 'PEN',
            $status === 'FINISHED' && str_contains($reason, 'EXTRA')  => 'AET',
            $status === 'FINISHED'                                     => 'FT',
            $status === 'LIVE'                                         => 'IN_PLAY',
            str_contains($status, 'POSTPON')                           => 'POSTPONED',
            str_contains($status, 'CANCEL') || str_contains($status, 'ABANDON') => 'CANCELLED',
            default                                                    => 'TIMED',
        };
    }

    protected function venue(array $m): ?string
    {
        $stadium = data_get($m, 'stadium.translations.officialName.EN')
            ?? data_get($m, 'stadium.translations.name.EN')
            ?? data_get($m, 'stadium.name');
        $city = data_get($m, 'stadium.city.translations.name.EN') ?? data_get($m, 'stadium.city.name');

        $label = trim(implode(', ', array_filter([$stadium, $city])));

        return $label !== '' ? mb_substr($label, 0, 120) : null;
    }

    /**
     * Our club when we have it (links, time zone, stadium), else UEFA's team
     * with a stable synthetic id.
     *
     * @return array{id: int, name: string, logo: ?string}|null
     */
    protected function team(array $t): ?array
    {
        $name = trim((string) ($t['internationalName'] ?? data_get($t, 'translations.displayName.EN') ?? ''));

        if ($name === '') {
            return null;
        }

        $logo = $t['mediumLogoUrl'] ?? $t['logoUrl'] ?? $t['bigLogoUrl'] ?? null;
        $ours = $this->national ? null : $this->teams->find($name);

        return [
            'id'   => $ours['id'] ?? self::TEAM_ID_BASE + (int) (preg_replace('/\D+/', '', (string) ($t['id'] ?? '')) ?: crc32(mb_strtolower($name))),
            'name' => $name,
            'logo' => $ours['logo'] ?? $logo,
        ];
    }

    protected function storeStandings(Competition $competition, array $groups, int $season): int
    {
        $leagueId = StandingsCalculator::leagueId($competition);
        $rows     = [];

        foreach ($groups as $g) {
            $label = (string) (data_get($g, 'group.metaData.groupName')
                ?? data_get($g, 'group.translations.name.EN')
                ?? '');

            foreach ((array) data_get($g, 'items', []) as $pos => $item) {
                $team = $this->team((array) data_get($item, 'team', []));

                if (! $team) {
                    continue;
                }

                $for     = self::int(data_get($item, 'goalsFor') ?? data_get($item, 'goalsScored'));
                $against = self::int(data_get($item, 'goalsAgainst') ?? data_get($item, 'goalsConceded'));

                $rows[] = [
                    'group'         => $label,
                    'team'          => $team,
                    'rank'          => self::int(data_get($item, 'rank') ?? data_get($item, 'position')) ?? $pos + 1,
                    'played'        => self::int(data_get($item, 'played') ?? data_get($item, 'playedMatches')) ?? 0,
                    'win'           => self::int(data_get($item, 'won') ?? data_get($item, 'wins')) ?? 0,
                    'draw'          => self::int(data_get($item, 'drawn') ?? data_get($item, 'draws')) ?? 0,
                    'lose'          => self::int(data_get($item, 'lost') ?? data_get($item, 'losses')) ?? 0,
                    'goals_for'     => $for ?? 0,
                    'goals_against' => $against ?? 0,
                    'points'        => self::int(data_get($item, 'points')) ?? 0,
                ];
            }
        }

        if (! $rows) {
            return 0;
        }

        $single = count(array_unique(array_column($rows, 'group'))) === 1;

        DB::transaction(function () use ($competition, $season, $leagueId, $rows, $single) {
            Standing::where('league_code', $competition->code)->where('season', $season)->delete();

            foreach ($rows as $r) {
                Standing::updateOrCreate(
                    ['league_api_id' => $leagueId, 'season' => $season, 'team_api_id' => $r['team']['id']],
                    [
                        'league_code'   => $competition->code,
                        'rank'          => $r['rank'],
                        'team_name'     => $r['team']['name'],
                        'team_logo'     => $r['team']['logo'],
                        'group_label'   => $single ? null : ($r['group'] ?: null),
                        'form'          => $this->form($competition->code, $season, $r['team']['id']),
                        'played'        => $r['played'],
                        'win'           => $r['win'],
                        'draw'          => $r['draw'],
                        'lose'          => $r['lose'],
                        'goals_for'     => $r['goals_for'],
                        'goals_against' => $r['goals_against'],
                        'points'        => $r['points'],
                    ]
                );
            }
        });

        return count($rows);
    }

    /** Last five results of a team in this competition season, oldest first: "W,D,L,W,W". */
    protected function form(string $code, int $season, int $teamId): ?string
    {
        $out = Fixture::where('league_code', $code)->where('season', $season)
            ->whereIn('status_short', Fixture::FINISHED)
            ->where(fn ($q) => $q->where('home_api_id', $teamId)->orWhere('away_api_id', $teamId))
            ->orderByDesc('kickoff_at')->take(5)->get()->reverse()
            ->map(function ($f) use ($teamId) {
                $mine   = (int) $f->home_api_id === $teamId ? $f->goals_home : $f->goals_away;
                $theirs = (int) $f->home_api_id === $teamId ? $f->goals_away : $f->goals_home;

                return $mine > $theirs ? 'W' : ($mine < $theirs ? 'L' : 'D');
            });

        return $out->isEmpty() ? null : $out->implode(',');
    }

    protected static function int($v): ?int
    {
        return is_numeric($v) ? (int) $v : null;
    }
}
