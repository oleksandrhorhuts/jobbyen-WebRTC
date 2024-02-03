<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobAgent extends Model
{

    protected $table = 'job_agents';

    public function agent_job_type()
    {
        return $this->hasOne('App\JobType', 'id', 'job_type_id');
    }
    // public function agent_company()
    // {
    //     return $this->hasOne('App\Company', 'company_id', 'company');
    // }


    public function agent_company(){
        return $this->hasMany('App\JobAgentCompany', 'agent_id', 'id');
    }

    public function agent_location()
    {
        return $this->hasMany('App\JobAgentLocation', 'agent_id', 'id');
    }

    public function agent_category()
    {
        return $this->hasMany('App\JobAgentCategory', 'agent_id', 'id');
    }
}
