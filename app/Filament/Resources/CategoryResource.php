<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Support\TranslatableTabs;
use App\Models\Category;
use App\Models\Industry;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Directory';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                Select::make('industry_id')
                    ->label('Industry')
                    ->options(fn () => Industry::query()->orderBy('sort_order')->get()
                        ->mapWithKeys(fn (Industry $i) => [$i->id => $i->translate('name')]))
                    ->searchable()
                    ->required(),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Globally unique across all industries.'),

                TextInput::make('sort_order')->numeric()->default(0),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]),

            TranslatableTabs::make('name', 'Name'),
            TranslatableTabs::make('description', 'Description', textarea: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->getStateUsing(fn (Category $record) => $record->translate('name'))
                    // JSON column: search inside the translations blob so a
                    // Ukrainian/Russian name is findable too, not just English.
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")),
                TextColumn::make('industry.name')
                    ->label('Industry')
                    ->getStateUsing(fn (Category $record) => $record->industry?->translate('name')),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('companies_count')
                    ->label('Companies')
                    ->counts('companies')
                    ->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('industry_id')
                    ->label('Industry')
                    ->options(fn () => Industry::query()->orderBy('sort_order')->get()
                        ->mapWithKeys(fn (Industry $i) => [$i->id => $i->translate('name')])),
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
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
