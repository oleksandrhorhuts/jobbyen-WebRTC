<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model {

    protected $table = 'notifications';

    public function type_11()
    {
        return $this->hasOne('App\User', 'id', 'name');
    }

    public function type_3(){
        return $this->hasOne('App\User', 'id', 'name');
    }

    public function type_10(){
        return $this->hasOne('App\Job', 'id', 'name');
    }

    public function agent_10(){
        return $this->hasOne('App\JobAgent', 'id', 'sender');
    }

    public function type_12(){
        return $this->hasOne('App\User', 'id', 'name');
    }

    public function agent_16(){
        return $this->hasOne('App\SeekerAgent', 'id', 'sender');
    }

    public function type_16(){
        return $this->hasOne('App\Cv', 'id', 'name');
    }

    public function agent_17(){
        return $this->hasOne('App\SeekerAgent', 'id', 'sender');
    }

    public function type_17(){
        return $this->hasOne('App\User', 'id', 'name');
    }

    public function type_20(){
        return $this->hasOne('App\User', 'id', 'name');
    }

    public function agent_20(){
        return $this->hasOne('App\Job', 'id', 'sender');
    }


    public function type_21(){
        return $this->hasOne('App\User', 'id', 'name');
    }

    public function agent_21(){
        return $this->hasOne('App\Job', 'id', 'sender');
    }




}
