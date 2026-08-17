<?php
namespace App\Filament\Resources\SportCountryResource\Pages;
use App\Filament\Resources\SportCountryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditSportCountry extends EditRecord
{
    protected static string $resource = SportCountryResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
