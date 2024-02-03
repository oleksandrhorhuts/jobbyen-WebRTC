<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $dateFormat = "Y-m-s H:m:s";
    public $timestamps = false;
    protected $fillable = [
        'username', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function cv()
    {
        return $this->hasOne('App\Cv', 'user_id', 'id');
    }

    public function videocv(){
        return $this->hasOne('App\CvVideo', 'user_id', 'id');
    }

    public function user_company(){
        return $this->hasOne('App\Company', 'user_id', 'id');
    }

    public function location()
    {
        return $this->hasOne('App\City', 'id', 'city');
    }

    public function member(){
        return $this->hasOne('App\Membership', 'user_id', 'id');
    }

    public function company_job(){
        return $this->hasMany('App\Job', 'user_id', 'id');
    }

    public function video_call(){
        return $this->hasMany('App\Interview', 'employer_id', 'id');
    }

    public function message(){
        return $this->hasMany('App\Message', 'sender_id', 'id');
    }

    public function unread(){
        return $this->hasMany('App\Message', 'receiver_id', 'id')->where('is_seen', '=', 0);
    }

    public function notification(){
        return $this->hasMany('App\Notification', 'user_id', 'id')->where('read', '=', 0);
    }
}
