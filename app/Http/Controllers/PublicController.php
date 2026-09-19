<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Fixture;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Standing;
use App\Models\Transfer;
use Illuminate\Support\Carbon;

class PublicController extends Controller
{
    public function home()
    {
        $latest   = Article::published()->with('category')->limit(13)->get();
        $featured = $latest->first();
        $articles = $latest->skip(1)->values();

        $fixtures    = Fixture::upcoming()->limit(5)->get();
        $nextFixture = $fixtures->first();

        $categories = Category::ordered()->get();

        return view('public.home', array_merge([
            'featured' => $featured, 'articles' => $articles, 'fixtures' => $fixtures,
            'nextFixture' => $nextFixture, 'categories' => $categories,
        ], $this->seo('home')));
    }

    /* ==================== СТАТЬИ (Post) ==================== */

    public function posts()
    {
        $posts = Post::published()->with('category')->paginate(12);

        return view('public.posts', array_merge([
            'posts'      => $posts,
            'categories' => Category::ordered()->get(),
        ], $this->seo('posts')));
    }

    public function postShow(Post $post)
    {
        abort_unless($post->isPublished(), 404);

        $post->load('category');

        return view('public.post', [
            'post'       => $post,
            'categories' => Category::ordered()->get(),
        ]);
    }

    /* ==================== НОВОСТИ ==================== */

    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $articles = Article::published()
            ->with('category')
            ->where('category_id', $category->id)
            ->paginate(12);

