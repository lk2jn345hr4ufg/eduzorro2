<?php

namespace App\Models;

use App\Support\HasSeoMeta;
use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasSeoMeta;
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'meta_title'       => 'array',
        'meta_description' => 'array',
        'name'        => 'array',
        'description' => 'array',
        'is_active'   => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function country()
    {
        return $this->belongsTo(SportCountry::class, 'sport_country_id');
    }

    public function news()
    {
        return $this->hasMany(TeamNews::class)->latest('published_at');
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
