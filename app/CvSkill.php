<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CvSkill extends Model {

    protected $table = 'cvs_skills';
  
    public function skill()
    {
        return $this->belongsTo('App\Skill');
    }
  
    public function Cv()
    {
        return $this->belongsTo('App\Cv');
    }
}
