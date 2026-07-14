<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Region;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    /**
     * Digital companies should reach every active region even if the
     * admin didn't hand-pick them all in the regions select.
     */
    protected function afterCreate(): void
    {
        if ($this->record->type === 'digital') {
            $this->record->regions()->syncWithoutDetaching(Region::active()->pluck('id'));
        }
    }
}
