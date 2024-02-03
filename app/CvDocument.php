<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CvDocument extends Model {

    protected $table = 'cvs_documents';
    public function Cv()
    {
        return $this->belongsTo('App\Cv');
    }

}
