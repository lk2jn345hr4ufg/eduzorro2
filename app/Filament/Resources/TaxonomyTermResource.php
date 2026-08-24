<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxonomyTermResource\Pages;
use App\Filament\Support\TranslatableTabs;
use App\Models\TaxonomyTerm;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The categories that actually drive the directory come from the WordPress
 * import and live in `taxonomy_terms`, not in `categories`. Without this
 * resource they were invisible in the admin — which is why only a handful of
 * categories showed up there, in English only.
 */
class TaxonomyTermResource extends Resource
{
    protected static ?string $model = TaxonomyTerm::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Directory';
    protected static ?string $navigationLabel = 'Imported categories';
    protected static ?string $modelLabel = 'imported category';
    protected static ?string $pluralModelLabel = 'imported categories';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                Select::make('taxonomy')
                    ->options(fn () => TaxonomyTerm::query()
                        ->select('taxonomy')->distinct()->orderBy('taxonomy')
                        ->pluck('taxonomy', 'taxonomy')->all())
                    ->searchable()
                    ->required()
                    ->helperText('Which vertical this term belongs to.'),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Used in URLs. Changing it breaks existing links.'),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Original imported name. Kept as the fallback and used to match re-imports — translate below instead of renaming.'),

                TextInput::make('parent_slug')
                    ->maxLength(255),
            ]),

            TranslatableTabs::make('name_i18n', 'Name translations'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (TaxonomyTerm $record) => $record->label())
                    ->description(fn (TaxonomyTerm $record) => $record->label() !== $record->name ? $record->name : null)
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('name_i18n', 'like', "%{$search}%")),

                TextColumn::make('taxonomy')->badge()->sortable(),
                TextColumn::make('slug')->searchable()->toggleable(),

                TextColumn::make('listings_count')
                    ->label('Listings')
                    ->counts('listings')
                    ->sortable(),

                TextColumn::make('translated')
                    ->label('Translated')
                    ->badge()
                    ->getStateUsing(fn (TaxonomyTerm $record) => collect($record->name_i18n ?? [])
                        ->filter()->keys()->map(fn ($c) => strtoupper($c))->implode(', ') ?: '—'),
            ])
            ->filters([
                SelectFilter::make('taxonomy')
                    ->options(fn () => TaxonomyTerm::query()
                        ->select('taxonomy')->distinct()->orderBy('taxonomy')
                        ->pluck('taxonomy', 'taxonomy')->all()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTaxonomyTerms::route('/'),
            'create' => Pages\CreateTaxonomyTerm::route('/create'),
            'edit'   => Pages\EditTaxonomyTerm::route('/{record}/edit'),
        ];
    }
}
