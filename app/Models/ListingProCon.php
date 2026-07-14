<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingProCon extends Model
{
    protected $table = 'listing_pros_cons';

    protected $guarded = [];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
