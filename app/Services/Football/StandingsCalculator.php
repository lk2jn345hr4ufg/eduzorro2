<?php

namespace App\Services\Football;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Standing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Group tables computed from our own results, for competitions whose source
 * gives fixtures but no tables (BBC Sport: Nations League, Euro qualifying,
 * Europa League).
 *
 * Only group / league-phase rounds are tabulated (knockout rounds are not).
 * Ordering: points, goal difference, goals scored, name. UEFA's real
 * tie-breakers start with head-to-head, so tied teams may occasionally be
 * listed in a different order than on uefa.com.
 */
class StandingsCalculator
{
    /** Round labels that form tables. */
    public static function isTableRound(?string $round): bool
    {
        $r = Str::lower((string) $round);

        return $r !== '' && (str_contains($r, 'group') || str_contains($r, 'league stage')
            || str_contains($r, 'league phase') || preg_match('/\bleague [a-d]\b/', $r));
    }

    /** Synthetic league id for standings rows of competitions without an API id. */
    public static function leagueId(Competition $competition): int
    {
        return (int) ($competition->api_id ?: 7_000_000 + $competition->id);
    }

    /** Rebuild the tables of one competition season. Returns rows written. */
    public function rebuild(Competition $competition, int $season): int
    {
        $fixtures = Fixture::where('league_code', $competition->code)
            ->where('season', $season)
            ->orderBy('kickoff_at')
            ->get()
            ->filter(fn ($f) => self::isTableRound($f->league_round));

        $tables = [];

        foreach ($fixtures as $f) {
            $group = trim((string) $f->league_round);

            foreach ([[$f->home_api_id, $f->home_name, $f->home_logo], [$f->away_api_id, $f->away_name, $f->away_logo]] as [$id, $name, $logo]) {
                $tables[$group][$id] ??= [
                    'team_api_id' => (int) $id, 'team_name' => $name, 'team_logo' => $logo,
                    'played' => 0, 'win' => 0, 'draw' => 0, 'lose' => 0,
                    'goals_for' => 0, 'goals_against' => 0, 'points' => 0, 'form' => [],
                ];
            }

            if (! in_array($f->status_short, Fixture::FINISHED, true) || $f->goals_home === null || $f->goals_away === null) {
                continue;
            }

            foreach ([[$f->home_api_id, $f->goals_home, $f->goals_away], [$f->away_api_id, $f->goals_away, $f->goals_home]] as [$id, $for, $against]) {
                $row = &$tables[$group][$id];
                $row['played']++;
                $row['goals_for']     += $for;
                $row['goals_against'] += $against;

                if ($for > $against) {
                    $row['win']++;
                    $row['points'] += 3;
                    $row['form'][] = 'W';
                } elseif ($for === $against) {
                    $row['draw']++;
                    $row['points'] += 1;
                    $row['form'][] = 'D';
                } else {
                    $row['lose']++;
                    $row['form'][] = 'L';
                }

                unset($row);
            }
        }

        $leagueId = self::leagueId($competition);
        $written  = 0;

        DB::transaction(function () use ($tables, $competition, $season, $leagueId, &$written) {
            Standing::where('league_code', $competition->code)->where('season', $season)->delete();

            foreach ($tables as $group => $rows) {
                usort($rows, fn ($a, $b) => [$b['points'], $b['goals_for'] - $b['goals_against'], $b['goals_for'], $a['team_name']]
                    <=> [$a['points'], $a['goals_for'] - $a['goals_against'], $a['goals_for'], $b['team_name']]);

                foreach ($rows as $i => $row) {
                    // One team can't sit in two groups of the same season.
                    Standing::updateOrCreate(
                        ['league_api_id' => $leagueId, 'season' => $season, 'team_api_id' => $row['team_api_id']],
                        [
                            'league_code'   => $competition->code,
                            'rank'          => $i + 1,
                            'team_name'     => $row['team_name'],
                            'team_logo'     => $row['team_logo'],
                            'group_label'   => count($tables) > 1 || ! Str::contains(Str::lower($group), 'league stage') ? $group : null,
                            'form'          => implode(',', array_slice($row['form'], -5)),
                            'played'        => $row['played'],
                            'win'           => $row['win'],
                            'draw'          => $row['draw'],
                            'lose'          => $row['lose'],
                            'goals_for'     => $row['goals_for'],
                            'goals_against' => $row['goals_against'],
                            'points'        => $row['points'],
                        ]
                    );
                    $written++;
                }
            }
        });

        return $written;
    }
}
