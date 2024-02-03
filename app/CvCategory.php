<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CvCategory extends Model {

  protected $table = 'cvs_categories';
  
    public function category()
    {
        return $this->belongsTo('App\Category');
    }
    
    public function Cv()
    {
        return $this->belongsTo('App\Cv');
    }
}
