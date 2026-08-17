<?php

namespace App\Services\News;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Searches a keyword news API by team name and returns a normalized list of
 * articles. Used by SyncTeamNews to populate the team_news table (API-Football
 * itself has no news endpoint).
 *
 * Normalized article shape:
 *   ['title','excerpt','body','url','image','published_at','source']
 */
class NewsClient
{
    public function searchTeam(string $query): array
    {
        $provider = config('news.provider', 'gnews');
        $key      = config('news.key');
        $lang     = config('news.language', 'en');
        $max      = (int) config('news.per_team', 6);

        if (empty($key)) {
            Log::warning('News API key missing; skipping news fetch');
            return [];
        }

        return $provider === 'newsapi'
            ? $this->newsapi($query, $lang, $max, $key)
            : $this->gnews($query, $lang, $max, $key);
    }

    protected function gnews(string $query, string $lang, int $max, string $key): array
    {
        $base = config('news.base_url.gnews');

        $res = $this->safeGet("{$base}/search", [
            'q'       => $query,
            'lang'    => $lang,
            'max'     => $max,
            'sortby'  => 'publishedAt',
            'apikey'  => $key,
        ]);

        return collect($res['articles'] ?? [])->map(fn ($a) => [
            'title'        => $a['title'] ?? null,
            'excerpt'      => $a['description'] ?? null,
            'body'         => $a['content'] ?? null,
            'url'          => $a['url'] ?? null,
            'image'        => $a['image'] ?? null,
            'published_at' => $this->date($a['publishedAt'] ?? null),
            'source'       => data_get($a, 'source.name'),
        ])->filter(fn ($a) => $a['title'] && $a['url'])->values()->all();
    }

    protected function newsapi(string $query, string $lang, int $max, string $key): array
    {
        $base = config('news.base_url.newsapi');

        $res = $this->safeGet("{$base}/everything", [
            'q'        => $query,
            'language' => $lang,
            'pageSize' => $max,
            'sortBy'   => 'publishedAt',
            'apiKey'   => $key,
        ]);

        return collect($res['articles'] ?? [])->map(fn ($a) => [
            'title'        => $a['title'] ?? null,
            'excerpt'      => $a['description'] ?? null,
            'body'         => $a['content'] ?? null,
            'url'          => $a['url'] ?? null,
            'image'        => $a['urlToImage'] ?? null,
            'published_at' => $this->date($a['publishedAt'] ?? null),
            'source'       => data_get($a, 'source.name'),
        ])->filter(fn ($a) => $a['title'] && $a['url'])->values()->all();
    }

    protected function safeGet(string $url, array $query): array
    {
        try {
            $res = Http::timeout((int) config('news.timeout', 12))
                ->acceptJson()
                ->get($url, $query);

            if ($res->failed()) {
                Log::warning('News API request failed', ['url' => $url, 'status' => $res->status()]);
                return [];
            }

            return (array) $res->json();
        } catch (\Throwable $e) {
            Log::warning('News API request threw', ['url' => $url, 'error' => $e->getMessage()]);
            return [];
        }
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
}
