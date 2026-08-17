<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Sport';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('sport_id')->relationship('sport', 'slug')->required(),
            Select::make('sport_country_id')->relationship('country', 'slug')->searchable()->required(),
            TextInput::make('slug')->required(),
            Fieldset::make('Name')->schema([
                TextInput::make('name.en')->label('EN')->required(),
                TextInput::make('name.uk')->label('UK'),
                TextInput::make('name.ru')->label('RU'),
                TextInput::make('name.es')->label('ES'),
            ])->columns(2),
            TextInput::make('api_id')->numeric()->helperText('API-Football team id (drives fixtures/standings/transfers).'),
            TextInput::make('primary_league_api_id')->numeric()->helperText('Domestic league id for the standings tab.'),
            TextInput::make('short_name'),
            TextInput::make('logo_url')->url(),
            TextInput::make('founded')->numeric(),
            TextInput::make('stadium'),
            TextInput::make('city'),
            TextInput::make('website')->url(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')->label('')->circular(),
                TextColumn::make('name.en')->label('Name')->searchable(),
                TextColumn::make('country.slug')->label('Country')->searchable(),
                TextColumn::make('api_id')->label('API id'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('sport_country_id')->relationship('country', 'slug')->label('Country'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('fetchNews')
                    ->label('Fetch news')
                    ->icon('heroicon-o-newspaper')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Pull the latest news for this team from the news API.')
                    ->action(function (Team $record) {
                        @set_time_limit(0);
                        $code = Artisan::call('sport:sync-news', ['--team' => $record->slug]);
                        $out  = trim(Artisan::output());
                        Notification::make()
                            ->title('News sync — '.$record->slug)
                            ->body(\Illuminate\Support\Str::limit($out, 400) ?: 'Done.')
                            ->{$code === 0 ? 'success' : 'danger'}()
                            ->send();
                    }),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit'   => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
