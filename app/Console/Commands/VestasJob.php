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

class VestasJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:VestasJob';

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
        $company_name = 'Vestas';
        $base_url = 'https://careers.vestas.com';

        $client = new \Goutte\Client();

        // Create and use a guzzle client instance that will time out after 90 seconds
        $guzzleClient = new \GuzzleHttp\Client(array(
            'timeout' => 90,
            // 'verify' => false,
        ));

        $client->setClient($guzzleClient);
        $crawler = $client->request('GET', 'http://careers.vestas.com/search/?q=&locationsearch=denmark');
        $logo = 'https://rmkcdn.successfactors.com/81de82ba/61261c29-433b-4828-aeed-5.png';
        //dd($crawler->html());
        $total_page = preg_filter('/Results 1 – [0-9]{1,4} of /', '', $crawler->filter('span.paginationLabel')->text());

        $total_page_count = intval($total_page / 10);
        if ($total_page % 10 != 0) {
            $total_page_count = intval($total_page / 10) + 1;
        }


        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

        $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['page_size' => intval($total_page_count)]);

        $cron_schedule = DB::table('cron_schedule')->where('company_name', $company_name)->first();
        $current_pagination = $cron_schedule->pagination;
        $totalpagesize = $cron_schedule->page_size;
        $checked_status = $cron_schedule->checked;

        $data = [];

        $i = $current_pagination;

        if ($i == 1) {
            $startrow = '';
        } else {
            $startrow = ($i - 1) * 10;
            $startrow = '&startrow=' . $startrow;
        }
        $url = 'http://careers.vestas.com/search/?q=&locationsearch=denmark' . $startrow;


        $this->info('job url :'.$url);
        $this->info('====================================');
        $crawler = $client->request('GET', $url);
        $temp_data = $crawler->filter('#searchresults tr.data-row')->each(function ($node, $index) use ($base_url, $url, $logo, $client) {

            $job_title = $node->filter('span.jobTitle > a')->text();

            $job_created_date = $node->filter('span.jobDate')->text();

            $job_url = $node->filter('span.jobTitle > a')->extract(['href']);
            $job_url = isset($job_url[0]) ? $base_url . $job_url[0] : '';
            $work_address = $node->filter('span.jobLocation')->text();
            $work_city = trim(explode(', ', $work_address)[0], " \t\n\r\0\x0B\xC2\xA0");
            $work_type = $node->filter('span.jobDepartment')->text();
            $crawler = $client->request('GET', $job_url);


            if($crawler->filter('span.jobdescription')->count()){

                $job_type = $crawler->filter('span.jobdescription span[style="font-size:18.0px"]');
                if ($job_type->count() == 0) {
                    $job_type = $crawler->filter('span.jobdescription span[style="font-size: 18px;"]');
                }


                if ($job_type->count() > 0) {
                    $job_type = explode('|', $job_type->text());
                    $job_type = trim($job_type[count($job_type)-1], " \t\n\r\0\x0B\xC2\xA0");

                    if ($job_type == 'Full-Time' || $job_type == 'Fuldtid' || $job_type == 'Vollzeit') {
                        $job_type = 1;
                    } else if ($job_type == 'Contract') {
                        $job_type = 4;
                    } else if ($job_type == 'Temporary') {
                        $job_type = 5;
                    } else {
                        $job_type = 1;
                    }
                } else {
                    $job_type = 1;
                }

                $job_description = $crawler->filter('span.jobdescription p')->each(function ($node, $index) {
                    if ($index > 2) {
                        return trim($node->text());
                    } else {
                        return '';
                    }
                });
                $job_description = implode(' ', $job_description);

                $job_description_html = $crawler->filter('span.jobdescription')->html();
                $job_description_html = preg_replace('/<img alt="(.*)" src="(.*)">/', '<img alt="$1" src="$2" style="width:100%;">', $job_description_html);

                $job_category_url = "https://www.jobsearch.dk/jobdatabase?searchText=" . $job_title;
                $goutte = new Client();
                // $goutte->setClient(new GuzzleClient(array('verify' => false)));
                $category_crawler = $goutte->request('GET', $job_category_url);

                $job_parent_category = '';
                $job_sub_category = '';
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
                    "Job Description" => trim($job_description, " \t\n\r\0\x0B\xC2\xA0"),
                    "Job Description Html" => $job_description_html,
                    "Job Created Date" => date('Y-m-d', strtotime(trim($job_created_date, " \t\n\r\0\x0B\xC2\xA0"))),
                    "Job Deadline Date" => '',
                    "Company Logo" => $logo,
                    "Work address" => '',
                    "Work City" => $work_city,
                    "Work Type" => $work_type,
                    "Job Type" => $job_type,
                    "Job Url" => $job_url,
                    "Company Name" => "Vestas",
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
                        $place_model->company = 'Vestas';
                        if ($place_model->save()) {
                            $place_inserted_id = $place_model->id;
                        }
                    }
                }

                $job_logo = 'Vestas.png';

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
                $new_job->company = 'Vestas';
                $new_job->company_website = '';
                $new_job->city_id = $job_location;
                $new_job->created_at = $job_created_date;
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
                        //$updated = Job::where('real_url', $job_url)->update(['job_start_date'=>$job_start_date]);
                        $old = true;
                    }
                }
            }
        }

        $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => intval($current_pagination + 1), 'updated_at' => date('Y-m-d H:i:s')]);

        if ($current_pagination == $totalpagesize) {
            $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['checked' => intval($checked_status + 1)]);
            $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        }
        if ($old) {
            $updated = DB::table('cron_schedule')->where('company_name', $company_name)->update(['pagination' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
