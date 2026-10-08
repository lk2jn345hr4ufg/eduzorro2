<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 1X2 odds for one fixture (see sport:sync-odds). */
class Odd extends Model
{
    protected $table = 'odds';

    protected $guarded = [];

    protected $casts = [
        'home'       => 'float',
        'draw'       => 'float',
        'away'       => 'float',
        'fetched_at' => 'datetime',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }
}
