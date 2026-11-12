<?php

namespace App\Services\Football;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * uefa.com's own JSON services (no key):
 *   https://match.uefa.com/v5/matches?competitionId=2014&seasonYear=2027&limit=100&offset=0&order=ASC
 *   https://standings.uefa.com/v1/standings?competitionId=2014&seasonYear=2027
 *
 * seasonYear is the year a season ENDS (2026/27 → 2027). Matches come 100 per
 * page: we page with offset until a page has fewer than 100.
 */
class UefaClient
{
    protected ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /** @return array<int, array>|null all matches (raw), null on error */
    public function matches(int $competitionId, int $seasonYear): ?array
    {
        $this->lastError = null;
        $all = [];

        for ($offset = 0, $page = 0; $page < 30; $offset += 100, $page++) {
            $rows = $this->get('https://match.uefa.com/v5/matches', [
                'competitionId' => $competitionId,
                'seasonYear'    => $seasonYear,
                'limit'         => 100,
                'offset'        => $offset,
                'order'         => 'ASC',
            ]);

            if ($rows === null) {
                return $all ?: null;
            }

            $rows = array_is_list($rows) ? $rows : (array) ($rows['matches'] ?? $rows['data'] ?? []);
            array_push($all, ...$rows);

            if (count($rows) < 100) {
                break;
            }

            usleep(400_000); // be gentle between pages
        }

        return $all;
    }

    /** @return array<int, array>|null groups (raw), null on error */
    public function standings(int $competitionId, int $seasonYear): ?array
    {
        $this->lastError = null;

        $rows = $this->get('https://standings.uefa.com/v1/standings', [
            'competitionId' => $competitionId,
            'seasonYear'    => $seasonYear,
        ]);

        if ($rows === null) {
            return null;
        }

        return array_is_list($rows) ? $rows : (array) ($rows['standings'] ?? $rows['data'] ?? []);
    }

    protected function get(string $url, array $query): ?array
    {
        try {
            $res = Http::timeout(25)
                ->retry(2, 1500, throw: false)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; eduzorro-sync/1.0)'])
                ->get($url, $query);
        } catch (\Throwable $e) {
            $this->lastError = 'Connection failed: '.$e->getMessage();
            Log::warning('[uefa] '.$this->lastError, ['url' => $url, 'query' => $query]);

            return null;
        }

        if (! $res->successful()) {
            $this->lastError = 'HTTP '.$res->status().' from '.parse_url($url, PHP_URL_HOST);
            Log::warning('[uefa] '.$this->lastError, ['url' => $url, 'query' => $query, 'body' => mb_substr($res->body(), 0, 300)]);

            return null;
        }

        $json = $res->json();

        if (! is_array($json)) {
            $this->lastError = 'Not JSON from '.parse_url($url, PHP_URL_HOST);

            return null;
        }

        return $json;
    }
}
