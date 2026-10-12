<?php

namespace App\Filament\Pages;

use App\Filament\Support\SyncRunner;
use App\Filament\Widgets\CompetitionFreshness;
use App\Filament\Widgets\CountryFreshness;
use App\Filament\Widgets\EduzorroOverview;
use App\Filament\Widgets\RecentSyncs;
use App\Filament\Widgets\SyncFreshness;
use App\Filament\Widgets\TeamFreshness;
use App\Models\Competition;
use App\Models\SportCountry;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Admin home: site content at a glance and when every kind of data was last
 * refreshed — per championship/tournament, per country and per team — plus
 * the log of sync runs.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = -10;

    protected static ?string $title = 'Dashboard';

    /**
     * Bulk sync buttons. Every one runs in the background (after the page
     * has answered), because they touch many competitions or teams and are
     * throttled to the APIs' per-minute limits. Per-row buttons in the
     * tables below sync a single competition, country or team.
     */
    protected function getHeaderActions(): array
    {
        $leagueOptions = fn (string $column = 'api_id') => Competition::query()
            ->whereNotNull($column)->orderBy('sort_order')->get()
            ->mapWithKeys(fn ($c) => [$c->code => trim($c->flag.' '.$c->code.' — '.$c->translate('name'))])
            ->all();

        return [
            Action::make('syncMatches')
                ->label('Matches & tables')
                ->icon('heroicon-o-table-cells')
                ->form([
                    Select::make('codes')->label('Competitions')->multiple()
                        ->options(fn () => $leagueOptions('api_id'))
                        ->placeholder('All with a football-data id'),
                ])
                ->modalDescription('football-data.org, 2 requests per competition, ~7 s apart. All 11 competitions take about 3 minutes.')
                ->action(function (array $data) {
                    $params = ['--sleep' => 7];
                    if (! empty($data['codes'])) {
                        $params['codes'] = array_values($data['codes']);
                    }
                    SyncRunner::background('Matches & tables', [['sport:sync-competition', $params]]);
                }),

            Action::make('syncOdds')
                ->label('Odds')
                ->icon('heroicon-o-currency-dollar')
                ->form([
                    Select::make('codes')->label('Competitions')->multiple()
                        ->options(fn () => $leagueOptions(config('odds.provider') === 'apisports' ? 'apisports_league_id' : 'oddsapi_sport_key'))
                        ->placeholder('All with an odds key'),
                    TextInput::make('days')->numeric()->default(3)->minValue(1)->maxValue(7),
                ])
                ->modalDescription('The Odds API: 1 credit per competition that has matches in the window (free plan: 500 a month).')
                ->action(function (array $data) {
                    $params = ['--days' => max(1, (int) ($data['days'] ?? 3))];
                    if (! empty($data['codes'])) {
                        $params['codes'] = array_values($data['codes']);
                    }
                    SyncRunner::background('Odds', [['sport:sync-odds', $params]]);
                }),

            ActionGroup::make([
                Action::make('importTeams')
                    ->label('Import teams (+ squads)')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        Select::make('codes')->label('Championships')->multiple()->required()
                            ->options(fn () => Competition::query()->leagues()->whereNotNull('api_id')->orderBy('sort_order')->get()
                                ->mapWithKeys(fn ($c) => [$c->code => trim($c->flag.' '.$c->code.' — '.$c->translate('name'))])->all()),
                        Toggle::make('create_countries')->label('Create missing countries'),
                    ])
                    ->modalDescription('1 request per championship. Squads are stored from the same response when the API includes them.')
                    ->action(function (array $data) {
                        $runs = collect($data['codes'])->map(fn ($code) => ['sport:sync-football', array_filter([
                            '--competition'      => $code,
                            '--sleep'            => 0,
                            '--create-countries' => ! empty($data['create_countries']) ?: null,
                        ])])->all();
                        SyncRunner::background('Import teams', $runs, 7);
                    }),

                Action::make('syncSquads')
                    ->label('Squads')
                    ->icon('heroicon-o-identification')
                    ->form([
                        Select::make('codes')->label('Championships')->multiple()
                            ->options(fn () => $leagueOptions('api_id'))->placeholder('All'),
                        Toggle::make('missing')->label('Only teams without a squad')->default(true),
                        TextInput::make('limit')->numeric()->default(40)
                            ->helperText('1 request per team, ~7 s apart: 40 teams ≈ 5 minutes. Stalest squads go first.'),
                    ])
                    ->action(function (array $data) {
                        $params = ['--limit' => (int) ($data['limit'] ?? 40), '--sleep' => 7];
                        if (! empty($data['codes'])) {
                            $params['codes'] = array_values($data['codes']);
                        }
                        if (! empty($data['missing'])) {
                            $params['--missing'] = true;
                        }
                        SyncRunner::background('Squads', [['sport:sync-squads', $params]]);
                    }),

                Action::make('syncTransfers')
                    ->label('Transfers')
                    ->icon('heroicon-o-arrows-right-left')
                    ->form([
                        Select::make('country')->label('Country')
                            ->options(fn () => SportCountry::query()->whereHas('teams')->orderBy('slug')->pluck('slug', 'slug')->all())
                            ->searchable()->placeholder('All countries'),
                        TextInput::make('limit')->numeric()->default(15)
                            ->helperText('API-Football free plan: 100 requests a day. Teams synced longest ago go first.'),
                    ])
                    ->action(function (array $data) {
                        $params = ['--limit' => (int) ($data['limit'] ?? 15)];
                        if (! empty($data['country'])) {
                            $params['--country'] = $data['country'];
                        }
                        SyncRunner::background('Transfers', [['sport:sync-transfers', $params]]);
                    }),
            ])
                ->label('More')
                ->icon('heroicon-m-ellipsis-vertical')
                ->button()
                ->color('gray'),
        ];
    }

    public function getWidgets(): array
    {
        return [
            EduzorroOverview::class,
            SyncFreshness::class,
            CompetitionFreshness::class,
            CountryFreshness::class,
            TeamFreshness::class,
            RecentSyncs::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
