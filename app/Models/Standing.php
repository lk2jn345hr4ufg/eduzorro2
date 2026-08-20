<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Standing extends Model
{
    protected $guarded = [];

    /** Re-shape a DB row into the API-Football payload the views expect. */
    public function toApiShape(): array
    {
        return [
            'rank'   => $this->rank,
            'points' => $this->points,
            'group'  => $this->group_label,
            'form'   => $this->form,
            'team'   => ['id' => $this->team_api_id, 'name' => $this->team_name, 'logo' => $this->team_logo],
            'all'    => [
                'played' => $this->played,
                'win'    => $this->win,
                'draw'   => $this->draw,
                'lose'   => $this->lose,
                'goals'  => ['for' => $this->goals_for, 'against' => $this->goals_against],
            ],
        ];
    }
}
