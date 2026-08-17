<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\TeamNews;
use App\Services\AI\GeminiClient;
use Illuminate\Console\Command;

class RewriteNews extends Command
{
    protected $signature = 'sport:rewrite-news
                            {--team= : Limit to a single team slug}
                            {--limit=0 : Max news rows to process this run (0 = all)}
                            {--all : Re-process every row, even ones already multilingual}
                            {--sleep=0 : Seconds to wait between articles}';

    protected $description = 'Rewrite & translate existing team news into all site languages via Gemini';

    public function handle(GeminiClient $gemini): int
    {
        if (! $gemini->isConfigured()) {
            $this->error('Gemini API key is not set (Settings → Gemini, or GEMINI_API_KEY).');
            return self::FAILURE;
        }

        $locales = Language::query()->active()->ordered()->pluck('code')->all();
        if (empty($locales)) {
            $this->error('No active languages found.');
            return self::FAILURE;
        }

        $query = TeamNews::query()->latest('published_at');

        if ($slug = $this->option('team')) {
            $query->whereHas('team', fn ($q) => $q->where('slug', $slug));
        }

        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $rows  = $query->get();
        $sleep = (int) $this->option('sleep');
        $all   = (bool) $this->option('all');
        $done  = 0;

        $this->info("Rewriting {$rows->count()} news row(s) into: ".implode(', ', $locales));

        foreach ($rows as $row) {
            // Skip rows that already cover every locale unless --all.
            if (! $all && $this->alreadyTranslated($row, $locales)) {
                continue;
            }

            $article = [
                'title'   => $this->firstValue($row->title),
                'excerpt' => $this->firstValue($row->excerpt),
                'body'    => $this->firstValue($row->body),
            ];

            if (! $article['title']) {
                continue;
            }

            $rewritten = $gemini->rewriteArticle($article, $locales, config('gemini.prompt'));

            if (! $rewritten) {
                $this->warn("  skip (Gemini failed): {$row->slug}");
                continue;
            }

            $row->update([
                'title'   => $rewritten['title'],
                'excerpt' => $rewritten['excerpt'],
                'body'    => $rewritten['body'],
            ]);

            $done++;
            $this->line("  ✓ {$row->slug}");

            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        $this->newLine();
        $this->info("Done. {$done} row(s) rewritten.");

        return self::SUCCESS;
    }

    protected function alreadyTranslated(TeamNews $row, array $locales): bool
    {
        $title = is_array($row->title) ? $row->title : [];

        foreach ($locales as $code) {
            if (empty($title[$code])) {
                return false;
            }
        }

        return true;
    }

    protected function firstValue($json): ?string
    {
        if (is_array($json)) {
            foreach ($json as $v) {
                if ($v !== null && $v !== '') {
                    return $v;
                }
            }
        }

        return null;
    }
}
