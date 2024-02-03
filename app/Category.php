<?php

    namespace App;

    use DB;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Category extends Model {
        protected $table = 'categories';
        
    }
