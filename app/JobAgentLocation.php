<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobAgentLocation extends Model
{
    protected $table = 'job_agents_location';

    public function location()
    {
        return $this->belongsTo('App\City');
    }
}
