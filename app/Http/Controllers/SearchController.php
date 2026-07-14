<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Language;
use App\Models\Listing;
use App\Models\Region;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** AJAX autocomplete: returns a small JSON list of matching listings + businesses. */
    public function suggest(Request $request, Region $region, Language $language)
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $like = '%' . $term . '%';
        $urlSlugByVertical = array_flip(array_map(fn ($v) => $v[0], DirectoryController::VERTICALS));

        $listings = Listing::query()
            ->active()
            ->where('region_id', $region->id)
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Listing $l) => [
                'type'  => 'listing',
                'label' => $l->name,
                'url'   => route('directory.show', [$region, $language, $urlSlugByVertical[$l->vertical], $l]),
            ]);

        $businesses = Business::query()
            ->active()
            ->where('region_id', $region->id)
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(4)
            ->get()
            ->map(fn (Business $b) => [
                'type'  => 'business',
                'label' => $b->name,
                'url'   => route('business.show', [$region, $language, $b]),
            ]);

        return response()->json($listings->concat($businesses)->values());
    }

    /** Full search results page. */
    public function results(Request $request, Region $region, Language $language)
    {
        $term = trim((string) $request->query('q', ''));

        $listings = collect();
        $businesses = collect();

        if (mb_strlen($term) >= 2) {
            $listings = Listing::query()
                ->active()
                ->where('region_id', $region->id)
                ->where('name', 'like', '%' . $term . '%')
                ->withRatingSummary()
                ->orderByDesc('average_rating')
                ->limit(24)
                ->get();

            $businesses = Business::query()
                ->active()
                ->where('region_id', $region->id)
                ->where('name', 'like', '%' . $term . '%')
                ->orderBy('name')
                ->limit(24)
                ->get();
        }

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => __('messages.search_results')],
        ];

        return view('search', [
            'term'        => $term,
            'listings'    => $listings,
            'businesses'  => $businesses,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
