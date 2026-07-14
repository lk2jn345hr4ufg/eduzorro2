<?php

namespace App\Filament\Support;

use App\Models\Language;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/**
 * Builds a Tabs component with one tab per active language for a JSON
 * translatable column (e.g. `name`, `description`), using dot-notation
 * field names ("name.en", "name.es") that map directly onto the model's
 * array-cast attribute — no translation package required.
 *
 * The first active language's field is marked required, since that's the
 * fallback locale every `translate()` call falls back to.
 */
class TranslatableTabs
{
    public static function make(string $field, string $label, bool $textarea = false): Component
    {
        $languages = Language::query()->orderBy('sort_order')->orderBy('code')->get();
        $first     = $languages->first();

        return Tabs::make("{$field}_translations")
            ->label($label)
            ->columnSpanFull()
            ->tabs(
                $languages->map(function (Language $language) use ($field, $textarea, $first) {
                    $name = "{$field}.{$language->code}";

                    $input = $textarea
                        ? Textarea::make($name)->rows(4)
                        : TextInput::make($name);

                    return Tab::make(strtoupper($language->code))
                        ->schema([
                            $input
                                ->label($language->native_name ?: $language->name)
                                ->required($first && $language->is($first))
                                ->maxLength($textarea ? 5000 : 255),
                        ]);
                })->all()
            );
    }
}
