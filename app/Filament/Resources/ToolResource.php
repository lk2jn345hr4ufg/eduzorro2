<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ToolResource\Pages;
use App\Models\Tool;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ToolResource extends Resource
{
    protected static ?string $model = Tool::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Tools';
    protected static ?string $navigationLabel = 'Study tools';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Must match a Blade view at resources/views/tools/partials/{slug}.blade.php'),
            Select::make('category')
                ->options([
                    'grades'   => 'Grades & scores',
                    'tests'    => 'Tests',
                    'planning' => 'Planning',
                    'math'     => 'Mathematics',
                    'other'    => 'Other',
                ])
                ->default('grades')
                ->native(false),
            Fieldset::make('Name')->schema([
                TextInput::make('name.en')->label('EN')->required(),
                TextInput::make('name.uk')->label('UK'),
                TextInput::make('name.ru')->label('RU'),
                TextInput::make('name.es')->label('ES'),
            ])->columns(2),
            Fieldset::make('Description')->schema([
                Textarea::make('description.en')->label('EN')->rows(2),
                Textarea::make('description.uk')->label('UK')->rows(2),
                Textarea::make('description.ru')->label('RU')->rows(2),
                Textarea::make('description.es')->label('ES')->rows(2),
            ])->columns(2),
            Fieldset::make('Intro (shown above the tool)')->schema([
                Textarea::make('intro.en')->label('EN')->rows(3),
                Textarea::make('intro.uk')->label('UK')->rows(3),
                Textarea::make('intro.ru')->label('RU')->rows(3),
                Textarea::make('intro.es')->label('ES')->rows(3),
            ])->columns(2),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name.en')->label('Name')->searchable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('category')->badge(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTools::route('/'),
            'create' => Pages\CreateTool::route('/create'),
            'edit'   => Pages\EditTool::route('/{record}/edit'),
        ];
    }
}
