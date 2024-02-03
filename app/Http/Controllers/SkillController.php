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

class SkillController extends Controller
{
    /**
     * EndPoint api/get_skills
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * get skills from given language
     * @param
     * language : language 0 : da, 1 : en
     * @return json array with skills
     */
    public function get_skills(Request $request, $language)
    {
        $result_json = [];
        foreach (Skill::get() as $value) {
            $data['name'] = $language == 1 ? $value->name : $value->da_name;
            $data['id'] = $value->id;
            array_push($result_json, $data);
        }
        return $result_json;
    }
    /**
     * EndPoint api/get_job_wishes
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * get job wishes from given language
     * @param
     * language : language
     * @return json array with skills
     */
    public function get_job_wishes(Request $request, $language)
    {
        $result_json = [];
        foreach (JobTitle::get() as $value) {
            $data['name'] = $value->name;
            $data['id'] = $value->id;
            array_push($result_json, $data);
        }
        return $result_json;
    }
} //End class
