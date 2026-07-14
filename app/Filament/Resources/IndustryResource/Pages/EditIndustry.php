<?php

namespace App\Filament\Resources\IndustryResource\Pages;

use App\Filament\Resources\IndustryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIndustry extends EditRecord
{
    protected static string $resource = IndustryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
