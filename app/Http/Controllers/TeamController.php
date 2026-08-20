<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Language;
use App\Models\Region;
use App\Models\SportCountry;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Transfer;
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

    /**
     * Fetch just the payload the active tab needs.
     *
     * Data is served from the local DB (populated by sport:sync-stats) and
     * re-shaped into the API-Football payload the views expect. If nothing has
     * been synced yet for this team, we fall back to a live API read so the
     * page still works before the first sync.
     */
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
                $all = $this->storedFixtures($team, $season) ?: $api->teamFixtures($team->api_id, $season);
                [$upcoming, $results] = $this->splitFixtures($all);
                return ['upcoming' => $upcoming, 'results' => $results];
            })(),

            'euro-cups' => (function () use ($api, $team, $season) {
                $euroIds = array_keys(config('football.euro_competitions', []));
                $stored  = $this->storedFixtures($team, $season, $euroIds);

                return ['euroFixtures' => $stored ?: $api->teamEuroFixtures($team->api_id, $season)];
            })(),

            'transfers' => (function () use ($api, $team) {
                $rows = Transfer::where('team_api_id', $team->api_id)
                    ->orderByDesc('transfer_date')
                    ->get();

                return ['transfers' => $rows->isNotEmpty()
                    ? Transfer::toApiShapeCollection($rows)
                    : $api->transfers($team->api_id)];
            })(),

            'standings' => (function () use ($api, $team, $season) {
                if (! $team->primary_league_api_id) {
                    return ['standings' => []];
                }

                $rows = Standing::where('league_api_id', $team->primary_league_api_id)
                    ->where('season', $season)
                    ->orderBy('rank')
                    ->get();

                return ['standings' => $rows->isNotEmpty()
                    ? $rows->map->toApiShape()->all()
                    : $api->standings($team->primary_league_api_id, $season)];
            })(),

            default => [],
        };
    }

    /** Stored fixtures for a team, optionally limited to certain leagues. */
    protected function storedFixtures(Team $team, int $season, array $leagueIds = []): array
    {
        $query = Fixture::forTeam($team->api_id)->season($season);

        if (! empty($leagueIds)) {
            $query->whereIn('league_api_id', $leagueIds);
        }

        return $query->orderBy('kickoff_at')->get()->map->toApiShape()->all();
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
