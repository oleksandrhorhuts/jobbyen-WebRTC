<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobAgentCategory extends Model {

    protected $table = 'job_agents_categories';
  
    public function category()
    {
        return $this->belongsTo('App\Category');
    }
}
