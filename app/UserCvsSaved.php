<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCvsSaved extends Model
{
    protected $table = "user_cvs_saved";


    public function user()
    {
        return $this->hasOne('App\User', 'id', 'resume_user_id');
    }
}
