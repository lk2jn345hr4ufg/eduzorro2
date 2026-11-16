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

            $this->autoSyncStat(),

            Stat::make('Matches in the next 7 days', $nextWeek)
                ->icon('heroicon-o-calendar-days')
                ->description("with odds: {$withOdds}")
                ->color($nextWeek && ! $withOdds ? 'warning' : 'success'),
        ];
    }

    /** Which competition the 15-minute rotation refreshes next, and when. */
    protected function autoSyncStat(): Stat
    {
        if (! config('football.auto_sync.enabled')) {
            return Stat::make('Auto sync', 'off')
                ->description('Settings → Auto sync')
                ->icon('heroicon-o-pause-circle')
                ->color('gray');
        }

        $due  = \App\Console\Commands\AutoSync::nextDue();
        $next = $due ?? \App\Console\Commands\AutoSync::queue()->first();

        // Next possible run: 15 minutes after the previous one (or now).
        $last = Competition::max('auto_synced_at');
        $tick = $last
            ? \Illuminate\Support\Carbon::parse($last)->addMinutes((int) config('football.auto_sync.gap_minutes', 15))->max(now())
            : now();
        $when = $due ? $tick : ($next?->auto_synced_at?->copy()->addHours((int) config('football.auto_sync.every_hours', 24))->max($tick));

        return Stat::make('Auto sync — next', $next ? trim($next->flag.' '.$next->code) : '—')
            ->description($next ? 'at '.$when?->format('H:i').' ('.$when?->diffForHumans().')' : 'no competition with a data source')
            ->icon('heroicon-o-arrow-path')
            ->color('success');
    }
}
