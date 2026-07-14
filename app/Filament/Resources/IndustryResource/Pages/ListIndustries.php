<?php

namespace App\Filament\Resources\IndustryResource\Pages;

use App\Filament\Resources\IndustryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIndustries extends ListRecords
{
    protected static string $resource = IndustryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
