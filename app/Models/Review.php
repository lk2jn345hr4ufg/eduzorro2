<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = [];

    protected $casts = [
        'rating'      => 'integer',
        'is_approved' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
