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
use GuzzleHttp\Exception\TooManyRedirectsException;
use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
class DanishCrownJob extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cronjob:DanishCrownJob';

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

		$company_name = 'Danish Crown';
		$base_url = 'https://jobs.danishcrown.com';
		$find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
		$replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];


		$cron_schedule = DB::table('cron_schedule')->where('company_name', $company_name)->first();
		$current_pagination = $cron_schedule->pagination;
		$totalpagesize = $cron_schedule->page_size;
		$checked_status = $cron_schedule->checked;

		$data = [];

		if($current_pagination == 0){
			$record = 1;
		} else {
			$record = $current_pagination * 10;
		}



		$client = new \GuzzleHttp\Client([
			// 'verify' => false
		]);
		$count = 0;
		try {
			$request = $client->get('https://jobs.danishcrown.com/search/?q=&location=DK&sortColumn=referencedate&sortDirection=desc');
			$content = $request->getBody()->getContents();
			$crawler = new Crawler( $content );


			$pagination = $crawler->filter('span.paginationLabel')->text();

			preg_match('/Results (.*) of (.*)/', $pagination, $matches);

			if(sizeof($matches) > 2){
				$count = $matches[2];
			} else {
				$count = 0;
			}

			$total_pagination = intval($count / 10);
			if ($count % 10 > 0) {
				$total_pagination = intval($count / 10) + 1;
			}

			$request = $client->get('https://jobs.danishcrown.com/search/?q=&location=DK&sortColumn=referencedate&sortDirection=desc&startrow=' . $record);
			$content = $request->getBody()->getContents();
			$crawler = new Crawler( $content );
		} catch (ClientErrorResponseException $exception) {


		} catch (TooManyRedirectsException $e){
		}

		if($count == 0){
			exit;
		}

		$data_25 = $crawler->filter('table tbody tr')->each(function ($node) use ($base_url, $client, $find_arr, $replace_arr) {
			$job_url = 	$node->filter('td .jobTitle a')->extract(['href']);
			$job_url = isset($job_url[0]) ? $base_url . $job_url[0] : '';

			$job_title = $node->filter('td .jobTitle a')->text();

			try {
				$request = $client->get($job_url);
				$content = $request->getBody()->getContents();
				$crawler = new Crawler( $content );

				$temp = $crawler->filter('.jobdescription p:nth-child(3)')->text();

				$job_created_date = '';
				if (sizeof(explode("/", $temp)) > 1) {
					$job_created_date = explode("/", $temp)[2] . "-" . explode("/", $temp)[1] . "-" . explode("/", $temp)[0];
				}


				$job_type = '';

				$address = trim($node->filter('td .jobLocation')->text(), " \t\n\r\0\x0B\xC2\xA0");

				$job_deadline_date = '';

				$work_address = $address;

				$work_city = explode(",", $address)[0];


				$job_detail_infos = '';
				$job_start_date = '';


				$work_type = '1';


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


				$job_description = $crawler->filter('.jobdescription p')->each(function ($node, $index) {
					if ($index > 6) {
						if (trim($node->text(), " \t\n\r\0\x0B\xC2\xA0") != '') {
							return $node->text();
						}
					}
				});

				$job_description_string = '';
				foreach ($job_description as $value) {
					if ($value != null) {
						$job_description_string .= $value . '<br>';
					}
				}

				$job_description_html = $crawler->filter('.jobdescription p')->each(function ($node, $index) {
					if ($index > 6) {
						if (trim($node->text(), " \t\n\r\0\x0B\xC2\xA0") != '') {
							return $node->html();
						}
					}
				});

				$job_description_html_string = '';
				foreach ($job_description_html as $value) {
					if ($value != null) {
						$job_description_html_string .= '<p>' . $value . '</p>';
					}
				}


			} catch (ClientErrorResponseException $exception) {


			} catch (TooManyRedirectsException $e){
				// Job::where('id', $value->id)->update(['is_active'=>0]);
			}

			// $crawler = Goutte::request('GET', $job_url);

			$temp_data = array(
				"Job Title" => $job_title,
				"Job Description" => $job_description_string,
				"Job Description Html" => $job_description_html_string,
				"Job Created Date" => $job_created_date,
				"Job Start Date" => '',
				"Job Deadline Date" => '',
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
		});

		$old = false;
		$place_inserted_id = 0;
		foreach ($data_25 as $value) {
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

			$job_location = 0;

			if ($work_city != '') {
				if ($work_city == 'Hovedstadsområdet') {
					$work_city = 'København';
				} else if ($work_city == 'Lyngby') {
					$work_city = 'Kongens Lyngby';
				} else if ($work_city == 'Sønderborg (Blans)'){
					$work_city = 'Sønderborg';
				}

				$city = City::where('name', '=', $work_city)->first();
				if ($city) {
					$job_location = $city->id;
				} else {
					$place_model = new Place;
					$place_model->name = $work_city;
					$place_model->company = 'Danish Crown';
					if ($place_model->save()) {
						$place_inserted_id = $place_model->id;
					}
				}
			}


			$job_logo = 'danish_crown.png';

			$new_job = new Job;
			$new_job->title = $job_title;
			$new_job->seo = str_slug(str_replace($dst, $src, $job_title));
			$new_job->is_redirect = 0;
			$new_job->url = Hash::make(date('Y-m-d H:i:s'));
			$new_job->real_url = $job_url;
			$new_job->description = strip_tags($job_description);
			$new_job->description_html = $job_description_html;
			$new_job->job_type_id = $job_type_id;
			$new_job->is_active = 1;
			$new_job->logo = $job_logo;
			$new_job->company = 'Danish Crown';
			$new_job->company_website = '';
			$new_job->city_id = $job_location;
			$created_at_temp = '';
			if ($job_created_date != '') {
				$new_job->created_at = date('Y-m-d', strtotime($job_created_date));
				$created_at_temp = date('Y-m-d', strtotime($job_created_date));
			} else {
				$new_job->created_at = date('Y-m-d');
				$created_at_temp = date('Y-m-d');
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
				} else {
					// $updated = Job::where('real_url', $job_url)->update(['created_at' => $created_at_temp]);
				}
			}
		}
		$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => intval($current_pagination + 1), 'updated_at' => date('Y-m-d H:i:s')]);
		if (intval($current_pagination) >= $total_pagination) {
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['checked' => intval($checked_status + 1)]);
			$updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
		}

	}
}
