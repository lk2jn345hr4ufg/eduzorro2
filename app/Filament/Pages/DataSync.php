<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class DataSync extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Sport';
    protected static ?int $navigationSort = 9;
    protected static ?string $navigationLabel = 'Data sync';
    protected static ?string $title = 'Data sync';

    protected static string $view = 'filament.pages.data-sync';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFootball')
                ->label('Import teams & countries')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('primary')
                ->form([
                    TextInput::make('season')
                        ->numeric()
                        ->placeholder((string) config('football.season'))
                        ->helperText('Leave empty to use the configured season.'),
                ])
                ->action(function (array $data) {
                    @set_time_limit(0);
                    $params = [];
                    if (! empty($data['season'])) {
                        $params['--season'] = (int) $data['season'];
                    }
                    $code = Artisan::call('sport:sync-football', $params);
                    $this->result('API-Football import', $code, Artisan::output());
                }),

            Action::make('syncNews')
                ->label('Sync team news')
                ->icon('heroicon-o-newspaper')
                ->color('primary')
                ->form([
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
                    $code = Artisan::call('sport:sync-news', $params);
                    $this->result('News sync', $code, Artisan::output());
                }),

            Action::make('rewriteNews')
                ->label('Rewrite & translate news (Gemini)')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->form([
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
