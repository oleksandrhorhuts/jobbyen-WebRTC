<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
	protected $table = 'contact';

	public function user()
    {
        return $this->hasOne('App\User', 'id', 'contact_users');
    }
}
