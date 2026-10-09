<?php

namespace App\Filament\Pages;

use App\Models\Competition;
use App\Models\SportCountry;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class DataSync extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Football';
    protected static ?int $navigationSort = 9;
    protected static ?string $navigationLabel = 'Data sync';
    protected static ?string $title = 'Data sync';

    protected static string $view = 'filament.pages.data-sync';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncCompetitions')
                ->label('Sync championships (matches + tables)')
                ->icon('heroicon-o-trophy')
                ->color('success')
                ->form([
                    Select::make('codes')
                        ->label('Competitions')
                        ->multiple()
                        ->options(fn () => Competition::query()->whereNotNull('api_id')->orderBy('sort_order')
                            ->get()->mapWithKeys(fn ($c) => [$c->code => $c->code.' — '.$c->translate('name')])->all())
                        ->placeholder('All with a football-data id')
                        ->helperText('2 requests per competition; the free plan allows ~10 per minute.'),
                    TextInput::make('season')->numeric()->placeholder('current'),
                    TextInput::make('sleep')->numeric()->default(7)->helperText('Seconds between competitions.'),
                ])
                ->action(function (array $data) {
                    @set_time_limit(0);
                    $params = ['--sleep' => (int) ($data['sleep'] ?? 7)];
                    if (! empty($data['codes']))  { $params['codes'] = array_values($data['codes']); }
                    if (! empty($data['season'])) { $params['--season'] = (int) $data['season']; }

                    $code = Artisan::call('sport:sync-competition', $params);
                    $this->result('Championships sync', $code, Artisan::output());
                }),

            Action::make('syncSquads')
                ->label('Sync squads')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->form([
                    Select::make('codes')
                        ->label('Championships')
                        ->multiple()
                        ->options(fn () => Competition::query()->leagues()->orderBy('sort_order')
                            ->get()->mapWithKeys(fn ($c) => [$c->code => $c->code.' — '.$c->translate('name')])->all())
                        ->placeholder('All'),
                    Toggle::make('missing')->label('Only teams without a squad')->default(true),
                    TextInput::make('limit')->numeric()->default(20)
                        ->helperText('1 request per team, ~10 per minute: 20 teams ≈ 2.5 minutes.'),
                ])
                ->action(function (array $data) {
                    @set_time_limit(0);
                    $params = ['--limit' => (int) ($data['limit'] ?? 20), '--sleep' => 7];
                    if (! empty($data['codes']))   { $params['codes'] = array_values($data['codes']); }
                    if (! empty($data['missing'])) { $params['--missing'] = true; }

                    $code = Artisan::call('sport:sync-squads', $params);
                    $this->result('Squads sync', $code, Artisan::output());
                }),

            Action::make('syncOdds')
                ->label('Sync odds')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->form([
                    Select::make('provider')
                        ->options(['oddsapi' => 'The Odds API', 'apisports' => 'API-Football'])
                        ->default(fn () => config('odds.provider', 'oddsapi'))
                        ->native(false)->live(),
                    Select::make('codes')
                        ->label('Competitions')
                        ->multiple()
                        ->options(fn ($get) => Competition::query()
                            ->whereNotNull($get('provider') === 'apisports' ? 'apisports_league_id' : 'oddsapi_sport_key')
                            ->orderBy('sort_order')
                            ->get()->mapWithKeys(fn ($c) => [$c->code => $c->code.' — '.$c->translate('name')])->all())
                        ->placeholder('All with a key for this provider'),
                    TextInput::make('days')->numeric()->default(3)
                        ->helperText('Days ahead from today. The Odds API: 1 credit per competition that has matches in the window.'),
                    TextInput::make('season')->numeric()->placeholder('site season')
                        ->helperText('API-Football only.'),
                ])
                ->action(function (array $data) {
                    @set_time_limit(0);
                    $params = [
                        '--days'     => max(1, (int) ($data['days'] ?? 3)),
                        '--provider' => $data['provider'] ?? config('odds.provider', 'oddsapi'),
                    ];
                    if (! empty($data['codes']))  { $params['codes'] = array_values($data['codes']); }
                    if (! empty($data['season'])) { $params['--season'] = (int) $data['season']; }

                    $code = Artisan::call('sport:sync-odds', $params);
                    $this->result('Odds sync', $code, Artisan::output());
                }),

            Action::make('syncFootball')
                ->label('Import teams & countries')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('primary')
                ->form([
                    TextInput::make('season')
                        ->numeric()
                        ->placeholder((string) config('football.season'))
                        ->helperText('Leave empty to use the configured season.'),
                    Toggle::make('create_countries')
                        ->label('Create missing countries')
                        ->helperText('Off (default): only import teams for countries you already created. On: auto-create any new country found.'),
                ])
                ->action(function (array $data) {
                    @set_time_limit(0);
                    $params = [];
                    if (! empty($data['season'])) {
                        $params['--season'] = (int) $data['season'];
                    }
                    if (! empty($data['create_countries'])) {
                        $params['--create-countries'] = true;
                    }
                    $code = Artisan::call('sport:sync-football', $params);
                    $this->result('API-Football import', $code, Artisan::output());
                }),

            Action::make('syncStats')
                ->label('Sync fixtures, standings & transfers')
                ->icon('heroicon-o-table-cells')
                ->color('primary')
                ->form([
                    Select::make('type')
                        ->label('What to sync')
                        ->options([
                            'all'       => 'Everything',
                            'fixtures'  => 'Fixtures only',
                            'standings' => 'Standings only',
                            'transfers' => 'Transfers only',
                        ])
                        ->default('all')
                        ->native(false),
                    Select::make('country')
                        ->label('Country')
                        ->options(fn () => SportCountry::query()
                            ->orderBy('slug')
                            ->pluck('slug', 'slug')
                            ->all())
                        ->searchable()
                        ->native(false)
                        ->placeholder('All countries'),
                    TextInput::make('team')->label('Team slug')->placeholder('all teams (leave empty)'),
                    TextInput::make('season')
                        ->numeric()
                        ->placeholder((string) config('football.season'))
                        ->helperText('Leave empty to use the configured season.'),
                    TextInput::make('limit')->numeric()->default(20)
                        ->helperText('Max teams this run (0 = all). Each team costs 1-2 API calls.'),
                    TextInput::make('sleep')->numeric()->default(0)
                        ->helperText('Seconds to wait between teams.'),
                ])
                ->action(function (array $data) {
                    @set_time_limit(0);
                    $params = [
                        '--type'  => $data['type'] ?? 'all',
                        '--limit' => (int) ($data['limit'] ?? 0),
                        '--sleep' => (int) ($data['sleep'] ?? 0),
                    ];
                    if (! empty($data['team'])) {
                        $params['--team'] = $data['team'];
                    }
                    if (! empty($data['country'])) {
                        $params['--country'] = $data['country'];
                    }
                    if (! empty($data['season'])) {
                        $params['--season'] = (int) $data['season'];
                    }
                    $code = Artisan::call('sport:sync-stats', $params);
                    $this->result('Fixtures / standings / transfers sync', $code, Artisan::output());
                }),

            Action::make('syncTransfers')
                ->label('Sync transfers (api-sports)')
                ->icon('heroicon-o-arrows-right-left')
                ->color('primary')
                ->form([
                    Select::make('country')
                        ->label('Country')
                        ->options(fn () => SportCountry::query()
                            ->orderBy('slug')->pluck('slug', 'slug')->all())
                        ->searchable()->native(false)
                        ->placeholder('All countries'),
                    TextInput::make('team')->label('Team slug')->placeholder('all teams (leave empty)'),
                    TextInput::make('limit')->numeric()->default(15)
                        ->helperText('Max teams this run. First run also spends one lookup call per team.'),
                    TextInput::make('sleep')->numeric()->default(1)
                        ->helperText('Seconds between teams.'),
                    Toggle::make('relink')
                        ->label('Re-resolve api-sports ids')
                        ->helperText('Only needed if a team got linked to the wrong club.'),
                ])
                ->action(function (array $data) {
                    @set_time_limit(0);
                    $params = [
                        '--limit' => (int) ($data['limit'] ?? 0),
                        '--sleep' => (int) ($data['sleep'] ?? 0),
                    ];
                    if (! empty($data['team']))     { $params['--team'] = $data['team']; }
                    if (! empty($data['country']))  { $params['--country'] = $data['country']; }
                    if (! empty($data['relink']))   { $params['--relink'] = true; }

                    $code = Artisan::call('sport:sync-transfers', $params);
                    $this->result('Transfers sync', $code, Artisan::output());
                }),

            Action::make('syncNews')
                ->label('Sync team news')
                ->icon('heroicon-o-newspaper')
                ->color('primary')
                ->form([
                    Select::make('country')
                        ->label('Country')
                        ->options(fn () => SportCountry::query()
                            ->orderBy('slug')
                            ->pluck('slug', 'slug')
                            ->all())
                        ->searchable()
                        ->native(false)
                        ->placeholder('All countries')
                        ->helperText('Limit the sync to the teams of one country.'),
                    TextInput::make('team')->label('Team slug')->placeholder('all teams (leave empty)'),
                    TextInput::make('limit')->numeric()->default(25)
                        ->helperText('Max teams this run (0 = all). Keep small to respect news API limits.'),
                    TextInput::make('sleep')->numeric()->default(0)
                        ->helperText('Seconds to wait between teams.'),
                ])
                ->action(function (array $data) {
                    @set_time_limit(0);
                    $params = [
                        '--limit' => (int) ($data['limit'] ?? 0),
                        '--sleep' => (int) ($data['sleep'] ?? 0),
                    ];
                    if (! empty($data['team'])) {
                        $params['--team'] = $data['team'];
                    }
                    if (! empty($data['country'])) {
                        $params['--country'] = $data['country'];
                    }
                    $code = Artisan::call('sport:sync-news', $params);
                    $this->result('News sync', $code, Artisan::output());
                }),

            Action::make('rewriteNews')
                ->label('Rewrite & translate news (Gemini)')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->form([
                    Select::make('country')
                        ->label('Country')
                        ->options(fn () => SportCountry::query()
                            ->orderBy('slug')
                            ->pluck('slug', 'slug')
                            ->all())
                        ->searchable()
                        ->native(false)
                        ->placeholder('All countries'),
                    TextInput::make('team')->label('Team slug')->placeholder('all teams (leave empty)'),
                    TextInput::make('limit')->numeric()->default(20)
                        ->helperText('Max news rows this run. Each row = one Gemini call.'),
                    TextInput::make('sleep')->numeric()->default(0)
                        ->helperText('Seconds between articles.'),
                ])
                ->action(function (array $data) {
                    @set_time_limit(0);
                    $params = [
                        '--limit' => (int) ($data['limit'] ?? 0),
                        '--sleep' => (int) ($data['sleep'] ?? 0),
                    ];
                    if (! empty($data['team'])) {
                        $params['--team'] = $data['team'];
                    }
                    if (! empty($data['country'])) {
                        $params['--country'] = $data['country'];
                    }
                    $code = Artisan::call('sport:rewrite-news', $params);
                    $this->result('Gemini rewrite', $code, Artisan::output());
                }),

            Action::make('clearCache')
                ->label('Clear cached live data')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Clears the application cache, including cached fixtures, standings and transfers. They are refetched on next view.')
                ->action(function () {
                    Artisan::call('cache:clear');
                    \Illuminate\Support\Facades\Cache::forget('nav_competitions');
                    Notification::make()->title('Cache cleared')->success()->send();
                }),
        ];
    }

    protected function result(string $title, int $code, string $output): void
    {
        Notification::make()
            ->title($title)
            ->body(Str::limit(trim($output), 600) ?: 'Done.')
            ->{$code === 0 ? 'success' : 'danger'}()
            ->persistent()
            ->send();
    }
}
