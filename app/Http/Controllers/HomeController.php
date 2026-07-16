<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Industry;
use App\Models\Language;
use App\Models\Listing;
use App\Models\Region;

class HomeController extends Controller
{
    public function index()
    {
        $regions    = Region::active()->ordered()->with('languages')->get();
        $languages  = Language::active()->ordered()->get();
        $industries = Industry::active()->ordered()->get();

        $verticalCountsByRegion = Listing::query()
            ->active()
            ->selectRaw('region_id, vertical, language_code, count(*) as total')
            ->groupBy('region_id', 'vertical', 'language_code')
            ->get()
            ->groupBy('region_id');

        $businessCountsByRegion = Business::query()
            ->active()
            ->selectRaw('region_id, count(*) as total')
            ->groupBy('region_id')
            ->pluck('total', 'region_id');

        $regionExtras = $regions->mapWithKeys(function (Region $region) use ($verticalCountsByRegion, $businessCountsByRegion) {
            $verticals = collect(DirectoryController::VERTICALS)
                ->map(fn ($v, $urlSlug) => [
                    'slug' => $urlSlug,
                    'label' => $v[1],
                    'count' => $verticalCountsByRegion->get($region->id)
                        ?->first(fn ($row) => $row->vertical === $v[0] && $row->language_code === ($region->languages->first()?->code))
                        ?->total ?? 0,
                ])
                ->filter(fn ($v) => $v['count'] > 0)
                ->values();

            $businessCount = $businessCountsByRegion[$region->id] ?? 0;

            return [$region->id => [
                'verticals' => $verticals,
                'businessCount' => $businessCount,
                // Each region's own first supported language — Kazakhstan
                // only has Russian, so links there always use Russian.
                'linkLanguage' => $region->languages->first(),
            ]];
        });

        // For the "All languages" entry-point list: the first active region
        // (by sort order) that actually offers each language.
        $languageEntryRegion = $languages->mapWithKeys(function (Language $language) use ($regions) {
            $region = $regions->first(fn (Region $r) => $r->languages->contains('id', $language->id));

            return [$language->id => $region];
        })->filter();

        return view('home', [
            'regions'    => $regions,
            'languages'  => $languages,
            'industries' => $industries,
            'regionExtras' => $regionExtras,
            'languageEntryRegion' => $languageEntryRegion,
        ]);
    }
}
