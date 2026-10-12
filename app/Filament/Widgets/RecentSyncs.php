<?php

namespace App\Filament\Widgets;

use App\Models\SyncLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/** Log of sync runs: what was updated, for which scope, and how it went. */
class RecentSyncs extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Sync log';

    public function table(Table $table): Table
    {
        return $table
            ->query(SyncLog::query())
            ->defaultSort('finished_at', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('finished_at')->label('When')->since()->dateTimeTooltip('d.m.Y H:i')->sortable(),
                TextColumn::make('type')->label('Data')->badge()
                    ->formatStateUsing(fn ($state) => SyncLog::TYPES[$state] ?? $state),
                TextColumn::make('scope')->label('Scope')->placeholder('all'),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'ok'      => 'success',
                    'partial' => 'warning',
                    'failed'  => 'danger',
                    default   => 'gray',
                }),
                TextColumn::make('items')->label('Items')->alignCenter(),
                TextColumn::make('message')->label('Details')->wrap()->limit(120),
                TextColumn::make('duration')->label('Took')
                    ->state(fn (SyncLog $r) => $r->started_at && $r->finished_at
                        ? $r->started_at->diffForHumans($r->finished_at, true, true)
                        : null),
            ])
            ->filters([
                SelectFilter::make('type')->options(SyncLog::TYPES),
                SelectFilter::make('status')->options(['ok' => 'ok', 'partial' => 'partial', 'failed' => 'failed', 'skipped' => 'skipped']),
            ]);
    }
}
