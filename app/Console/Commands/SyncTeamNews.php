<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\Team;
use App\Models\TeamNews;
use App\Services\AI\GeminiClient;
use App\Services\News\NewsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncTeamNews extends Command
{
    protected $signature = 'sport:sync-news
                            {--team= : Limit to a single team slug}
                            {--limit=0 : Max number of teams to process this run (0 = all)}
                            {--sleep=0 : Seconds to wait between teams (helps with API rate limits)}';

    protected $description = 'Pull recent news per football team into the team_news table';

    public function handle(NewsClient $news, GeminiClient $gemini): int
    {
        if (empty(config('news.key'))) {
            $this->error('NEWS_API_KEY is not set. Add it to your .env first.');
            return self::FAILURE;
        }

        $lang   = config('news.language', 'en');
        $suffix = config('news.query_suffix', ' football');
        $sleep  = (int) $this->option('sleep');

        $query = Team::query()->active()->ordered();

        if ($slug = $this->option('team')) {
            $query->where('slug', $slug);
        }

        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $teams = $query->get();

        if ($teams->isEmpty()) {
            $this->warn('No matching teams.');
            return self::SUCCESS;
        }

        $this->info("Fetching news for {$teams->count()} team(s) [{$lang}] via ".config('news.provider').'...');

        // Target locales for Gemini rewrite/translation = active site languages.
        $locales = Language::query()->active()->ordered()->pluck('code')->all();
        if (empty($locales)) {
            $locales = [$lang];
        }

        $useGemini = config('gemini.enabled') && $gemini->isConfigured();
        if ($useGemini) {
            $this->line('Gemini rewrite & translate: ON ('.implode(', ', $locales).')');
        }

        $total = 0;

        foreach ($teams as $team) {
            $name = $team->translate('name');
            if (! $name) {
                continue;
            }

            $articles = $news->searchTeam(trim($name.$suffix));
            $this->line("→ {$name}: ".count($articles).' article(s)');

            foreach ($articles as $a) {
                $slug = $this->slug($a['title'], $a['url']);

                // Rewrite + translate into all locales, or fall back to raw.
                $rewritten = $useGemini
                    ? $gemini->rewriteArticle($a, $locales, config('gemini.prompt'))
                    : null;

                if ($rewritten) {
                    $title   = $rewritten['title'];
                    $excerpt = $rewritten['excerpt'];
                    $body    = $rewritten['body'];
                } else {
                    $title   = [$lang => $a['title']];
                    $excerpt = $a['excerpt'] ? [$lang => $a['excerpt']] : null;
                    $body    = $a['body'] ? [$lang => $a['body']] : null;
                }

                TeamNews::updateOrCreate(
                    ['team_id' => $team->id, 'slug' => $slug],
                    [
                        'title'        => $title,
                        'excerpt'      => $excerpt,
                        'body'         => $body,
                        'image_url'    => $a['image'],
                        'source_url'   => $a['url'],
                        'published_at' => $a['published_at'],
                        'is_active'    => true,
                    ]
                );

                $total++;
            }

            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        $this->newLine();
        $this->info("Done. {$total} news row(s) upserted.");

        return self::SUCCESS;
    }

    /** Stable, readable, collision-resistant slug per article. */
    protected function slug(string $title, string $url): string
    {
        $base = Str::slug(Str::limit($title, 60, ''));
        $hash = substr(sha1($url), 0, 6);

        return trim($base, '-')."-{$hash}";
    }
}
