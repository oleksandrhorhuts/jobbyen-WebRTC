<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $table = 'interviews';

    public function seeker()
    {
        return $this->belongsTo('App\User', 'seeker_id', 'id');
    }

    public function employer()
    {
        return $this->belongsTo('App\User', 'employer_id', 'id');
    }
}
