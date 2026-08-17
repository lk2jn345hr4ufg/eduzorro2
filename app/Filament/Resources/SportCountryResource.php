<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SportCountryResource\Pages;
use App\Models\SportCountry;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SportCountryResource extends Resource
{
    protected static ?string $model = SportCountry::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Sport';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Countries';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('sport_id')->relationship('sport', 'slug')->required(),
            TextInput::make('slug')->required(),
            Fieldset::make('Name')->schema([
                TextInput::make('name.en')->label('EN')->required(),
                TextInput::make('name.uk')->label('UK'),
                TextInput::make('name.ru')->label('RU'),
                TextInput::make('name.es')->label('ES'),
            ])->columns(2),
            TextInput::make('api_name')->helperText('API-Football country name, e.g. England'),
            TextInput::make('flag_url')->url(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name.en')->label('Name')->searchable(),
                TextColumn::make('sport.slug')->label('Sport'),
                TextColumn::make('teams_count')->counts('teams')->label('Teams'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSportCountries::route('/'),
            'create' => Pages\CreateSportCountry::route('/create'),
            'edit'   => Pages\EditSportCountry::route('/{record}/edit'),
        ];
    }
}
