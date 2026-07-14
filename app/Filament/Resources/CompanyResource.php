<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Support\TranslatableTabs;
use App\Models\Category;
use App\Models\Company;
use App\Models\Region;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Directory';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Basics')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Proper noun — not translated.'),

                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Select::make('category_id')
                        ->label('Category')
                        ->options(fn () => Category::query()->orderBy('sort_order')->get()
                            ->mapWithKeys(fn (Category $c) => [$c->id => $c->translate('name')]))
                        ->searchable()
                        ->required(),

                    Select::make('type')
                        ->options(['local' => 'Local', 'digital' => 'Digital'])
                        ->default('local')
                        ->required()
                        ->helperText('Digital companies are attached to every active region automatically below.'),

                    Select::make('regions')
                        ->relationship('regions', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Region $region) => $region->translate('name') . ' (' . $region->slug . ')')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                ]),
            ]),

            Section::make('Contact & location')->schema([
                Grid::make(2)->schema([
                    TextInput::make('address')->maxLength(255)->columnSpanFull(),
                    TextInput::make('latitude')->numeric(),
                    TextInput::make('longitude')->numeric(),
                    TextInput::make('email')->email()->maxLength(255),
                    TextInput::make('phone')->tel()->maxLength(50),
                    TextInput::make('website')->url()->maxLength(255),
                    TextInput::make('logo')->url()->maxLength(255)->helperText('URL to a logo image.'),
                ]),
            ]),

            Section::make('Status')->schema([
                Grid::make(2)->schema([
                    Toggle::make('is_verified')->label('Verified')->default(false),
                    Toggle::make('is_active')->label('Active')->default(true),
                ]),
            ]),

            TranslatableTabs::make('description', 'Description', textarea: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->getStateUsing(fn (Company $record) => $record->category?->translate('name')),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('regions_count')
                    ->label('Regions')
                    ->counts('regions')
                    ->sortable(),
                TextColumn::make('reviews_count')
                    ->label('Reviews')
                    ->counts('reviews')
                    ->sortable(),
                IconColumn::make('is_verified')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(['local' => 'Local', 'digital' => 'Digital']),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::query()->orderBy('sort_order')->get()
                        ->mapWithKeys(fn (Category $c) => [$c->id => $c->translate('name')])),
                TernaryFilter::make('is_verified'),
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit'   => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
