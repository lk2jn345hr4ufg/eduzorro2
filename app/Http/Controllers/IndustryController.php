<?php

namespace App\Http\Controllers;

use App\Models\Industry;
use App\Models\Language;
use App\Models\Region;

class IndustryController extends Controller
{
    public function show(Region $region, Language $language, Industry $industry)
    {
        abort_unless($industry->is_active, 404);

        $categories = $industry->categories()->active()->ordered()->get();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => $industry->translate('name')],
        ];

        return view('industry', [
            'industry'    => $industry,
            'categories'  => $categories,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
