<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte;
use App\Job;
use App\Place;
use App\City;
use File;
use DB;
use App\JobCategory;
use App\Category;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use App\JobLocation;

use Illuminate\Support\Facades\Hash;

class NetcompanyJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:NetcompanyJob';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $src = ['oe', 'aa', 'ae'];
        $dst = ['ø', 'å', 'æ'];
        $company_name = 'Netcompany';
	   	$base_url = 'https://www.netcompany.com';
    	$find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
    	$replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

	    $data = [];
    	$page = 1;

        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $goutte->request('GET', 'https://www.netcompany.com/da/Career/job-search');
		$logo =$crawler->filter('#logoLink img')->extract(['src']);
        $logo = isset($logo[0]) ? $base_url . $logo[0] : '';

		$total_pagination = $crawler->filter('.job')->count();

		$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['page_size'=>intval($total_pagination)]);

        $cron_schedule = DB::table('cron_schedule')->where('company_name', $company_name)->first();
    	$current_pagination = $cron_schedule->pagination;
    	$totalpagesize = $cron_schedule->page_size;
    	$checked_status = $cron_schedule->checked;


    	if($totalpagesize <= $current_pagination)
        {
    	    $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['checked'=>intval($checked_status + 1)]);
        	$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>1]);
        	$current_pagination = 1;
        }

	    $data_25 = $crawler->filter('.job')->each(function ($node, $index) use ($base_url, $current_pagination, $logo, $find_arr, $replace_arr) {

            if($current_pagination - 1 <=$index && $current_pagination + 10 > $index)
            {
                $location = $node->filter('a .body .location')->text();

                if($location == 'Denmark'){
                    $job_url = $node->filter('a')->extract(['href']);
                    $job_url = isset($job_url[0]) ? $base_url . $job_url[0] : '';

                    $goutte = new Client();
                    // $goutte->setClient(new GuzzleClient(array('verify' => false)));
    				$crawler = $goutte->request('GET', $job_url);

    				$job_title = trim($node->filter('a .title h2')->text(), " \t\n\r\0\x0B\xC2\xA0");

    				$job_created_date = '';

    		    	$job_type = $node->filter('a .body .buisnessArea')->text();

    		    	$address = $node->filter('a .body .city')->text();

    		    	$job_deadline_date = '';

    		    	$work_address = $address;

    		    	$work_city = $address;

    		    	$temp = explode(",", $address);
    		    	if(sizeof($temp) > 1)
    		    	{
    		    	    $work_city = $temp[0];
    		    	}

    		    	$job_detail_infos = '';
    		    	$job_start_date = '';

    		    	$work_type ='';

    				$job_parent_category = '';
        			$job_sub_category = '';
                    $job_category_url = "https://www.jobsearch.dk/jobdatabase?searchText=".$job_title;

                    $goutte = new Client();
                    // $goutte->setClient(new GuzzleClient(array('verify' => false)));
                    $category_crawler = $goutte->request('GET', $job_category_url);

        			if($category_crawler->filter('a.read-more')->count())
        			{
        			    $job_category=$category_crawler->filter('a.read-more')->extract(['href']);
        			    if(isset($job_category[0])){
                            $categoryArr = explode('/', $job_category[0]);
                            $job_parent_category = $categoryArr[2];
                            $job_sub_category = $categoryArr[3];
                        }
        			}

                    if($crawler->filter('.job-entry .col-md-8')->count()){
                        $job_description = $crawler->filter('.job-entry .col-md-8')->text();
                        $job_description = preg_replace("/\r|\n/", '', $job_description);

                        $job_description_html_string = $crawler->filter('.job-entry .col-md-8')->html();

                        $temp_data = array(
                            "Job Title" => $job_title,
                            "Job Description" => $job_description,
                            "Job Description Html" => $job_description_html_string,
                            "Job Created Date" => '',
                            "Job Start Date" => '',
                            "Job Deadline Date" => '',
                            "Company Logo" => $logo,
                            "Work address" => $work_address,
                            "Work City" => $work_city,
                            "Work Type" => '',
                            "Job Type" => '',
                            "Job Url" => $job_url,
                            "Job Parent Category" => $job_parent_category,
                            "Job Sub Category" => $job_sub_category
                        );

                        return $temp_data;
                    }
                }
            }
	    });
	    $data = array_merge($data, $data_25);

        $old = false;
        $place_inserted_id = 0;
    	foreach($data as $key=>$value)
    	{
    	    if(isset($value['Job Title']) && $value['Job Title'] !='')
    	    {
    	        $work_city = $value['Work City'];
    	        $work_type = $value['Work Type'];
        	    $job_title = $value['Job Title'];
        	    $job_url = $value['Job Url'];
        	    $job_description = $value['Job Description'];
        	    $job_description_html = $value['Job Description Html'];
        	    $job_created_date = $value['Job Created Date'];
        	    $job_parent_category = $value['Job Parent Category'];
        	    $job_sub_category = $value['Job Sub Category'];
        	    $job_type_id = $value['Job Type'];
        	    $job_deadline_date = $value['Job Deadline Date'];

        	    $job_location = 0;

    			if($work_city != '')
    			{
    			    if($work_city == 'Hovedstadsområdet'){
                        $work_city = 'København';
                    } else if($work_city == 'Lyngby'){
                        $work_city = 'Kongens Lyngby';
                    } else {

                    }

                    $city = City::where('name', '=', $work_city)->first();
                    if ($city) {
                        $job_location = $city->id;
                    } else {
                        $place_model = new Place;
                        $place_model->name = $work_city;
                        $place_model->company = 'Aalborg kommune';
                        if ($place_model->save()) {
                            $place_inserted_id = $place_model->id;
                        }
                    }
    			}

                $job_logo = 'netcompany.png';

                $new_job = new Job;
                $new_job->title = $job_title;
                $new_job->seo = str_slug(str_replace($dst, $src, $job_title));
                $new_job->is_redirect = 0;
                $new_job->url = Hash::make(date('Y-m-d H:i:s'));
                $new_job->real_url = $job_url;
                $new_job->description = $job_description;
                $new_job->description_html = $job_description_html;
                $new_job->job_type_id = 1;
                $new_job->is_active = 1;
                $new_job->logo = $job_logo;
                $new_job->company = 'Netcompany';
                $new_job->company_website = '';
                $new_job->city_id = $job_location;
                $new_job->created_at = date('Y-m-d');
                $new_job->created_on = date('Y-m-d');

                if($job_title != '')
                {
                    $existing_job = Job::where('real_url', $job_url)->first();

                    if(!$existing_job){
                        if($new_job->save())
                        {
                            $inserted_id = $new_job->id;
                            if ($place_inserted_id) {
                                Place::where('id', $place_inserted_id)->update(['job_id' => $inserted_id]);
                            }
                            if($job_sub_category !='')
                            {
                                $category = Category::where('seo', $job_sub_category)->where('level', 2)->first();
                                if ($category) {
                                    $job_category = new JobCategory;
                                    $job_category->job_id = $inserted_id;
                                    $job_category->category_id = $category->id;
                                    $job_category->save();
                                }
                            }

                            if ($job_location) {
                                if (JobLocation::where('job_id', $inserted_id)->where('location_id', $job_location)->first()) {
                                } else {
                                    $new_job_locations = new JobLocation();
                                    $new_job_locations->job_id = $inserted_id;
                                    $new_job_locations->location_id = $job_location;
                                    $new_job_locations->save();
                                }
                            }
                        }
                    }
                    else
                    {
						// $old = true;
                        //$updated = Job::where('real_url', $job_url)->update(['description_html'=>$job_description_html]);
                    }
                }
    	    }
    	}

	    $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>intval($current_pagination + 11), 'updated_at'=>date('Y-m-d H:i:s')]);
	    if($old) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>1, 'updated_at'=>date('Y-m-d H:i:s')]);
	    }
    }
}
