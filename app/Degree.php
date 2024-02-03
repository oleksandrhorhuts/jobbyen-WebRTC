<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Degree extends Model {
    
	protected $fillable = ['title'];
    protected $table = 'degrees_da';

}
