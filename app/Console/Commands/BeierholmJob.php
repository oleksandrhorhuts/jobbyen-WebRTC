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

class BeierholmJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:BeierholmJob';

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
        $company_name = 'Beierholm';
	   	$base_url = 'https://www.beierholm.dk';
    	$find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
    	$replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

	    $data = [];
    	$page = 1;


        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $goutte->request('GET', 'https://www.beierholm.dk/om-beierholm/ledige-job/');

		$logo ='';
        $logoSource = $crawler->filter('img.js-logo-image')->extract(['src']);
        if(isset($logoSource[0])){
            $logo =$base_url. $logoSource[0];
        }

        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $goutte->request('GET', 'https://beierholm-career.talent-soft.com/accueil.aspx?LCID=1030');

	    $total_pagination = $crawler->filter('ul.ts-related-offers__row li.ts-offer-list-item.offerlist-item')->count();


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

	    $data_25 = $crawler->filter('ul.ts-related-offers__row li.ts-offer-list-item.offerlist-item')->each(function ($node, $index) use ($base_url, $current_pagination, $logo, $find_arr, $replace_arr) {
            if($current_pagination - 1 <=$index && $current_pagination + 5 > $index)
            {
                $job_title = $node->filter('.ts-offer-list-item__title-link')->html();

                $job_url = $node->filter('.ts-offer-list-item__title-link')->extract(['href']);
                $job_url = isset($job_url[0]) ? $job_url[0] : '';

                $job_created_date = date("Y-m-d",strtotime($node->filter('.ts-offer-list-item__description li:nth-child(1)')->html()));
                $work_city = $node->filter('.ts-offer-list-item__description li:nth-child(3)')->html();

                $goutte = new Client();
                // $goutte->setClient(new GuzzleClient(array('verify' => false)));
				$crawler = $goutte->request('GET', 'https://beierholm-career.talent-soft.com/'.$job_url);

				$job_description = '';
				if($crawler->filter('.content-col-1 div')->count()){
				    $job_description = $crawler->filter('.content-col-1 div')->html();
				} else {
				    $job_description = '';
				}

				if($crawler->filter('.content-col-2 div div:nth-child(1)')->count()){
				    $ddate = date("Y-m-d",strtotime(str_replace("Ansøgningsfrist:", "", $crawler->filter('.content-col-2 div div:nth-child(1)')->text())));
				} else {
				    $ddate = "1970-01-01";
				}


                if($ddate == "1970-01-01")
		    	{
		    		$job_deadline_date = "";
		    	}
		    	else
		    	{
		    		$job_deadline_date = $ddate;
		    	}


		    	$job_type = '';
		    	if($crawler->filter('.content-col-2 div div:nth-child(4)')->count()){
		    	    $job_type = trim(str_replace("Ansættelsesform:", "", $crawler->filter('.content-col-2 div div:nth-child(4)')->text()));
		    	}


    	    	if($job_type == 'Full-Time' || $job_type == 'Fuldtid' || $job_type == 'Vollzeit')
                {
                    $job_type = 1;
                }
                else if($job_type == 'Contract')
                {
                    $job_type = 4;
                }
                else if($job_type == 'Temporary')
                {
                    $job_type = 4;
                }
                else if($job_type == 'Praktikant')
                {
                    $job_type = 8;

                } else {
                    $job_type = 1;
                }


	            $job_parent_category = '';
    			$job_sub_category = '';
                $job_category_url = "https://www.jobsearch.dk/jobdatabase?searchText=".$job_title;
                $goutte = new Client();
                // $goutte->setClient(new GuzzleClient(array('verify' => false)));
                $category_crawler = $goutte->request('GET', $job_category_url);

    			if($category_crawler->filter('a.read-more')->count())
    			{
    			    $job_category = $category_crawler->filter('a.read-more')->extract(['href']);
                    if(isset($job_category[0])){
                        $categoryArr = explode('/', $job_category[0]);
                        $job_parent_category = $categoryArr[2];
                        $job_sub_category = $categoryArr[3];
                    }
    			}

        		$job_description = '';
                if($crawler->filter('.content-col-1 div')->count()){
                    $job_description = $crawler->filter('.content-col-1 div')->text();
                }

				$job_description_html_string = '';
				if($crawler->filter('.content-col-1 div')->count()){
				    $job_description_html_string = $crawler->filter('.content-col-1 div')->html();
				}

		    	$temp_data = array(
		    	    "Job Title" => $job_title,
					"Job Description" => $job_description,
					"Job Description Html" => $job_description_html_string,
					"Job Created Date" => $job_created_date,
					"Job Start Date" => '',
					"Job Deadline Date" => $job_deadline_date,
					"Company Logo" => $logo,
					"Work address" => $work_city,
					"Work City" => $work_city,
					"Work Type" => $job_type,
					"Job Type" => $job_type,
					"Job Url" => $job_url,
	                "Job Parent Category" => $job_parent_category,
	                "Job Sub Category" => $job_sub_category
				);

		    	return $temp_data;
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
        	    $job_start_date = $value['Job Start Date'];
        	    $job_deadline_date = $value['Job Deadline Date'];
        	    $job_parent_category = $value['Job Parent Category'];
        	    $job_sub_category = $value['Job Sub Category'];
        	    $job_type_id = $value['Work Type'];

        	    $job_location = 0;

    			if($work_city != '')
    			{
                    if($work_city == 'Hovedstadsområdet'){
                        $work_city = 'København';
                    } else if($work_city == 'Lyngby'){
                        $work_city = 'Kongens Lyngby';
                    } else if($work_city == 'Aarhus'){
                        $work_city = 'Århus';
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
                        $place_model->company = 'Beierholm';
                        if ($place_model->save()) {
                            $place_inserted_id = $place_model->id;
                        }
                    }
    			}


                $job_logo = 'beierholm.png';

                $new_job = new Job;
                $new_job->title = $job_title;
                $new_job->seo = str_slug(str_replace($dst, $src, $job_title));
                $new_job->is_redirect = 0;
                $new_job->url = Hash::make(date('Y-m-d H:i:s'));
                $new_job->real_url = 'https://beierholm-career.talent-soft.com'.$job_url;
                $new_job->description = $job_description;
                $new_job->description_html = $job_description_html;
                $new_job->job_type_id = $job_type_id;
                $new_job->is_active = 1;
                $new_job->logo = $job_logo;
                $new_job->company = 'Beierholm';
                $new_job->company_website = '';
                $new_job->city_id = $job_location;
                $new_job->created_at = date('Y-m-d');

                if($job_created_date!='')
                    $new_job->created_at = $job_created_date;
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

                                $job_category = new JobCategory;
                                $job_category->job_id = $inserted_id;
                                $job_category->category_id = $category->id;
                                $job_category->save();

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
						$old = true;
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
