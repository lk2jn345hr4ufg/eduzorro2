<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingReview extends Model
{
    public $timestamps = false; // created_at/updated_at set explicitly from the WP comment dates on import

    protected $guarded = [];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
