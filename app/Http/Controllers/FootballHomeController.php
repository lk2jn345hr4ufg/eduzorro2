<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Language;
use App\Models\Standing;
use App\Models\Team;
use App\Models\TeamNews;
use App\Support\TeamLinks;

class FootballHomeController extends Controller
{
    /** "/" — send visitors to the default language home. */
    public function root()
    {
        $language = Language::active()->where('code', config('app.locale'))->first()
            ?? Language::active()->ordered()->first();

        abort_unless($language, 404);

        // 302, not 301: the default language is a setting and may change.
        return redirect()->route('football.home', [$language]);
    }

    /** /{language} — dashboard: championships, popular teams, tournaments, news. */
    public function index(Language $language)
    {
        $leagues = Competition::active()->leagues()->ordered()->with('country')->get();
        $cups    = Competition::active()->cups()->ordered()->get();
        $codes   = $leagues->pluck('code')->merge($cups->pluck('code'));

        // Current leader of each competition (newest season we hold).
        $leaders = Standing::query()
            ->whereIn('league_code', $codes)
            ->where('rank', 1)
            ->orderByDesc('season')
            ->get()
            ->unique('league_code')
            ->keyBy('league_code');

        // Matches today per competition, for the "live now / today" badges.
        $today = Fixture::query()
            ->whereIn('league_code', $codes)
            ->whereDate('kickoff_at', now()->toDateString())
            ->get()
            ->groupBy('league_code')
            ->map->count();

        $popular = Team::active()->popular()->with('competition.country')->take(12)->get();

        // Nothing flagged yet: fall back to the top two of every championship,
        // so the block is never empty on a fresh install.
        if ($popular->isEmpty()) {
            $topIds = Standing::query()
                ->whereIn('league_code', $leagues->pluck('code'))
                ->where('rank', '<=', 2)
                ->orderByDesc('season')
                ->get()
                ->unique(fn ($row) => $row->league_code.'-'.$row->rank)
                ->pluck('team_api_id');

            $popular = Team::active()->whereIn('api_id', $topIds)->with('competition.country')->take(12)->get();
        }

        $news = ! config('football.news_enabled') ? collect() : TeamNews::query()
            ->active()->published()
            ->with('team')
            ->latest('published_at')
            ->take(8)
            ->get();

        // Schedule for the next 7 days (today included) across every
        // competition we show, grouped by day, then by competition.
        $start = now()->startOfDay();
        $end   = now()->startOfDay()->addDays(7);

        $week = Fixture::query()
            ->whereIn('league_code', $codes)
            ->whereBetween('kickoff_at', [$start, $end])
            ->whereNotIn('status_short', ['CANCELLED'])
            ->with('odd')
            ->orderBy('kickoff_at')
            ->get();

        $competitions = $leagues->concat($cups)->keyBy('code');
        $order        = $competitions->keys()->flip();

        $days = collect(range(0, 6))->mapWithKeys(function ($i) use ($start, $week, $order) {
            $date = $start->copy()->addDays($i)->toDateString();

            $groups = $week
                ->filter(fn ($f) => $f->kickoff_at->toDateString() === $date)
                ->groupBy('league_code')
                ->sortBy(fn ($g, $code) => $order[$code] ?? 999);

            return [$date => $groups];
        });

        // Results of the previous 7 days (oldest → yesterday), same grouping.
        $pastStart = now()->startOfDay()->subDays(7);

        $played = Fixture::query()
            ->whereIn('league_code', $codes)
            ->played()
            ->whereBetween('kickoff_at', [$pastStart, now()->startOfDay()])
            ->orderBy('kickoff_at')
            ->get();

        $pastDays = collect(range(7, 1))->mapWithKeys(function ($i) use ($played, $order) {
            $date = now()->startOfDay()->subDays($i)->toDateString();

            return [$date => $played
                ->filter(fn ($f) => $f->kickoff_at->toDateString() === $date)
                ->groupBy('league_code')
                ->sortBy(fn ($g, $code) => $order[$code] ?? 999)];
        });

        $upcoming = $week->filter(fn ($f) => ! $f->isPlayed())->take(8)->values();
        $links    = TeamLinks::forFixtures($week->concat($played));

        return view('football.home', compact(
            'leagues', 'cups', 'leaders', 'today', 'popular', 'news', 'upcoming', 'links', 'days', 'pastDays', 'competitions'
        ));
    }
}
