<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CvLanguage extends Model {

    protected $table = 'cvs_languages';
    public function language()
    {
        return $this->hasOne('App\Language', 'id', 'language_id');
    }
    public function Cv()
    {
        return $this->belongsTo('App\Cv');
    }
}
