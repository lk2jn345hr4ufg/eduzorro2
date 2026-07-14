<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxonomyTerm extends Model
{
    protected $guarded = [];

    public function listings()
    {
        return $this->belongsToMany(Listing::class);
    }

    public function businesses()
    {
        return $this->belongsToMany(Business::class);
    }

    public function scopeTaxonomy($query, string $taxonomy)
    {
        return $query->where('taxonomy', $taxonomy);
    }
}
