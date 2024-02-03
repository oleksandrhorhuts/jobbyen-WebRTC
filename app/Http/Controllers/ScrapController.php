<?php

namespace App\Http\Controllers;

use Goutte;
use App\Job;
use App\Place;
use App\City;
use File;
use DB;
use App\JobCategory;
use App\Category;
use App\Helpers\GeneralHelper;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use App\JobLocation;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Clue\React\Buzz\Browser;
use Exception;

class ScrapController extends Controller
{
    public function scrap_test()
    {
        $cv_birthday = '7 june, 2000';
        echo $t = GeneralHelper::md_picker_parse_date($cv_birthday);
        echo '<br>';
        echo  Carbon::parse($t)->format('Y-m-d');
        echo '<br>';
        echo '<br>';
        echo '<br>';
        echo '<br>';
        $cv_birthday = '7 june, 1999';
        echo  Carbon::parse($cv_birthday)->format('Y-m-d');
    }
} //End class
