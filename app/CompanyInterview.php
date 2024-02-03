<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyInterview extends Model
{
    protected $table = 'company_interviews';

    public function employer()
    {
        return $this->belongsTo('App\User', 'employer_id', 'id');
    }
}
