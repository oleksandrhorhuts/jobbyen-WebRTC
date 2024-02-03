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
use GuzzleHttp\Client as GuzzleClient;

use App\JobLocation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class HRIndustriesJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:HRIndustriesJob';

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
        $company_name = 'HR-Industries A/S';
        $base_url = 'https://www.hviidoglarsen.dk/';
        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];


        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
        $crawler = $goutte->request('GET', 'https://www.hr-industries.dk/jobmuligheder/ledige-stillinger/');
        $total_pagination = $crawler->filter('table.tx_idefastillingsmodul tr')->count();

        $total_pagination = $total_pagination - 1;

        $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['page_size' => $total_pagination]);

        $cron_schedule = DB::table('cron_schedule')->where('company_name', $company_name)->first();
        $current_pagination = $cron_schedule->pagination;
        $totalpagesize = $cron_schedule->page_size;
        $checked_status = $cron_schedule->checked;

        if ($totalpagesize < $current_pagination) {
            $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['checked' => intval($checked_status + 1)]);
            $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1]);
            $current_pagination = 1;
        }

        $data_25 = $crawler->filter('table.tx_idefastillingsmodul tr')->each(function ($node, $index) use ($base_url, $current_pagination, $find_arr, $replace_arr) {
            if ($current_pagination - 1 <= $index && $current_pagination + 3 > $index && $index > 0) {
                try {

                    $job_title = $node->filter('td:nth-child(3)')->text();
                    $job_url = $node->filter('td:nth-child(3) a')->extract(['href']);
                    $job_url = isset($job_url[0]) ? $job_url[0] : '';
                    $work_city = $node->filter('td:nth-child(2)')->text();

                    $job_url = 'https://www.hr-industries.dk' . $job_url;

                    $goutte = new Client();
                    // $goutte->setClient(new GuzzleClient(array('verify' => false)));
                    $crawler = $goutte->request('GET', $job_url);

                    $job_description_html = $crawler->filter('.post-content .tx-idefa-stillingsmodul .job_left p')->each(function ($node, $index) {
                        return $node->html();
                    });
                    $job_description_html_string = '';
                    foreach ($job_description_html as $value) {
                        if ($value != null) {
                            $job_description_html_string .= '<p>' . $value . '</p>';
                        }
                    }

                    $job_description = strip_tags($job_description_html_string);
                    $work_city = trim(str_replace("Nær ", "", $work_city));

                    $job_category_url = "https://www.jobsearch.dk/jobdatabase?searchText=" . $job_title;
                    $goutte = new Client();
                    // $goutte->setClient(new GuzzleClient(array('verify' => false)));
                    $category_crawler = $goutte->request('GET', $job_category_url);
                    $job_parent_category = '';
                    $job_sub_category = '';
                    if ($category_crawler->filter('a.read-more')->count()) {
                        $job_category=$category_crawler->filter('a.read-more')->extract(['href']);
                        if(isset($job_category[0])){
                            $categoryArr = explode('/', $job_category[0]);
                            $job_parent_category = $categoryArr[2];
                            $job_sub_category = $categoryArr[3];
                        }
                    }

                    $temp_data = array(
                        "Job Title" => $job_title,
                        "Job Description" => $job_description,
                        "Job Description Html" => $job_description_html_string,
                        "Job Created Date" => '',
                        "Job Deadline Date" =>'',
                        "Company Logo" => '',
                        "Work address" => '',
                        "Work City" => $work_city,
                        "Work Type" => 1,
                        "Job Type" => 1,
                        "Job Url" => $job_url,
                        "Job Parent Category" => $job_parent_category,
                        "Job Sub Category" => $job_sub_category
                    );
                    return $temp_data;


                } catch (\Exception $e) {
                    echo json_encode($e->getMessage());
                }
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
                $job_created_date = $value['Job Created Date'];
                $job_parent_category = $value['Job Parent Category'];
                $job_sub_category = $value['Job Sub Category'];
                $job_type_id = $value['Job Type'];

                $job_deadline_date = $value['Job Deadline Date'];

                $job_location = 0;

                if ($work_city != '') {

                    if ($work_city == 'Aarhus C') {
                        $work_city = 'Århus C';
                    } else if ($work_city == 'Aarhus N') {
                        $work_city = 'Århus N';
                    } else if ($work_city == 'Aarhus V') {
                        $work_city = 'Århus V';
                    } else if($work_city == 'Vest Aarhus' || $work_city == 'Aarhus' || $work_city == 'Syd for Aarhus') {
                        $work_city = 'Århus';
                    } else if($work_city == 'Hobro kanten'){
                        $work_city = 'Hobro';
                    } else if($work_city == 'Djursland'){
                        $work_city = 'Ørum Djurs';
                    } else if($work_city == 'Syd for Aalborg'){
                        $work_city = 'Aalborg';
                    } else if($work_city == 'Nord for Aarhus'){
                        $work_city = 'Århus';
                    }

                    $city = City::where('name', '=', $work_city)->first();
                    if ($city) {
                        $job_location = $city->id;
                    } else {
                        $place_model = new Place;
                        $place_model->name = $work_city;
                        $place_model->company = 'HR-Industries A/S';
                        if ($place_model->save()) {
                            $place_inserted_id = $place_model->id;
                        }

                    }
                }

                $job_logo = 'hr-industries.png';

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
                $new_job->company = 'HR-Industries A/S';
                $new_job->company_website = '';
                $new_job->city_id = $job_location;
                $new_job->created_at = date('Y-m-d');
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
                        Job::where('real_url', $job_url)->update(['job_deadline_date' => date('Y-m-d', strtotime($job_deadline_date))]);
                    }
                }
            }
        }

        $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => intval($current_pagination + 4), 'updated_at' => date('Y-m-d H:i:s')]);
        if ($old) {
            $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
