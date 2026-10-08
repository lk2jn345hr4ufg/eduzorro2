<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Language;
use App\Models\Team;
use App\Models\TeamNews;

/**
 * Football sitemap with hreflang alternates on every URL: language homes,
 * the news index and articles, every competition tab and every team tab.
 */
class SitemapController extends Controller
{
    public function index()
    {
        $languages = Language::active()->ordered()->get();
        $urls      = [];

        $urls[] = $this->entry('football.home', [], $languages);
        $urls[] = $this->entry('sport.news.index', [], $languages);

        foreach (Competition::active()->ordered()->get() as $competition) {
            foreach ($competition->tabs() as $tab) {
                $urls[] = $tab === 'dashboard'
                    ? $this->entry('competition.show', [$competition], $languages)
                    : $this->entry('competition.tab', [$competition, $tab], $languages);
            }
        }

        Team::active()->whereNotNull('competition_id')->orderBy('id')->chunk(500, function ($teams) use ($languages, &$urls) {
            foreach ($teams as $team) {
                foreach (TeamController::TABS as $tab) {
                    $urls[] = $tab === 'dashboard'
                        ? $this->entry('sport.team', [$team], $languages)
                        : $this->entry('sport.team.tab', [$team, $tab], $languages);
                }
            }
        });

        TeamNews::query()->active()->published()->latest('published_at')->take(1000)->get()
            ->each(function ($news) use ($languages, &$urls) {
                $urls[] = $this->entry('sport.news.show', [$news], $languages);
            });

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    /** One <url>: default-language loc plus alternates for every language. */
    private function entry(string $routeName, array $params, $languages): array
    {
        $alternates = [];

        foreach ($languages as $language) {
            $alternates[$language->code] = route($routeName, array_merge([$language], $params));
        }

        return ['loc' => reset($alternates) ?: url('/'), 'alternates' => $alternates];
    }
}
