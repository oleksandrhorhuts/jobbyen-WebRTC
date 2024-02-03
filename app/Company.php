<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';
    public function company_location()
    {
        return $this->hasOne('App\City', 'id', 'company_city');
    }

    public function user(){
        return $this->hasOne('App\User', 'id', 'user_id');
    }
}