        return view('public.category', array_merge([
            'category'   => $category,
            'articles'   => $articles,
            'categories' => Category::ordered()->get(),
        ], [
            'metaTitle'       => "{$category->name} — новости «Ливерпуля» | LiverpoolIn",
            'metaDescription' => "Материалы «Ливерпуля» в категории «{$category->name}»: "
                . ($category->description ?: 'свежие новости и статьи по теме.'),
        ]));
    }

    public function show(string $slug)
    {
        $id = Article::idFromSlug($slug);

        abort_if($id === null, 404);

        $article = Article::findOrFail($id);

        abort_unless($article->status === 'published', 404);

        if ($slug !== $article->getRouteKey()) {
            return redirect()->route('article.show', $article, 301);
        }

        $article->load('category');
        $categories = Category::ordered()->get();

        return view('public.article', compact('article', 'categories'));
    }

    /* ==================== РАСПИСАНИЕ ==================== */

    public function fixtures()
    {
        $comp  = request('comp');
        $range = request('range');

        $default = $range !== 'all';
        [$from, $to] = $this->resolveWindow();

        $query = Fixture::query()
            ->when($comp, fn ($q) => $q->where('competition', $comp));

        if ($default) {
            $query->whereBetween('kickoff_at', [$from, $to]);
        }

        $all     = $query->get();
        $ordered = $this->actualFirst($all);

        $next = Fixture::upcoming()->first();

        $meta = $this->seo('fixtures');
        if ($next) {
            $meta['metaDescription'] .= ' Ближайший матч: '
                . $next->home_team . ' — ' . $next->away_team
                . ($next->kickoff_at ? ', ' . $next->kickoff_at->isoFormat('D MMMM') : '') . '.';
        }

        return view('public.fixtures', array_merge([
            'grouped'      => $this->groupByMonth($ordered),
            'competitions' => $this->competitionOptions(),
            'activeComp'   => $comp,
            'nextFixture'  => $next,
            'stats'        => $this->summarise($ordered),
            'categories'   => Category::ordered()->get(),
            'title'        => 'Расписание',
            'intro'        => 'Матчи «Ливерпуля»: ближайшие сверху, сыгранные ниже.',
            'isIntl'       => false,
            'seasons'      => collect(),
            'activeSeason' => null,
            'intlComps'    => collect(),
            'showCalendar' => true,
            'fromDate'     => $from,
            'toDate'       => $to,
            'isAll'        => ! $default,
        ], $meta));
    }

    public function internationalFixtures()
    {
        $base = Fixture::international();

        $everything = (clone $base)->get();

        $seasons = $everything
            ->map(fn ($f) => $this->seasonOf($f))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $activeSeason = request('season');
        $activeSeason = ($activeSeason !== null && $seasons->contains((int) $activeSeason))
            ? (int) $activeSeason
            : $seasons->first();

        $activeComp = request('comp');

        $filtered = $everything
            ->when($activeSeason !== null, fn ($c) => $c->filter(fn ($f) => $this->seasonOf($f) === $activeSeason))
            ->when($activeComp, fn ($c) => $c->filter(fn ($f) => $f->competition === $activeComp))
            ->values();

        $intlComps = $everything
            ->when($activeSeason !== null, fn ($c) => $c->filter(fn ($f) => $this->seasonOf($f) === $activeSeason))
            ->pluck('competition')
            ->filter()
            ->unique()
            ->values();

        $nextIntl = $everything
            ->filter(fn ($f) => $f->kickoff_at && $f->kickoff_at->isFuture())
            ->sortBy('kickoff_at')
            ->first();

        return view('public.fixtures', array_merge([
            'grouped'      => $this->groupByMonth($this->actualFirst($filtered)),
            'competitions' => collect(),
            'activeComp'   => $activeComp,
            'nextFixture'  => $nextIntl,
            'stats'        => $this->summarise($filtered),
            'categories'   => Category::ordered()->get(),
            'title'        => 'Международные матчи',
            'intro'        => 'Еврокубки «Ливерпуля»: Лига чемпионов, Лига Европы, Лига конференций и Суперкубок УЕФА.',
            'isIntl'       => true,
            'seasons'      => $seasons,
            'activeSeason' => $activeSeason,
            'intlComps'    => $intlComps,
            'showCalendar' => false,
            'fromDate'     => null,
            'toDate'       => null,
            'isAll'        => false,
        ], $this->seo('euro')));
    }

    public function table()
    {
        $seasons = Standing::seasons();

        if ($seasons->isEmpty()) {
            return view('public.table', array_merge([
                'rows' => collect(), 'seasons' => $seasons, 'season' => null,
                'matchday' => 0, 'history' => [], 'lfc' => null,
                'categories' => Category::ordered()->get(),
            ], $this->seo('standings')));
        }

        $season = (int) (request('season') ?: $seasons->first());

        if (! $seasons->contains($season)) {
            $season = (int) $seasons->first();
        }

        $rows = Standing::tableFor($season);
        $lfc  = $rows->first(fn ($r) => $r->isLiverpool());

        $meta = $this->seo('standings');
        if ($lfc) {
            $meta['metaDescription'] .= " Сейчас «Ливерпуль» на {$lfc->position}-м месте, {$lfc->points} очк.";
        }

        return view('public.table', array_merge([
            'rows'       => $rows,
            'seasons'    => $seasons,
            'season'     => $season,
            'matchday'   => Standing::latestMatchday($season),
            'history'    => Standing::historyFor($season, Standing::LIVERPOOL_TEAM_ID),
            'lfc'        => $lfc,
            'categories' => Category::ordered()->get(),
        ], $meta));
    }

    public function transfers()
    {
        $seasons = Transfer::seasons();
        $season  = request('season');
        $dir     = request('dir');

        if (filled($season) && ! $seasons->contains((int) $season)) {
            $season = null;
        }

        $query = Transfer::query()
            ->when(filled($season), fn ($q) => $q->where('season', (int) $season))
            ->when(in_array($dir, ['in', 'out'], true), fn ($q) => $q->where('direction', $dir))
            ->orderByDesc('transfer_date');

        $transfers = (clone $query)->get();

        $baseT = Transfer::query()->when(filled($season), fn ($q) => $q->where('season', (int) $season));

        return view('public.transfers', array_merge([
            'transfers'  => $transfers,
            'grouped'    => $transfers->groupBy(fn ($t) => $t->transfer_date
                ? $t->transfer_date->isoFormat('MMMM YYYY')
                : 'Дата неизвестна'),
            'seasons'    => $seasons,
            'season'     => $season ? (int) $season : null,
            'dir'        => in_array($dir, ['in', 'out'], true) ? $dir : null,
            'countIn'    => (clone $baseT)->where('direction', 'in')->count(),
            'countOut'   => (clone $baseT)->where('direction', 'out')->count(),
            'categories' => Category::ordered()->get(),
        ], $this->seo('transfers')));
    }

    /* -------- SEO -------- */

    /**
     * Мета-теги раздела: сначала правки из админки (таблица settings),
     * затем значения по умолчанию из config/seo.php.
     */
    private function seo(string $key): array
    {
        $cfg = config("seo.$key", []);

        $title = Setting::get("seo.$key.title") ?: ($cfg['title'] ?? 'LiverpoolIn');
        $desc  = Setting::get("seo.$key.description") ?: ($cfg['description'] ?? '');

        return ['metaTitle' => $title, 'metaDescription' => $desc];
    }

    /* -------- helpers -------- */

    private function resolveWindow(): array
    {
        $from = rescue(
            fn () => request('from') ? Carbon::parse(request('from'))->startOfDay() : null,
            null,
            false
        ) ?: now()->startOfDay();

        $to = rescue(
            fn () => request('to') ? Carbon::parse(request('to'))->endOfDay() : null,
            null,
            false
        ) ?: now()->copy()->addMonths(3)->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function actualFirst($fixtures)
    {
        $now = now();

        $future = $fixtures
            ->filter(fn ($f) => $f->kickoff_at && $f->kickoff_at->gte($now))
            ->sortBy('kickoff_at')
            ->values();

        $past = $fixtures
            ->filter(fn ($f) => $f->kickoff_at && $f->kickoff_at->lt($now))
            ->sortByDesc('kickoff_at')
            ->values();

        $noDate = $fixtures->filter(fn ($f) => ! $f->kickoff_at)->values();

        return $future->concat($past)->concat($noDate);
    }

    private function seasonOf(Fixture $f): ?int
    {
        if (! $f->kickoff_at) {
            return null;
        }

        return $f->kickoff_at->month >= 7
            ? $f->kickoff_at->year
            : $f->kickoff_at->year - 1;
    }

    private function groupByMonth($fixtures)
    {
        return $fixtures->groupBy(function ($f) {
            return $f->kickoff_at ? $f->kickoff_at->isoFormat('MMMM YYYY') : 'Дата уточняется';
        });
    }

    private function competitionOptions()
    {
        return Fixture::query()
            ->whereNotNull('competition')
            ->distinct()
            ->orderBy('competition')
            ->pluck('competition');
    }

    private function summarise($fixtures): array
    {
        $played = $fixtures->filter(fn ($f) => $f->hasResult());

        return [
            'total'    => $fixtures->count(),
            'played'   => $played->count(),
            'upcoming' => $fixtures->count() - $played->count(),
            'win'      => $played->filter(fn ($f) => $f->outcome() === 'win')->count(),
            'draw'     => $played->filter(fn ($f) => $f->outcome() === 'draw')->count(),
            'loss'     => $played->filter(fn ($f) => $f->outcome() === 'loss')->count(),
        ];
    }
}
