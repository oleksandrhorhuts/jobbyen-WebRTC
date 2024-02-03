<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SeekerAgentLanguage extends Model
{

    protected $table = 'seeker_agents_languages';

    public function language()
    {
        return $this->belongsTo('App\Language');
    }
}
