<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;

/**
 * Collapsed "SEO" section appended to content resource forms.
 *
 * Both fields are optional overrides: left empty, the page keeps the title and
 * description it generates from its own content, which is the right default for
 * the vast majority of records.
 *
 * Note: TranslatableTabs returns a Tabs layout component, which has no hint(),
 * so the length guidance lives in the section description instead.
 */
class SeoFields
{
    public static function make(?string $hint = null): Component
    {
        return Section::make('SEO')
            ->description($hint ?: 'Optional overrides. Leave empty to keep the automatically generated tags. Meta title works best at roughly 50–60 characters, meta description at 140–160.')
            ->collapsed()
            ->schema([
                TranslatableTabs::make('meta_title', 'Meta title'),
                TranslatableTabs::make('meta_description', 'Meta description', textarea: true),
            ]);
    }
}
