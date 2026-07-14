<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Language;
use App\Models\Region;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** AJAX autocomplete: returns a small JSON list of matching companies + categories. */
    public function suggest(Request $request, Region $region, Language $language)
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $locale = app()->getLocale();
        $like   = '%' . $term . '%';

        $companies = Company::active()
            ->inRegion($region)
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Company $c) => [
                'type'  => 'company',
                'label' => $c->name,
                'url'   => route('company.show', [$region, $language, $c]),
            ]);

        // Categories store names as JSON; query the current locale via JSON path.
        $categories = Category::active()
            ->where("name->{$locale}", 'like', $like)
            ->with('industry')
            ->limit(4)
            ->get()
            ->map(fn (Category $cat) => [
                'type'  => 'category',
                'label' => $cat->translate('name'),
                'url'   => route('category.show', [$region, $language, $cat->industry, $cat]),
            ]);

        return response()->json($companies->concat($categories)->values());
    }

    /** Full search results page. */
    public function results(Request $request, Region $region, Language $language)
    {
        $term = trim((string) $request->query('q', ''));

        $companies = collect();
        if (mb_strlen($term) >= 2) {
            $companies = Company::active()
                ->inRegion($region)
                ->where('name', 'like', '%' . $term . '%')
                ->withRatingSummary()
                ->orderByDesc('average_rating')
                ->paginate(12)
                ->withQueryString();
        }

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => __('messages.search_results')],
        ];

        return view('search', [
            'term'        => $term,
            'companies'   => $companies,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
