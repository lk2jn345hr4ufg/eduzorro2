<?php

namespace App\Filament\Pages;

use App\Models\Language;
use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Meta tags for pages that have no database record of their own (home page,
 * the tools landing, the sport section...). Per-record pages are edited in the
 * SEO section of their own resource form instead.
 *
 * Values are stored in the settings table as JSON per page, so no migration is
 * needed and everything takes effect immediately.
 */
class SeoMeta extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?string $navigationLabel = 'Meta tags';
    protected static ?string $title = 'Meta tags';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.seo-meta';

    public ?array $data = [];

    public function mount(): void
    {
        $state = [];

        foreach ($this->pages() as $key => $page) {
            $stored = Setting::get('seo_page_'.$key);
            $stored = $stored ? (json_decode($stored, true) ?: []) : [];

            foreach ($this->languages() as $language) {
                $state[$key]['title'][$language->code]       = data_get($stored, "title.{$language->code}");
                $state[$key]['description'][$language->code] = data_get($stored, "description.{$language->code}");
            }
        }

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        $languages = $this->languages();

        return $form
            ->schema([
                Tabs::make('pages')
                    ->columnSpanFull()
                    ->tabs(collect($this->pages())->map(function (array $page, string $key) use ($languages) {
                        return Tab::make($page['label'])
                            ->schema([
                                Section::make($page['sample'])
                                    ->description('Leave a field empty to keep the text the page generates on its own.')
                                    ->schema([
                                        Tabs::make($key.'_langs')
                                            ->columnSpanFull()
                                            ->tabs($languages->map(fn (Language $language) => Tab::make(strtoupper($language->code))
                                                ->schema([
                                                    TextInput::make("{$key}.title.{$language->code}")
                                                        ->label('Meta title')
                                                        ->maxLength(255),
                                                    Textarea::make("{$key}.description.{$language->code}")
                                                        ->label('Meta description')
                                                        ->rows(3)
                                                        ->maxLength(500),
                                                ]))->all()),
                                    ]),
                            ]);
                    })->values()->all()),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($this->pages() as $key => $page) {
            $values = $state[$key] ?? [];

            // Drop empty locales so a blank field means "use the default"
            // rather than storing an empty string that would blank the tag.
            foreach (['title', 'description'] as $field) {
                $values[$field] = array_filter(
                    $values[$field] ?? [],
                    fn ($v) => is_string($v) && trim($v) !== ''
                );
            }

            $clean = array_filter($values, fn ($v) => ! empty($v));

            Setting::put('seo_page_'.$key, $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : '');
        }

        Notification::make()
            ->title('Meta tags saved')
            ->body('Changes are live immediately.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')->label('Save')->submit('save'),
        ];
    }

    protected function pages(): array
    {
        return config('seo_pages', []);
    }

    protected function languages()
    {
        return Language::query()->orderBy('sort_order')->orderBy('code')->get();
    }
}
