<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** URLs use the ISO code (e.g. /united-states/en). */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Regions where this language is actually offered. */
    public function regions()
    {
        return $this->belongsToMany(Region::class);
    }
}
