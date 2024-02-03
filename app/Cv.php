<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\Constraint\CountTest;
use Auth;
use DB;

class Cv extends Model
{
	protected $table = 'cvs';
	public function CvCategory()
	{
		return $this->hasMany('App\CvCategory');
	}

	public function CvEducation()
	{
		return $this->hasMany('App\CvEducation', 'cv_id', 'id');
	}

	public function CvExperience()
	{
		return $this->hasMany('App\CvExperience');
	}

	public function CvSkill()
	{
		return $this->hasMany('App\CvSkill');
	}

	public function CvWish()
	{
		return $this->hasMany('App\CvWish');
	}

	public function cv_city()
	{
		return $this->hasOne('App\City', 'id', 'location_id');
	}

	public function cv_degree()
	{
		return $this->belongsTo('App\Degree', 'degree_id', 'id');
	}

	public function cv_language()
	{
		return $this->hasMany('App\CvLanguage', 'cv_id', 'id');
	}

	public function user(){
		return $this->hasOne('App\User', 'id', 'user_id');
	}

	public function cv_document(){
		return $this->hasOne('App\CvDocument', 'cv_id', 'id');
	}

	public function cv_video(){
		return $this->hasOne('App\CvVideo', 'cv_id', 'id');
	}


	public function doc(){
		return $this->hasMany('App\CvDocument', 'cv_id', 'id');
	}

	public function jobtype(){
		return $this->hasOne('App\JobType', 'id', 'job_type');
	}

}


