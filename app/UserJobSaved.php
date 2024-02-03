<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserJobSaved extends Model
{
    protected $table = "user_job_saved";


    public function Job()
    {
        return $this->belongsTo('App\Job');
    }
}
