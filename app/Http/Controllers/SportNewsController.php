<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\TeamNews;

class SportNewsController extends Controller
{
    /** /{language}/sport/news — all sports news, newest first. */
    public function index(Language $language)
    {
        $news = TeamNews::query()
            ->active()->published()
            ->with('team.country')
            ->latest('published_at')
            ->paginate(24);

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('home')],
            ['label' => __('sport.sports'), 'url' => route('sport.index', [$language])],
            ['label' => __('sport.sports_news')],
        ];

        return view('sport.news.index', compact('news', 'breadcrumbs'));
    }

    /** /{language}/sport/news/{news} — a single article. */
    public function show(Language $language, TeamNews $news)
    {
        abort_unless($news->is_active && $news->published_at && $news->published_at <= now(), 404);

        $news->loadMissing('team.country');
        $team = $news->team;

        // A few more from the same team.
        $related = TeamNews::query()
            ->active()->published()
            ->where('team_id', $news->team_id)
            ->whereKeyNot($news->getKey())
            ->latest('published_at')
            ->take(6)
            ->get();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('home')],
            ['label' => __('sport.sports'), 'url' => route('sport.index', [$language])],
            ['label' => __('sport.sports_news'), 'url' => route('sport.news.index', [$language])],
            ['label' => $news->translate('title')],
        ];

        return view('sport.news.show', compact('news', 'team', 'related', 'breadcrumbs'));
    }
}
