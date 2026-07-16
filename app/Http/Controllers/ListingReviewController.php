<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Listing;
use App\Models\ListingReview;
use App\Models\Region;
use App\Support\MathCaptcha;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListingReviewController extends Controller
{
    public function store(Request $request, Region $region, Language $language, string $vertical, Listing $listing)
    {
        // Same routability checks as the profile page itself.
        if (! isset(DirectoryController::VERTICALS[$vertical])
            || DirectoryController::VERTICALS[$vertical][0] !== $listing->vertical
            || $listing->region_id !== $region->id
            || ! $listing->is_active) {
            throw new NotFoundHttpException;
        }

        // Honeypot: real users never see this field; bots fill everything.
        // Pretend success so the bot learns nothing.
        if ($request->filled('website_url')) {
            return redirect()
                ->route('directory.show', [$region, $language, $vertical, $listing])
                ->with('status', __('messages.review_submitted'));
        }

        $data = $request->validate([
            'author_name'  => ['required', 'string', 'max:255'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'rating'       => ['required', 'integer', 'between:1,5'],
            'body'         => ['required', 'string', 'min:10', 'max:5000'],
            'captcha'      => ['required'],
        ]);

        if (! MathCaptcha::check($data['captcha'])) {
            return back()
                ->withInput()
                ->withErrors(['captcha' => __('messages.captcha_error')])
                ->withFragment('review-form');
        }

        ListingReview::create([
            'listing_id'   => $listing->id,
            'author_name'  => $data['author_name'],
            'author_email' => $data['author_email'] ?? null,
            'rating'       => (int) $data['rating'],
            'body'         => $data['body'],
            'is_approved'  => false,   // moderation queue — admin approves in /admin
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return redirect()
            ->route('directory.show', [$region, $language, $vertical, $listing])
            ->with('status', __('messages.review_submitted'))
            ->withFragment('reviews');
    }
}
