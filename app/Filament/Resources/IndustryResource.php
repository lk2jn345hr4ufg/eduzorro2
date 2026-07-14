<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IndustryResource\Pages;
use App\Filament\Support\TranslatableTabs;
use App\Models\Industry;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IndustryResource extends Resource
{
    protected static ?string $model = Industry::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Directory';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('icon')
                    ->maxLength(100)
                    ->helperText('Optional icon identifier for future use.'),

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
                    ->getStateUsing(fn (Industry $record) => $record->translate('name')),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('categories_count')
                    ->label('Categories')
                    ->counts('categories')
                    ->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIndustries::route('/'),
            'create' => Pages\CreateIndustry::route('/create'),
            'edit'   => Pages\EditIndustry::route('/{record}/edit'),
        ];
    }
}
