<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingAddress extends Model
{
    protected $guarded = [];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
