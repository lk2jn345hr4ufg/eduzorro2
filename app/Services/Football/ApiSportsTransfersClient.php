<?php

namespace App\Services\Football;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * api-sports.io client used for ONE thing: transfers, which football-data.org
 * does not expose at all.
 *
 * Teams are keyed by football-data ids everywhere else, so this client also
 * resolves and caches the matching api-sports id per team (stored on
 * teams.apisports_id) by searching on the club name.
 */
class ApiSportsTransfersClient
{
    public function isConfigured(): bool
    {
        return ! empty(config('apisports.key'));
    }

    protected function http(): PendingRequest
    {
        $key  = config('apisports.key');
        $host = config('apisports.host');

        $headers = $host
            ? ['x-rapidapi-key' => $key, 'x-rapidapi-host' => $host]   // RapidAPI
            : ['x-apisports-key' => $key];                              // direct

        return Http::baseUrl(config('apisports.base_url'))
            ->timeout((int) config('apisports.timeout', 12))
            ->withHeaders($headers)
            ->acceptJson();
    }

    /** Returns the API "response" array, or [] on any failure. */
    public function get(string $path, array $query = []): array
    {
        if (! $this->isConfigured()) {
            Log::warning('api-sports key missing; transfers skipped', ['path' => $path]);
            return [];
        }

        try {
            $res = $this->http()->get($path, $query);

            if ($res->failed()) {
                Log::warning('api-sports request failed', ['path' => $path, 'status' => $res->status()]);
                return [];
            }

            $body = $res->json();

            // api-sports answers 200 with an errors object when the plan or
            // quota blocks a call, so surface that instead of silent zeros.
            if ($this->hasErrors($body['errors'] ?? [])) {
                Log::warning('api-sports returned errors', ['path' => $path, 'errors' => $body['errors']]);
            }

            return (array) ($body['response'] ?? []);
        } catch (\Throwable $e) {
            Log::warning('api-sports request threw', ['path' => $path, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /** Raw transfers payload for an api-sports team id. */
    public function transfers(int $apiSportsTeamId): array
    {
        $ttl = (int) config('apisports.cache.transfers', 86400);

        return Cache::remember("apisports:transfers:{$apiSportsTeamId}", $ttl,
            fn () => $this->get('/transfers', ['team' => $apiSportsTeamId]));
    }

    /**
     * Find the api-sports team id for a club name, optionally narrowed by
     * country. Cached, because this is a lookup that almost never changes.
     */
    public function resolveTeamId(string $name, ?string $country = null): ?int
    {
        $clean = $this->searchable($name);

        if (Str::length($clean) < 3) {
            return null;
        }

        $key = 'apisports:lookup:'.md5($clean.'|'.$country);

        $id = Cache::remember($key, (int) config('apisports.cache.lookup', 604800), function () use ($clean, $country) {
            $rows = $this->get('/teams', array_filter([
                'search'  => $clean,
                'country' => $country,
            ]));

            if (empty($rows)) {
                return 0;
            }

            // Prefer an exact-ish name match, else the first hit.
            $target = Str::lower($clean);

            foreach ($rows as $row) {
                if (Str::lower((string) data_get($row, 'team.name')) === $target) {
                    return (int) data_get($row, 'team.id');
                }
            }

            return (int) data_get($rows, '0.team.id', 0);
        });

        return $id ?: null;
    }

    /** api-sports sends [] or {} for "no errors" depending on the endpoint. */
    protected function hasErrors($errors): bool
    {
        foreach ((array) $errors as $e) {
            if (! empty($e)) {
                return true;
            }
        }

        return false;
    }

    /** Strip club suffixes so "Manchester United FC" searches as "Manchester United". */
    protected function searchable(string $name): string
    {
        $clean = preg_replace('/\b(fc|cf|afc|sc|ac|as|ss|ssc|bk|if|sk|vfl|vfb|tsv|fsv|rc|cd|ud|sd)\b/iu', ' ', $name);

        return trim(preg_replace('/\s+/u', ' ', $clean));
    }
}
