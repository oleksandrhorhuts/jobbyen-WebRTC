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
class HillerodKommuneJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:HillerodKommuneJob';

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
        $company_name = 'HILLERØD KOMMUNE';
        $base_url = 'https://www.hillerod.dk/';
        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];


        $goutte = new Client();
        // $goutte->setClient(new GuzzleClient(array('verify' => false)));
        $crawler = $goutte->request('GET', 'https://signatur.frontlab.com/ExtJobs/DefaultHosting/JobList.aspx?ClientId=1468');
        $total_pagination = $crawler->filter('.content-outer-wrapper .category-item li:not(.empty-category)')->count();

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

        $data_25 = $crawler->filter('.content-outer-wrapper .category-item li:not(.empty-category)')->each(function ($node, $index) use ($base_url, $current_pagination, $find_arr, $replace_arr) {
            if ($current_pagination - 1 <= $index && $current_pagination + 3 > $index) {

                $job_url = $node->filter('.special-link')->extract(['href']);
                $job_url = isset($job_url[0]) ? $job_url[0] : '';
                $job_url = 'https://signatur.frontlab.com'.$job_url;

                $job_deadline_date = $node->filter('.deadline-xs')->text();
                $job_deadline_date = str_replace($find_arr, $replace_arr, $job_deadline_date);

                $goutte = new Client();
                // $goutte->setClient(new GuzzleClient(array('verify' => false)));
                $crawler = $goutte->request('GET', $job_url);

                $job_title = $crawler->filter('.content-wrapper .content-wrapper-header')->text();

                $job_description_html = $crawler->filter('#ctl00_mainContent_contentBodyOuterDiv')->html();
                $job_description = strip_tags($job_description_html);

                $temp_infos = $crawler->filter('.right-menu-content .menu-box')->each(function ($node) {

                    if ($node->filter("h2.menu-box-outer-header")->count()) {
                        if ($node->filter("h2.menu-box-outer-header")->text() == 'Ansættelsesforhold:') {
                            return ['job_type' => trim(str_replace("Ansættelsesforhold:", "", $node->text()))];
                        }
                        if ($node->filter("h2.menu-box-outer-header")->text() == 'Kontakt:') {
                            return ['contact' => trim(str_replace("Kontakt:", "", $node->text()))];
                        }
                    }
                });

                $data_infos = array_values(array_filter($temp_infos));

                $job_detail_infos = [];

                foreach ($data_infos as $k => $v) {
                    $job_detail_infos[array_keys($v)[0]] = $v[array_keys($v)[0]];
                }

                if (isset($job_detail_infos['job_type'])) {
                    $work_type = $job_detail_infos['job_type'];
                    if ($work_type == 'Fuldtid') {
                        $job_type = 1;
                    } else if ($work_type == 'Deltid') {
                        $job_type = 2;
                    } else if ($work_type == 'Freelance') {
                        $job_type = 3;
                    } else if ($work_type == 'Frivillig') {
                        $job_type = 4;
                    } else {
                        $job_type = 1;
                    }
                } else {
                    $job_type = 1;
                }
                $contact_person = '';
                $contact_phone = '';

                if (isset($job_detail_infos['contact'])) {
                    $contact = $job_detail_infos['contact'];

                    $contact_arr = explode('Tlf.', $contact);
                    if(sizeof($contact_arr) > 1){
                        $contact_person = $contact_arr[0];
                        $contact_phone = $contact_arr[1];
                    }

                } else {
                    $contact = '';
                }

                $work_city = 'Hillerød';

                $temp_data = array(
                    "Job Title" => $job_title,
                    "Job Description" => $job_description,
                    "Job Description Html" => $job_description_html,
                    "Job Created Date" => '',
                    "Job Start Date" => '',
                    "Job Deadline Date" => $job_deadline_date,
                    "Company Logo" => '',
                    "Work address" => '',
                    "Work City" => $work_city,
                    "Work Type" => '',
                    "Job Type" => $job_type,
                    "Job Url" => $job_url,
                    "Job Parent Category" => '',
                    "Job Sub Category" => '',
                    "contact_person"=>$contact_person,
                    "contact_phone"=>$contact_phone
                );
                return $temp_data;
            }
        });

        $old = false;
        $place_inserted_id = 0;
        foreach ($data_25 as $key => $value) {
            if (isset($value['Job Title']) && $value['Job Title'] != '') {
                $work_city = $value['Work City'];
                $work_type = $value['Job Type'];
                $job_title = $value['Job Title'];
                $job_url = $value['Job Url'];
                $job_description = $value['Job Description'];
                $job_description_html = $value['Job Description Html'];
                $job_created_date = $value['Job Created Date'];
                $job_parent_category = $value['Job Parent Category'];
                $job_sub_category = $value['Job Sub Category'];
                $job_type_id = $value['Job Type'];
                $job_deadline_date = $value['Job Deadline Date'];
                $contact_person = $value['contact_person'];
                $contact_phone = $value['contact_phone'];
                $job_location = 0;

                if ($work_city != '') {
                    $city = City::where('name', '=', $work_city)->first();
                    if ($city) {
                        $job_location = $city->id;
                    } else {
                        $place_model = new Place;
                        $place_model->name = $work_city;
                        $place_model->company = 'HILLERØD KOMMUNE';
                        if ($place_model->save()) {
                            $place_inserted_id = $place_model->id;
                        }
                    }
                }


                $job_logo = 'hillerod_kommune.png';

                $new_job = new Job;
                $new_job->title = $job_title;
                $new_job->seo = str_slug(str_replace($dst, $src, $job_title));
                $new_job->is_redirect = 0;
                $new_job->url = Hash::make(date('Y-m-d H:i:s'));
                $new_job->real_url = $job_url;
                $new_job->description = $job_description;
                $new_job->description_html = $job_description_html;
                $new_job->job_type_id = $work_type;
                $new_job->is_active = 1;
                $new_job->logo = $job_logo;
                $new_job->company = 'HILLERØD KOMMUNE';
                $new_job->company_website = '';
                $new_job->city_id = $job_location;
                $new_job->job_deadline_date = date('Y-m-d H:i:s', strtotime($job_deadline_date));
                $new_job->created_at = date('Y-m-d');
                $new_job->created_on = date('Y-m-d');
                $new_job->contact_person = $contact_person;
                $new_job->contact_phone = $contact_phone;

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
