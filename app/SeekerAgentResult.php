<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SeekerAgentResult extends Model
{

    protected $table = 'seeker_agents_results';

    public function user(){
        return $this->hasOne('App\User', 'id', 'matched_user_id');
    }

}
