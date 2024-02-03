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
use App\Language;

class LanguageController extends Controller
{
    /**
     * EndPoint api/get_languages
     * HTTP SUBMIT : GET
     * Get all languages as json array
     * @return json array with fetched languages
     */
    public function get_languages(Request $request)
    {
        $result_json = [];
        foreach (Language::get() as $value) {
            $data['name'] = $value->name;
            $data['id'] = $value->id;
            $result_json[] = $data;
        }
        return $result_json;
    }
} //End class
