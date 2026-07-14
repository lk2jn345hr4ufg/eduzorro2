<?php

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected $casts = [
        'description' => 'array',   // translatable
        'latitude'    => 'float',
        'longitude'   => 'float',
        'is_active'   => 'boolean',
        'is_verified' => 'boolean',
    ];

    /** name and address are plain strings; description is translatable. */

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function regions()
    {
        return $this->belongsToMany(Region::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Limit to companies available in a given region. */
    public function scopeInRegion(Builder $query, Region $region)
    {
        return $query->whereHas('regions', fn ($q) => $q->where('regions.id', $region->id));
    }

    /**
     * Attach the approved-review average + count as
     * `average_rating` and `reviews_count` on each row.
     */
    public function scopeWithRatingSummary(Builder $query)
    {
        return $query
            ->withAvg(['reviews as average_rating' => fn ($q) => $q->where('is_approved', true)], 'rating')
            ->withCount(['reviews as reviews_count' => fn ($q) => $q->where('is_approved', true)]);
    }
}
