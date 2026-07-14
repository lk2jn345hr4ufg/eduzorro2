<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Language;
use App\Models\Region;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BusinessController extends Controller
{
    public function index(Request $request, Region $region, Language $language)
    {
        $q = trim((string) $request->get('q', ''));

        $businesses = Business::query()
            ->active()
            ->where('region_id', $region->id)
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('edrpou', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => __('messages.all_businesses')],
        ];

        return view('directory.businesses', [
            'region' => $region,
            'businesses' => $businesses,
            'q' => $q,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function show(Region $region, Language $language, Business $business)
    {
        if ($business->region_id !== $region->id || ! $business->is_active) {
            throw new NotFoundHttpException;
        }

        $business->load('taxonomyTerms');

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => __('messages.all_businesses'), 'url' => route('business.index', [$region, $language])],
            ['label' => $business->name],
        ];

        return view('directory.business', [
            'region' => $region,
            'business' => $business,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
