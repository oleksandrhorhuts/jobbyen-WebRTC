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
use App\JobLocation;
use Illuminate\Support\Facades\Hash;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;

class LidlJob extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cronjob:LidlJob';

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
		$company_name = 'Lidl';
		$base_url = 'https://karriere.lidl.dk';
		$find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
		$replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];


		$cron_schedule = DB::table('cron_schedule')->where('company_name', $company_name)->first();
		$current_pagination = $cron_schedule->pagination;
		$totalpagesize = $cron_schedule->page_size;
		$checked_status = $cron_schedule->checked;

		$data = [];

		$client = new Client();
		// $client->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $client->request('GET', 'https://karriere.lidl.dk/search_api/jobsearch?page=' . $current_pagination . '&filter={%22contract_type%22:[],%22employment_area%22:[],%22entry_level%22:[]}&min_lat=null&min_lon=null&max_lat=null&max_lon=null&with_event=true');
		$jobs_data = json_decode($client->getResponse()->getContent());

		$jobs = $jobs_data->result->hits;

		if (sizeof($jobs) == 0) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['checked' => intval($checked_status + 1)]);
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1]);
			$current_pagination = 1;

			$crawler = $client->request('GET', 'https://karriere.lidl.dk/search_api/jobsearch?page=' . $current_pagination . '&filter={%22contract_type%22:[],%22employment_area%22:[],%22entry_level%22:[]}&min_lat=null&min_lon=null&max_lat=null&max_lon=null&with_event=true');
			$jobs_data = json_decode($client->getResponse()->getContent());
			$jobs = $jobs_data->result->hits;
		}




		$data25 = [];
		foreach ($jobs as $index => $job) {

			$jobId = $job->jobId;
			$job_title = $job->title;
			$job_url = $job->url;

			$job_url = 'https://karriere.lidl.dk' . $job_url;


			$work_city = '';
			$work_address = '';
			if ($job->location) {
				$work_city = $job->location->city;
				$work_address = $job->location->address;
			}

			$goutte = new Client();
			// $goutte->setClient(new GuzzleClient(array('verify' => false)));
			$crawler = $goutte->request('GET', $job_url);

			$job_description_html = $crawler->filter('.oJobDescription-textContainer')->html();

			$job_description = strip_tags($job_description_html);

			$job_start_date = '';
			if ($crawler->filter('.oJobDescription-jobInfo .oJobDescription-entryDate')->count()) {
				if ($crawler->filter('.oJobDescription-jobInfo .oJobDescription-entryDate .headline')->text() == 'Startdato') {
					$job_start_date = $crawler->filter('.oJobDescription-jobInfo .oJobDescription-entryDate .oJobDescription-detailInfo')->text();
				}
			}


			$job_parent_category = '';
			$job_sub_category = '';
			$job_category_url = "https://www.jobsearch.dk/jobdatabase?searchText=" . $job_title;
			$goutte = new Client();
			// $goutte->setClient(new GuzzleClient(array('verify' => false)));
			$category_crawler = $goutte->request('GET', $job_category_url);

			if ($category_crawler->filter('a.read-more')->count()) {
				$job_category = $category_crawler->filter('a.read-more')->extract(['href']);
				if(isset($job_category[0])){
					$categoryArr = explode('/', $job_category[0]);
					$job_parent_category = $categoryArr[2];
					$job_sub_category = $categoryArr[3];
				}
			}

			$temp_data = array(
				"Job Title" => $job_title,
				"Job Description" => $job_description,
				"Job Description Html" => $job_description_html,
				"Job Created Date" => '',
				"Job Start Date" => $job_start_date,
				"Job Deadline Date" => '',
				"Company Logo" => '',
				"work_address" => $work_address,
				"Work City" => $work_city,
				"Work Type" => 1,
				"Job Type" => 1,
				"Job Url" => $job_url,
				"Job Parent Category" => $job_parent_category,
				"Job Sub Category" => $job_sub_category
			);
			$data25[] = $temp_data;
		}


		// $data_25 = $crawler->filter('article a.link-vacancy')->each(function ($node) use ($base_url, $logo, $find_arr, $replace_arr) {
		// 	$job_url = $base_url.$node->attr('href');
		// 	$crawler = Goutte::request('GET', $job_url);
		// 	$temp = $crawler->filter('section.apply-online-box ul > li')->each(function($node){
		// 		return trim($node->text());
		// 	});
		// 	$job_title = $crawler->filter('section.apply-online-box h1')->first()->text();
		// 	$job_created_date = $temp[0];
		// 	$job_type = $temp[1];
		// 	$address = array_map('trim',explode(',',$temp[2]));
		// 	$work_address = $address[0];
		// 	$work_city = (count($address) > 1) ? $address[1]:'';

		// 	$temp = $crawler->filter('aside.span2 > article > table > tr td')->each(function($node){
		// 		return $node->text();
		// 	});

		// 	foreach($temp as $key=>$value)
		// 	{
		// 	    if($key % 2 == 0)
		// 	        $keys[] = trim($value);
		// 	    if($key % 2 == 1)
		// 	        $vals[] = $value;
		// 	}
		// 	$job_detail_infos = array_combine($keys, $vals);
		// 	$job_start_date = '';
		// 	$job_deadline_date = '';
		// 	$work_type = 1;
		// 	foreach($job_detail_infos as $key=>$value)
		// 	{
		// 	    if($key == 'Tiltrædelsesdato:')
		// 	    {
		// 	        $job_start_date = $value;
		// 	    }
		// 	    if($key == 'Ansættelsesform:')
		// 	    {
		// 	        if($value == 'Deltid')
		// 	        {
		// 	            $work_type = 2;
		// 	        }
		// 	        else if($value == 'Fuldtid')
		// 	        {
		// 	            $work_type = 1;
		// 	        }
		// 	        else{
		// 	            $work_type = 1;
		// 	        }

		// 	    }
		// 	}
		// 	$job_parent_category = '';
		// 	$job_sub_category = '';
		//     $job_category_url = "https://www.jobsearch.dk/jobdatabase?searchText=".$job_title;
		//     $category_crawler = Goutte::request('GET', $job_category_url);

		// 	if($category_crawler->filter('a.read-more')->count())
		// 	{
		// 	    $job_category = $category_crawler->filter('a.read-more')->attr('href');
		// 	    $categoryArr = explode('/', $job_category);
		// 	    $job_parent_category = $categoryArr[2];
		// 	    $job_sub_category = $categoryArr[3];
		// 	}

		// 	$job_description = $crawler->filter('section.widestcontent p:nth-child(2)')->text();

		// 	$job_description_html = $crawler->filter('section.widestcontent .vacancy-features')->each(function($node){
		// 		return $node->html();
		// 	});

		// 	$job_description_html_string = '';
		// 	foreach($job_description_html as $value)
		// 	{

		// 	    $value = preg_replace('/<img alt="Om jobbet" src="(.*)">/', '<img alt="Om jobbet" src="https://karriere.lidl.dk$1" class="lidl_img">', $value);
		// 	    $value = preg_replace('/<img alt="Om dig" src="(.*)">/', '<img alt="Om dig" src="https://karriere.lidl.dk$1" class="lidl_img">', $value);
		// 	    $value = preg_replace('/<img alt="Vi tilbyder" src="(.*)">/', '<img alt="Vi tilbyder" src="https://karriere.lidl.dk$1" class="lidl_img">', $value);

		// 	    $job_description_html_string.=$value;
		// 	}


		// 	$temp_data = array(
		// 	    "Job Title" => $job_title,
		// 		"Job Description" => $job_description,
		// 		"Job Description Html" => $job_description_html_string,
		// 		"Job Created Date" => $job_created_date,
		// 		"Job Start Date" => str_replace($find_arr, $replace_arr, $job_start_date),
		// 		"Job Deadline Date" => $job_deadline_date,
		// 		"Company Logo" => $logo,
		// 		"Work address" => $work_address,
		// 		"Work City" => $work_city,
		// 		"Work Type" => $work_type,
		// 		"Job Type" => $job_type,
		// 		"Job Url" => $job_url,
		//         "Job Parent Category" => $job_parent_category,
		//         "Job Sub Category" => $job_sub_category
		// 	);
		// 	return $temp_data;
		// });

		// $data = array_merge($data, $data_25);

		$old = false;
		$place_inserted_id = 0;
		foreach ($data25 as $value) {
			$work_city = $value['Work City'];
			$job_title = $value['Job Title'];
			$job_url = $value['Job Url'];
			$job_description = $value['Job Description'];
			$job_description_html = $value['Job Description Html'];
			$job_created_date = $value['Job Created Date'];
			$job_start_date = $value['Job Start Date'];
			$job_parent_category = $value['Job Parent Category'];
			$job_sub_category = $value['Job Sub Category'];
			$job_type_id = $value['Work Type'];
			$work_address = $value['work_address'];

			$job_location = 0;

			if ($work_city != '') {
				if ($work_city == 'Hovedstadsområdet') {
					$work_city = 'København';
				} else if ($work_city == 'Lyngby') {
					$work_city = 'Kongens Lyngby';
				} else if ($work_city == 'København V.') {
					$work_city = 'København V';
				}

				$city = City::where('name', '=', $work_city)->first();
				if ($city) {
					$job_location = $city->id;
				} else {
					$place_model = new Place;
					$place_model->name = $work_city;
					$place_model->company = $company_name;
					if ($place_model->save()) {
						$place_inserted_id = $place_model->id;
					}
				}
			}


			$job_logo = 'LIDLelskerDK.png';

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
			$new_job->company = $company_name;
			$new_job->company_website = '';
			$new_job->city_id = $job_location;
			$new_job->created_at = date('Y-m-d');

			$new_job->address = $work_address;

			if ($job_start_date != '') {
				$new_job->job_start_date = date('Y-m-d', strtotime($job_start_date));
			}

			$new_job->created_on = date('Y-m-d');

			if ($job_title != '') {
				$existing_job = Job::where('real_url', $job_url)->first();

				if (!$existing_job) {
					if ($new_job->save()) {
						$inserted_id = $new_job->id;
						if ($place_inserted_id) {
							Place::where('id', $place_inserted_id)->update(['job_id' => $inserted_id]);
						}
						if ($job_sub_category != '') {
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
				} else {
					$updated = Job::where('real_url', $job_url)->update(['description_html' => $job_description_html]);
					$old = true;
				}
			}
		}
		$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => intval($current_pagination + 1), 'updated_at' => date('Y-m-d H:i:s')]);
	}
}
