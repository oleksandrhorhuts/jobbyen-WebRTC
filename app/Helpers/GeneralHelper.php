<?php

namespace App\Helpers;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\City;
use App\CategorySubCategory;
use App\Category;
use App\CategoryEn;
use App\Language;
use App\JobTitle;
use App\Skill;

class GeneralHelper
{
    public static function md_picker_parse_date($string)
    {
        $months = array(
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july ',
            'august',
            'september',
            'october',
            'november',
            'december',
        );



        $dateArr = explode(",", $string);
        $year = $dateArr[sizeof($dateArr) - 1];

        $m_d_string = trim($dateArr[0]);
        $monthArr = explode(" ", $m_d_string);
        $d = $monthArr[0];
        $m = $monthArr[1];

        $start = array_search($m, $months);
        $month = $start + 1;

        return date('Y-m-d', strtotime($year . '-' . $month . '-' . $d));
    }
    public static function insert_new_skill($skill)
    {
        $new_skill = new Skill();
        $new_skill->name = $skill;
        $new_skill->da_name = $skill;
        $new_skill->is_active = 1;
        if ($new_skill->save()) {
            return $new_skill->id;
        } else {
            return 0;
        }
    }
    public static function insert_new_jobtitle($title)
    {
        $new_title = new JobTitle();
        $new_title->name = $title;
        $new_title->is_active = 1;
        if ($new_title->save()) {
            return $new_title->id;
        } else {
            return 0;
        }
    }
    public static function insert_new_language($language)
    {
        $new_language = new Language();
        $new_language->name = $language;
        $new_language->short_code = 'da';
        $new_language->is_active = 1;
        if ($new_language->save()) {
            return $new_language->id;
        } else {
            return 0;
        }
    }
    public static function insert_new_location($location_name)
    {
        $new_city = new City();
        $new_city->zip = 0;
        $new_city->is_active = 1;
        $new_city->name = $location_name;
        $new_city->region = 0;
        if ($new_city->save()) {
            return $new_city->id;
        } else {
            return 0;
        }
    }
    public static function insert_new_category($category_name)
    {

        $new_category = new Category();
        $new_category->name = $category_name;
        $new_category->level = 2;
        $new_category->seo = strtolower($category_name);
        if ($new_category->save()) {
            $new_subCategory = new CategorySubCategory();
            $new_subCategory->category_id = 128;
            $new_subCategory->subcategory_id = $new_category->id;
            $new_subCategory->display_order = CategorySubCategory::where('category_id', 128)->count() + 1;
            $new_subCategory->save();
            return $new_category->id;
        } else {
            return 0;
        }
    }
    public static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString . uniqid();
    }

    public static function get_region_filter_with_query($query)
    {
        $result_cities_json = [];
        $cities = City::where('zip', 'like', '%' . $query . '%')
            ->orWhere('name', 'like', '%' . $query . '%')
            ->orderBy('name', 'asc')->get();
        foreach ($cities as $key => $value) {
            $city_data['zip'] = $value->zip;
            $city_data['id'] = $value->id;
            $city_data['name'] = $value->name;
            $city_data['region'] = $value->region;
            array_push($result_cities_json, $city_data);
        }
        return $result_cities_json;
    }
    public static function get_region_filter()
    {
        $result_cities_json = [];
        $cities = City::orderBy('name', 'asc')->get();
        foreach ($cities as $key => $value) {
            $city_data['zip'] = $value->zip;
            $city_data['id'] = $value->id;
            $city_data['name'] = $value->name;
            $city_data['region'] = $value->region;
            array_push($result_cities_json, $city_data);
        }
        return $result_cities_json;
    }
    public static function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }


    public static function get_employer_package_name($plan)
    {
        if ($plan == 1) {
            return 'Gør-det-selv';
        } else if ($plan == 2) {
            return 'Introtilbud';
        } else if ($plan == 3) {
            return 'Pro Pakke';
        } else {
            return '';
        }
    }

    public static function get_seeker_package_name($plan)
    {
        if ($plan == 1) {
            return 'Basic pakke';
        } else if ($plan == 2) {
            return 'Pro Pakke';
        } else {
            return '';
        }
    }

    public static function get_employer_package_price($plan)
    {
        if ($plan == 1) {
            return '495';
        } else if ($plan == 2) {
            return '1495';
        } else if ($plan == 3) {
            return '2495';
        } else {
            return '';
        }
    }

    public static function get_seeker_package_price($plan)
    {
        if ($plan == 1) {
            return '695';
        } else if ($plan == 2) {
            return '1495';
        } else {
            return '';
        }
    }

    public static function get_local_time()
    {
        $ip = file_get_contents("http://ipecho.net/plain");
        $url = 'http://ip-api.com/json/' . $ip;
        $tz = file_get_contents($url);
        $tz = json_decode($tz, true)['timezone'];
        return $tz;
    }
}
