<?php

namespace App\Services\Football;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for football-data.org (v4).
 *
 * Responses are normalised into the internal shape the rest of the app already
 * speaks (fixture.*, teams.home.*, goals.*, league.*, standings rows), so the
 * controllers, sync command and Blade views did not have to change when we
 * moved off api-sports.
 *
 * Never throws for HTTP/API errors: it logs and returns an empty array, so a
 * rate-limited or flaky upstream degrades to an empty tab instead of a 500.
 */
class ApiFootballClient
{
    /** Statuses football-data uses for a finished match. */
    protected const FINISHED = ['FINISHED', 'AWARDED'];

    protected function http(): PendingRequest
    {
        $cfg = config('football.api');

        return Http::baseUrl($cfg['base_url'])
            ->timeout($cfg['timeout'] ?? 15)
            ->withHeaders(['X-Auth-Token' => $cfg['token']])
            ->acceptJson();
    }

    public function isConfigured(): bool
    {
        return ! empty(config('football.api.token'));
    }

    /** Low-level GET returning the decoded body, or [] on failure. */
    public function get(string $path, array $query = []): array
    {
        if (! $this->isConfigured()) {
            Log::warning('football-data token missing; skipping request', ['path' => $path]);
            return [];
        }

        try {
            $res = $this->http()->get($path, $query);

            if ($res->status() === 429) {
                Log::warning('football-data rate limit hit', ['path' => $path]);
                return [];
            }

            if ($res->failed()) {
                Log::warning('football-data request failed', [
                    'path' => $path, 'status' => $res->status(), 'body' => $res->body(),
                ]);
                return [];
            }

            return (array) $res->json();
        } catch (\Throwable $e) {
            Log::warning('football-data request threw', ['path' => $path, 'error' => $e->getMessage()]);
            return [];
        }
    }

    protected function remember(string $key, int $ttl, callable $cb): array
    {
        return Cache::remember("football:{$key}", $ttl, $cb);
    }

    // ---- Taxonomy -----------------------------------------------------------

    /**
     * Teams of a competition, already normalised:
     * ['id','name','code','crest','founded','venue','area','competition_id','competition_code']
     */
    public function teamsByCompetition(string $code, ?int $season = null): array
    {
        $body = $this->get("/competitions/{$code}/teams", array_filter(['season' => $season]));

        $competitionId = data_get($body, 'competition.id');

        return collect(data_get($body, 'teams', []))->map(fn ($t) => [
            'id'               => data_get($t, 'id'),
            'name'             => data_get($t, 'name'),
            'code'             => data_get($t, 'tla'),
            'crest'            => data_get($t, 'crest'),
            'founded'          => data_get($t, 'founded'),
            'venue'            => data_get($t, 'venue'),
            'area'             => data_get($t, 'area.name'),
            'competition_id'   => $competitionId,
            'competition_code' => $code,
        ])->filter(fn ($t) => $t['id'] && $t['name'])->values()->all();
    }

    // ---- Per-team data ------------------------------------------------------

    /** All matches for a team in a season, in the app's internal fixture shape. */
    public function teamFixtures(int $teamId, ?int $season = null): array
    {
        $ttl = (int) config('football.cache.fixtures');

        return $this->remember("fixtures:{$teamId}:".($season ?: 'current'), $ttl, function () use ($teamId, $season) {
            $body = $this->get("/teams/{$teamId}/matches", array_filter([
                'season' => $season,
                'limit'  => 200,
            ]));

            return collect(data_get($body, 'matches', []))
                ->map(fn ($m) => $this->normaliseMatch($m))
                ->all();
        });
    }

    /** Team fixtures limited to the configured European competitions. */
    public function teamEuroFixtures(int $teamId, ?int $season = null): array
    {
        $codes = array_keys(config('football.euro_competitions', []));

        return array_values(array_filter(
            $this->teamFixtures($teamId, $season),
            fn ($fx) => in_array(data_get($fx, 'league.code'), $codes, true)
        ));
    }

    /** Standings table for a competition code, in the app's internal shape. */
    public function standings(string $code, ?int $season = null): array
    {
        $ttl = (int) config('football.cache.standings');

        return $this->remember("standings:{$code}:".($season ?: 'current'), $ttl, function () use ($code, $season) {
            $body = $this->get("/competitions/{$code}/standings", array_filter(['season' => $season]));

            $competitionId = data_get($body, 'competition.id');

            // Prefer the overall table; group stages return several blocks.
            $group = collect(data_get($body, 'standings', []))
                ->firstWhere('type', 'TOTAL') ?? data_get($body, 'standings.0', []);

            return collect(data_get($group, 'table', []))->map(fn ($r) => [
                'rank'   => data_get($r, 'position'),
                'points' => data_get($r, 'points'),
                'group'  => data_get($group, 'group'),
                'form'   => data_get($r, 'form'),
                'team'   => [
                    'id'   => data_get($r, 'team.id'),
                    'name' => data_get($r, 'team.name'),
                    'logo' => data_get($r, 'team.crest'),
                ],
                'all' => [
                    'played' => data_get($r, 'playedGames'),
                    'win'    => data_get($r, 'won'),
                    'draw'   => data_get($r, 'draw'),
                    'lose'   => data_get($r, 'lost'),
                    'goals'  => [
                        'for'     => data_get($r, 'goalsFor'),
                        'against' => data_get($r, 'goalsAgainst'),
                    ],
                ],
                'competition_id'   => $competitionId,
                'competition_code' => $code,
            ])->all();
        });
    }

    /**
     * football-data.org has no transfers endpoint. Kept so callers don't need
     * to special-case it; the tab falls back to whatever is already stored.
     */
    public function transfers(int $teamId): array
    {
        return [];
    }

    // ---- Normalisation ------------------------------------------------------

    /** football-data match -> the internal fixture shape used across the app. */
    protected function normaliseMatch(array $m): array
    {
        $status = data_get($m, 'status');

        return [
            'fixture' => [
                'id'     => data_get($m, 'id'),
                'date'   => data_get($m, 'utcDate'),
                'status' => [
                    // Map to the short codes the views/splitters already expect.
                    'short' => in_array($status, self::FINISHED, true) ? 'FT' : $status,
                ],
            ],
            'league' => [
                'id'    => data_get($m, 'competition.id'),
                'code'  => data_get($m, 'competition.code'),
                'name'  => data_get($m, 'competition.name'),
                'round' => data_get($m, 'matchday'),
            ],
            'teams' => [
                'home' => [
                    'id'   => data_get($m, 'homeTeam.id'),
                    'name' => data_get($m, 'homeTeam.name'),
                    'logo' => data_get($m, 'homeTeam.crest'),
                ],
                'away' => [
                    'id'   => data_get($m, 'awayTeam.id'),
                    'name' => data_get($m, 'awayTeam.name'),
                    'logo' => data_get($m, 'awayTeam.crest'),
                ],
            ],
            'goals' => [
                'home' => data_get($m, 'score.fullTime.home'),
                'away' => data_get($m, 'score.fullTime.away'),
            ],
        ];
    }
}
