<?php

namespace App\Services\Football;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BBC Sport "scores & fixtures" pages, e.g.
 *   https://www.bbc.com/sport/football/europa-league/scores-fixtures/2026-11
 *
 * The page is rendered by React but ships all its data in the HTML as
 * window.__INITIAL_DATA__ = "<JSON encoded as a JS string>". We take that
 * blob, find the "sport-data-scores-fixtures…" block and flatten its
 * eventGroups → secondaryGroups → events into plain rows.
 */
class BbcScoresClient
{
    public const BASE = 'https://www.bbc.com/sport/football/%s/scores-fixtures';

    protected ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Matches of one month ('YYYY-MM'); null month = the default page.
     *
     * @return array<int, array>|null  null on a fetch/parse error (see lastError())
     */
    public function month(string $slug, ?string $month = null): ?array
    {
        $this->lastError = null;
        $url = sprintf(self::BASE, rawurlencode($slug)).($month ? '/'.$month : '');

        $html = $this->fetch($url);

        if ($html === null) {
            return null;
        }

        if ($html === '') {
            return []; // 404: no page for that month
        }

        try {
            return $this->parseEvents($this->extractBlock($html));
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::warning('[bbc] parse failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }

    protected function fetch(string $url, int $retries = 3): ?string
    {
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $res = Http::timeout(20)
                    ->withHeaders([
                        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36',
                        'Accept-Language' => 'en-GB,en;q=0.9',
                    ])
                    ->get($url);

                if ($res->status() === 404) {
                    return '';
                }

                if ($res->successful()) {
                    return $res->body();
                }

                $this->lastError = "HTTP {$res->status()} for {$url}";
            } catch (\Throwable $e) {
                $this->lastError = 'Connection failed: '.$e->getMessage();
            }

            if ($attempt < $retries) {
                sleep(2 * $attempt);
            }
        }

        Log::warning('[bbc] fetch failed', ['url' => $url, 'error' => $this->lastError]);

        return null;
    }

    /** The decoded "sport-data-scores-fixtures…" data block ([] if the page has none). */
    public function extractBlock(string $html): array
    {
        $pos = strpos($html, 'window.__INITIAL_DATA__');

        if ($pos === false) {
            throw new \RuntimeException('window.__INITIAL_DATA__ not found — has BBC changed its markup?');
        }

        // The value is a JS string literal: scan to its closing quote by hand
        // (a regex over a ~1 MB string can hit PCRE's backtracking limits).
        $start = strpos($html, '"', $pos);

        if ($start === false) {
            throw new \RuntimeException('window.__INITIAL_DATA__ has no string value');
        }

        $len = strlen($html);
        $i   = $start + 1;

        while ($i < $len) {
            $c = $html[$i];

            if ($c === '\\') {
                $i += 2;
                continue;
            }

            if ($c === '"') {
                break;
            }

            $i++;
        }

        $literal = substr($html, $start, $i - $start + 1);
        $json    = json_decode($literal, true);              // JS string → JSON text
        $data    = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($data)) {
            throw new \RuntimeException('window.__INITIAL_DATA__ is not valid JSON');
        }

        foreach ((array) ($data['data'] ?? []) as $key => $block) {
            if (str_starts_with((string) $key, 'sport-data-scores-fixtures')) {
                return (array) (($block['data'] ?? null) ?: []);
            }
        }

        return [];
    }

    /** @return array<int, array> flat rows, one per match */
    public function parseEvents(array $block): array
    {
        $rows = [];

        foreach ((array) ($block['eventGroups'] ?? []) as $day) {
            foreach ((array) ($day['secondaryGroups'] ?? []) as $group) {
                foreach ((array) ($group['events'] ?? []) as $e) {
                    $home = (array) ($e['home'] ?? []);
                    $away = (array) ($e['away'] ?? []);

                    $comment = $e['statusComment'] ?? '';
                    $comment = is_array($comment) ? ($comment['value'] ?? '') : (string) $comment;

                    $rows[] = [
                        'event_id'       => (string) ($e['id'] ?? ''),
                        'kickoff_utc'    => (string) ($e['startDateTime'] ?? ''),
                        'stage'          => (string) ($group['displayLabel'] ?? ''),
                        'home'           => trim((string) ($home['fullName'] ?? '')),
                        'away'           => trim((string) ($away['fullName'] ?? '')),
                        'home_score'     => self::score($home['score'] ?? null),
                        'away_score'     => self::score($away['score'] ?? null),
                        'status'         => (string) ($e['status'] ?? ''),                     // PreEvent / MidEvent / PostEvent
                        'period'         => (string) (($e['periodLabel'] ?? [])['value'] ?? ''), // FT, HT, AET, Pens…
                        'status_comment' => $comment,
                    ];
                }
            }
        }

        return array_values(array_filter($rows, fn ($r) => $r['event_id'] !== '' && $r['home'] !== '' && $r['away'] !== ''));
    }

    protected static function score($value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
