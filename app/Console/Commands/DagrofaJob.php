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
class DagrofaJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:DagrofaJob';

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
        $company_name = 'Dagrofa';
        $base_url = 'https://www.dagrofa.dk';
        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];

        $data = [];
        $page = 1;

        $logo = '';
        $goutte = new Client();
		// $goutte->setClient(new GuzzleClient(array('verify' => false)));
        $crawler = $goutte->request('GET', 'https://candidate.hr-manager.net/vacancies/list.aspx?customer=dagrofa_tr');
        $values = $crawler->filter('#ctl00_ctl00_ContentPlaceHolder_MainContent_ContentPlaceHolder_PageContent_ctl00_HiddenField_PositionList')->extract(['value']);
        $total_pagination = count(json_decode($values[0])->PositionList->Items);
        $this->info('total pagination :' . $total_pagination);
        $this->info('==============================================');

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

        $data_25 = [];

        foreach (json_decode($values[0])->PositionList->Items as $index => $node) {
            if ($current_pagination - 1 < $index && $current_pagination + 2 >= $index) {

                $this->info(json_encode($node));
                $this->info('==================================');
                $job_created_date = $node->PublishedString;
                $this->info('Job Published date :' . $job_created_date);
                $job_deadline_date = $node->ApplicationDueString;
                $this->info('Job deadline date :' . $job_deadline_date);
                $job_title = $node->Name;
                $this->info('Job Title :' . $job_title);



                $contact_person = '';
                $contact_phone = '';
                $contact_email = '';

                if($node->Users){
                    if($node->Users->ProjectParticipants){
                        $contact = $node->Users->ProjectParticipants[0];
                        if ($contact->FirstName) {
                            $contact_person = $contact->FirstName." ".$contact->LastName;
                        }
                        $this->info('Job Contact Person :' . $contact_person);

                        if ($contact->Phone) {
                            $contact_phone = $contact->Phone;
                        }
                        $this->info('Job Contact Phone :' . $contact_phone);

                        if ($contact->Email) {
                            $contact_email = $contact->Email;
                        }
                        $this->info('Job Contact Email :' . $contact_email);

                    }
                }

                $work_city = '';
                if ($node->PositionLocation) {
                    $work_city = $node->PositionLocation->Name;
                }
                $this->info('Work City :' . $work_city);
                $job_url = $node->AdvertisementUrl . '&SkipAdvertisement=False';
                $this->info('Job url :' . $node->AdvertisementUrl . '&SkipAdvertisement=False');

                $goutte = new Client();
                // $goutte->setClient(new GuzzleClient(array('verify' => false)));
                $crawler = $goutte->request('GET', $job_url);

                $job_description_html = $crawler->filter('#AdvertisementInnerContent')->html();
                $job_description = strip_tags($job_description_html);


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
                    "work_address" => $work_city,
                    "Work City" => $work_city,
                    "Work Type" => 1,
                    "Job Type" => 1,
                    "Job Url" => $job_url,
                    "Job Parent Category" => $job_parent_category,
                    "Job Sub Category" => $job_sub_category,
                    "contact_person" => $contact_person,
                    "contact_phone" => $contact_phone,
                    "contact_email" => $contact_email
                );

                $data_25[] = $temp_data;
            }
        }

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
                $contact_person = $value['contact_person'];
                $contact_phone = $value['contact_phone'];
                $contact_email = $value['contact_email'];
                $job_location = 0;

                if ($work_city != '') {

                    if ($work_city == 'Hovedstadsområdet') {
                        $work_city = 'København';
                    } else if ($work_city == 'Lyngby') {
                        $work_city = 'Kongens Lyngby';
                    } else if ($work_city == 'Aarhus') {
                        $work_city = 'Århus';
                    } else if($work_city == 'Region Syddanmark'){
                        $work_city = 'Syddanmark';
                    } else if($work_city == 'Region Midtjylland'){
                        $work_city = 'Midtjylland';
                    } else if($work_city == 'Region Nordjylland'){
                        $work_city = 'Nordjylland';
                    } else if($work_city == 'Region Hovedstaden'){
                        $work_city = 'Hovedstaden';
                    } else if($work_city == 'Jylland'){
                        $work_city = 'Jylland';
                    }
                    $city = City::where('name', '=', $work_city)->first();
                    if ($city) {
                        $job_location = $city->id;
                    } else {
                        $place_model = new Place;
                        $place_model->name = $work_city;
                        $place_model->company = 'Dagrofa';
                        if ($place_model->save()) {
                            $place_inserted_id = $place_model->id;
                        }
                    }
                }


                $job_logo = 'dagrofa.png';

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
                $new_job->company = 'Dagrofa';
                $new_job->company_website = '';
                $new_job->contact_person = $contact_person;
                $new_job->contact_phone = $contact_phone;
                $new_job->contact_email = $contact_email;
                $new_job->city_id = $job_location;

                if ($job_created_date != '') {
                    $new_job->created_at = date('Y-m-d', strtotime($job_created_date));
                } else {
                    $new_job->created_at = date('Y-m-d');
                }

                if ($job_deadline_date != '') {
                    $new_job->job_deadline_date = date('Y-m-d', strtotime($job_deadline_date));
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
                        // $updated = Job::where('real_url', $job_url)->update(['job_deadline_date'=>$job_deadline_date]);
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
