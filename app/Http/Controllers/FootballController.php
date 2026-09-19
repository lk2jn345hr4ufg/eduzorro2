<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Sport;
use App\Models\SportCountry;

class FootballController extends Controller
{
    protected function football(): Sport
    {
        return Sport::where('slug', 'football')->active()->firstOrFail();
    }

    /** /{language}/sport/football — list of countries. */
    public function countries(Language $language)
    {
        $sport     = $this->football();
        $countries = $sport->countries()->active()->ordered()
            ->withCount(['teams' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('home')],
            ['label' => __('sport.sports'), 'url' => route('sport.index', [$language])],
            ['label' => $sport->translate('name')],
        ];

        return view('sport.countries', compact('sport', 'countries', 'breadcrumbs'));
    }

    /** /{language}/sport/football/{country} — teams in a country. */
    public function teams(Language $language, SportCountry $country)
    {
        $sport = $this->football();
        abort_unless($country->sport_id === $sport->id && $country->is_active, 404);

        $teams = $country->teams()->active()->ordered()->get();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('home')],
            ['label' => __('sport.sports'), 'url' => route('sport.index', [$language])],
            ['label' => $sport->translate('name'), 'url' => route('sport.football.countries', [$language])],
            ['label' => $country->translate('name')],
        ];

        return view('sport.teams', compact('sport', 'country', 'teams', 'breadcrumbs'));
    }
}
