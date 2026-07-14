<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Listing;
use App\Models\Region;
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

    const SORTS = ['rating', 'reviews', 'name', 'newest'];

    public function index(Request $request, Region $region, Language $language, string $vertical)
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
            ->withRatingSummary()
            ->when($sort === 'rating', fn ($q) => $q->orderByDesc('average_rating')->orderByDesc('reviews_count'))
            ->when($sort === 'reviews', fn ($q) => $q->orderByDesc('reviews_count'))
            ->when($sort === 'name', fn ($q) => $q->orderBy('name'))
            ->when($sort === 'newest', fn ($q) => $q->orderByDesc('id'))
            ->paginate(15)
            ->withQueryString();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => $label],
        ];

        return view('directory.index', [
            'region' => $region,
            'vertical' => $vertical,
            'verticalLabel' => $label,
            'verticals' => self::VERTICALS,
            'listings' => $listings,
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

        $listing->load(['addresses', 'prices', 'prosAndCons', 'approvedReviews']);
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
        ]);
    }
}
