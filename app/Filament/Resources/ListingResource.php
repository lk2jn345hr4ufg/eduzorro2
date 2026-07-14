<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Models\Listing;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

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

            TextInput::make('description_title')->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->rows(4)->columnSpanFull(),
            Textarea::make('details_description')->rows(4)->columnSpanFull()
                ->helperText('Long-form writeup (mainly used on affiliate-network listings).'),
            Textarea::make('contacts_text')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('vertical')->badge()->sortable(),
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
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListings::route('/'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}
