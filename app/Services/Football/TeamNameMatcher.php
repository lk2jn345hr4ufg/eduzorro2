<?php

namespace App\Services\Football;

use App\Models\Team;

/**
 * Finds our club for a team name from another provider (BBC, UEFA…):
 * exact normalised names first, then the looser OddsMatcher rules.
 * Unknown clubs / national teams get a stable synthetic id per provider.
 */
class TeamNameMatcher
{
    /** @var array<int, array{id: int, logo: ?string, names: array<int, string>}> */
    protected array $teams;

    protected OddsMatcher $matcher;

    public function __construct()
    {
        $this->matcher = new OddsMatcher();

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

    /** @return array{id: int, logo: ?string}|null */
    public function find(string $name): ?array
    {
        $wanted = $this->matcher->normalise($name);

        foreach ([true, false] as $exact) {
            foreach ($this->teams as $team) {
                foreach ($team['names'] as $candidate) {
                    if ($exact ? $this->matcher->normalise($candidate) === $wanted : $this->matcher->sameTeam($candidate, $name)) {
                        return ['id' => $team['id'], 'logo' => $team['logo']];
                    }
                }
            }
        }

        return null;
    }
}
