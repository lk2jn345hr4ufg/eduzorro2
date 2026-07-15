<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Http\Controllers\DirectoryController;
use App\Models\Listing;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'WordPress Import';
    protected static ?int $navigationSort = 1;

    const VERTICALS = [
        'course' => 'Course',
        'online_course' => 'Online course',
        'university' => 'University',
        'online_business' => 'Online business',
        'affiliate_network' => 'Affiliate network',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->maxLength(255),

                Select::make('vertical')->options(self::VERTICALS)->required(),

                TextInput::make('specialization')->maxLength(255),

                TextInput::make('editorial_rating')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(5)
                    ->step(0.1)
                    ->helperText('The site editor\'s own 0-5 score — separate from real user reviews below.'),

                TextInput::make('year_of_founded')->maxLength(20),
                TextInput::make('website')->url()->maxLength(255),
                TextInput::make('logo_url')->url()->maxLength(255),

                Toggle::make('is_active')->default(true),
            ]),

            TextInput::make('description_title')
                ->maxLength(255)
                ->columnSpanFull()
                ->helperText('Short heading shown above the description. Inline HTML (<strong>, <em>, <a>) is rendered on the site.'),

            RichEditor::make('description')
                ->columnSpanFull()
                ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList', 'h2', 'h3', 'blockquote', 'undo', 'redo']),

            RichEditor::make('details_description')
                ->columnSpanFull()
                ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList', 'h2', 'h3', 'blockquote', 'undo', 'redo'])
                ->helperText('Long-form writeup (mainly used on affiliate-network listings).'),

            Textarea::make('contacts_text')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('region.languages'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('vertical')->badge()->sortable(),
                IconColumn::make('description')
                    ->label('Description')
                    ->boolean()
                    ->getStateUsing(fn (Listing $record) => filled($record->description))
                    // Sortable by emptiness: ascending puts empty-description
                    // rows first, descending puts filled ones first.
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw(
                        "(description IS NOT NULL AND description != '') {$direction}"
                    )),
                TextColumn::make('editorial_rating')->label('Editorial')->sortable(),
                TextColumn::make('reviews_count')->label('Reviews')->counts('reviews')->sortable(),
                TextColumn::make('reviews_avg_rating')
                    ->label('Avg rating')
                    ->avg('reviews', 'rating')
                    ->numeric(1)
                    ->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('vertical')->options(self::VERTICALS),
                TernaryFilter::make('description')
                    ->label('Description')
                    ->placeholder('All listings')
                    ->trueLabel('Has description')
                    ->falseLabel('Empty description')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('description')->where('description', '!=', ''),
                        false: fn (Builder $query) => $query->where(fn (Builder $q) => $q->whereNull('description')->orWhere('description', '')),
                    ),
                TernaryFilter::make('is_active'),
            ])
            ->actions([
                Action::make('viewOnSite')
                    ->label('View on site')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Listing $record) => static::publicUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (Listing $record) => static::publicUrl($record) !== null),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    /** Public profile URL for a listing, or null if it isn't routable (no region). */
    public static function publicUrl(Listing $listing): ?string
    {
        if (! $listing->region) {
            return null;
        }

        $urlSlug = array_flip(array_map(fn ($v) => $v[0], DirectoryController::VERTICALS))[$listing->vertical] ?? null;
        $language = $listing->region->languages->first()?->code ?? 'ru';

        return $urlSlug
            ? route('directory.show', [$listing->region, $language, $urlSlug, $listing])
            : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListings::route('/'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}
