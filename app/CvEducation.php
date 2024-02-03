<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CvEducation extends Model {

    protected $table = 'cvs_educations';
  
    public function degree()
    {
        return $this->hasOne('App\Degree', 'id', 'course_name');
    }
    
    public function Cv()
    {
        return $this->belongsTo('App\Cv');
    }
}
