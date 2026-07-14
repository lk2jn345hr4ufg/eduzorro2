<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'editorial_rating' => 'float',
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

    public function addresses()
    {
        return $this->hasMany(ListingAddress::class);
    }

    public function prices()
    {
        return $this->hasMany(ListingPrice::class);
    }

    public function prosAndCons()
    {
        return $this->hasMany(ListingProCon::class);
    }

    public function reviews()
    {
        return $this->hasMany(ListingReview::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(ListingReview::class)->where('is_approved', true)->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVertical($query, string $vertical)
    {
        return $query->where('vertical', $vertical);
    }

    /** Attach the review average + count as `average_rating` / `reviews_count`. */
    public function scopeWithRatingSummary(Builder $query)
    {
        return $query
            ->withAvg(['reviews as average_rating' => fn ($q) => $q->where('is_approved', true)], 'rating')
            ->withCount(['reviews as reviews_count' => fn ($q) => $q->where('is_approved', true)]);
    }
}
