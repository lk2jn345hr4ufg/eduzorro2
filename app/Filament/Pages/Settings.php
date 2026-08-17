<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Sport';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'Settings (API keys)';
    protected static ?string $title = 'Settings';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    /** The setting keys this page manages. */
    protected array $keys = [
        'api_football_key',
        'api_football_season',
        'news_provider',
        'news_api_key',
        'news_language',
        'news_query_suffix',
        'news_per_team',
        'gemini_api_key',
        'gemini_model',
        'gemini_enabled',
        'gemini_news_prompt',
    ];

    public function mount(): void
    {
        // Prefill with the current effective value: DB setting, else config default.
        $this->form->fill([
            'api_football_key'    => Setting::get('api_football_key', config('football.api.key')),
            'api_football_season' => Setting::get('api_football_season', config('football.season')),
            'news_provider'       => Setting::get('news_provider', config('news.provider')),
            'news_api_key'        => Setting::get('news_api_key', config('news.key')),
            'news_language'       => Setting::get('news_language', config('news.language')),
            'news_query_suffix'   => Setting::get('news_query_suffix', config('news.query_suffix')),
            'news_per_team'       => Setting::get('news_per_team', config('news.per_team')),
            'gemini_api_key'      => Setting::get('gemini_api_key', config('gemini.key')),
            'gemini_model'        => Setting::get('gemini_model', config('gemini.model')),
            'gemini_enabled'      => filter_var(Setting::get('gemini_enabled', config('gemini.enabled')), FILTER_VALIDATE_BOOLEAN),
            'gemini_news_prompt'  => Setting::get('gemini_news_prompt', config('gemini.prompt')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('API-Football')
                    ->description('api-sports.io — drives countries, teams, fixtures, standings and transfers.')
                    ->schema([
                        TextInput::make('api_football_key')
                            ->label('API key')
                            ->password()->revealable()
                            ->autocomplete(false),
                        TextInput::make('api_football_season')
                            ->label('Season (start year)')
                            ->numeric()->placeholder('2024'),
                    ])->columns(2),

                Section::make('News API')
                    ->description('Pulls team news (API-Football has no news feed).')
                    ->schema([
                        Select::make('news_provider')
                            ->label('Provider')
                            ->options(['gnews' => 'GNews (gnews.io)', 'newsapi' => 'NewsAPI (newsapi.org)'])
                            ->native(false),
                        TextInput::make('news_api_key')
                            ->label('API key')
                            ->password()->revealable()
                            ->autocomplete(false),
                        TextInput::make('news_language')
                            ->label('Language')
                            ->placeholder('en')
                            ->helperText('Stored under this locale (en/uk/ru/es).'),
                        TextInput::make('news_query_suffix')
                            ->label('Query suffix')
                            ->placeholder(' football'),
                        TextInput::make('news_per_team')
                            ->label('Articles per team')
                            ->numeric()->placeholder('6'),
                    ])->columns(2),

                Section::make('Gemini (AI rewrite & translate)')
                    ->description('Rewrites each news article in original words and translates it into every active site language on import.')
                    ->schema([
                        Toggle::make('gemini_enabled')
                            ->label('Rewrite & translate news on import')
                            ->helperText('When off, news is stored as-is in the source language.'),
                        TextInput::make('gemini_api_key')
                            ->label('Gemini API key')
                            ->password()->revealable()
                            ->autocomplete(false),
                        TextInput::make('gemini_model')
                            ->label('Model')
                            ->placeholder('gemini-2.0-flash'),
                        Textarea::make('gemini_news_prompt')
                            ->label('Rewrite prompt')
                            ->rows(6)
                            ->columnSpanFull()
                            ->helperText('Editorial instructions only. The article text and the strict JSON output format are added automatically.'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            Setting::put($key, is_null($value) ? '' : (string) $value);
        }

        Notification::make()
            ->title('Settings saved')
            ->body('Keys take effect immediately. No .env edit or config:clear needed.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
    }
}
