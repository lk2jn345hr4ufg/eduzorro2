<?php

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'name'             => 'array',
        'description'      => 'array',
        'has_competitions' => 'boolean',
        'is_active'        => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function countries()
    {
        return $this->hasMany(SportCountry::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
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
