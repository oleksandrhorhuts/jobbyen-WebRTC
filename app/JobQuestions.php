<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobQuestions extends Model
{

  protected $table = 'job_questions';

  public function answer()
  {
    return $this->hasOne('App\JobAnswer', 'question_id', 'id');
  }

  public function emp(){
    return $this->hasOne('App\User', 'id', 'employer_id');
  }
}
