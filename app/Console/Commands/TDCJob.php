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
use Symfony\Component\DomCrawler\Crawler;

use Illuminate\Support\Facades\Hash;

class TDCJob extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cronjob:TDCJob';

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
		$company_name = 'TDC';
		$base_url = 'https://tdcgroup.com';
		$find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
		$replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

		$data = [];
		$page = 1;

		$goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $goutte->request('GET', 'https://tdcgroup.com/da/career/ledige-jobs');
		$pagination = $crawler->filter('div.joblist__container p')->text();
		preg_match('/af (.*) resultater/', $pagination, $matches);
		$count = $matches[1];

		$total_pagination = intval($count / 10);
		if ($count % 10 > 0) {
			$total_pagination = intval($count / 10) + 1;
		}

		$this->info('Total pagination :'.$total_pagination);
		$this->info('===========================================');

		$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['page_size' => intval($total_pagination)]);

		$cron_schedule = DB::table('cron_schedule')->where('company_name', $company_name)->first();
		$current_pagination = $cron_schedule->pagination;
		$totalpagesize = $cron_schedule->page_size;
		$checked_status = $cron_schedule->checked;


		if ($totalpagesize <= $current_pagination) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['checked' => intval($checked_status + 1)]);
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1]);
			$current_pagination = 1;
		}

		$goutte = new Client();
		// $goutte->setClient(new GuzzleClient(array('verify' => false)));
		$crawler = $goutte->request('GET', 'https://tdcgroup.com/da/career/ledige-jobs?page=' . $current_pagination);
		$logo = $crawler->filter('.header__logoimage')->first()->extract(['src']);
		$logo = isset($logo[0]) ? $base_url . $logo[0] : '';

		$data_25 = $crawler->filter('table tbody tr.joblist__row')->each(function ($node, $index) use ($base_url, $logo, $find_arr, $replace_arr) {
			$job_title = $node->filter('td:nth-child(2) h4')->text();
			$work_city = $node->filter('td:nth-child(3)')->text();
			$job_deadline_date = date('Y-m-d', strtotime($node->filter('td:nth-child(4)')->text()));
			$job_created_date = date('Y-m-d');
			$job_url = $node->filter('td:nth-child(5) a')->extract(['href']);
			$job_url = isset($job_url[0]) ? $job_url[0] : '';

			// $crawler = Goutte::request('GET', $job_url);
			// $text = $crawler->filter('body')->text();
			// // echo $text;
			// preg_match('/\"token\":\"(.*)\",\"debug\"/', $text, $matches);
			// $token = null;
			// if(sizeof($matches) > 1){
			// 	$token = $matches[1];
			// }

			// echo '---------';
			// echo $token;
			// exit;

			$job_id = 0;
			$slashArr = explode("/", $job_url);
			$jobId = $slashArr[sizeof($slashArr) - 1];
			$jobIds = explode("?", $jobId);
			if (sizeof($jobIds) > 1) {
				$job_id = $jobIds[0];
			}
			$job_description_html = '';
			$job_description = '';

			if ($job_id) {


				$job_desc_url = 'https://tdc.csod.com/Services/API/ATS/CareerSite/2/JobRequisitions/' . $job_id . '?useMobileAd=false&cultureId=26';

				$headers = [
					'Content-Type' => 'application/json',
					'cookie' => 'ASP.NET_SessionId=vfffbr0iawzl1lgtrunt2l0g; cscx=tdc|-101|26|1|sW55FoD7cWYJNvrrJ5wfwjQXHjyRi7wtiXzdODPTtBI=',
					// 'Authorization' => 'Bearer '.$token,
					'Authorization' => 'Bearer eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCIsImNsaWQiOiIxdG05NmgzNWpmYzhiIn0.eyJzdWIiOi0xMDEsImF1ZCI6InZmZmZicjBpYXd6bDFsZ3RydW50MmwwZyIsImNvcnAiOiJ0ZGMiLCJjdWlkIjoyNiwidHppZCI6MSwibmJkIjoiMjAyMDEwMjcyMjE1MDg2MjEiLCJleHAiOiIyMDIwMTAyNzIzMTYwODYyMSIsImlhdCI6IjIwMjAxMDI3MjIxNTA4NjIxIn0.D5FPklmw5RPZTtS7Uq8h-3xx3eA0KmC9N9LPZ8usxH5_a9-wYyrbp4JYLLRf9d1Owrgxf11UeFm1kk4zZ3J4Ew'

				];
				$client = new GuzzleClient([
					// 'verify'=>false,
					'headers' => $headers
				]);


				$r = $client->request('GET', $job_desc_url);
				$jobs_data = json_decode($r->getBody()->getContents());
				$job_description_html = $jobs_data->data[0]->items[0]->fields->ad;


				$crawler = new Crawler($job_description_html);
				// $data = $crawler->filter('div.card--post')
				$desc = $crawler->filter('p')->each(function ($node, $index) {
					return $node->text();
				});

				$job_description = '';
				foreach ($desc as $k => $v) {
					$job_description .= $v;
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
				"Job Created Date" => $job_created_date,
				"Job Start Date" => '',
				"Job Deadline Date" => $job_deadline_date,
				"Company Logo" => $logo,
				"Work address" => '',
				"Work City" => $work_city,
				"Work Type" => '',
				"Job Type" => 1,
				"Job Url" => $job_url,
				"Job Parent Category" => $job_parent_category,
				"Job Sub Category" => $job_sub_category
			);
			return $temp_data;
		});



		$old = false;
		$place_inserted_id = 0;
		foreach ($data_25 as $value) {
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
					} else if ($work_city == 'København eller Aarhus') {
						$work_city = 'København';
					} else if ($work_city == 'storkøbenhavn') {
						$work_city = 'København';
					} else if ($work_city == 'Sjælland – Nord') {
						$work_city = 'Region Sjælland';
					}
					$city = City::where('name', '=', $work_city)->first();
					if ($city) {
						$job_location = $city->id;
					} else {
						$place_model = new Place;
						$place_model->name = $work_city;
						$place_model->company = 'TDC';
						if ($place_model->save()) {
							$place_inserted_id = $place_model->id;
						}
					}
				}


				$job_logo = 'tdcgroup_logo.png';

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
				$new_job->company = 'TDC';
				$new_job->company_website = '';
				$new_job->city_id = $job_location;
				$new_job->created_at = $job_created_date;
				$new_job->job_deadline_date = $job_deadline_date;
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
						Job::where('real_url', $job_url)->update(['description' => $job_description, 'description_html' => $job_description_html]);
					}
				}
			}
		}

		$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => intval($current_pagination + 1), 'updated_at' => date('Y-m-d H:i:s')]);
		if ($old) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
		}
	}
}
