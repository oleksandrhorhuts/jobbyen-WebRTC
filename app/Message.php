<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model {
    public function sender()
    {
        return $this->belongsTo('App\User', 'sender_id', 'id');
    }

}
