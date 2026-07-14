<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectResource\Pages;
use App\Models\Redirect;
use Filament\Forms\Components\Grid;
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

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-right';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Redirects';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('from_path')
                    ->label('From')
                    ->required()
                    ->placeholder('/old-page')
                    ->helperText('The incoming path visitors will hit. Root-relative, e.g. /old-region/en')
                    ->unique(ignoreRecord: true)
                    ->rules(['different:to_path'])
                    ->validationMessages(['different' => 'From and To can\'t be the same path — that would redirect a page to itself.'])
                    ->dehydrateStateUsing(fn (?string $state) => $state ? Redirect::normalizePath($state) : $state),

                TextInput::make('to_path')
                    ->label('To')
                    ->required()
                    ->placeholder('/global/en/language-learning or https://example.com/new-page')
                    ->helperText('Relative path or a full external URL.')
                    ->dehydrateStateUsing(fn (?string $state) => $state ? Redirect::normalizeTarget($state) : $state),

                Select::make('match_type')
                    ->options([
                        'exact'  => 'Exact match',
                        'prefix' => 'Prefix (matches anything under this path)',
                    ])
                    ->default('exact')
                    ->required()
                    ->helperText('Prefix rules append whatever comes after the matched path onto "To".'),

                Select::make('status_code')
                    ->options([
                        301 => '301 — Permanent',
                        302 => '302 — Temporary',
                        307 => '307 — Temporary (preserves method)',
                        308 => '308 — Permanent (preserves method)',
                    ])
                    ->default(301)
                    ->required(),

                Toggle::make('is_active')->default(true),

                TextInput::make('notes')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->helperText('Optional — why this redirect exists, e.g. "category renamed Jun 2026".'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_path')->searchable()->sortable(),
                TextColumn::make('to_path')->limit(50)->searchable(),
                TextColumn::make('match_type')->badge(),
                TextColumn::make('status_code')->badge()->sortable(),
                TextColumn::make('hits')->sortable(),
                TextColumn::make('last_hit_at')->dateTime('M j, Y')->sortable()->placeholder('never'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('match_type')->options(['exact' => 'Exact', 'prefix' => 'Prefix']),
                TernaryFilter::make('is_active'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit'   => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
