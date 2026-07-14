<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegionResource\Pages;
use App\Filament\Support\TranslatableTabs;
use App\Models\Region;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Directory';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Used in URLs, e.g. /united-states/en'),

                TextInput::make('code')
                    ->maxLength(50)
                    ->helperText('ISO country/region code shown in the header stamp, e.g. US'),

                Select::make('parent_id')
                    ->label('Parent region')
                    ->relationship('parent', 'slug')
                    ->searchable()
                    ->preload(),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                TextInput::make('latitude')->numeric(),
                TextInput::make('longitude')->numeric(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive regions return 404 and are hidden everywhere.'),

                Select::make('languages')
                    ->relationship('languages', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($language) => ($language->native_name ?: $language->name) . ' (' . $language->code . ')')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Which languages this region actually offers — e.g. Kazakhstan is Russian-only.')
                    ->columnSpanFull(),
            ]),

            TranslatableTabs::make('name', 'Name'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (Region $record) => $record->translate('name'))
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('code')->badge(),
                TextColumn::make('companies_count')
                    ->label('Companies')
                    ->counts('companies')
                    ->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([])
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit'   => Pages\EditRegion::route('/{record}/edit'),
        ];
    }
}
