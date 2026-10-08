<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitionResource\Pages;
use App\Filament\Support\SeoFields;
use App\Models\Competition;
use App\Models\Language;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
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
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

/**
 * Championships (one per country, enforced by a unique index) and
 * tournaments. Each one gets a page at /{language}/{slug}.
 */
class CompetitionResource extends Resource
{
    protected static ?string $model = Competition::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Football';
    protected static ?string $navigationLabel = 'Championships & tournaments';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->columns(2)->schema([
                Select::make('type')
                    ->options([Competition::LEAGUE => 'Championship (domestic league)', Competition::CUP => 'Tournament (cup)'])
                    ->required()->live()->default(Competition::LEAGUE),
                Select::make('sport_country_id')
                    ->label('Country')
                    ->relationship('country', 'slug')
                    ->searchable()->preload()
                    ->required(fn ($get) => $get('type') === Competition::LEAGUE)
                    ->helperText('A country can have only ONE championship.')
                    // One championship per country (also enforced by a DB
                    // unique index on league_country_id). Tournaments skip it.
                    ->unique(
                        table: 'competitions',
                        column: 'league_country_id',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, $get) => $get('type') === Competition::LEAGUE
                            ? $rule
                            : $rule->where('id', 0),
                    )
                    ->validationMessages(['unique' => 'This country already has a championship.']),
                TextInput::make('code')
                    ->required()->maxLength(16)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state)))
                    ->helperText('football-data.org code, e.g. PL, PD, CL. Fixtures and standings are keyed by it.'),
                TextInput::make('slug')
                    ->required()->maxLength(80)
                    ->unique(ignoreRecord: true)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->notIn(Competition::RESERVED_SLUGS)
                    ->helperText('URL: /{language}/{slug}. Lowercase letters, digits and dashes.'),
                Fieldset::make('Name')->columns(2)->schema(
                    collect(['en', 'uk', 'ru', 'es'])->map(fn ($code) => TextInput::make("name.{$code}")
                        ->label(strtoupper($code))->required($code === 'en'))->all()
                ),
                TextInput::make('api_id')->numeric()->label('football-data id')
                    ->helperText('Empty = not covered by football-data (e.g. the Ukrainian league).'),
                TextInput::make('apisports_league_id')->numeric()->label('API-Football league id')
                    ->helperText('Used for odds and transfers (e.g. 39 = Premier League).'),
                TextInput::make('emblem_url')->url()->maxLength(500),
                TextInput::make('flag')->maxLength(32)->helperText('Emoji flag shown next to the name.'),
                TextInput::make('sort_order')->numeric()->default(0),
                Toggle::make('is_featured')->label('Featured')->default(false),
                Toggle::make('is_active')->default(true),
            ]),

            SeoFields::make(),

            Section::make('SEO per tab')
                ->description('Optional overrides for each tab URL. Empty fields fall back to the SEO section above, then to the templates in SEO → Meta tags.')
                ->collapsed()
                ->schema([
                    Tabs::make('meta_tabs_tabs')->columnSpanFull()->tabs(
                        collect([
                            'dashboard' => 'Overview',
                            'standings' => 'Standings',
                            'fixtures'  => 'Fixtures',
                            'results'   => 'Results',
                            'teams'     => 'Teams',
                            'transfers' => 'Transfers',
                        ])->map(fn ($label, $tab) => Tab::make($label)->schema([
                            Tabs::make($tab.'_langs')->columnSpanFull()->tabs(
                                Language::query()->orderBy('sort_order')->orderBy('code')->get()
                                    ->map(fn ($language) => Tab::make(strtoupper($language->code))->schema([
                                        TextInput::make("meta_tabs.{$tab}.heading.{$language->code}")->label('H1 heading')->maxLength(255),
                                        TextInput::make("meta_tabs.{$tab}.title.{$language->code}")->label('Meta title')->maxLength(255),
                                        Textarea::make("meta_tabs.{$tab}.description.{$language->code}")->label('Meta description')->rows(3)->maxLength(500),
                                    ]))->all()
                            ),
                        ]))->values()->all()
                    ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('emblem_url')->label(''),
                TextColumn::make('flag')->label(''),
                TextColumn::make('name.en')->label('Name')->searchable(),
                TextColumn::make('code')->badge(),
                TextColumn::make('type')->badge()
                    ->color(fn ($state) => $state === Competition::LEAGUE ? 'success' : 'warning'),
                TextColumn::make('country.slug')->label('Country'),
                TextColumn::make('teams_count')->counts('teams')->label('Teams'),
                TextColumn::make('api_id')->label('FD id')->placeholder('—'),
                TextColumn::make('apisports_league_id')->label('AS id')->placeholder('—'),
                ToggleColumn::make('is_active')->label('Active'),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([Competition::LEAGUE => 'Championship', Competition::CUP => 'Tournament']),
            ])
            ->actions([
                EditAction::make(),
                Action::make('sync')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Fetch matches and standings for this competition from football-data.org (2 requests).')
                    ->visible(fn (Competition $record) => (bool) $record->api_id)
                    ->action(function (Competition $record) {
                        @set_time_limit(0);
                        $code = Artisan::call('sport:sync-competition', ['codes' => [$record->code], '--sleep' => 0]);
                        Notification::make()
                            ->title('Sync — '.$record->code)
                            ->body(Str::limit(trim(Artisan::output()), 400) ?: 'Done.')
                            ->{$code === 0 ? 'success' : 'danger'}()
                            ->send();
                    }),
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Competition $record) => ($language = Language::active()->ordered()->first())
                        ? route('competition.show', [$language, $record])
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompetitions::route('/'),
            'create' => Pages\CreateCompetition::route('/create'),
            'edit'   => Pages\EditCompetition::route('/{record}/edit'),
        ];
    }

    /** The header menu caches the competition list; drop it after edits. */
    public static function forgetNavCache(): void
    {
        Cache::forget('nav_competitions');
    }
}
