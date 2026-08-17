<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Region;
use App\Models\Sport;

class SportController extends Controller
{
    /** /{region}/{lang}/sport — list of sports. */
    public function index(Region $region, Language $language)
    {
        $sports = Sport::active()->ordered()->get();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => __('sport.sports')],
        ];

        return view('sport.index', compact('sports', 'breadcrumbs'));
    }

    /** /{region}/{lang}/sport/{sport} — non-football sports (no deep hierarchy yet). */
    public function show(Region $region, Language $language, Sport $sport)
    {
        abort_unless($sport->is_active, 404);

        // Football has its own dedicated routes/hierarchy.
        if ($sport->slug === 'football') {
            return redirect()->route('sport.football.countries', [$region, $language]);
        }

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => __('sport.sports'), 'url' => route('sport.index', [$region, $language])],
            ['label' => $sport->translate('name')],
        ];

        return view('sport.show', compact('sport', 'breadcrumbs'));
    }
}
