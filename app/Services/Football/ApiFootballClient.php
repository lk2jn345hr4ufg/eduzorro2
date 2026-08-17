<?php

namespace App\Services\Football;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around API-Football (api-sports.io).
 *
 * Taxonomy (countries, teams) is imported into the DB by SyncFootball.
 * The per-team tabs (fixtures, standings, transfers) are read live through
 * here and cached, so pages stay fast and we stay within the API quota.
 *
 * Every method returns the decoded "response" array (possibly empty) and
 * never throws for API/HTTP errors — it logs and returns [] so a flaky
 * upstream degrades gracefully instead of 500-ing a public page.
 */
class ApiFootballClient
{
    protected function http(): PendingRequest
    {
        $cfg     = config('football.api');
        $headers = [];

        if (! empty($cfg['host'])) {
            // RapidAPI style
            $headers['x-rapidapi-key']  = $cfg['key'];
            $headers['x-rapidapi-host'] = $cfg['host'];
        } else {
            // Direct api-sports.io
            $headers['x-apisports-key'] = $cfg['key'];
        }

        return Http::baseUrl($cfg['base_url'])
            ->timeout($cfg['timeout'] ?? 12)
            ->withHeaders($headers)
            ->acceptJson();
    }

    /** Low-level GET returning the API "response" array, or [] on failure. */
    public function get(string $path, array $query = []): array
    {
        if (empty(config('football.api.key'))) {
            Log::warning('API-Football key missing; skipping request', ['path' => $path]);
            return [];
        }

        try {
            $res = $this->http()->get($path, $query);

            if ($res->failed()) {
                Log::warning('API-Football request failed', [
                    'path' => $path, 'status' => $res->status(),
                ]);
                return [];
            }

            return (array) ($res->json('response') ?? []);
        } catch (\Throwable $e) {
            Log::warning('API-Football request threw', ['path' => $path, 'error' => $e->getMessage()]);
            return [];
        }
    }

    protected function remember(string $key, int $ttl, callable $cb): array
    {
        return Cache::remember("football:{$key}", $ttl, $cb);
    }

    // ---- Taxonomy (used by the sync command) --------------------------------

    /** Teams in a league+season. */
    public function teamsByLeague(int $leagueId, int $season): array
    {
        return $this->get('/teams', ['league' => $leagueId, 'season' => $season]);
    }

    public function team(int $teamId): array
    {
        $res = $this->get('/teams', ['id' => $teamId]);
        return $res[0] ?? [];
    }

    // ---- Per-team tabs (read live + cached) ---------------------------------

    /** All fixtures for a team this season (past + upcoming). */
    public function teamFixtures(int $teamId, int $season): array
    {
        $ttl = config('football.cache.fixtures');
        return $this->remember("fixtures:{$teamId}:{$season}", $ttl,
            fn () => $this->get('/fixtures', ['team' => $teamId, 'season' => $season]));
    }

    /** Fixtures for a team limited to the configured European competitions. */
    public function teamEuroFixtures(int $teamId, int $season): array
    {
        $euroIds = array_keys(config('football.euro_competitions', []));
        $all     = $this->teamFixtures($teamId, $season);

        return array_values(array_filter($all, function ($fx) use ($euroIds) {
            $leagueId = data_get($fx, 'league.id');
            return in_array($leagueId, $euroIds, true);
        }));
    }

    /** Standings table for a league+season. */
    public function standings(int $leagueId, int $season): array
    {
        $ttl = config('football.cache.standings');
        $res = $this->remember("standings:{$leagueId}:{$season}", $ttl,
            fn () => $this->get('/standings', ['league' => $leagueId, 'season' => $season]));

        // API shape: response[0].league.standings[0] = rows
        return data_get($res, '0.league.standings.0', []);
    }

    /** Transfers in/out for a team. */
    public function transfers(int $teamId): array
    {
        $ttl = config('football.cache.transfers');
        return $this->remember("transfers:{$teamId}", $ttl,
            fn () => $this->get('/transfers', ['team' => $teamId]));
    }
}
