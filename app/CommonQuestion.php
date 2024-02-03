<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommonQuestion extends Model
{
  protected $table = 'common_questions';

  public function answer()
  {
    return $this->hasOne('App\CommonAnswer', 'question_id', 'id');
  }
}
