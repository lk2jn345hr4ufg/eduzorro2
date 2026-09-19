<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Sport;

class SportController extends Controller
{
    /** /{language}/sport — list of sports. */
    public function index(Language $language)
    {
        $sports = Sport::active()->ordered()->get();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('home')],
            ['label' => __('sport.sports')],
        ];

        return view('sport.index', compact('sports', 'breadcrumbs'));
    }

    /** /{language}/sport/{sport} — non-football sports (no deep hierarchy yet). */
    public function show(Language $language, Sport $sport)
    {
        abort_unless($sport->is_active, 404);

        // Football has its own dedicated routes/hierarchy.
        if ($sport->slug === 'football') {
            return redirect()->route('sport.football.countries', [$language]);
        }

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('home')],
            ['label' => __('sport.sports'), 'url' => route('sport.index', [$language])],
            ['label' => $sport->translate('name')],
        ];

        return view('sport.show', compact('sport', 'breadcrumbs'));
    }
}
