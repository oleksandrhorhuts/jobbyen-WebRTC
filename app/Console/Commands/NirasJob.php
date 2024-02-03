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

class NirasJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:NirasJob';

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
        $company_name = 'Niras';
        $base_url = 'https://www.niras.dk';
        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

        $data = [];
        $page = 1;

        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
        $crawler = $goutte->request('GET', 'https://www.niras.dk/job/ledige-job/?q=country:Denmark');
        $logo = '';


        $total_pagination = $crawler->filter('div.media-list .media--jobpost')->each(function ($node, $index) use ($base_url, $logo, $find_arr, $replace_arr) {
            return $index;
        });
        if(sizeof($total_pagination) > 0){
            $total_pagination = $total_pagination[sizeof($total_pagination) - 1];
        }else{
            $total_pagination = 0;
        }

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


        $data_25 = $crawler->filter('div.media-list .media--jobpost')->each(function ($node) use ($base_url, $logo, $find_arr, $replace_arr) {

            $job_url = $node->extract(['onclick']);
            $job_url = isset($job_url[0]) ? $job_url[0] : '';
            $job_url = str_replace("window.location.href='", "", $job_url);
            $job_url = str_replace("';", "", $job_url);

            $client = new \GuzzleHttp\Client([
                // 'verify' => false,
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => 'Command Prompt',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, br',
                ]
            ]);
            $clientRequest = $client->request('GET', $job_url);
            $getBody = $clientRequest->getBody();
            $getStatusCode = $clientRequest->getStatusCode();
            if ($getStatusCode == 200) {
                $goutte = new Client();
                // $goutte->setClient(new GuzzleClient(array('verify' => false)));
                $crawler = $goutte->request('GET', trim($job_url));

                $job_title = $node->filter('div.media-body h3')->text();

                $job_created_date = '';

                $job_type = 1;

                $temp_infos = $crawler->filter('div.job-offer-info h5')->each(function ($node, $index) use ($crawler) {
                    if ($node->text() == 'Kontor') {
                        $n_index = ($index + 1) * 2 + 1;
                        return ['city' => trim($crawler->filter('div.job-offer-info p:nth-child(' . $n_index . ')')->text(), " \t\n\r\0\x0B\xC2\xA0")];
                    }
                    if ($node->text() == 'Deadline') {
                        $n_index = ($index + 1) * 2 + 1;
                        return ['job_deadline_date' => $crawler->filter('div.job-offer-info p:nth-child(' . $n_index . ')')->text()];
                    }
                });
                $data_infos = array_values(array_filter($temp_infos));

                $address = '';

                $job_detail_infos = [];

                foreach ($data_infos as $k => $v) {
                    $job_detail_infos[array_keys($v)[0]] = $v[array_keys($v)[0]];
                }
                if (isset($job_detail_infos['job_deadline_date'])) {

                    $job_deadline_date = str_replace($find_arr, $replace_arr, $job_detail_infos['job_deadline_date']);
                    $job_deadline_date = date("Y-m-d H:i:s", strtotime($job_deadline_date));
                } else {
                    $job_deadline_date = '';
                }
                $this->info('job deadline date : ' . $job_deadline_date);

                if (isset($job_detail_infos['city'])) {
                    $work_city = $job_detail_infos['city'];

                    if ($work_city == 'Aarhus C') {
                        $work_city = 'Århus C';
                    } else if ($work_city == 'Aarhus N') {
                        $work_city = 'Århus N';
                    } else if ($work_city == 'Aarhus V') {
                        $work_city = 'Århus V';
                    }
                } else {
                }

                if (intval(explode("-", $job_deadline_date)[0]) < 2000) {
                    $job_deadline_date = '';
                }

                $work_address = $address;

                $job_detail_infos = '';
                $job_start_date = '';


                $work_type = '';

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

                $job_description = $crawler->filter('#page-body')->text();

                $job_description_html_string = $crawler->filter('#page-body')->html();

                $temp_data = array(
                    "Job Title" => $job_title,
                    "Job Description" => $job_description,
                    "Job Description Html" => $job_description_html_string,
                    "Job Created Date" => '',
                    "Job Start Date" => '',
                    "Job Deadline Date" => $job_deadline_date,
                    "Company Logo" => $logo,
                    "Work address" => $work_address,
                    "Work City" => $work_city,
                    "Work Type" => '',
                    "Job Type" => '',
                    "Job Url" => $job_url,
                    "Job Parent Category" => $job_parent_category,
                    "Job Sub Category" => $job_sub_category
                );
                return $temp_data;
            }
        });
        $data_25 = array_values(array_filter($data_25));

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
                    $place_model->company = 'Niras';
                    if ($place_model->save()) {
                        $place_inserted_id = $place_model->id;
                    }
                }
            }


            $job_logo = 'niras-logo.svg';

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
            $new_job->company = 'Niras';
            $new_job->company_website = '';
            $new_job->city_id = $job_location;
            $new_job->created_at = date('Y-m-d');
            $new_job->created_on = date('Y-m-d');
            if ($job_deadline_date != '')
                $new_job->job_deadline_date = $job_deadline_date;
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
                    //$updated = Job::where('real_url', $job_url)->update(['description_html'=>$job_description_html]);
                    // $old = true;
                }
            }
        }
        $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => intval($current_pagination + 1), 'updated_at' => date('Y-m-d H:i:s')]);
        if ($old) {
            $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
