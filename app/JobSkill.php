<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobSkill extends Model {

    protected $table = 'job_skills';
  
    public function skill()
    {
        return $this->belongsTo('App\Skill');
    }
  
    public function Job()
    {
        return $this->belongsTo('App\Job');
    }
}
