<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxonomyTerm extends Model
{
    protected $guarded = [];

    protected $casts = [
        'name_i18n' => 'array',
    ];

    public function listings()
    {
        return $this->belongsToMany(Listing::class);
    }

    public function businesses()
    {
        return $this->belongsToMany(Business::class);
    }

    public function scopeTaxonomy($query, string $taxonomy)
    {
        return $query->where('taxonomy', $taxonomy);
    }

    /**
     * Display name for the current locale.
     *
     * Imported terms only have the original `name`; `name_i18n` holds optional
     * per-language overrides added in the admin. Falls back to the current
     * locale, then any filled translation, then the imported name — so a
     * partially translated term never renders blank.
     */
    public function label(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $t      = $this->name_i18n ?? [];

        if (! empty($t[$locale])) {
            return $t[$locale];
        }

        foreach ($t as $value) {
            if (! empty($value)) {
                return $value;
            }
        }

        return (string) $this->name;
    }
}
