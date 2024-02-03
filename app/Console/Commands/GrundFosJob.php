<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte;
use App\Job;
use App\Place;
use App\City;
use File;
use App\JobCategory;
use App\Category;
use Goutte\Client;
use App\JobLocation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DomCrawler\Crawler;

class GrundFosJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:GrundFosJob';

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
        $this->headers = [
            'Accept' => '*',
            'User-Agent' => '*/*',
        ];

        $this->client = new GuzzleClient();
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

        $company_name = 'GRUNDFOS';
        $base_url = 'https://www.grundfos.com';


        $search_link = 'https://www.grundfos.com/about-us/career/jobs/_jcr_content/par/columns2_6395/column0/par/category_search_9fe1.search_results.html';
        $this->info('cron executed');
        try {
            $r = $this->client->get($search_link, [
                // 'debug' => true,
                // 'headers' => $this->headers,
                // 'port' => 443,
                // 'timeout' => 30,
                // 'connect_timeout' => 30
                'query' => [
                    '_charset' => 'UTF-8',
                    'country' => '10134',
                    'searchText' => ''
                ],
            ]);
            $contents = $r->getBody()->getContents();
            $this->info($r->getStatusCode());
            $crawler = new Crawler($contents);
            $total_pagination = $crawler->filter('.search-result .pagerItem')->count();
            $this->info('total pagination : '.$total_pagination);

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
            $data_25 = $crawler->filter('div.search-result div.pagerItem a')->each(function ($node, $index) use ($base_url, $current_pagination) {
                if ($current_pagination - 1 <= $index && $current_pagination + 3 > $index) {
                    $job_url = $node->extract(['href']);
                    $job_url = isset($job_url[0]) ? $base_url . $job_url[0] : '';
                    $job_title = $node->text();
                    // $crawler = Goutte::request('GET', $job_url);
                    $this->info($job_url);
                    $this->info('==================');
                    $this->info($job_title);
                    $this->info('==================');

                    $job_query_arr = explode("?", $job_url);
                    $job_query = $job_query_arr[count($job_query_arr) - 1];

                    $jobId = null;
                    $queryArr = preg_match_all("/jobid=(.*)/", $job_query, $matches);
                    if ($matches[1]) {
                        if ($matches[1][0]) {
                            $jobId = $matches[1][0];
                        }
                    }
                    $this->info('job id :'.$jobId);
                    $this->info('==================');

                    $job_response = $this->client->get($job_url, [
                        // 'headers' => $this->headers,
                        'query' => [
                            'jobid' => $jobId,
                        ],
                    ]);
                    $contents = $job_response->getBody()->getContents();
                    $job_crawler = new Crawler($contents);

                    $temp_infos = $job_crawler->filter('.pertinent-info ul li')->each(function ($node) {
                        if (strpos($node->text(), "Deadline:") !== false) {
                            return ['job_deadline_date' => $node->text()];
                        }
                        if (strpos($node->text(), "Location:") !== false) {
                            return ['location' => $node->text()];
                        }
                    });

                    $data_infos = array_values(array_filter($temp_infos));

                    $job_detail_infos = [];

                    foreach ($data_infos as $k => $v) {
                        $job_detail_infos[array_keys($v)[0]] = $v[array_keys($v)[0]];
                    }

                    $job_deadline_date = '';
                    if (isset($job_detail_infos['job_deadline_date'])) {
                        $dd_date = $job_detail_infos['job_deadline_date'];
                        preg_match_all('/Deadline: (.+)/', $dd_date, $ddStr);
                        if ($ddStr[1]) {
                            if ($ddStr[1][0]) {
                                $getDeadline = $ddStr[1][0];
                                $job_deadline_date = $getDeadline;
                            }
                        }
                    }
                    $this->info($job_deadline_date);
                    $this->info('==================');

                    $work_city = '';
                    if (isset($job_detail_infos['location'])) {
                        $location = $job_detail_infos['location'];
                        $location = trim($location, " \t\n\r\0\x0B\xC2\xA0");
                        preg_match_all('/Location: (.+)/', $location, $locationStr);
                        if ($locationStr[1]) {
                            if ($locationStr[1][0]) {
                                $getLocation = $locationStr[1][0];
                                $locationStrArr = explode(",", $getLocation);
                                $work_city = $locationStrArr[0];
                            }
                        }
                    }
                    $this->info($work_city);
                    $this->info('==================');

                    $job_crawler->filter('div.opportunity-details .pertinent-info')->each(function (Crawler $crawler) {
                        foreach ($crawler as $node) {
                            $node->parentNode->removeChild($node);
                        }
                    });

                    $job_crawler->filter('div.opportunity-details .btn-group')->each(function (Crawler $crawler) {
                        foreach ($crawler as $node) {
                            $node->parentNode->removeChild($node);
                        }
                    });

                    $job_description_html = $job_crawler->filter('div.opportunity-details')->html();
                    $job_description_html = trim($job_description_html, " \t\n\r\0\x0B\xC2\xA0");
                    $this->info($job_description_html);
                    $job_description = strip_tags($job_description_html);

                    $job_parent_category = '';
                    $job_sub_category = '';
                    $job_category_url = "https://www.jobsearch.dk/jobdatabase?searchText=" . $job_title;

                    $goutte = new Client();
                    // $goutte->setClient(new GuzzleClient(array('verify' => false)));
                    $category_crawler = $goutte->request('GET', $job_category_url);

                    if ($category_crawler->filter('a.read-more')->count()) {
                        $job_category=$category_crawler->filter('a.read-more')->extract(['href']);
                        if(isset($job_category[0])){
                            $categoryArr = explode('/', $job_category[0]);
                            $job_parent_category = $categoryArr[2];
                            $job_sub_category = $categoryArr[3];
                        }
                    }

                    return array(
                        "Job Title" => $job_title,
                        "Job Description" => $job_description,
                        "Job Description Html" => $job_description_html,
                        "Job Created Date" => '',
                        "job_start_date" => '',
                        "Job Deadline Date" => $job_deadline_date,
                        "Company Logo" => '',
                        "Work address" => '',
                        "Work City" => $work_city,
                        "Work Type" => 1,
                        "Job Type" => 1,
                        "Job Url" => $job_url,
                        "Job Parent Category" => $job_parent_category,
                        "Job Sub Category" => $job_sub_category,
                    );
                }
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

                    $job_deadline_date = $value['Job Deadline Date'];
                    $job_parent_category = $value['Job Parent Category'];
                    $job_sub_category = $value['Job Sub Category'];
                    $job_type_id = $value['Work Type'];

                    $job_location = 0;

                    if ($work_city != '') {

                        if ($work_city == 'Hovedstadsområdet') {
                            $work_city = 'København';
                        } else if ($work_city == 'Lyngby') {
                            $work_city = 'Kongens Lyngby';
                        } else {
                        }

                        $city = City::where('name', '=', $work_city)->first();
                        if ($city) {
                            $job_location = $city->id;
                        } else {
                            $place_model = new Place;
                            $place_model->name = $work_city;
                            $place_model->company = 'GRUNDFOS';
                            if ($place_model->save()) {
                                $place_inserted_id = $place_model->id;
                            }
                        }
                    }



                    $job_logo = 'grundfoslogo.png';
                    $new_job = Job::where('real_url', $job_url)->first();
                    if (!$new_job) {
                        $new_job->created_at = date('Y-m-d');
                        $new_job = new Job();
                    }
                    $new_job->title = htmlspecialchars_decode($job_title);
                    $new_job->seo = str_slug(str_replace($dst, $src, htmlspecialchars_decode($job_title)));
                    $new_job->is_redirect = 0;
                    $new_job->url = Hash::make(date('Y-m-d H:i:s'));
                    $new_job->real_url = $job_url;
                    $new_job->description = $job_description;
                    $new_job->description_html = $job_description_html;
                    $new_job->job_type_id = $job_type_id;
                    $new_job->is_active = 1;
                    $new_job->logo = $job_logo;
                    $new_job->company = 'GRUNDFOS';
                    $new_job->company_website = '';
                    $new_job->city_id = $job_location;

                    if ($job_deadline_date != '') {
                        if (date('Y-m-d', strtotime($job_deadline_date)) == '1970-01-01') {
                            // $new_job->job_deadline_date = NULL;
                        } else {
                            $new_job->job_deadline_date = date('Y-m-d', strtotime($job_deadline_date));
                        }
                    }
                    $new_job->created_on = date('Y-m-d');
                    if ($new_job->save()) {
                        $inserted_id = $new_job->id;
                        if ($place_inserted_id) {
                            Place::where('id', $place_inserted_id)->update(['job_id' => $inserted_id]);
                        }
                        if ($job_sub_category != '') {
                            $category = Category::where('seo', $job_sub_category)->where('level', 2)->first();
                            if ($category) {
                                if (!JobCategory::where('job_id', $inserted_id)->where('category_id', $category->id)->first()) {
                                    $job_category = new JobCategory;
                                    $job_category->job_id = $inserted_id;
                                    $job_category->category_id = $category->id;
                                    $job_category->save();
                                }
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
            }

            $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => intval($current_pagination + 4), 'updated_at' => date('Y-m-d H:i:s')]);
            if ($old) {
                $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
