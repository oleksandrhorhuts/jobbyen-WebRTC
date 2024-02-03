<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobLocation extends Model {

    protected $table = 'job_locations';
  
    public function location()
    {
        return $this->belongsTo('App\City');
    }
  
    public function Job()
    {
        return $this->belongsTo('App\Job');
    }
}
