<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Language;
use App\Models\Sport;
use App\Models\SportCountry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FootballController extends Controller
{
    protected function football(): Sport
    {
        return Sport::where('slug', 'football')->active()->firstOrFail();
    }

    /**
     * /{language}/sport/football — match-day hub.
     *
     * Shows the matches of one day (today by default, ?date=YYYY-MM-DD
     * otherwise) grouped by competition, with a strip of nearby days and the
     * competition/country navigation alongside. Everything is read from the
     * local fixtures table, so the page costs no API calls.
     */
    public function countries(Language $language, Request $request)
    {
        $sport = $this->football();

        $countries = $sport->countries()->active()->ordered()
            ->withCount(['teams' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $date = $this->resolveDate($request->query('date'));

        $fixtures = Fixture::query()
            ->whereBetween('kickoff_at', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ])
            ->orderBy('kickoff_at')
            ->get();

        // Group by competition, keeping the configured leagues first so the
        // page opens on the competitions this site actually curates.
        $priority = array_keys(config('football.leagues', []) + config('football.euro_competitions', []));

        $groups = $fixtures
            ->groupBy(fn (Fixture $f) => $f->league_code ?: 'other')
            ->sortBy(function ($group, $code) use ($priority) {
                $index = array_search($code, $priority, true);

                return $index === false ? PHP_INT_MAX : $index;
            });

        // A day strip centred on the selected date.
        $days = collect(range(-3, 3))->map(fn (int $offset) => $date->copy()->addDays($offset));

        // Counts per day so empty days are visibly empty before clicking.
        $counts = Fixture::query()
            ->whereBetween('kickoff_at', [
                $days->first()->copy()->startOfDay(),
                $days->last()->copy()->endOfDay(),
            ])
            ->get()
            ->groupBy(fn (Fixture $f) => optional($f->kickoff_at)->toDateString())
            ->map->count();

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('home')],
            ['label' => __('sport.sports'), 'url' => route('sport.index', [$language])],
            ['label' => $sport->translate('name')],
        ];

        return view('sport.countries', compact(
            'sport', 'countries', 'breadcrumbs', 'groups', 'date', 'days', 'counts'
        ));
    }

    /** Selected day, defaulting to today and ignoring anything unparseable. */
    protected function resolveDate(?string $raw): Carbon
    {
        if ($raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
            } catch (\Throwable) {
                // fall through to today
            }
        }

        return Carbon::today();
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
