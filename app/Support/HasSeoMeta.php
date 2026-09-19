<?php

namespace App\Support;

/**
 * Admin-editable meta title/description for a model.
 *
 * Both are translatable JSON columns. Each accessor takes the value the page
 * would have generated on its own and returns the override only when one is
 * actually filled in for the current locale — so an empty field is invisible
 * rather than blanking the tag.
 *
 * Requires HasTranslations on the model (for translate()).
 */
trait HasSeoMeta
{
    public function metaTitle(?string $fallback = null): ?string
    {
        return $this->seoValue('meta_title') ?: $fallback;
    }

    public function metaDescription(?string $fallback = null): ?string
    {
        return $this->seoValue('meta_description') ?: $fallback;
    }

    /** True when an editor has set anything for the current locale. */
    public function hasSeoOverride(): bool
    {
        return (bool) ($this->seoValue('meta_title') || $this->seoValue('meta_description'));
    }

    protected function seoValue(string $field): ?string
    {
        $value = $this->translate($field);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
