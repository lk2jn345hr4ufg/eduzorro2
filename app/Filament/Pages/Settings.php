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
    protected static ?string $navigationGroup = 'Football';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'Settings (API keys)';
    protected static ?string $title = 'Settings';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    /** The setting keys this page manages. */
    protected array $keys = [
        'football_data_token',
        'apisports_key',
        'odds_provider',
        'oddsapi_key',
        'oddsapi_regions',
        'oddsapi_bookmakers',
        'api_football_season',
        'news_provider',
        'news_api_key',
        'news_language',
        'news_query_suffix',
        'news_per_team',
        'gemini_api_key',
        'gemini_model',
        'gemini_enabled',
        'news_enabled',
        'gemini_news_prompt',
    ];

    public function mount(): void
    {
        // Prefill with the current effective value: DB setting, else config default.
        $this->form->fill([
            'football_data_token' => Setting::get('football_data_token', config('football.api.token')),
            'apisports_key'       => Setting::get('apisports_key', config('apisports.key')),
            'odds_provider'       => Setting::get('odds_provider', config('odds.provider')),
            'oddsapi_key'         => Setting::get('oddsapi_key', config('odds.oddsapi.key')),
            'oddsapi_regions'     => Setting::get('oddsapi_regions', config('odds.oddsapi.regions')),
            'oddsapi_bookmakers'  => Setting::get('oddsapi_bookmakers', config('odds.oddsapi.bookmakers')),
            'api_football_season' => Setting::get('api_football_season', config('football.season')),
            'news_provider'       => Setting::get('news_provider', config('news.provider')),
            'news_api_key'        => Setting::get('news_api_key', config('news.key')),
            'news_language'       => Setting::get('news_language', config('news.language')),
            'news_query_suffix'   => Setting::get('news_query_suffix', config('news.query_suffix')),
            'news_per_team'       => Setting::get('news_per_team', config('news.per_team')),
            'gemini_api_key'      => Setting::get('gemini_api_key', config('gemini.key')),
            'gemini_model'        => Setting::get('gemini_model', config('gemini.model')),
            'news_enabled'        => filter_var(Setting::get('news_enabled', config('football.news_enabled')), FILTER_VALIDATE_BOOLEAN),
            'gemini_enabled'      => filter_var(Setting::get('gemini_enabled', config('gemini.enabled')), FILTER_VALIDATE_BOOLEAN),
            'gemini_news_prompt'  => Setting::get('gemini_news_prompt', config('gemini.prompt')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('football-data.org')
                    ->description('Drives countries, teams, fixtures and standings. Free tier covers the current season of 12 competitions; it has no transfers endpoint.')
                    ->schema([
                        TextInput::make('football_data_token')
                            ->label('API token (X-Auth-Token)')
                            ->password()->revealable()
                            ->autocomplete(false),
                        TextInput::make('api_football_season')
                            ->label('Season (start year)')
                            ->numeric()
                            ->placeholder((string) config('football.season'))
                            ->helperText('Free plans generally only allow the current season.'),
                    ])->columns(2),

                Section::make('Transfers (api-sports.io)')
                    ->description('football-data.org has no transfers endpoint, so transfers alone come from API-Football. Team ids are matched by club name and cached automatically.')
                    ->schema([
                        TextInput::make('apisports_key')
                            ->label('API-Football key')
                            ->password()->revealable()
                            ->autocomplete(false),
                    ]),

                Section::make('Odds')
                    ->description('1X2 odds for upcoming matches (sport:sync-odds). The Odds API free plan gives 500 credits a month; each competition costs 1 credit per sync and empty responses are free.')
                    ->schema([
                        Select::make('odds_provider')
                            ->label('Provider')
                            ->options([
                                'oddsapi'   => 'The Odds API (the-odds-api.com)',
                                'apisports' => 'API-Football (paid plan needed for the current season)',
                            ])
                            ->native(false),
                        TextInput::make('oddsapi_key')
                            ->label('The Odds API key')
                            ->password()->revealable()
                            ->autocomplete(false),
                        TextInput::make('oddsapi_regions')
                            ->label('Region')
                            ->placeholder('eu')
                            ->helperText('eu, uk, us or au. One region = 1 credit per request.'),
                        TextInput::make('oddsapi_bookmakers')
                            ->label('Preferred bookmakers')
                            ->placeholder('pinnacle,betfair_ex_eu,unibet_eu')
                            ->helperText('Comma-separated keys, best first.'),
                    ])->columns(2),

                Section::make('News API')
                    ->description('Pulls team news (API-Football has no news feed).')
                    ->schema([
                        Toggle::make('news_enabled')
                            ->label('Show news on the site')
                            ->helperText('Off: the news feed, article pages, the team "News" tab and all news blocks are hidden; /news URLs redirect to the home page.')
                            ->columnSpanFull(),
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
