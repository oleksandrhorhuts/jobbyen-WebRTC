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
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;


use Illuminate\Support\Facades\Hash;

class SallingGroupJob extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cronjob:SallingGroupJob';

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
		$company_name = 'Salling Group';
		$base_url = 'https://sallinggroup.com';

		$goutte = new Client();
		// $goutte->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $goutte->request('GET', $base_url);
		$logo = '';
		$response_data = [];
		$find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
		$replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

		$cron_schedule = DB::table('cron_schedule')->where('company_name', $company_name)->first();
		$current_pagination = $cron_schedule->pagination;
		$totalpagesize = $cron_schedule->page_size;
		$checked_status = $cron_schedule->checked;

		$i = $current_pagination;

		$data = json_decode(file_get_contents('https://sallinggroup.com/umbraco/api/jobsapi/search?page=' . $i . '&language=da'), true);
		foreach ($data['jobs'] as $key => $value) {
			$job_created_date = $value['displayDate'];

			$job_title = $value['title'];
			$work_address = ($value['address']) ? implode(', ', array_values($value['address'])) : '';
			$work_city = $value['address']['city'];
			$work_country = $value['address']['country'];
			if ($work_country != 'DK')
				continue;
			$job_url = trim("https://sallinggroup.com/job/ledige-stillinger/job/?id=" . $value['id']);
			$goutte = new Client();
			// $goutte->setClient(new GuzzleClient(array('verify' => false)));
			$crawler = $goutte->request('GET', $job_url);
			$job_description = '';
			$temp = $crawler->filter('div.jobpage__content p')->each(function ($node) {
				return $node->text();
			});
			foreach ($temp as $value) {
				$job_description .= $value . '<br>';
			}

			$job_description_html = $crawler->filter('div.jobpage__content')->html();
			$job_description_html = preg_replace("/<h1>.*<\/h1>/", "", $job_description_html);
			$job_description_html = preg_replace("/<a.*<\/a>/", "", $job_description_html);

			$job_detail_info = $crawler->filter('tr.jobpage-description__row td')->each(function ($node) {
				return $node->html();
			});

			$job_start_date = '';
			if ($job_detail_info[4] == 'Startdato') {
				$job_start_date = $job_detail_info[5];
			}

			$job_category_url = "https://www.jobsearch.dk/jobdatabase?searchText=" . $job_title;
			$goutte = new Client();
			// $goutte->setClient(new GuzzleClient(array('verify' => false)));
			$category_crawler = $goutte->request('GET', $job_category_url);


			$job_parent_category = '';
			$job_sub_category = '';

			if ($category_crawler->filter('a.read-more')->count()) {
				$job_category = $category_crawler->filter('a.read-more')->extract(array('href'));
				if (isset($job_category[0])) {
					$categoryArr = explode('/', $job_category[0]);
					$job_parent_category = $categoryArr[2];
					$job_sub_category = $categoryArr[3];
				}
			}

			$temp_data = array(
				"Job Title" => $job_title,
				"Job Description" => $job_description,
				"Job Description Html" => $job_description_html,
				"Job Created Date" => str_replace($find_arr, $replace_arr, $job_created_date),
				"Job Start Date" => $job_start_date,
				"Job Deadline Date" => '',
				"Company Logo" => $logo,
				"Work address" => $work_address,
				"Work City" => $work_city,
				"Work Type" => '',
				"Job Type" => '',
				"Job Url" => $job_url,
				"Company Name" => $company_name,
				"Job Parent Category" => $job_parent_category,
				"Job Sub Category" => $job_sub_category
			);
			$response_data[] = $temp_data;
		}
		if (!$data['hasNextPage']) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1]);
			exit;
		}


		$old = false;
		$place_inserted_id = 0;
		foreach ($response_data as $value) {
			$work_city = $value['Work City'];
			$job_title = $value['Job Title'];
			$job_url = $value['Job Url'];
			$job_description = $value['Job Description'];
			$job_description_html = $value['Job Description Html'];
			$job_created_date = $value['Job Created Date'];
			$job_start_date = $value['Job Start Date'];
			$job_parent_category = $value['Job Parent Category'];
			$job_sub_category = $value['Job Sub Category'];


			$job_location = 0;

			if ($work_city != '') {
				if ($work_city == 'Hovedstadsområdet') {
					$work_city = 'København';
				} else if ($work_city == 'Lyngby') {
					$work_city = 'Kongens Lyngby';
				} else if ($work_city == 'Frederiksbjerg C') {
					$work_city = 'Århus C';
				} else if ($work_city == 'Aarhus C') {
					$work_city = 'Århus C';
				} else {
				}
				$city = City::where('name', '=', $work_city)->first();
				if ($city) {
					$job_location = $city->id;
				} else {
					$place_model = new Place;
					$place_model->name = $work_city;
					$place_model->company = 'Salling Group';
					if ($place_model->save()) {
						$place_inserted_id = $place_model->id;
					}
				}
			}


			$job_logo = 's-group_logo.svg';

			$new_job = new Job;
			$new_job->title = $job_title;
			$new_job->seo = str_slug(str_replace($dst, $src, $job_title));
			$new_job->is_redirect = 0;
			$new_job->url = Hash::make(date('Y-m-d H:i:s'));
			$new_job->real_url = $job_url;
			$new_job->description = strip_tags($job_description);
			$new_job->description_html = $job_description_html;
			$new_job->job_type_id = 1;
			$new_job->is_active = 1;
			$new_job->logo = $job_logo;
			$new_job->company = 'Salling Group';
			$new_job->company_website = '';
			$new_job->city_id = $job_location;
			$new_job->created_at = date('Y-m-d', strtotime($job_created_date));
			$new_job->job_start_date = $job_start_date;
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
					//$updated = Job::where('real_url', $job_url)->update(['job_start_date'=>$job_start_date, 'description'=>$job_description]);
					// $old = true;
				}
			}
		}


		$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => intval($current_pagination + 1), 'updated_at' => date('Y-m-d H:i:s')]);
		if (intval($current_pagination) == $totalpagesize) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['checked' => intval($checked_status + 1)]);
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
		}
	}
}
