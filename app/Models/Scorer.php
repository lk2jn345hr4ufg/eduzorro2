<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Top scorer row (football-data /competitions/{code}/scorers). */
class Scorer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'goals'     => 'integer',
        'assists'   => 'integer',
        'penalties' => 'integer',
        'played'    => 'integer',
    ];
}
