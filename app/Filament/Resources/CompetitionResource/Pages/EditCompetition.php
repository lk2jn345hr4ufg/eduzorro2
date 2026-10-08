<?php

namespace App\Filament\Resources\CompetitionResource\Pages;

use App\Filament\Resources\CompetitionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompetition extends EditRecord
{
    protected static string $resource = CompetitionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->after(fn () => CompetitionResource::forgetNavCache())];
    }

    protected function afterSave(): void
    {
        CompetitionResource::forgetNavCache();
    }
}
