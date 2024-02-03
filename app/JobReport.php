<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobReport extends Model
{

    protected $table = 'job_reports';
    public function job()
    {
        return $this->hasOne('App\Job', 'id', 'job_id');
    }
}
