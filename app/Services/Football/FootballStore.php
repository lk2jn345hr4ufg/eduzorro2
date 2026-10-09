<?php

namespace App\Services\Football;

use App\Models\Fixture;
use App\Models\Standing;
use Illuminate\Support\Carbon;

/**
 * Persists normalised fixtures and standing rows. Shared by the sync commands
 * so every path writes the same columns the same way.
 */
class FootballStore
{
    public function fixtures(array $rows, int $fallbackSeason): int
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
                    'league_code'   => data_get($row, 'league.code'),
                    'league_name'   => data_get($row, 'league.name'),
                    'league_round'  => data_get($row, 'league.round'),
                    'season'        => (int) (data_get($row, 'season') ?: $fallbackSeason),
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

    public function standings(array $rows, string $code, int $season): int
    {
        $n = 0;

        foreach ($rows as $row) {
            $teamId = data_get($row, 'team.id');

            if (! $teamId) {
                continue;
            }

            Standing::updateOrCreate(
                [
                    'league_api_id' => data_get($row, 'competition_id'),
                    'season'        => $season,
                    'team_api_id'   => $teamId,
                ],
                [
                    'league_code'   => $code,
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

    /**
     * Replace a team's squad. Returns the number of players stored; an empty
     * squad leaves the existing one untouched (an API hiccup must not wipe it).
     */
    public function squad(\App\Models\Team $team, array $players, ?array $coach = null): int
    {
        if (empty($players)) {
            return 0;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($team, $players, $coach) {
            $team->players()->delete();

            foreach ($players as $p) {
                $team->players()->create([
                    'api_id'         => $p['id'] ?: null,
                    'name'           => mb_substr($p['name'], 0, 255),
                    'position'       => $p['position'] ? mb_substr($p['position'], 0, 40) : null,
                    'line'           => \App\Models\Player::lineFor($p['position']),
                    'shirt_number'   => is_numeric($p['shirt_number']) ? (int) $p['shirt_number'] : null,
                    'date_of_birth'  => $p['date_of_birth'] ?: null,
                    'nationality'    => $p['nationality'] ? mb_substr($p['nationality'], 0, 80) : null,
                    'contract_until' => $p['contract_until'] ? mb_substr((string) $p['contract_until'], 0, 10) : null,
                ]);
            }

            $team->forceFill([
                'coach_name'        => $coach['name'] ?? $team->coach_name,
                'coach_nationality' => $coach['nationality'] ?? $team->coach_nationality,
                'squad_synced_at'   => now(),
            ])->save();
        });

        return count($players);
    }
}
