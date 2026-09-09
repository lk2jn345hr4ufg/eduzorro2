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
                // Seasons actually stored for this team, newest first.
                $seasons = Fixture::forTeam($team->api_id)
                    ->distinct()->orderByDesc('season')->pluck('season')->all();

                // Prefer the configured season, but if nothing was synced for
                // it fall back to the newest season we do have, so the page
                // never renders empty just because the setting drifted.
                $effective = in_array($season, $seasons, true)
                    ? $season
                    : ($seasons[0] ?? $season);

                $all = $this->storedFixtures($team, $effective)
                    ?: $api->teamFixtures($team->api_id, $effective);

                [$upcoming, $results] = $this->splitFixtures($all);

                return [
                    'upcoming'       => $upcoming,
                    'results'        => $results,
                    'allFixtures'    => $all,
                    'seasons'        => $seasons,
                    'currentSeason'  => $effective,
                ];
            })(),

            'euro-cups' => (function () use ($api, $team, $season) {
                $codes  = array_keys(config('football.euro_competitions', []));
                $stored = $this->storedFixtures($team, $season, $codes);

                return ['euroFixtures' => $stored ?: $api->teamEuroFixtures($team->api_id, $season)];
            })(),

            'transfers' => (function () use ($team) {
                // Transfers come from api-sports, which has its own team ids —
                // hence apisports_id rather than the football-data api_id.
                // Older rows imported before the provider switch are still keyed
                // to that same api-sports id, so both keep working.
                $transferId = $team->apisports_id ?: $team->api_id;

                $rows = Transfer::where('team_api_id', $transferId)
                    ->orderByDesc('transfer_date')
                    ->get();

                return ['transfers' => Transfer::toApiShapeCollection($rows)];
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
                    : ($team->primary_league_code
                        ? $api->standings($team->primary_league_code, $season)
                        : [])];
            })(),

            default => [],
        };
    }

    /** Stored fixtures for a team, optionally limited to certain competitions. */
    protected function storedFixtures(Team $team, int $season, array $leagueCodes = []): array
    {
        $query = Fixture::forTeam($team->api_id)->season($season);

        if (! empty($leagueCodes)) {
            $query->whereIn('league_code', $leagueCodes);
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
