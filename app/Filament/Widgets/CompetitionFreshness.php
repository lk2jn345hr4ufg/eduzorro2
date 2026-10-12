<?php

namespace App\Filament\Widgets;

use App\Filament\Support\Freshness;
use App\Models\Competition;
use App\Models\Fixture;
use App\Filament\Support\SyncRunner;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
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
                    ->with('country')
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
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('matches')->label('Sync matches & table')->icon('heroicon-o-table-cells')
                        ->visible(fn (Competition $r) => (bool) $r->api_id)
                        ->action(fn (Competition $r) => SyncRunner::now("Matches & table — {$r->code}",
                            'sport:sync-competition', ['codes' => [$r->code], '--sleep' => 0])),
                    Action::make('teams')->label('Import teams (+ squads)')->icon('heroicon-o-user-group')
                        ->visible(fn (Competition $r) => $r->isLeague() && $r->api_id)
                        ->action(fn (Competition $r) => SyncRunner::now("Teams — {$r->code}",
                            'sport:sync-football', ['--competition' => $r->code, '--sleep' => 0])),
                    Action::make('squads')->label('Load missing squads')->icon('heroicon-o-identification')
                        ->visible(fn (Competition $r) => $r->isLeague() && $r->api_id)
                        ->action(fn (Competition $r) => SyncRunner::background("Squads — {$r->code}",
                            [['sport:sync-squads', ['codes' => [$r->code], '--missing' => true, '--sleep' => 7]]])),
                    Action::make('transfers')->label('Sync transfers')->icon('heroicon-o-arrows-right-left')
                        ->visible(fn (Competition $r) => $r->isLeague() && $r->country)
                        ->action(fn (Competition $r) => SyncRunner::background("Transfers — {$r->code}",
                            [['sport:sync-transfers', ['--country' => $r->country->slug]]])),
                    Action::make('odds')->label('Sync odds (3 days)')->icon('heroicon-o-currency-dollar')
                        ->visible(fn (Competition $r) => (bool) ($r->oddsapi_sport_key || $r->apisports_league_id))
                        ->action(fn (Competition $r) => SyncRunner::now("Odds — {$r->code}",
                            'sport:sync-odds', ['codes' => [$r->code], '--days' => 3])),
                ])->label('Sync')->icon('heroicon-o-arrow-path')->button()->size('sm')->color('gray'),
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
