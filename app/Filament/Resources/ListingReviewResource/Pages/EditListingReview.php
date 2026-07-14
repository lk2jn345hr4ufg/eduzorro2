<?php

namespace App\Filament\Resources\ListingReviewResource\Pages;

use App\Filament\Resources\ListingReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditListingReview extends EditRecord
{
    protected static string $resource = ListingReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
