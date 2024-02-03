<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';

    public function user(){
        return $this->hasOne('App\User', 'id', 'user_id');
    }
}
