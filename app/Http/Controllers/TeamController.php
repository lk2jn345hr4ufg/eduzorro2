<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Region;
use App\Models\SportCountry;
use App\Models\Team;
use App\Services\Football\ApiFootballClient;

class TeamController extends Controller
{
    public const TABS = ['news', 'fixtures', 'euro-cups', 'transfers', 'standings'];

    public function show(
        Region $region,
        Language $language,
        SportCountry $country,
        Team $team,
        ApiFootballClient $api,
        string $tab = 'news'
    ) {
        abort_unless($team->sport_country_id === $country->id && $team->is_active, 404);
        abort_unless(in_array($tab, self::TABS, true), 404);

        $season = (int) config('football.season');
        $data   = $this->tabData($tab, $team, $api, $season);

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('region.home', [$region, $language])],
            ['label' => __('sport.sports'), 'url' => route('sport.index', [$region, $language])],
            ['label' => __('sport.football'), 'url' => route('sport.football.countries', [$region, $language])],
            ['label' => $country->translate('name'), 'url' => route('sport.football.country', [$region, $language, $country])],
            ['label' => $team->translate('name')],
        ];

        return view('sport.team', array_merge([
            'country'     => $country,
            'team'        => $team,
            'tab'         => $tab,
            'tabs'        => self::TABS,
            'breadcrumbs' => $breadcrumbs,
        ], $data));
    }

    /** Fetch just the payload the active tab needs (keeps API calls minimal). */
    protected function tabData(string $tab, Team $team, ApiFootballClient $api, int $season): array
    {
        // News is local; everything else needs the team's API id.
        if ($tab === 'news') {
            return ['news' => $team->news()->active()->published()->take(30)->get()];
        }

        if (! $team->api_id) {
            return ['apiMissing' => true];
        }

        return match ($tab) {
            'fixtures' => (function () use ($api, $team, $season) {
                $all = $api->teamFixtures($team->api_id, $season);
                [$upcoming, $results] = $this->splitFixtures($all);
                return ['upcoming' => $upcoming, 'results' => $results];
            })(),

            'euro-cups' => ['euroFixtures' => $api->teamEuroFixtures($team->api_id, $season)],

            'transfers' => ['transfers' => $api->transfers($team->api_id)],

            'standings' => [
                'standings' => $team->primary_league_api_id
                    ? $api->standings($team->primary_league_api_id, $season)
                    : [],
            ],

            default => [],
        };
    }

    /** Partition fixtures into upcoming (not finished) and results (finished). */
    protected function splitFixtures(array $fixtures): array
    {
        $finished = ['FT', 'AET', 'PEN'];
        $upcoming = [];
        $results  = [];

        foreach ($fixtures as $fx) {
            $status = data_get($fx, 'fixture.status.short');
            if (in_array($status, $finished, true)) {
                $results[] = $fx;
            } else {
                $upcoming[] = $fx;
            }
        }

        // Upcoming ascending by date, results most-recent first.
        usort($upcoming, fn ($a, $b) => strcmp(data_get($a, 'fixture.date', ''), data_get($b, 'fixture.date', '')));
        usort($results, fn ($a, $b) => strcmp(data_get($b, 'fixture.date', ''), data_get($a, 'fixture.date', '')));

        return [$upcoming, $results];
    }
}
