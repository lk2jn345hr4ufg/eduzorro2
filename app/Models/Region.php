<?php

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'name'      => 'array',      // translatable: {"en": "...", "es": "..."}
        'latitude'  => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    /** URLs use the slug (e.g. /united-states/en). */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parent()
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    /** Companies operating in this region (many-to-many via company_region). */
    public function companies()
    {
        return $this->belongsToMany(Company::class);
    }

    /** WordPress-imported listings (courses, universities, etc.) headquartered/operating in this region. */
    public function listings()
    {
        return $this->hasMany(Listing::class);
    }

    /** WordPress-imported business-registry entries in this region. */
    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    /** Languages this region actually supports (not every active language applies to every region). */
    public function languages()
    {
        return $this->belongsToMany(Language::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
