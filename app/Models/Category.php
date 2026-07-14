<?php

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'name'        => 'array',
        'description' => 'array',
        'is_active'   => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /** Sibling categories in the same industry (used for "related categories"). */
    public function siblings()
    {
        return $this->hasMany(Category::class, 'industry_id', 'industry_id')
            ->where('id', '!=', $this->id);
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
