<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CronSchedule extends Model {

    protected $table = 'cron_schedule';
  
    public function job()
    {
        return $this->hasMany('App\Job', 'company', 'company_name')->where('is_active', 1);
    }

    public function today_job()
    {
        return $this->hasMany('App\Job', 'company', 'company_name')->where('is_active', 1)->where('created_at', 'like', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")))) . '%');
    }
}
