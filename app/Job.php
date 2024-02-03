<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\Constraint\CountTest;
use Auth;
use DB;

class Job extends Model
{

    protected $table = 'jobs';

    protected $fillable = [
        'title', 'company', 'is_redirect', 'url', 'real_url', 'description', 'job_type_id', 'is_active', 'logo', 'city_id', 'created_at'
    ];


    public function JobCategory()
    {
        return $this->hasMany('App\JobCategory');
    }

    public function JobSkill()
    {
        return $this->hasMany('App\JobSkill');
    }

    public function job_city()
    {
        return $this->belongsTo('App\City', 'city_id', 'id');
    }

    public function job_degree()
    {
        return $this->belongsTo('App\Degree', 'education_id', 'id');
    }

    public function city()
    {
        return $this->hasOne('App\City', 'id', 'city_id');
    }

    public function job_location()
    {   
        return $this->hasMany('App\JobLocation', 'job_id', 'id');
    }

    public function job_apply()
    {
        return $this->hasMany('App\UserJobsApply', 'job_id', 'id');
    }

    public function job_apply_reject()
    {
        return $this->hasMany('App\UserJobsApply', 'job_id', 'id')->where('reject', 1);
    }

    public function job_visit()
    {
        return $this->hasMany('App\JobVisit', 'job_id', 'id');
    }

    public function user(){
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function job_desc_file(){
        return $this->hasOne('App\JobDescriptionFile', 'job_id', 'id');
    }
}
