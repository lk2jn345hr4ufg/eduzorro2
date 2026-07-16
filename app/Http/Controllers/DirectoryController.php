<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Listing;
use App\Models\Region;
use App\Models\TaxonomyTerm;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DirectoryController extends Controller
{
    /** vertical slug (as used in URLs) => [db value, display label]. */
    public const VERTICALS = [
        'courses' => ['course', 'Курсы'],
        'online-courses' => ['online_course', 'Онлайн-курсы'],
        'universities' => ['university', 'Университеты'],
        'online-business' => ['online_business', 'Онлайн-бизнес'],
        'affiliate-networks' => ['affiliate_network', 'Партнёрские сети'],
    ];

    /** db vertical value => the old WordPress taxonomy domain that categorizes it. */
    public const VERTICAL_TAXONOMY = [
        'course' => 'category',
        'online_course' => 'categories',
        'university' => 'direction',
        'online_business' => 'business',
        'affiliate_network' => 'industry',
    ];

    const SORTS = ['rating', 'reviews', 'name', 'newest'];

    public function index(Request $request, Region $region, Language $language, string $vertical)
    {
        return $this->listingsView($request, $region, $language, $vertical, null);
    }

    public function category(Request $request, Region $region, Language $language, string $vertical, string $categorySlug)
    {
        if (! isset(self::VERTICALS[$vertical])) {
            throw new NotFoundHttpException;
        }
        [$dbVertical] = self::VERTICALS[$vertical];

        $category = TaxonomyTerm::query()
            ->where('taxonomy', self::VERTICAL_TAXONOMY[$dbVertical])
            ->where('slug', $categorySlug)
            ->firstOrFail();

        return $this->listingsView($request, $region, $language, $vertical, $category);
    }

    protected function listingsView(Request $request, Region $region, Language $language, string $vertical, ?TaxonomyTerm $category)
    {
        if (! isset(self::VERTICALS[$vertical])) {
            throw new NotFoundHttpException;
        }
        [$dbVertical, $label] = self::VERTICALS[$vertical];

        $sort = in_array($request->get('sort'), self::SORTS, true) ? $request->get('sort') : 'rating';

        $listings = Listing::query()
            ->active()
            ->vertical($dbVertical)
            ->where('region_id', $region->id)
            ->when($category, fn ($q) => $q->whereHas('taxonomyTerms', fn ($t) => $t->whereKey($category->id)))
            ->withRatingSummary()
            ->when($sort === 'rating', fn ($q) => $q->orderByDesc('average_rating')->orderByDesc('reviews_count'))
            ->when($sort === 'reviews', fn ($q) => $q->orderByDesc('reviews_count'))
            ->when($sort === 'name', fn ($q) => $q->orderBy('name'))
            ->when($sort === 'newest', fn ($q) => $q->orderByDesc('id'))
            ->paginate(15)
            ->withQueryString();

        // Every category with at least one active listing in this vertical +
        // region, so the sidebar/chip list only ever links somewhere real.
        $categories = TaxonomyTerm::query()
            ->where('taxonomy', self::VERTICAL_TAXONOMY[$dbVertical])
            ->whereHas('listings', fn ($q) => $q->where('vertical', $dbVertical)->where('region_id', $region->id)->where('is_active', true))
            ->withCount(['listings' => fn ($q) => $q->where('vertical', $dbVertical)->where('region_id', $region->id)->where('is_active', true)])
            ->orderByDesc('listings_count')
            ->get();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => $label, 'url' => route('directory.index', [$region, $language, $vertical])],
        ];
        if ($category) {
            $breadcrumbs[] = ['label' => $category->name];
        }

        // Every vertical (courses, universities, ...) that has active listings
        // in this region, with counts — cross-navigation so a visitor browsing
        // Courses can jump straight to Universities, etc.
        $verticalCounts = Listing::query()
            ->active()
            ->where('region_id', $region->id)
            ->selectRaw('vertical, count(*) as total')
            ->groupBy('vertical')
            ->pluck('total', 'vertical');

        $industries = collect(self::VERTICALS)
            ->map(fn ($v, $urlSlug) => [
                'slug' => $urlSlug,
                'label' => $v[1],
                'count' => $verticalCounts[$v[0]] ?? 0,
            ])
            ->filter(fn ($v) => $v['count'] > 0)
            ->values();

        return view('directory.index', [
            'region' => $region,
            'vertical' => $vertical,
            'verticalLabel' => $label,
            'verticals' => self::VERTICALS,
            'industries' => $industries,
            'listings' => $listings,
            'categories' => $categories,
            'category' => $category,
            'sort' => $sort,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function show(Region $region, Language $language, string $vertical, Listing $listing)
    {
        if (! isset(self::VERTICALS[$vertical]) || self::VERTICALS[$vertical][0] !== $listing->vertical) {
            throw new NotFoundHttpException;
        }
        if ($listing->region_id !== $region->id || ! $listing->is_active) {
            throw new NotFoundHttpException;
        }

        $listing->load(['addresses', 'prices', 'prosAndCons', 'approvedReviews', 'taxonomyTerms']);
        $listing->loadCount(['reviews as reviews_count' => fn ($q) => $q->where('is_approved', true)]);
        $listing->loadAvg(['reviews as average_rating' => fn ($q) => $q->where('is_approved', true)], 'rating');

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => self::VERTICALS[$vertical][1], 'url' => route('directory.index', [$region, $language, $vertical])],
            ['label' => $listing->name],
        ];

        return view('directory.show', [
            'region' => $region,
            'vertical' => $vertical,
            'verticalLabel' => self::VERTICALS[$vertical][1],
            'listing' => $listing,
            'breadcrumbs' => $breadcrumbs,
            'captchaQuestion' => \App\Support\MathCaptcha::question(),
        ]);
    }
}
