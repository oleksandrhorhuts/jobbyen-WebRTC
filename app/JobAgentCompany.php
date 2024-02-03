<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobAgentCompany extends Model
{
    protected $table = 'job_agents_company';

    public function company()
    {
        return $this->hasOne('App\Company', 'company_id', 'company_id');
    }
}
