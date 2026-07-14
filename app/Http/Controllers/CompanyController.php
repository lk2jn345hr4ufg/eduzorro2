<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Language;
use App\Models\Region;

class CompanyController extends Controller
{
    public function show(Region $region, Language $language, Company $company)
    {
        // Company must be active and available in the current region.
        abort_unless(
            $company->is_active && $company->regions()->whereKey($region->id)->exists(),
            404
        );

        $company->load(['category.industry', 'approvedReviews']);

        $reviewsCount = $company->approvedReviews->count();
        $averageRating = $reviewsCount
            ? round($company->approvedReviews->avg('rating'), 1)
            : 0.0;

        // Related companies: same category, same region, excluding self, best-rated first.
        $related = Company::active()
            ->where('category_id', $company->category_id)
            ->where('id', '!=', $company->id)
            ->inRegion($region)
            ->withRatingSummary()
            ->orderByDesc('average_rating')
            ->limit(6)
            ->get();

        $industry = $company->category->industry;

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => $industry->translate('name'), 'url' => route('industry.show', [$region, $language, $industry])],
            ['label' => $company->category->translate('name'), 'url' => route('category.show', [$region, $language, $industry, $company->category])],
            ['label' => $company->name],
        ];

        return view('company', [
            'company'       => $company,
            'industry'      => $industry,
            'reviews'       => $company->approvedReviews,
            'reviewsCount'  => $reviewsCount,
            'averageRating' => $averageRating,
            'related'       => $related,
            'breadcrumbs'   => $breadcrumbs,
        ]);
    }
}
