<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Language;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Transfer;
use App\Support\TeamLinks;

/**
 * Team page: /{language}/team/{team}[/{tab}].
 *
 * Everything is served from the local DB, filled by sport:sync-competition
 * (fixtures + standings), sport:sync-transfers and the news pipeline.
 */
class TeamController extends Controller
{
    public const TABS = ['dashboard', 'news', 'standings', 'euro-cups', 'transfers', 'fixtures', 'results'];

    public function show(Language $language, Team $team, string $tab = 'dashboard')
    {
        abort_unless($team->is_active, 404);
        abort_unless(in_array($tab, self::TABS, true), 404);

        $team->loadMissing('competition.country', 'country');
        $competition = $team->competition;

        $breadcrumbs = [['label' => __('messages.home'), 'url' => route('football.home', [$language])]];

        if ($competition && $competition->is_active) {
            $breadcrumbs[] = [
                'label' => $competition->translate('name'),
                'url'   => route('competition.show', [$language, $competition]),
            ];
        }

        $breadcrumbs[] = ['label' => $team->translate('name')];

        $data = $team->api_id || in_array($tab, ['news', 'transfers'], true)
            ? $this->tabData($tab, $team, $competition)
            : ['apiMissing' => true];

        return view('sport.team', array_merge([
            'team'        => $team,
            'competition' => $competition,
            'tab'         => $tab,
            'tabs'        => self::TABS,
            'breadcrumbs' => $breadcrumbs,
        ], $data));
    }

    protected function tabData(string $tab, Team $team, ?Competition $competition): array
    {
        $apiId = (int) $team->api_id;

        switch ($tab) {
            case 'dashboard':
                $next = Fixture::forTeam($apiId)->upcoming()
                    ->where('kickoff_at', '>=', now()->subHours(3))
                    ->with('odd')->orderBy('kickoff_at')->take(5)->get();

                $recent = Fixture::forTeam($apiId)->played()
                    ->orderByDesc('kickoff_at')->take(5)->get();

                [$table] = $this->leagueTable($team, $competition);

                // A window of the table around this team (two above, two below).
                $pos   = $table->search(fn ($row) => (int) $row->team_api_id === $apiId);
                $slice = $pos === false
                    ? $table->take(5)
                    : $table->slice(max(0, min($pos - 2, $table->count() - 5)), 5);

                return [
                    'next'      => $next,
                    'recent'    => $recent,
                    'form'      => $recent->map(fn ($f) => $this->outcome($f, $apiId))->reverse()->values(),
                    'tableRows' => $slice->values(),
                    'teamRow'   => $pos === false ? null : $table[$pos],
                    'news'      => $team->news()->active()->published()->take(3)->get(),
                    'links'     => TeamLinks::forIds(
                        $slice->pluck('team_api_id')
                            ->merge($next->pluck('home_api_id'))->merge($next->pluck('away_api_id'))
                            ->merge($recent->pluck('home_api_id'))->merge($recent->pluck('away_api_id'))
                    ),
                ];

            case 'news':
                return ['news' => $team->news()->active()->published()->take(30)->get()];

            case 'standings':
                [$table] = $this->leagueTable($team, $competition);

                return [
                    'tableGroups' => $table->isEmpty() ? collect() : collect(['' => $table]),
                    'links'       => TeamLinks::forIds($table->pluck('team_api_id')),
                ];

            case 'euro-cups':
                $codes = Competition::cups()->pluck('code')
                    ->merge(array_keys(config('football.euro_competitions', [])))
                    ->unique()->values()->all();

                $rows = Fixture::forTeam($apiId)->whereIn('league_code', $codes)
                    ->with('odd')->orderByDesc('kickoff_at')->get();

                return [
                    'cupGroups' => $rows->groupBy('league_code'),
                    'cupNames'  => Competition::whereIn('code', $codes)->get()->keyBy('code'),
                    'links'     => TeamLinks::forFixtures($rows),
                ];

            case 'transfers':
                // Transfers come from api-sports, which has its own team ids.
                $rows = Transfer::where('team_api_id', $team->apisports_id ?: $team->api_id)
                    ->orderByDesc('transfer_date')->get();

                return ['transfers' => Transfer::toApiShapeCollection($rows)];

            case 'fixtures':
                $rows = Fixture::forTeam($apiId)->upcoming()
                    ->where('kickoff_at', '>=', now()->subHours(3))
                    ->with('odd')->orderBy('kickoff_at')->get();

                return [
                    'days'  => $rows->groupBy(fn ($f) => optional($f->kickoff_at)->toDateString()),
                    'links' => TeamLinks::forFixtures($rows),
                ];

            case 'results':
                $rows = Fixture::forTeam($apiId)->played()->orderByDesc('kickoff_at')->get();

                return [
                    'days'     => $rows->groupBy(fn ($f) => optional($f->kickoff_at)->toDateString()),
                    'links'    => TeamLinks::forFixtures($rows),
                    'outcomes' => $rows->mapWithKeys(fn ($f) => [$f->id => $this->outcome($f, $apiId)]),
                ];
        }

        return [];
    }

    /**
     * The team's championship table for the newest season we hold.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: ?int}
     */
    protected function leagueTable(Team $team, ?Competition $competition): array
    {
        $code = $competition?->code ?: $team->primary_league_code;

        if (! $code) {
            return [collect(), null];
        }

        $season = $competition
            ? $competition->currentSeason()
            : (int) Standing::where('league_code', $code)->max('season');

        $rows = Standing::where('league_code', $code)
            ->where('season', $season)
            ->orderBy('rank')
            ->get()
            ->values();

        return [$rows, $season];
    }

    /** W / D / L from this team's point of view. */
    protected function outcome(Fixture $fixture, int $apiId): string
    {
        $home   = (int) $fixture->home_api_id === $apiId;
        $mine   = $home ? $fixture->goals_home : $fixture->goals_away;
        $theirs = $home ? $fixture->goals_away : $fixture->goals_home;

        return $mine > $theirs ? 'W' : ($mine < $theirs ? 'L' : 'D');
    }
}
