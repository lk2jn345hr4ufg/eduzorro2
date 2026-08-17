<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SportResource\Pages;
use App\Models\Sport;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SportResource extends Resource
{
    protected static ?string $model = Sport::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Sport';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Fieldset::make('Name')->schema([
                TextInput::make('name.en')->label('EN')->required(),
                TextInput::make('name.uk')->label('UK'),
                TextInput::make('name.ru')->label('RU'),
                TextInput::make('name.es')->label('ES'),
            ])->columns(2),
            TextInput::make('icon'),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('has_competitions')->helperText('Enable deep hierarchy (only football today).'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name.en')->label('Name')->searchable(),
                TextColumn::make('slug')->searchable(),
                IconColumn::make('has_competitions')->boolean(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSports::route('/'),
            'create' => Pages\CreateSport::route('/create'),
            'edit'   => Pages\EditSport::route('/{record}/edit'),
        ];
    }
}
