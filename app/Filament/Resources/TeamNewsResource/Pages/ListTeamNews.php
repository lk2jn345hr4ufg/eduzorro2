<?php
namespace App\Filament\Resources\TeamNewsResource\Pages;
use App\Filament\Resources\TeamNewsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListTeamNews extends ListRecords
{
    protected static string $resource = TeamNewsResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
