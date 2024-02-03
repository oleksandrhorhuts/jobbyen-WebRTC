<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobCategory extends Model {

    protected $table = 'job_categories';
    public function category()
    {
        return $this->belongsTo('App\Category');
    }
    
    public function Job()
    {
        return $this->belongsTo('App\Job');
    }
}
