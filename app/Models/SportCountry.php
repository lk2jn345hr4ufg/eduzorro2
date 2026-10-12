<?php

namespace App\Models;

use App\Support\HasSeoMeta;
use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class SportCountry extends Model
{
    use HasSeoMeta;
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'meta_title'       => 'array',
        'meta_description' => 'array',
        'name'      => 'array',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    /** The country's one championship (see competitions.league_country_id). */
    public function league()
    {
        return $this->hasOne(Competition::class, 'sport_country_id')->where('type', Competition::LEAGUE);
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
