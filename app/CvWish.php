<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CvWish extends Model {

    protected $table = 'cvs_wishes';
  
    public function job_title()
    {
        return $this->hasOne('App\JobTitle', 'id', 'job_title_id');
    }
    
    public function Cv()
    {
        return $this->belongsTo('App\Cv');
    }
  
}
