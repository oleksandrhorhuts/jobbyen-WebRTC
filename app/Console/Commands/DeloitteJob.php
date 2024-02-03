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

class DeloitteJob extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cronjob:DeloitteJob';

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

		$company_name = 'Deloitte';
		$base_url = 'https://deloitte.recman.dk/';
		$find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
		$replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
		$data = [];

		$goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $goutte->request('GET', 'https://deloitte.recman.dk/');

		$total_pagination = $crawler->filter('#job-post-listing-box .box')->each(function ($node, $index) use ($base_url) {
			return $index;
		});


		$total_pagination = $total_pagination[sizeof($total_pagination) - 1];
		$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['page_size' => intval($total_pagination + 1)]);

		$cron_schedule = DB::table('cron_schedule')->where('company_name', $company_name)->first();
		$current_pagination = $cron_schedule->pagination;
		$totalpagesize = $cron_schedule->page_size;
		$checked_status = $cron_schedule->checked;


		if ($totalpagesize <= $current_pagination) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['checked' => intval($checked_status + 1)]);
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1]);
			$current_pagination = 1;
		}

		$temp_data = $crawler->filter('#job-post-listing-box .box')->each(function ($node, $index) use ($base_url, $current_pagination, $find_arr, $replace_arr) {
			if ($current_pagination - 1 <= $index && $current_pagination + 2 > $index) {
				$job_url = $node->extract(['onclick']);
				$job_url = isset($job_url[0]) ? $job_url[0] : '';
				$url = str_replace("'", "", $job_url);
				$job_url = $base_url . str_replace("location.href=", "", $url);

				$goutte = new Client();
				// $goutte->setClient(new GuzzleClient(array('verify' => false)));
				$crawler = $goutte->request('GET', $job_url);
				$temp = $crawler->filter('table.job_description tr td:nth-child(2)')->each(function ($node) {
					return trim($node->text());
				});

				$temp_name = $crawler->filter('table.job_description tr td:nth-child(1)')->each(function ($node) {
					return trim($node->text());
				});

				$job_deadline_idx = 0;
				$job_type_idx = 0;
				foreach ($temp_name as $key => $val) {
					if ($val == 'Application deadline') {
						$job_deadline_idx = $key;
					}

					if ($val == 'Extent') {
						$job_type_idx = $key;
					}
				}


				$job_title = trim($crawler->filter('h1.colored')->first()->text(), " \t\n\r\0\x0B\xC2\xA0");

				$job_created_date = '';
				if (isset($temp[12]))
					$job_type = $temp[12];
				else
					$job_type = '';

				$address = $temp[0] . ", " . $temp[1] . ", " . $temp[2];
				$work_address = $temp[1] . $temp[2];


				$work_city_arr = explode(' ', $temp[2]);

				$work_city = '';
				foreach ($work_city_arr as $key => $value) {
					if ($key > 0) {
						$work_city .= $value . ' ';
					}
				}
				$work_city = substr($work_city, 0, strlen($work_city) - 1);


				$job_start_date = $temp[7];
				$job_deadline_date = $temp[$job_deadline_idx];



				$work_type = $temp[$job_type_idx];

				$job_type = $temp[$job_type_idx];

				if ($job_type == 'Temporary') {
					$job_type = 4;
				} else if ($job_type == 'Permanent') {
					$job_type = 5;
				} else {
					$job_type = 1;
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

				$job_description = trim($crawler->filter('#job_body')->text(), " \t\n\r\0\x0B\xC2\xA0");

				$job_description_html = $crawler->filter('#job_body')->each(function ($node) {
					return $node->html();
				});
				$job_description_html = $job_description_html[0];


				$temp_data = array(
					"Job Title" => $job_title,
					"Job Description" => $job_description,
					"Job Description Html" => $job_description_html,
					"Job Created Date" => '',
					"Job Start Date" => '',
					"Job Deadline Date" => date('Y-m-d', strtotime(str_replace($find_arr, $replace_arr, $job_deadline_date))),
					"Company Logo" => '',
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

		$old = false;
		$place_inserted_id = 0;

		foreach ($temp_data as $value) {
			if (isset($value['Job Title']) && $value['Job Title'] != '') {
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

				if ($work_city != '') {
					if ($work_city == 'Hovedstadsområdet') {
						$work_city = 'København';
					} else if ($work_city == 'Lyngby') {
						$work_city = 'Kongens Lyngby';
					} else if ($work_city == 'Aarhus') {
						$work_city = 'Århus';
					}

					$city = City::where('name', '=', $work_city)->first();
					if ($city) {
						$job_location = $city->id;
					} else {
						$place_model = new Place;
						$place_model->name = $work_city;
						$place_model->company = 'Deloitte';
						if ($place_model->save()) {
							$place_inserted_id = $place_model->id;
						}
					}
				}


				$job_logo = 'Deloitte.png';

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
				$new_job->company = 'Deloitte';
				$new_job->company_website = '';
				$new_job->city_id = $job_location;
				$new_job->created_at = date('Y-m-d');

				if($job_deadline_date!='' && $job_deadline_date!='1970-01-01'){
					$new_job->job_deadline_date = $job_deadline_date;
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
					} else {
						if($job_deadline_date!=''){
							if($job_deadline_date == '1970-01-01'){
								$updated = Job::where('real_url', $job_url)->update(['job_deadline_date'=>NULL]);
							}
						}
						// $old = true;
					}
				}
			}
		}


		$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => intval($current_pagination + 3), 'updated_at' => date('Y-m-d H:i:s')]);
		if ($old) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
		}
	}
}
