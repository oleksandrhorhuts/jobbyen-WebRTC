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

class CategoryController extends Controller
{
    /**
     * EndPoint api/get_all_categories
     * HTTP SUBMIT : GET
     * Get all categories given language for search job filter
     *
     * @param
     * language : type [ 1: en, 0 : da]
     * @return json array with fetched categories
     */
    public function get_all_categories(Request $request, $language)
    {

        $final_json = [];
        if ($language == 1) { //en
            $result = CategoryEn::where('level', 1)->orderBy('name', 'asc')->get();
        } else { //da
            $result = Category::where('level', 1)->orderBy('name', 'asc')->get();
        }
        foreach ($result as $key => $value) {

            $item['id'] = $value['id'];
            $item['level'] = $value['level'];
            $item['name'] = $value['name'];
            $item['seo'] = $value['seo'];
            $item['checked'] = 0;

            $result_json_categories = [];
            if ($language == 1) { //en

                $sub_category = CategorySubCategory::leftJoin('categories_en', 'categories_en.id', '=', 'categories_subcategories.subcategory_id')->where('category_id', $value->id)->get();
                foreach ($sub_category as $key1 => $value1) {
                    $sub_data['name'] = $value1->name;
                    $sub_data['id'] = $value1->id;
                    $sub_data['checked'] = 0;
                    array_push($result_json_categories, $sub_data);
                }
            } else { //da
                $sub_category = CategorySubCategory::leftJoin('categories', 'categories.id', '=', 'categories_subcategories.subcategory_id')->where('category_id', $value->id)->get();
                foreach ($sub_category as $key1 => $value1) {
                    $sub_data['name'] = $value1->name;
                    $sub_data['id'] = $value1->id;
                    $sub_data['checked'] = 0;
                    array_push($result_json_categories, $sub_data);
                }
            }
            $item['sub_categories'] = $result_json_categories;

            array_push($final_json, $item);
        }

        return response()->json($final_json, 200);
    }
    /**
     * EndPoint api/get_main_categories
     * Get main categories name
     *
     * @param $language type [ 1: en, 0 : da]
     * @return json with id, name, seo of categories parent
     */
    public function get_main_categories(Request $request, $language)
    {
        $result_json = [];
        if ($language == 1) { //en
            $result = CategoryEn::where('level', 1)->get();
        } else { //da
            $result = Category::where('level', 1)->get();
        }
        foreach ($result as $key => $value) {
            $item['id'] = $value->id;
            $item['name'] = $value->name;
            $item['seo'] = $value->seo;
            $result_json[] = $item;
        }
        return response()->json($result_json, 200);
    }
    /**
     * EndPoint api/get_categories
     * HTTP SUBMIT : GET
     * Get all categories given language for job form
     *
     * @param
     * language : type [ 1: en, 0 : da]
     * @return json array with fetched categories
     */
    public function get_categories(Request $request, $language)
    {
        $result_json_categories = [];
        if ($language == 1) { //en
            $result = CategoryEn::where('level', 1)->get();
        } else { //da
            $result = Category::where('level', 1)->get();
        }
        foreach ($result as $key => $value) {
            $sub_category = CategorySubCategory::leftJoin('categories', 'categories.id', '=', 'categories_subcategories.subcategory_id')->where('category_id', $value->id)->orderBy('categories_subcategories.display_order', 'asc')->get();
            foreach ($sub_category as $key1 => $value1) {
                $sub_data['name'] = $value1->name;
                $sub_data['id'] = $value1->id;
                array_push($result_json_categories, $sub_data);
            }
        }

        $result_json_categories = collect($result_json_categories)->unique('name');


        $result_json = [];
        foreach ($result_json_categories as $val) {
            $result_json[] = $val;
        }
        return response()->json($result_json, 200);
    }
} //End class
