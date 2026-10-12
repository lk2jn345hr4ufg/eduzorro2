<?php

namespace App\Filament\Widgets;

use App\Models\SportCountry;
use App\Filament\Support\SyncRunner;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/** Per country: its championship and the freshness of its clubs' data. */
class CountryFreshness extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Countries — data dates';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SportCountry::query()
                    ->whereHas('teams', fn ($q) => $q->where('is_active', true))
                    ->with('league')
                    ->withCount(['teams' => fn ($q) => $q->where('is_active', true)])
                    ->withCount(['teams as squads_count' => fn ($q) => $q->where('is_active', true)->whereNotNull('squad_synced_at')])
                    ->withMax('teams', 'updated_at')
                    ->withMax('teams', 'squad_synced_at')
                    ->withMax('teams', 'transfers_synced_at')
                    ->withMin('teams', 'transfers_synced_at')
            )
            ->defaultSort('slug')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('country')
                    ->label('Country')
                    ->state(fn (SportCountry $r) => $r->translate('name') ?: $r->slug)
                    ->description(fn (SportCountry $r) => $r->league
                        ? trim($r->league->flag.' '.$r->league->translate('name').' ('.$r->league->code.')')
                        : 'no championship'),
                TextColumn::make('teams_count')->label('Teams')->alignCenter()->sortable(),
                CompetitionFreshness::dateColumn('league.teams_synced_at', 'Teams import', 'teams'),
                CompetitionFreshness::dateColumn('league.matches_synced_at', 'Matches & table', 'matches'),
                CompetitionFreshness::dateColumn('teams_max_squad_synced_at', 'Squads (latest)', 'squads')
                    ->description(fn (SportCountry $r) => "{$r->squads_count}/{$r->teams_count} teams"),
                CompetitionFreshness::dateColumn('teams_max_transfers_synced_at', 'Transfers (latest)', 'transfers'),
                CompetitionFreshness::dateColumn('teams_min_transfers_synced_at', 'Transfers (oldest team)', 'transfers'),
                CompetitionFreshness::dateColumn('league.odds_synced_at', 'Odds', 'odds'),
            ])
            ->actions([
                Action::make('transfers')->label('Transfers')->icon('heroicon-o-arrows-right-left')
                    ->button()->size('sm')->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Runs in the background: about 1 API-Football request per team (free plan: 100 a day).')
                    ->action(fn (SportCountry $r) => SyncRunner::background("Transfers — {$r->slug}",
                        [['sport:sync-transfers', ['--country' => $r->slug]]])),
            ]);
    }
}
