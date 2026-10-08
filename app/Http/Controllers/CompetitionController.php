<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Language;
use App\Models\Standing;
use App\Models\TeamNews;
use App\Models\Transfer;
use App\Support\TeamLinks;

/**
 * Championship (league) and tournament (cup) pages. Leagues show every tab;
 * tournaments omit the team list and transfers (see Competition::tabs()).
 */
class CompetitionController extends Controller
{
    public const TABS = ['dashboard', 'standings', 'fixtures', 'results', 'teams', 'transfers'];

    public function show(Language $language, Competition $competition, string $tab = 'dashboard')
    {
        abort_unless($competition->is_active, 404);
        abort_unless(in_array($tab, $competition->tabs(), true), 404);

        $competition->loadMissing('country');

        $season = $competition->currentSeason();
        $data   = $this->tabData($tab, $competition, $season);

        $breadcrumbs = [
            ['label' => __('messages.home'), 'url' => route('football.home', [$language])],
            ['label' => $competition->translate('name')],
        ];

        return view('football.competition', array_merge([
            'competition' => $competition,
            'tab'         => $tab,
            'tabs'        => $competition->tabs(),
            'season'      => $season,
            'breadcrumbs' => $breadcrumbs,
        ], $data));
    }

    protected function tabData(string $tab, Competition $competition, int $season): array
    {
        $fixtures = fn () => Fixture::where('league_code', $competition->code)->where('season', $season);

        $standings = fn () => Standing::where('league_code', $competition->code)
            ->where('season', $season)
            ->orderBy('group_label')
            ->orderBy('rank')
            ->get();

        switch ($tab) {
            case 'dashboard':
                $table    = $standings();
                $upcoming = $fixtures()->upcoming()->with('odd')->orderBy('kickoff_at')->take(6)->get();
                $recent   = $fixtures()->played()->orderByDesc('kickoff_at')->take(6)->get();
                $teams    = $competition->isLeague()
                    ? $competition->teams()->active()->get()->sortBy(fn ($t) => $t->translate('name'))->values()
                    : collect();

                return [
                    'tableGroups' => $this->groupTable($table, $competition->isLeague() ? 6 : 2),
                    'upcoming'    => $upcoming,
                    'recent'      => $recent,
                    'teams'       => $teams,
                    'news'        => TeamNews::query()->active()->published()
                        ->whereIn('team_id', $competition->teams()->pluck('id'))
                        ->with('team')->latest('published_at')->take(4)->get(),
                    'links'       => TeamLinks::forIds(
                        $table->pluck('team_api_id')
                            ->merge($upcoming->pluck('home_api_id'))->merge($upcoming->pluck('away_api_id'))
                            ->merge($recent->pluck('home_api_id'))->merge($recent->pluck('away_api_id'))
                    ),
                ];

            case 'standings':
                $table = $standings();

                return [
                    'tableGroups' => $this->groupTable($table),
                    'links'       => TeamLinks::forIds($table->pluck('team_api_id')),
                ];

            case 'fixtures':
                $rows = $fixtures()->upcoming()->with('odd')->orderBy('kickoff_at')->get();

                return [
                    'days'  => $rows->groupBy(fn ($f) => optional($f->kickoff_at)->toDateString()),
                    'links' => TeamLinks::forFixtures($rows),
                ];

            case 'results':
                $rows = $fixtures()->played()->orderByDesc('kickoff_at')->get();

                return [
                    'days'  => $rows->groupBy(fn ($f) => optional($f->kickoff_at)->toDateString()),
                    'links' => TeamLinks::forFixtures($rows),
                ];

            case 'teams':
                return [
                    'teams' => $competition->teams()->active()->get()
                        ->sortBy(fn ($t) => $t->translate('name'))->values(),
                ];

            case 'transfers':
                $teams = $competition->teams()->active()->get();

                // Transfers are keyed by API-Football team ids (see TeamController).
                $byTransferId = $teams->keyBy(fn ($t) => (int) ($t->apisports_id ?: $t->api_id));

                return [
                    'transfers' => Transfer::query()
                        ->whereIn('team_api_id', $byTransferId->keys())
                        ->orderByDesc('transfer_date')
                        ->take(150)
                        ->get(),
                    'transferTeams' => $byTransferId,
                ];
        }

        return [];
    }

    /**
     * Group standing rows by group label (a league has a single unnamed group),
     * optionally keeping only the top N of each.
     */
    protected function groupTable($rows, ?int $limit = null)
    {
        return $rows
            ->groupBy(fn ($row) => $row->group_label ?: '')
            ->map(fn ($group) => $limit ? $group->take($limit) : $group);
    }
}
