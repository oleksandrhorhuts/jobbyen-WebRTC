<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;

use App\Blog;

class BlogController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the blog index page from given blog id and blog title 
     * @param
     * blog_id : the id of blog
     * blog_title : title of blog
     * @return blog index blade view
     */
    public function index(Request $request, $blog_id, $blog_title)
    {
        $blog = Blog::with(['detail'])->where('id', $blog_id)->first();
        $data['og_title'] = $blog['name'];
        $data['og_image'] = $request->root() . '/images/blogs/' . $blog['detail'][0]['blog_file_name'] . '.png';


        $string = $blog['description'];
        $string = substr($string, strpos($string, "<p"), strpos($string, "</p>") + 4);

        $text = strip_tags($string);

        $data['og_description'] = $text;
        return view('blog.index', ['data' => $data]);
    }
}
