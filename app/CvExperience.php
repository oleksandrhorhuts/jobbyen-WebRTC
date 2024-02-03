<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CvExperience extends Model
{
    protected $table = 'cvs_experiences';

    public function Cv()
    {
        return $this->belongsTo('App\Cv');
    }
}
