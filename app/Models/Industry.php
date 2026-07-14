<?php

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Industry extends Model
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

    public function categories()
    {
        return $this->hasMany(Category::class);
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
