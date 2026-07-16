<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingReviewResource\Pages;
use App\Models\ListingReview;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ListingReviewResource extends Resource
{
    protected static ?string $model = ListingReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Moderation';
    protected static ?string $navigationLabel = 'Listing Reviews';
    protected static ?int $navigationSort = 2;

    /** Sidebar badge: how many reviews are waiting for approval. */
    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('is_approved', false)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                Select::make('listing_id')->relationship('listing', 'name')->searchable()->required(),
                Select::make('rating')->options(array_combine(range(1, 5), range(1, 5)))->required(),
                TextInput::make('author_name')->required()->maxLength(255),
                TextInput::make('author_email')->email()->maxLength(255),
                Toggle::make('is_approved')->columnSpanFull(),
            ]),
            Textarea::make('body')->required()->rows(5)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('listing.name')->label('Listing')->searchable()->sortable(),
                TextColumn::make('author_name')->label('Author')->searchable(),
                TextColumn::make('rating')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->sortable(),
                IconColumn::make('is_approved')->boolean()->sortable(),
                TextColumn::make('created_at')->dateTime('M j, Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_approved')->placeholder('All')->trueLabel('Approved')->falseLabel('Pending'),
            ])
            ->actions([
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (ListingReview $record) => ! $record->is_approved)
                    ->action(function (ListingReview $record) {
                        $record->update(['is_approved' => true]);
                        Notification::make()->title('Review approved')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Unapprove')
                    ->icon('heroicon-o-x-circle')->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ListingReview $record) => $record->is_approved)
                    ->action(function (ListingReview $record) {
                        $record->update(['is_approved' => false]);
                        Notification::make()->title('Review unapproved')->warning()->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Approve selected')->icon('heroicon-o-check-circle')->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('rejectSelected')
                        ->label('Unapprove selected')->icon('heroicon-o-x-circle')->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListingReviews::route('/'),
            'edit' => Pages\EditListingReview::route('/{record}/edit'),
        ];
    }
}
