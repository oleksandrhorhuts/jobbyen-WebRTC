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

class ECCOJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:ECCOJob';

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


        $company_name = 'ECCO';
	   	$base_url = 'https://enter.ecco.com';
    	$find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
    	$replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

	    $data = [];

        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
    	$crawler = $goutte->request('GET', 'https://enter.ecco.com/en/job-search-v2');

		$total_pagination = $crawler->filter('.list-of-jobs li.list-of-jobs__item')->count();

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

	    $data_25 = $crawler->filter('.list-of-jobs li.list-of-jobs__item')->each(function ($node, $index) use ($base_url, $current_pagination, $find_arr, $replace_arr){

            if($current_pagination - 1 < $index && $current_pagination + 5 >=$index)
            {
                $address = str_replace("Location",'', $node->filter('a ul li:nth-child(1)')->text());
    	    	$work_address = $address;

    	    	$work_city = $address;
            	$work_city = trim(preg_replace('/(.*)\((.*)\)/', '$1', $address), " \t\n\r\0\x0B\xC2\xA0");
            	$work_country = trim(preg_replace('/(.*)\((.*)\)/', '$2', $address), " \t\n\r\0\x0B\xC2\xA0");

            	if($work_country == 'Denmark') {
            	    $job_url = $node->filter('.list-of-jobs__link')->extract(['href']);
                    $job_url = isset($job_url[0]) ? $base_url . $job_url[0] : '';

                    $goutte = new Client();
                    // $goutte->setClient(new GuzzleClient(array('verify' => false)));
    				$crawler = $goutte->request('GET', $job_url);

    				$job_title = trim($node->filter('a h3.list-of-jobs__title')->text());

    				$job_created_date = date("Y-m-d",strtotime(str_replace("Published",'', $node->filter('a ul li:nth-child(3)')->text())));

    		    	$job_type = str_replace("Department",'', $node->filter('a ul li:nth-child(2)')->text());

    		    	if($crawler->filter('.job-meta__content p.gridzone_0_content_0_lblApplyEnd strong')->count() > 0)
    		    	{
    		    		$job_deadline_date = date("Y-m-d",strtotime($crawler->filter('.job-meta__content p.gridzone_0_content_0_lblApplyEnd strong')->text()));
    		    	}
    		    	else
    		    	{
    		    		$job_deadline_date = "";
    		    	}


    		    	$job_detail_infos = '';
    		    	if($crawler->filter('.job-meta__content p.gridzone_0_content_0_lblApplyStart strong')->count() > 0)
    		    	{
    		    		$job_start_date = $crawler->filter('.job-meta__content p.gridzone_0_content_0_lblApplyStart strong')->text();
    		    	}
    		    	else
    		    	{
    		    		$job_start_date = "";
    		    	}

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


    	            if($crawler->filter('.job-article__content div.content')->count() > 0)
    	            {
    	            	$job_description = trim(strip_tags($crawler->filter('.job-article__content div.content')->text()));
                        $job_description = preg_replace("/\r|\n/", "", $job_description);
                        $job_description = preg_replace("/\t/", " ", $job_description);
                        $job_description = trim($job_description, " \t\n\r\0\x0B\xC2\xA0");

    	            	$job_description_html_string = $crawler->filter('.job-article__content div.content')->html();

                        $job_description_html_string = preg_replace("/\r|\n/", "", $job_description_html_string);
                        $job_description_html_string = preg_replace("/\t/", " ", $job_description_html_string);
                        $job_description_html_string = trim($job_description_html_string, " \t\n\r\0\x0B\xC2\xA0");
    	            }
    		    	else
    		    	{
    		    		$job_description = "";
    		    		$job_description_html_string = "";
    		    	}

    		    	$temp_data = array(
    		    	    "Job Title" => $job_title,
    					"Job Description" => $job_description,
    					"Job Description Html" => $job_description_html_string,
    					"Job Created Date" => $job_created_date,
    					"Job Start Date" => '',
    					"Job Deadline Date" => $job_deadline_date,
    					"Work address" => $work_address,
    					"Work City" => $work_city,
    					"Work Country" => $work_country,
    					"Work Type" => $work_type,
    					"Job Type" => $job_type,
    					"Job Url" => $job_url,
    	                "Job Parent Category" => $job_parent_category,
    	                "Job Sub Category" => $job_sub_category
    				);

    		    	return $temp_data;
            	}
            }




	    });
	    $data = array_merge($data, $data_25);
        $old = false;
        $place_inserted_id = 0;
	    foreach($data as $value)
    	{
    	    if(isset($value['Job Title']) && $value['Job Title'] !='')
    	    {
    	        $work_city = $value['Work City'];
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
    			    if ($work_city == 'Hovedstadsområdet') {
                        $work_city = 'København';
                    } else if ($work_city == 'Lyngby') {
                        $work_city = 'Kongens Lyngby';
                    } else {
                    }

                    $city = City::where('name', '=', $work_city)->first();
                    if($city)
                    {
                        $job_location = $city->id;
                    }
                    else
                    {
                        $place_model = new Place;
                        $place_model->name = $work_city;
                        $place_model->company = 'ECCO';
                        if ($place_model->save()) {
                            $place_inserted_id = $place_model->id;
                        }
                    }
    			}


                $job_logo = 'ecco.png';

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
                $new_job->company = 'ECCO';
                $new_job->company_website = '';
                $new_job->city_id = $job_location;
                if($job_created_date!='')
                    $new_job->created_at = $job_created_date;
                else
                    $new_job->created_at = date('Y-m-d');
                $new_job->created_on = date('Y-m-d');
                if($job_deadline_date!='')
                        $new_job->job_deadline_date = $job_deadline_date;
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
                                if($category){
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
                        //$updated = Job::where('real_url', $job_url)->update(['job_deadline_date'=>$job_deadline_date]);
                    }
                }

    	    }

    	}

    	$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>intval($current_pagination + 6), 'updated_at'=>date('Y-m-d H:i:s')]);
    	if($old) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>1, 'updated_at'=>date('Y-m-d H:i:s')]);
    	}
    }
}