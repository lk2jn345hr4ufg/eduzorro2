<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Language;
use App\Models\Region;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /** Allowed sort keys -> human labels (labels are translated in the view). */
    public const SORTS = ['rating', 'reviews', 'name', 'newest'];

    public function show(Request $request, Region $region, Language $language, Industry $industry, Category $category)
    {
        // Verify the category really belongs to the industry in the URL (keeps URLs canonical).
        abort_unless(
            $industry->is_active && $category->is_active && $category->industry_id === $industry->id,
            404
        );

        $sort = in_array($request->query('sort'), self::SORTS, true)
            ? $request->query('sort')
            : 'rating';

        $query = Company::active()
            ->where('category_id', $category->id)
            ->inRegion($region)
            ->withRatingSummary();

        $query = match ($sort) {
            'reviews' => $query->orderByDesc('reviews_count'),
            'name'    => $query->orderBy('name'),
            'newest'  => $query->latest(),
            default   => $query->orderByDesc('average_rating')->orderByDesc('reviews_count'),
        };

        $companies = $query->paginate(12)->withQueryString();

        // Related categories = active siblings in the same industry.
        $relatedCategories = $category->siblings()->active()->ordered()->get();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => $industry->translate('name'), 'url' => route('industry.show', [$region, $language, $industry])],
            ['label' => $category->translate('name')],
        ];

        return view('category', [
            'industry'          => $industry,
            'category'          => $category,
            'companies'         => $companies,
            'relatedCategories' => $relatedCategories,
            'sort'              => $sort,
            'breadcrumbs'       => $breadcrumbs,
        ]);
    }
}
