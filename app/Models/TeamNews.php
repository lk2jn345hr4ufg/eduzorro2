<?php

namespace App\Models;

use App\Support\HasSeoMeta;
use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class TeamNews extends Model
{
    use HasSeoMeta;
    use HasTranslations;

    protected $table = 'team_news';

    protected $guarded = [];

    protected $casts = [
        'meta_title'       => 'array',
        'meta_description' => 'array',
        'title'        => 'array',
        'excerpt'      => 'array',
        'body'         => 'array',
        'published_at' => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
