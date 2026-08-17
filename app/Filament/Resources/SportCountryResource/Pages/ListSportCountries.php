<?php
namespace App\Filament\Resources\SportCountryResource\Pages;
use App\Filament\Resources\SportCountryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListSportCountries extends ListRecords
{
    protected static string $resource = SportCountryResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
