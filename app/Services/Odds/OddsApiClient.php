<?php

namespace App\Services\Odds;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal client for The Odds API v4 (the-odds-api.com): 1X2 ("h2h") odds
 * for one sport key within a time window.
 *
 * Cost: markets × regions credits per request (1 with the defaults); a
 * request that returns no events is not charged.
 */
class OddsApiClient
{
    protected ?string $lastError = null;
    protected ?int $remaining = null;
    protected ?int $used = null;

    public function isConfigured(): bool
    {
        return (bool) config('odds.oddsapi.key');
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /** Credits left this month, from the last response headers. */
    public function remaining(): ?int
    {
        return $this->remaining;
    }

    public function used(): ?int
    {
        return $this->used;
    }

    /**
     * Events with h2h odds for one sport key.
     *
     * @return array<int, array> raw events: id, commence_time, home_team, away_team, bookmakers[]
     */
    public function odds(string $sportKey, Carbon $from, Carbon $to): array
    {
        $this->lastError = null;

        try {
            $response = Http::timeout((int) config('odds.oddsapi.timeout', 15))
                ->acceptJson()
                ->get(config('odds.oddsapi.base_url').'/v4/sports/'.rawurlencode($sportKey).'/odds', [
                    'apiKey'           => config('odds.oddsapi.key'),
                    'regions'          => config('odds.oddsapi.regions', 'eu'),
                    'markets'          => 'h2h',
                    'oddsFormat'       => 'decimal',
                    'dateFormat'       => 'iso',
                    'commenceTimeFrom' => $from->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                    'commenceTimeTo'   => $to->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                ]);
        } catch (\Throwable $e) {
            $this->lastError = 'Connection failed: '.$e->getMessage();
            Log::warning('[odds-api] '.$this->lastError);

            return [];
        }

        if ($response->header('x-requests-remaining') !== '') {
            $this->remaining = (int) $response->header('x-requests-remaining');
            $this->used      = (int) $response->header('x-requests-used');
        }

        if (! $response->successful()) {
            $message = $response->json('message') ?: $response->body();
            $this->lastError = 'HTTP '.$response->status().': '.mb_substr((string) $message, 0, 300);
            Log::warning('[odds-api] '.$sportKey.' '.$this->lastError);

            return [];
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * Pick one bookmaker's home/draw/away prices for an event: the first of
     * the preferred bookmakers that offers it, else the first one listed.
     *
     * @return array{bookmaker: string, home: float, draw: ?float, away: float}|null
     */
    public static function prices(array $event): ?array
    {
        $preferred = array_values(array_filter(array_map('trim', explode(',', (string) config('odds.oddsapi.bookmakers', '')))));
        $books     = collect($event['bookmakers'] ?? [])->keyBy('key');

        $ordered = collect($preferred)
            ->filter(fn ($key) => $books->has($key))
            ->map(fn ($key) => $books->get($key))
            ->merge($books->values());

        $home = $event['home_team'] ?? null;
        $away = $event['away_team'] ?? null;

        foreach ($ordered as $book) {
            $market = collect($book['markets'] ?? [])->firstWhere('key', 'h2h');

            if (! $market) {
                continue;
            }

            $outcomes = collect($market['outcomes'] ?? [])->mapWithKeys(fn ($o) => [(string) ($o['name'] ?? '') => (float) ($o['price'] ?? 0)]);

            if (! $outcomes->get($home) || ! $outcomes->get($away)) {
                continue;
            }

            return [
                'bookmaker' => (string) ($book['title'] ?? $book['key'] ?? ''),
                'home'      => $outcomes->get($home),
                'draw'      => $outcomes->get('Draw') ?: null,
                'away'      => $outcomes->get($away),
            ];
        }

        return null;
    }
}
