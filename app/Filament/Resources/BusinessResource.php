<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessResource\Pages;
use App\Models\Business;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'WordPress Import';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Businesses (registry)';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('slug')->required()->maxLength(255),
                TextInput::make('edrpou')->label('EDRPOU')->maxLength(50),
                TextInput::make('address')->maxLength(255)->columnSpanFull(),
                TextInput::make('phones')->maxLength(255),
                TextInput::make('email')->email()->maxLength(255),
                TextInput::make('website')->url()->maxLength(255),
                TextInput::make('director')->maxLength(255),
                TextInput::make('kved_codes')->label('KVED codes')->maxLength(255),
                TextInput::make('latitude')->numeric(),
                TextInput::make('longitude')->numeric(),
                Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->limit(50),
                TextColumn::make('edrpou')->label('EDRPOU')->searchable(),
                TextColumn::make('address')->limit(40)->searchable(),
                TextColumn::make('kved_codes')->label('KVED'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
        ];
    }
}
