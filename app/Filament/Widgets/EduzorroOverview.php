<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Region;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EduzorroOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $pending = Review::where('is_approved', false)->count();

        return [
            Stat::make('Active companies', Company::where('is_active', true)->count())
                ->icon('heroicon-o-building-storefront')
                ->color('success'),

            Stat::make('Active regions', Region::where('is_active', true)->count())
                ->icon('heroicon-o-globe-alt')
                ->color('info'),

            Stat::make('Pending reviews', $pending)
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color($pending > 0 ? 'warning' : 'success')
                ->description($pending > 0 ? 'Waiting for moderation' : 'Queue is clear')
                ->url(route('filament.admin.resources.reviews.index')),
        ];
    }
}
