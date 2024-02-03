<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommonAsk extends Model
{

  protected $table = 'common_ask';
  public function emp()
  {
    return $this->hasOne('App\User', 'id', 'employer_id');
  }
}
