<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DegreeDa extends Model {
    
	protected $fillable = ['title'];
    protected $table = 'degrees_da';
}
