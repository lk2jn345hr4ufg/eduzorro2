<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Language;
use App\Models\Region;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Region $region, Language $language, Company $company)
    {
        abort_unless(
            $company->is_active && $company->regions()->whereKey($region->id)->exists(),
            404
        );

        $data = $request->validate([
            'author_name'  => ['required', 'string', 'max:120'],
            'author_email' => ['nullable', 'email', 'max:190'],
            'rating'       => ['required', 'integer', 'between:1,5'],
            'title'        => ['nullable', 'string', 'max:150'],
            'body'         => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $company->reviews()->create([
            ...$data,
            'is_approved' => false, // held for moderation
        ]);

        return redirect()
            ->route('company.show', [$region, $language, $company])
            ->with('status', __('messages.review_submitted'));
    }
}
