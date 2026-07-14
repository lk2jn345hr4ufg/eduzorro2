<?php

namespace App\Support;

/**
 * Minimal translatable-attribute trait.
 *
 * Store translatable columns as JSON, e.g. {"en": "Language Schools", "es": "Escuelas de idiomas"},
 * cast them to array in the model, then read them with:  $model->translate('name').
 *
 * Falls back to the app fallback locale, then to the first available value.
 * This keeps Eduzorro package-free; swap for spatie/laravel-translatable later if you prefer.
 */
trait HasTranslations
{
    public function translate(string $field, ?string $locale = null): ?string
    {
        $locale   = $locale ?: app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');
        $data     = $this->translationData($field);

        if (isset($data[$locale]) && $data[$locale] !== '') {
            return $data[$locale];
        }

        if (isset($data[$fallback]) && $data[$fallback] !== '') {
            return $data[$fallback];
        }

        return $data ? reset($data) : null;
    }

    /**
     * Normalise a translatable attribute to an array regardless of cast state.
     */
    protected function translationData(string $field): array
    {
        $value = $this->attributes[$field] ?? null;

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
