<?php

namespace App\Filament\Widgets;

use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Site content at a glance. */
class EduzorroOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $teams      = Team::active()->whereNotNull('competition_id')->count();
        $withSquads = Team::active()->whereNotNull('competition_id')->whereNotNull('squad_synced_at')->count();
        $upcoming   = Fixture::upcoming()->whereBetween('kickoff_at', [now(), now()->addDays(7)]);
        $nextWeek   = (clone $upcoming)->count();
        $withOdds   = (clone $upcoming)->has('odd')->count();

        return [
            Stat::make('Championships / tournaments',
                Competition::active()->leagues()->count().' / '.Competition::active()->cups()->count())
                ->icon('heroicon-o-trophy')
                ->color('success')
                ->url(route('filament.admin.resources.competitions.index')),

            Stat::make('Teams', $teams)
                ->icon('heroicon-o-user-group')
                ->description("squads loaded: {$withSquads} / {$teams}")
                ->color($withSquads < $teams ? 'warning' : 'success')
                ->url(route('filament.admin.resources.teams.index')),

            Stat::make('Matches in the next 7 days', $nextWeek)
                ->icon('heroicon-o-calendar-days')
                ->description("with odds: {$withOdds}")
                ->color($nextWeek && ! $withOdds ? 'warning' : 'success'),
        ];
    }
}
