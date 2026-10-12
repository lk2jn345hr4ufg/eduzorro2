<?php

namespace App\Filament\Widgets;

use App\Models\Team;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/** Per team: squad, transfers and matches dates — stalest first. */
class TeamFreshness extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Teams — data dates';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Team::query()
                    ->where('is_active', true)
                    ->whereNotNull('competition_id')
                    ->with('competition')
                    ->withCount('players')
            )
            ->defaultSort('squad_synced_at')
            ->paginated([10, 25, 50, 100])
            ->searchable()
            ->columns([
                TextColumn::make('name.en')
                    ->label('Team')
                    ->searchable()
                    ->description(fn (Team $r) => $r->competition ? trim($r->competition->flag.' '.$r->competition->code) : null),
                TextColumn::make('players_count')->label('Players')->alignCenter()->sortable(),
                CompetitionFreshness::dateColumn('squad_synced_at', 'Squad', 'squads'),
                CompetitionFreshness::dateColumn('transfers_synced_at', 'Transfers', 'transfers'),
                CompetitionFreshness::dateColumn('competition.matches_synced_at', 'Matches & table', 'matches'),
                CompetitionFreshness::dateColumn('updated_at', 'Team record', 'teams'),
            ])
            ->filters([
                SelectFilter::make('competition_id')->label('Championship')
                    ->relationship('competition', 'code'),
                Filter::make('no_squad')->label('Without squad')
                    ->query(fn ($q) => $q->whereNull('squad_synced_at')),
                Filter::make('no_transfers')->label('Without transfers')
                    ->query(fn ($q) => $q->whereNull('transfers_synced_at')),
            ]);
    }
}
