<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function taxonomyTerms()
    {
        return $this->belongsToMany(TaxonomyTerm::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
