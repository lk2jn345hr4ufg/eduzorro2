<?php

namespace App\Filament\Widgets;

use App\Filament\Support\Freshness;
use App\Models\SyncLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** When each kind of data was last refreshed (any scope). */
class SyncFreshness extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Last data updates';

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $stats = [];

        foreach (SyncLog::TYPES as $type => $label) {
            $last = SyncLog::where('type', $type)->whereIn('status', ['ok', 'partial'])->latest('finished_at')->first();
            $fail = SyncLog::where('type', $type)->where('status', 'failed')
                ->where('finished_at', '>', $last?->finished_at ?? now()->subYears(10))
                ->latest('finished_at')->first();

            $stats[] = Stat::make($label, $last ? $last->finished_at->diffForHumans() : 'never')
                ->description($fail
                    ? 'last attempt failed: '.$fail->scope.' · '.$fail->finished_at->diffForHumans()
                    : ($last ? trim(($last->scope ?? '').' · '.$last->message, ' ·') : 'no runs logged yet'))
                ->descriptionIcon($fail ? 'heroicon-m-exclamation-triangle' : null)
                ->color($fail ? 'danger' : Freshness::color($type, $last?->finished_at));
        }

        return $stats;
    }
}
