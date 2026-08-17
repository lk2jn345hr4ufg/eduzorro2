<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamNewsResource\Pages;
use App\Models\Language;
use App\Models\TeamNews;
use App\Services\AI\GeminiClient;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeamNewsResource extends Resource
{
    protected static ?string $model = TeamNews::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Sport';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Team news';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('team_id')->relationship('team', 'slug')->searchable()->required(),
            TextInput::make('slug')->required(),
            Fieldset::make('Title')->schema([
                TextInput::make('title.en')->label('EN')->required(),
                TextInput::make('title.uk')->label('UK'),
                TextInput::make('title.ru')->label('RU'),
                TextInput::make('title.es')->label('ES'),
            ])->columns(2),
            Fieldset::make('Excerpt')->schema([
                Textarea::make('excerpt.en')->label('EN')->rows(2),
                Textarea::make('excerpt.uk')->label('UK')->rows(2),
                Textarea::make('excerpt.ru')->label('RU')->rows(2),
                Textarea::make('excerpt.es')->label('ES')->rows(2),
            ])->columns(2),
            Fieldset::make('Body')->schema([
                Textarea::make('body.en')->label('EN')->rows(5),
                Textarea::make('body.uk')->label('UK')->rows(5),
                Textarea::make('body.ru')->label('RU')->rows(5),
                Textarea::make('body.es')->label('ES')->rows(5),
            ])->columns(2),
            TextInput::make('image_url')->url(),
            TextInput::make('source_url')->url(),
            DateTimePicker::make('published_at')->default(now()),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title.en')->label('Title')->searchable()->limit(50),
                TextColumn::make('team.slug')->label('Team')->searchable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('team_id')->relationship('team', 'slug')->label('Team'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('geminiRewrite')
                    ->label('Rewrite (Gemini)')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('Rewrite and translate this article into all site languages, then save.')
                    ->action(function (TeamNews $record) {
                        @set_time_limit(0);
                        $gemini = app(GeminiClient::class);

                        if (! $gemini->isConfigured()) {
                            Notification::make()->title('Gemini key not set')
                                ->body('Add it in Settings → Gemini.')->danger()->send();
                            return;
                        }

                        $locales = Language::query()->active()->ordered()->pluck('code')->all() ?: ['en'];
                        $article = [
                            'title'   => self::firstValue($record->title),
                            'excerpt' => self::firstValue($record->excerpt),
                            'body'    => self::firstValue($record->body),
                        ];

                        if (! $article['title']) {
                            Notification::make()->title('Article has no title')->warning()->send();
                            return;
                        }

                        $rw = $gemini->rewriteArticle($article, $locales, config('gemini.prompt'));

                        if (! $rw) {
                            Notification::make()->title('Gemini failed')->danger()->send();
                            return;
                        }

                        $record->update([
                            'title'   => $rw['title'],
                            'excerpt' => $rw['excerpt'],
                            'body'    => $rw['body'],
                        ]);

                        Notification::make()->title('Rewritten & translated')->success()->send();
                    }),
            ])
            ->defaultSort('published_at', 'desc');
    }

    /** First non-empty value of a translatable JSON attribute. */
    public static function firstValue($json): ?string
    {
        if (is_array($json)) {
            foreach ($json as $v) {
                if ($v !== null && $v !== '') {
                    return $v;
                }
            }
        }

        return null;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeamNews::route('/'),
            'create' => Pages\CreateTeamNews::route('/create'),
            'edit'   => Pages\EditTeamNews::route('/{record}/edit'),
        ];
    }
}
