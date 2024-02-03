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
class VejleKommuneJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:VejleKommuneJob';

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

        $company_name = 'Vejle kommune';
	   	$base_url = 'https://www.vejle.dk';

	    $data = [];
    	$page = 1;

        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
    	$crawler = $goutte->request('GET', 'https://www.vejle.dk/job/job-hos-os/ledige-job/#?type=');
		$logo=$crawler->filter('.navigation-desktop-logo img')->extract(["src"]);
        $logo = isset($logo[0]) ? $base_url . $logo[0] : '';

		$client = new Client();
        // $client->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $client->request('GET', 'https://www.vejle.dk/umbraco/api/ListSearchApi/SearchStilling?limit=100&offset=0&type=');

		$jobs_data=json_decode($client->getResponse()->getContent());

		$total_pagination = sizeof($jobs_data->data);

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



	    foreach ($jobs_data->data as $index=>$job_data)
	    {
	        if($current_pagination - 1 < $index && $current_pagination + 2 >=$index)
            {

                $job_url=$base_url.$job_data->url;
                $this->info('URL :'.$job_url);
                $goutte = new Client();
                // $goutte->setClient(new GuzzleClient(array('verify' => false)));
    			$crawler = $goutte->request('GET', $job_url);
    			$job_description=trim(strip_tags($crawler->filter(".grid-rte")->text()));

                $job_description = preg_replace("/\r|\n/", "", $job_description);
                $job_description = preg_replace("/\t/", " ", $job_description);
                $job_description = trim($job_description, " \t\n\r\0\x0B\xC2\xA0");

    			$job_description_html=$crawler->filter(".grid-rte")->html();

                $job_description_html = preg_replace("/\r|\n/", "", $job_description_html);
                $job_description_html = preg_replace("/\t/", " ", $job_description_html);
                $job_description_html = trim($job_description_html, " \t\n\r\0\x0B\xC2\xA0");

    			$job_created_date = date("Y-m-d",strtotime($job_data->createDate));

    	    	$job_deadline_date = date("Y-m-d",strtotime($job_data->ansoegningsfrist));

    	    	$job_parent_category = '';
    			$job_sub_category = '';
                $job_category_url = "https://www.jobsearch.dk/jobdatabase?searchText=".$job_data->title;
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

    	    	$temp_data = array(
    	    	    "Job Title" => $job_data->title,
    				"Job Description" => $job_description,
    				"Job Description Html" => $job_description_html,
    				"Job Created Date" => $job_created_date,
    				"Job Start Date" => '',
    				"Job Deadline Date" => $job_deadline_date,
    				"Company Logo" => $logo,
    				"Work address" => '',
    				"Work City" => 'Vejle',
    				"Work Type" => '',
    				"Job Type" => 1,
    				"Job Url" => $job_url,
                    "Job Parent Category" =>$job_parent_category,
                    "Job Sub Category" => $job_sub_category
    			);
    	    	$data_25[] = $temp_data;
            }
	    }
	    $data = array_merge($data, $data_25);
	    $old = false;
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

                    $city = City::where('name', '=', $work_city)->first();
                    if($city)
                    {
                        $job_location = $city->id;
                    }
                    else
                    {

                    }
    			}

                $job_logo = 'vejle-kommune.png';

                $new_job = new Job;
                $new_job->title = $job_title;
                $new_job->seo = str_slug(str_replace($dst, $src, $job_title));
                $new_job->is_redirect = 0;
                $new_job->url = Hash::make(date('Y-m-d H:i:s'));
                $new_job->real_url = $job_url;
                $new_job->description = $job_description;
                $new_job->description_html = $job_description_html;
                $new_job->job_type_id = 10;
                $new_job->is_active = 1;
                $new_job->logo = $job_logo;
                $new_job->company = 'Vejle kommune';
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
						$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>1, 'updated_at'=>date('Y-m-d H:i:s')]);
                    }
                }

    	    }

    	}

    	$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>intval($current_pagination + 3), 'updated_at'=>date('Y-m-d H:i:s')]);
    	if($old) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination'=>1, 'updated_at'=>date('Y-m-d H:i:s')]);
    	}

    }
}
