<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LanguageResource\Pages;
use App\Models\Language;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static ?string $navigationGroup = 'Directory';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('code')
                    ->required()
                    ->maxLength(10)
                    ->unique(ignoreRecord: true)
                    ->helperText('ISO 639-1 code, e.g. en, es'),

                Select::make('direction')
                    ->options(['ltr' => 'Left to right', 'rtl' => 'Right to left'])
                    ->default('ltr')
                    ->required(),

                TextInput::make('name')
                    ->label('English name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('e.g. Spanish'),

                TextInput::make('native_name')
                    ->label('Native name')
                    ->maxLength(255)
                    ->helperText('e.g. Español'),

                TextInput::make('sort_order')->numeric()->default(0),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive languages disappear from every switcher and hreflang tag.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('native_name')->searchable(),
                TextColumn::make('direction')->badge(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLanguages::route('/'),
            'create' => Pages\CreateLanguage::route('/create'),
            'edit'   => Pages\EditLanguage::route('/{record}/edit'),
        ];
    }
}
