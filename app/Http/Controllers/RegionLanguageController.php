<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Industry;
use App\Models\Language;
use App\Models\Listing;
use App\Models\Region;

class RegionLanguageController extends Controller
{
    public function index(Region $region, Language $language)
    {
        // All industries with their active categories (crawlable link hub).
        $industries = Industry::active()
            ->ordered()
            ->with(['categories' => fn ($q) => $q->active()->ordered()])
            ->get();

        // WordPress-imported content for this region, if any: a count per
        // vertical (courses, universities, ...) plus the business-registry
        // count, so the "Explore" section on region-language.blade.php only
        // shows up where there's real data behind it.
        $verticalCounts = Listing::query()
            ->active()
            ->where('region_id', $region->id)
            ->where('language_code', $language->code)
            ->selectRaw('vertical, count(*) as total')
            ->groupBy('vertical')
            ->pluck('total', 'vertical');

        $verticals = collect(DirectoryController::VERTICALS)
            ->map(fn ($v, $urlSlug) => [
                'slug' => $urlSlug,
                'label' => $v[1],
                'count' => $verticalCounts[$v[0]] ?? 0,
            ])
            ->filter(fn ($v) => $v['count'] > 0)
            ->values();

        $businessCount = Business::active()->where('region_id', $region->id)->count();

        return view('region-language', [
            'industries' => $industries,
            'verticals' => $verticals,
            'businessCount' => $businessCount,
        ]);
    }
}
