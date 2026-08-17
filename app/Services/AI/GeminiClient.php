<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Rewrites and translates a news article via Google's Gemini API.
 *
 * rewriteArticle() returns translation maps ready to store in the
 * team_news JSON columns:
 *   ['title' => ['en'=>..,'uk'=>..], 'excerpt' => [...], 'body' => [...]]
 * or null on failure (caller then falls back to storing the raw article).
 */
class GeminiClient
{
    public function isConfigured(): bool
    {
        return ! empty(config('gemini.key'));
    }

    public function rewriteArticle(array $article, array $locales, ?string $stylePrompt = null): ?array
    {
        if (! $this->isConfigured() || empty($locales)) {
            return null;
        }

        $prompt = $this->buildPrompt($article, $locales, $stylePrompt ?: (string) config('gemini.prompt'));
        $json   = $this->generateJson($prompt);

        if (! is_array($json)) {
            return null;
        }

        return $this->normalize($json, $article, $locales);
    }

    protected function buildPrompt(array $article, array $locales, string $style): string
    {
        $codes = implode(', ', $locales);

        return $style."\n\n"
            ."Target languages (ISO codes): {$codes}\n\n"
            ."Source article:\n"
            ."TITLE: ".($article['title'] ?? '')."\n"
            ."EXCERPT: ".($article['excerpt'] ?? '')."\n"
            ."BODY: ".($article['body'] ?? '')."\n\n"
            ."Respond with ONLY a JSON object of exactly this shape, with one entry "
            ."per target language code and no markdown fences:\n"
            .'{"title":{"'.$locales[0].'":"..."},"excerpt":{"'.$locales[0].'":"..."},"body":{"'.$locales[0].'":"..."}}';
    }

    protected function generateJson(string $prompt): ?array
    {
        $model = config('gemini.model', 'gemini-2.0-flash');
        $url   = config('gemini.base_url')."/models/{$model}:generateContent";

        try {
            $res = Http::timeout((int) config('gemini.timeout', 40))
                ->acceptJson()
                ->post($url.'?key='.config('gemini.key'), [
                    'contents' => [[
                        'parts' => [['text' => $prompt]],
                    ]],
                    'generationConfig' => [
                        'temperature'      => (float) config('gemini.temperature', 0.7),
                        'response_mime_type' => 'application/json',
                    ],
                ]);

            if ($res->failed()) {
                Log::warning('Gemini request failed', ['status' => $res->status(), 'body' => $res->body()]);
                return null;
            }

            $text = data_get($res->json(), 'candidates.0.content.parts.0.text');

            if (! $text) {
                return null;
            }

            // response_mime_type=json means $text should already be pure JSON,
            // but strip accidental fences just in case.
            $text = trim(preg_replace('/^```(json)?|```$/m', '', $text));

            $decoded = json_decode($text, true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Log::warning('Gemini request threw', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** Ensure every requested locale is present for each field. */
    protected function normalize(array $json, array $article, array $locales): array
    {
        $out = [];

        foreach (['title', 'excerpt', 'body'] as $field) {
            $map      = (array) ($json[$field] ?? []);
            $fallback = $map[$locales[0]] ?? ($article[$field] ?? '');

            foreach ($locales as $code) {
                $val = $map[$code] ?? $fallback;
                if ($val !== null && $val !== '') {
                    $out[$field][$code] = $val;
                }
            }

            if (empty($out[$field] ?? null)) {
                $out[$field] = null;
            }
        }

        // A title is required for a usable article.
        return empty($out['title']) ? [] : $out;
    }
}
