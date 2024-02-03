<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserJobsApply extends Model
{
    protected $table = 'user_jobs_applied';


    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function attach()
    {
        return $this->hasOne('App\ApplyAttachFile', 'apply_id', 'id');
    }

    public function job()
    {
        return $this->hasOne('App\Job', 'id', 'job_id');
    }
}
