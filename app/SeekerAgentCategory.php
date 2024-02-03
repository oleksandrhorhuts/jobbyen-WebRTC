<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SeekerAgentCategory extends Model
{

    protected $table = 'seeker_agents_categories';

    public function category()
    {
        return $this->belongsTo('App\Category');
    }
}
