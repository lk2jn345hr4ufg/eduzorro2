<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CompetitionFreshness;
use App\Filament\Widgets\CountryFreshness;
use App\Filament\Widgets\EduzorroOverview;
use App\Filament\Widgets\RecentSyncs;
use App\Filament\Widgets\SyncFreshness;
use App\Filament\Widgets\TeamFreshness;
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
