<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Region;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function afterSave(): void
    {
        if ($this->record->type === 'digital') {
            $this->record->regions()->syncWithoutDetaching(Region::active()->pluck('id'));
        }
    }
}
