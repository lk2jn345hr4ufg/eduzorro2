<?php

namespace App\Filament\Widgets;

use App\Filament\Support\Freshness;
use App\Models\Competition;
use App\Models\Fixture;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/** Per championship / tournament: when each kind of data was refreshed. */
class CompetitionFreshness extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Championships & tournaments — data dates';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Competition::query()
                    ->withCount(['teams' => fn ($q) => $q->where('is_active', true)])
                    ->withCount(['teams as squads_count' => fn ($q) => $q->where('is_active', true)->whereNotNull('squad_synced_at')])
                    ->withMax('teams', 'squad_synced_at')
                    ->withMax('teams', 'transfers_synced_at')
            )
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label('Competition')
                    ->state(fn (Competition $r) => trim($r->flag.' '.$r->translate('name')))
                    ->description(fn (Competition $r) => $r->code.' · '.($r->isLeague() ? 'championship' : 'tournament').($r->is_active ? '' : ' · inactive')),
                TextColumn::make('teams_count')->label('Teams')->alignCenter(),
                self::dateColumn('teams_synced_at', 'Teams import', 'teams'),
                self::dateColumn('matches_synced_at', 'Matches & table', 'matches'),
                TextColumn::make('upcoming')
                    ->label('Next 7 days')
                    ->state(fn (Competition $r) => Fixture::where('league_code', $r->code)->upcoming()
                        ->whereBetween('kickoff_at', [now(), now()->addDays(7)])->count())
                    ->alignCenter(),
                self::dateColumn('teams_max_squad_synced_at', 'Squads (latest)', 'squads')
                    ->description(fn (Competition $r) => $r->teams_count ? "{$r->squads_count}/{$r->teams_count} teams" : null),
                self::dateColumn('teams_max_transfers_synced_at', 'Transfers (latest)', 'transfers'),
                self::dateColumn('odds_synced_at', 'Odds', 'odds'),
            ]);
    }

    public static function dateColumn(string $name, string $label, string $type): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->since()
            ->dateTimeTooltip('d.m.Y H:i')
            ->placeholder('never')
            ->badge()
            ->color(fn ($state) => Freshness::color($type, $state))
            // Relationship paths ("league.x") can't be sorted on directly.
            ->sortable(! str_contains($name, '.'));
    }
}
