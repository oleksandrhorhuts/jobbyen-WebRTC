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

class KobenhavnKommuneJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:KobenhavnKommuneJob';

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

        $company_name = 'København kommune';
	   	$base_url = 'https://www.kk.dk';
    	$find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
    	$replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

	    $data = [];
        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
	    $crawler = $goutte->request('GET', 'https://www.kk.dk/ledigestillinger?page=0');
	    $tmp = $crawler->filter('.reg-middle .placeholder')->text();


		$total_pagination = intval($tmp / 10);
		if($tmp % 10) {
		    $total_pagination = $total_pagination + 1;
		}

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


        //$data_25 = [];
        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $goutte->request('GET', 'https://www.kk.dk/ledigestillinger?page='.$current_pagination);

		$data_25 = $crawler->filter('.apachesolr_search-results li')->each(function ($node) use ($base_url, $find_arr, $replace_arr){

			if($node->filter('.title')->text() != "Placering"){

				$job_url = $node->filter('.title a')->extract(['href']);
                $job_url = isset($job_url[0]) ? $job_url[0] : '';

				$job_title = trim($node->filter('.title a')->text());

                $goutte = new Client();
                // $goutte->setClient(new GuzzleClient(array('verify' => false)));
				$crawler = $goutte->request('GET', $job_url);

				$job_created_date = '';

		    	//$job_type = trim($node->filter('.employment-type')->text());
		    	$job_type = 1;

		    	$address = '';

		    	$ddate=date("Y-m-d",strtotime(str_replace("Ansøgningsfrist:", "",$node->filter('.deadline')->text())));

		    	if($ddate=="1970-01-01")
		    	{
		    		$job_deadline_date = "";
		    	}
		    	else
		    	{
		    		$job_deadline_date = $ddate;
		    	}

		    	$work_address = "";

		    	$work_city = 'København';


		    	$job_detail_infos = '';
		    	$job_start_date = '';


		    	$work_type =trim($node->filter('.working-time')->text());

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

		    	$job_description = trim($crawler->filter('.reg-content')->text());

		    	$job_description = preg_replace("/\r|\n/", "", $job_description);
                $job_description = preg_replace("/\t/", " ", $job_description);
                $job_description = trim($job_description, " \t\n\r\0\x0B\xC2\xA0");


		    	$job_description_html_string = $crawler->filter('.reg-content')->html();

		    	$job_description_html_string = preg_replace("/\r|\n/", "", $job_description_html_string);
                $job_description_html_string = preg_replace("/\t/", " ", $job_description_html_string);
                $job_description_html_string = trim($job_description_html_string, " \t\n\r\0\x0B\xC2\xA0");

		    	$temp_data = array(
		    	    "Job Title" => $job_title,
					"Job Description" => $job_description,
					"Job Description Html" => $job_description_html_string,
					"Job Created Date" => $job_created_date,
					"Job Start Date" => '',
					"Job Deadline Date" => $job_deadline_date,
					"Work address" => $work_address,
					"Work City" => $work_city,
					"Work Type" => $work_type,
					"Job Type" => $job_type,
					"Job Url" => $job_url,
	                "Job Parent Category" => $job_parent_category,
	                "Job Sub Category" => $job_sub_category
				);

		    	return $temp_data;
		    }

	    });
	    $data = $data_25;

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
                        $place_model->company = 'København kommune';
                        if ($place_model->save()) {
                            $place_inserted_id = $place_model->id;
                        }
                    }
    			}


                $job_logo = 'kobenhavn-kommune.png';

                $new_job = new Job;
                $new_job->title = $job_title;
                $new_job->seo = str_slug(str_replace($dst, $src, $job_title));
                $new_job->is_redirect = 0;
                $new_job->url = Hash::make(date('Y-m-d H:i:s'));
                $new_job->real_url = $job_url;
                $new_job->description = $job_description;
                $new_job->description_html = $job_description_html;
                $new_job->job_type_id = $job_type_id;
                $new_job->is_active = 1;
                $new_job->logo = $job_logo;
                $new_job->company = 'København kommune';
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
                                if($category) {
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
                    }
                }

    	    }

    	}

    	$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>intval($current_pagination + 1), 'updated_at'=>date('Y-m-d H:i:s')]);

		if($old) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>1, 'updated_at'=>date('Y-m-d H:i:s')]);
    	}
    }
}
