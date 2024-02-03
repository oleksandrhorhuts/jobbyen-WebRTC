<?php

namespace App\Http\Controllers;

use App;

use App\User;
use Carbon\Carbon;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Nexmo;
use App\CategorySubCategory;
use App\Category;
use App\CategoryEn;
use PDF;
use App\Skill;
use App\JobTitle;

class DeveloperController extends Controller
{
    public function coming_soon()
    {
        return view('developer.coming_soon');
    }
} //End class
