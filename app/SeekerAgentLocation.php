<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SeekerAgentLocation extends Model
{
    protected $table = 'seeker_agents_location';

    public function location()
    {
        return $this->belongsTo('App\City');
    }
}
