<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SeekerAgent extends Model
{

    protected $table = 'seeker_agents';

    public function agent_job_type()
    {
        return $this->hasOne('App\JobType', 'id', 'job_type_id');
    }

    public function agent_location()
    {
        return $this->hasMany('App\SeekerAgentLocation', 'agent_id', 'id');
    }

    public function agent_category()
    {
        return $this->hasMany('App\SeekerAgentCategory', 'agent_id', 'id');
    }

    public function agent_language()
    {
        return $this->hasMany('App\SeekerAgentLanguage', 'agent_id', 'id');
    }

    public function agent_education(){
        return $this->hasOne('App\Degree', 'id', 'education_id');
    }

    public function agent_result(){
        return $this->hasMany('App\SeekerAgentResult', 'agent_id', 'id')->where('type', 0);
    }

    public function agent_result1(){
        return $this->hasMany('App\SeekerAgentResult', 'agent_id', 'id');
    }
}
