<?php

namespace App\Http\Controllers;

use App\City;
use App\Http\Requests;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Cv;
use App\CvCategory;
use App\CvEducation;
use App\CvExperience;
use App\CvWish;
use App\CvSkill;
use App\CvLanguage;
use App\Helpers\GeneralHelper;
use App\Job;
use App\JobCategory;
use App\JobSkill;
use App\JobLocation;
use App\UserJobSaved;
use Illuminate\Support\Facades\Log;
use App\UserJobsApply;
use App\ApplyAttachFile;
use App\JobVisit;
use App\Note;
use App\JobAgent;
use App\JobAgentCategory;
use App\JobAgentLocation;
use App\JobAgentCompany;
use App\Company;
use App\JobAnswer;
use App\JobPermission;

use App\JobDescriptionFile;
use App\Notification;

use App\User;
use PHPMailer\PHPMailer\PHPMailer;
use App\Rating;
use App\Interview;
use App\JobQuestions;
use Illuminate\Support\Facades\DB;

use App\CommonAsk;
use App\CommonQuestion;
use App\CommonAnswer;

class JobController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }
    /**
     * emulate server side with routing which has prefix job/
     * @param
     * job_id : the job id which requested
     * job_seo : the seo of job
     * @return
     * resources/views/job/index.blade.php
     */
    public function index(Request $request, $job_id, $job_seo)
    {
        $job = Job::where('id', $job_id)->first();

        $data['job_id'] = $job_id;
        $data['job_seo'] = $job_seo;

        $data['og_title'] = $job['title'];
        $data['og_image'] = $request->root() . '/images/company_logo/' . $job['logo'];

        $description = $job['description'];

        $meta_desc = substr($description, 0, 150) . '...';
        $meta_desc = str_replace("'", '"', $meta_desc);
        $meta_desc = str_replace(chr(13) . chr(10), chr(13), $meta_desc);
        $meta_desc = str_replace(chr(13), '', $meta_desc);
        $meta_desc = str_replace(['<br/>', '<div>', '</div>', '<br />', '<br>'], '', $meta_desc);
        $meta_desc = nl2br($meta_desc);
        $meta_desc = strip_tags($meta_desc);
        $meta_desc = str_replace('"', "'", $meta_desc);
        $meta_desc = addslashes($meta_desc);


        $data['og_description'] = $meta_desc;

        return view('job.index', ['data' => $data]);
    }
    /**
     * EndPoint api/remove_saved_job
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Remove Saved job with given job id, and updated jobs will be returned by page per size and page number
     * @param
     * id : the job id which removed
     * dataPerPage : date Per page,
     * pageNumber : current page number
     * @return json with updated saved jobs and its count
     */
    public function remove_saved_job(Request $request)
    {
        $job_id = $request->post('id');
        $dataPerPage = $request->post('dataPerPage');
        $pageNumber = $request->post('pageNumber');
        $currentSkip = ($pageNumber - 1) * $dataPerPage;

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;
        if (UserJobSaved::where('id', $job_id)->first()) {
            UserJobSaved::where('id', $job_id)->delete();
            $rows_jobs = UserJobSaved::with(['job', 'job.city', 'job.JobCategory'])->where('user_id', $user_id)->skip($currentSkip)->take($dataPerPage)->get();
            $count = UserJobSaved::with(['job', 'job.city', 'job.JobCategory'])->where('user_id', $user_id)->count();
            return response()->json(['result' => 'success', 'jobs' => $rows_jobs, 'count' => $count], 200);
        } else {
            return response()->json(['result' => 'failed', 'jobs' => [], 'count' => []], 200);
        }
    }
    /**
     * EndPoint api/get_saved_jobs/${pagePersize}/${pageNumber}
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get Saved jobs which created by given user, and it will be returned by page per size and page number
     * @param
     * pagePerSize : page Size
     * pageNumber : current page number
     * @return json with saved jobs and its count
     */
    public function get_saved_jobs(Request $request, $pagePerSize, $pageNumber)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $currentSkip = ($pageNumber - 1) * $pagePerSize;

        $user_id = $user->id;
        $rows_jobs = UserJobSaved::with(['job', 'job.job_location', 'job.job_location.location', 'job.JobCategory'])->where('user_id', $user_id)->skip($currentSkip)->take($pagePerSize)->get();
        $count = UserJobSaved::with(['job', 'job.city', 'job.JobCategory'])->where('user_id', $user_id)->count();
        return response()->json(['jobs' => $rows_jobs, 'count' => $count], 200);
    }
    /**
     * EndPoint api/get_agent_by_id
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Get agent detail from given agent id
     * @param
     * agent_id : the id of job agent
     * @return json with job agent detail
     */
    public function get_agent_by_id(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;
        $agent_id = $request->post('agent_id');
        if (JobAgent::where('id', $agent_id)->where('user_id', $user_id)->first()) {
            $job_agent = JobAgent::with(['agent_job_type', 'agent_company', 'agent_company.company', 'agent_location', 'agent_location.location', 'agent_category', 'agent_category.category'])->where('id', $agent_id)->where('user_id', $user_id)->first();
            return response()->json($job_agent, 200);
        } else {
            return response()->json(null, 200);
        }
    }
    /**
     * EndPoint api/get_agent
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get all agents which created by given user
     * @return json with job agents array
     */
    public function get_agent(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $job_agents = JobAgent::with(['agent_job_type', 'agent_company', 'agent_company.company', 'agent_location', 'agent_category', 'agent_category.category'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();

        return response()->json($job_agents, 200);
    }
    /**
     * EndPoint api/delete_agent
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Delete Candidate Job agent with agent id which provided and it will be added to notification.
     * @param
     * agent_id : agent id which will be removed.
     * @return json with SUCCESS or FAILED with job agents which be updated from db.
     */
    public function delete_agent(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $agent_id = $request->post('agent_id');
        if (JobAgent::where('id', $agent_id)->first()) {
            JobAgent::where('id', $agent_id)->delete();
            JobAgentCompany::where('agent_id', $agent_id)->delete();
            JobAgentCategory::where('agent_id', $agent_id)->delete();
            JobAgentLocation::where('agent_id', $agent_id)->delete();

            $job_agents = JobAgent::with(['agent_job_type', 'agent_company', 'agent_location', 'agent_category', 'agent_category.category'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();

            $new_notification = new Notification();
            $new_notification->user_id = $user_id;
            $new_notification->sender = 1;
            $new_notification->type = 8;
            $new_notification->name = 0;
            $new_notification->save();

            Notification::where('user_id', $user_id)->where('type', 10)->where('sender', $agent_id)->delete();

            return response()->json(['result' => 'success', 'agents' => $job_agents], 200);
        } else {
            return response()->json(['result' => 'failed', 'agents' => []], 200);
        }
    }
    /**
     * EndPoint api/update_agent
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Update Candidate Job Agent from given agent details and add notification
     * @param
     * agent : agent details with json object,
     * title : title of agent
     * job_type : job type of agent
     * categories : given job categories json array
     * location : given job locations json array
     * @return json with success or fail
     */
    public function update_agent(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $agent = $request->post('agent');
        $agent = json_decode($agent);

        $agent_id = $request->post('agent_id');

        $title = $agent->name;
        $company = $agent->agent_company;
        $job_type = $agent->job_type;
        $categories = $agent->agent_category;
        $location = $agent->agent_location;

        if (JobAgent::where('id', $agent_id)->first()) {
            $type = 0;
            if ($job_type != '') {
                $type++;
            }


            JobAgent::where('id', $agent_id)->update(['name' => $title, 'job_type_id' => $job_type != '' ? $job_type->id : 0]);




            if ($company != '') {
                JobAgentCompany::where('agent_id', $agent_id)->delete();
                foreach ($company as $k => $v) {
                    $job_agent_company = new JobAgentCompany();
                    $job_agent_company->agent_id = $agent_id;
                    $job_agent_company->company_id = $v->id;
                    $job_agent_company->save();
                }

                if (sizeof($company)) {
                    $type++;
                }
            }


            if ($categories != '') {
                JobAgentCategory::where('agent_id', $agent_id)->delete();
                foreach ($categories as $k => $v) {
                    $job_agent_category = new JobAgentCategory();
                    $job_agent_category->agent_id = $agent_id;
                    if (empty($v->id)) {
                        $selected_category_id = GeneralHelper::insert_new_category($v->name);
                    } else {
                        $selected_category_id = $v->id;
                    }
                    $job_agent_category->category_id = $selected_category_id;
                    $job_agent_category->save();
                }
                if (sizeof($categories)) {
                    $type++;
                }
            }




            if ($location != '') {
                JobAgentLocation::where('agent_id', $agent_id)->delete();
                foreach ($location as $k => $v) {
                    $job_agent_location = new JobAgentLocation();
                    $job_agent_location->agent_id = $agent_id;
                    if (empty($v->id)) {
                        $selected_location_id = GeneralHelper::insert_new_location($v->name);
                    } else {
                        $selected_location_id = $v->id;
                    }
                    $job_agent_location->location_id = $selected_location_id;
                    $job_agent_location->save();
                }
                if (sizeof($location)) {
                    $type++;
                }
            }

            JobAgent::where('id', $agent_id)->update(['type' => $type]);

            $new_notification = new Notification();
            $new_notification->user_id = $user_id;
            $new_notification->sender = 1;
            $new_notification->type = 7;
            $new_notification->name = $agent_id;
            $new_notification->save();

            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/create_agent
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create Candidate Job Agent from given agent details and add notification
     * @param
     * agent : agent details with json object,
     * title : title of agent
     * job_type : job type of agent
     * categories : given job categories json array
     * location : given job locations json array
     * @return json with success or fail
     */
    public function create_agent(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $agent = $request->post('agent');
        $agent = json_decode($agent);

        $title = $agent->title;
        $company = $agent->company;
        $job_type = $agent->job_type;
        $categories = $agent->categories;
        $location = $agent->location;

        $new_job_agent = new JobAgent();
        $new_job_agent->name = $title;
        $new_job_agent->user_id = $user_id;

        $type = 0;

        if ($job_type != '') {
            $new_job_agent->job_type_id = $job_type->id;
            $type++;
        }
        if ($new_job_agent->save()) {

            if ($company != '') {
                foreach ($company as $k => $v) {
                    $job_agent_company = new JobAgentCompany();
                    $job_agent_company->agent_id = $new_job_agent->id;
                    $job_agent_company->company_id = $v->id;
                    $job_agent_company->save();
                }

                if (sizeof($company)) {
                    $type++;
                }
            }
            if ($categories != '') {
                foreach ($categories as $k => $v) {
                    $job_agent_category = new JobAgentCategory();
                    $job_agent_category->agent_id = $new_job_agent->id;

                    if (empty($v->id)) {
                        $selected_category_id = GeneralHelper::insert_new_category($v->name);
                    } else {
                        $selected_category_id = $v->id;
                    }
                    $job_agent_category->category_id = $selected_category_id;
                    $job_agent_category->save();
                }
                if (sizeof($categories)) {
                    $type++;
                }
            }



            if ($location != '') {
                foreach ($location as $k => $v) {
                    $job_agent_location = new JobAgentLocation();
                    $job_agent_location->agent_id = $new_job_agent->id;
                    if (empty($v->id)) {
                        $selected_location_id = GeneralHelper::insert_new_location($v->name);
                    } else {
                        $selected_location_id = $v->id;
                    }
                    $job_agent_location->location_id = $selected_location_id;
                    $job_agent_location->save();
                }
                if (sizeof($location)) {
                    $type++;
                }
            }


            JobAgent::where('id', $new_job_agent->id)->update(['type' => $type]);

            $new_notification = new Notification();
            $new_notification->user_id = $user_id;
            $new_notification->sender = 1;
            $new_notification->type = 6;
            $new_notification->name = $new_job_agent->id;
            $new_notification->save();

            return  response()->json(['result' => 'success'], 200);
        } else {
            return  response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/get_total_saved_jobs
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get all saved jobs with JWT TOKEN which provided by token after user logged in
     * @return json with saved jobs
     */
    public function get_total_saved_jobs(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $saved_jobs = UserJobSaved::where('user_id', $user_id)->get();
        return response()->json($saved_jobs, 200);
    }
    /**
     * EndPoint api/saveJob
     * HTTP SUBMIT : POST
     * Save job with JWT TOKEN which provided by token after user logged in
     * @param $job id should be saved
     * @return json with saved jobs
     */
    public function saveJob(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $job_id = $request->post('job_id');

        $count_job_saved = UserJobSaved::where('user_id', $user_id)->where('job_id', $job_id)->count();
        if ($count_job_saved) {
        } else {
            $user_job_saved = new UserJobSaved;
            $user_job_saved->user_id = $user_id;
            $user_job_saved->job_id = $job_id;
            $user_job_saved->save();
        }
        $saved_jobs = UserJobSaved::where('user_id', $user_id)->get();
        return response()->json($saved_jobs, 200);
    }
    /**
     * Get postal codes from given city name, it is used on api/search_job_condition end point
     * @param
     * name : given city name,
     * id : given postal code ids arr
     * @return array with postal ids
     */
    public function special_search($name, $id)
    {
        if ($name == 'Odense') {
            // return ['Odense C', 'Odense V', 'Odense NV', 'Odense SØ', 'Odense M', 'Odense NØ', 'Odense SV', 'Odense S', 'Odense N'];
            return [5000, 5200, 5210, 5220, 5230, 5240, 5250, 5260, 5270];
        } else if ($name == 'København') {
            // return ['København K', 'København Ø', 'København N', 'København S', 'København NV', 'København SV'];
            return [1301, 2100, 2200, 2300, 2400, 2450, 1786];
        } else if ($name == 'Aalborg') {
            // return ['Aalborg SV', 'Aalborg SØ', 'Aalborg Øst'];
            return [9000, 9200, 9210, 9220];
        } else {
            return [$id];
        }
    }
    /**
     * Get postal codes from given region name, it is used on api/search_job_condition end point
     * @param
     * name : given city name,
     * id : given postal code ids arr
     * @return array with postal ids
     */
    public function special_region_search($name, $id)
    {
        if ($name == 'Nordjylland') {
            return [7700, 7730, 7760, 7770, 7900, 7950, 7960, 7970, 7980, 7990, 9000, 9100, 9200, 9210, 9220, 9230, 9240, 9260, 9270, 9280, 9293, 9300, 9310, 9320, 9330, 9340, 9352, 9362, 9370, 9380, 9381, 9400, 9430];
        } else if ($name == 'Sjælland') {
            return [2670, 2680, 2690, 4000, 4030, 4100, 4060, 4040, 4130, 4140, 4160, 4171, 4173, 4174, 4180, 4190, 4200, 4220, 4230, 4244, 4245, 4250, 4262, 4270, 4281, 4293, 4295, 4300, 4340, 4350, 4370, 4390, 4400];
        } else if ($name == 'Hovedstaden') {
            return [1301, 2100, 2200, 2300, 2400, 2450, 1786];
        } else if ($name == 'Midtjylland') {
            return [6880, 6900, 6920, 6933, 6950, 6960, 6990, 7130, 7160, 7330, 7361, 7430, 7441, 7451, 7470, 7480, 7490, 7500, 7570, 7600, 7620, 7673, 7680, 7800, 7830, 7840, 7850, 8000, 8210, 8220, 8230, 8240, 8245, 8250, 8260, 8270, 8300, 8462, 8530, 8543, 8541, 8450, 8600, 8620];
        } else if ($name == 'Syddanmark') {
            return [5000, 5200, 5210, 5220, 5230, 5240, 5250, 5260, 5270, 5450, 5400, 5380, 5330, 5300, 5290, 5464, 5471, 5492, 5500, 5540, 5560, 5580, 5591, 5592, 5600, 5601, 5602, 5603, 5610, 5620, 5672, 5683, 5690, 5700, 5750, 5750, 5762, 5771, 5856, 5900, 6092, 6100, 6290, 6300, 6270, 6392, 6400, 6600, 7000, 7007];
        } else if ($name == 'Fyn') {
            $fynarr = [];
            for ($idx = 5000; $idx <= 5999; $idx++) {
                $fynarr[] = $idx;
            }
            return $fynarr;
        } else if ($name == 'Jylland') {
            $jyllandarr = [];
            for ($idx = 6000; $idx <= 9999; $idx++) {
                $jyllandarr[] = $idx;
            }
            return $jyllandarr;
        }
    }
    /**
     * EndPoint api/repost_job
     * HTTP SUBMIT : POST
     * According job permission and update job created date
     * @param
     * job_id : the id of job which should be reposted.
     * @return json with result success or failed
     */
    public function repost_job(Request $request)
    {
        $job_id = $request->post('job_id');
        if (Job::where('id', $job_id)->first()) {
            $job_permission = Job::where('id', $job_id)->first()->permission;
            $job_increased = Job::where('id', $job_id)->first()->increased;
            if (($job_permission == 2 || $job_permission == 3) && $job_increased == 0) {
                Job::where('id', $job_id)->update(['created_at' => date('Y-m-d 00:00:00'), 'increased' => 1, 'is_active' => 1]);
                return response()->json(['result' => 'success'], 200);
            } else {
                return response()->json(['result' => 'failed'], 200);
            }
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/search_job_condition
     * HTTP SUBMIT : POST
     * Get Jobs according the given condition.
     * @param
     * keyword : job title,
     * location[] : job location id with array, as a default "all" string
     * pageNumber : current page number
     * pagePerSize : current size per page. as a default 10
     * category_condition[] : category id with array, as a default "all" string
     * job_type[] : json array with job type
     * sort_type : sort type id, 1 : Seneste, 2 : Populær, 3 : Ansøgningsfrist, as a default : 1
     * radius : radius kilmeter which will be used in the location search with radius
     * postcode : postcode which will be used in the location search with radius.
     * @return json with jobs and jobs of count
     */
    public function search_job_condition(Request $request)
    {
        $keyword = $request->post('keyword');
        $location = $request->post('location');
        $pageNumber = $request->post('pageNumber');
        $pagePerSize = $request->post('pagePerSize');
        $datePosted = $request->post('datePosted');
        $category_condition = $request->post('category_condition');
        $job_type = $request->post('job_type');
        $sort_type = $request->post('sort_type');
        $radius = $request->post('radius');
        $postcode = $request->post('postcode');



        $currentSkip = ($pageNumber - 1) * $pagePerSize;

        if ($location == 'all') {
            $location = null;
        } else {
            $location = json_decode($location);
        }

        if ($category_condition == 'all') {
            $category_condition = null;
        } else {
        }

        $ordering = 'desc';
        if ($sort_type == 1) {
            $orderBy = 'created_at';
            $ordering = 'desc';
        } else if ($sort_type == 2) {
            $orderBy = 'job_visit_count';
            $ordering = 'desc';
        } else if ($sort_type == 3) {
            $orderBy = 'job_deadline_date';
            $ordering = 'desc';
        } else {
        }

        foreach ($datePosted as $k => $v) {
            if ($v['status'] == 1) {
                if ($k == 1) {
                    $orderBy = 'created_at';
                    $ordering = 'desc';
                } else if ($k == 2) {
                    $orderBy = 'created_at';
                    $ordering = 'asc';
                }
            }
        }


        $postcode_condition = null;
        $filter_cities = [];
        if ($postcode == 0) {
        } else {

            $zip = City::where('zip', $postcode)->first();
            $lat = $zip->lat;
            $lng = $zip->lng;
            if ($lat > 0 && $lng > 0) {
                $postcode_condition = 1;
                $filter_cities = City::where(DB::raw(
                    '( 6371 * acos( cos( radians(' . $lat . ') )
                    * cos( radians( lat ) )
                    * cos( radians( lng )
                    - radians(' . $lng  . ') )
                    + sin( radians(' . $lat  . ') )
                    * sin( radians( lat ) ) ) )'
                ), '<', $radius)->get();
            } else {
                $postcode_condition = null;
            }
        }

        $rows_jobs = Job::with(['job_location', 'job_location.location', 'JobCategory', 'job_visit'])->withCount(['job_visit'])
            ->when($location, function ($query) use ($location) {
                $query->whereHas('job_location.location', function ($query) use ($location) {
                    if ($location == "all") {
                    } else {
                        $query_location = [];
                        foreach ($location as $v) {
                            if ($v->region) {
                                $query_location = array_merge($query_location, $this->special_region_search($v->name, $v->zip));
                            } else {
                                $query_location = array_merge($query_location, $this->special_search($v->name, $v->zip));
                            }
                        }
                        if (sizeof($query_location)) {
                            $query->whereIn('zip', $query_location);
                        }
                    }
                });
            })
            ->when($postcode_condition, function ($query) use ($filter_cities) {
                $query->whereHas('job_location.location', function ($query) use ($filter_cities) {
                    $query_location = [];
                    foreach ($filter_cities as $v) {
                        $query_location[] = $v->zip;
                    }
                    if (sizeof($query_location)) {
                        $query->whereIn('zip', $query_location);
                    }
                });
            })

            ->when($category_condition, function ($query) use ($category_condition) {
                $query->whereHas('JobCategory', function ($query) use ($category_condition) {
                    if ($category_condition == "all") {
                    } else {
                        $query->whereIn('category_id', $category_condition);
                    }
                });
            })
            ->where('is_active', 1)
            ->where(function ($query) use ($keyword, $sort_type) {
                if ($keyword == "all") {
                } else {
                    $query->where('title', 'like', '%' . $keyword . '%');
                    // $query->orWhere('company', 'like', '%' . $keyword . '%');
                }

                if ($sort_type == 3) {
                    $query->whereDate('job_deadline_date', '<', Carbon::now());
                }
            })
            ->orderBy($orderBy, $ordering)
            ->where(function ($query) use ($datePosted, $job_type) {

                $job_idx = 0;
                foreach ($job_type as $k => $v) {
                    if ($v['status'] == 1) {
                        switch ($k) {
                            case 0:
                                if ($job_idx == 0) {
                                    $query->where('job_type_id', 1);
                                } else {
                                    $query->orWhere('job_type_id', 1);
                                }
                                break;
                            case 1:
                                if ($job_idx == 0) {
                                    $query->where('job_type_id', 2);
                                } else {
                                    $query->orWhere('job_type_id', 2);
                                }
                                break;
                            case 2:
                                if ($job_idx == 0) {
                                    $query->where('job_type_id', 3);
                                } else {
                                    $query->orWhere('job_type_id', 3);
                                }
                                break;
                            case 3:
                                if ($job_idx == 0) {
                                    $query->where('job_type_id', 4);
                                } else {
                                    $query->orWhere('job_type_id', 4);
                                }
                                break;
                        }
                        $job_idx++;
                    }
                }


                $idx = 0;
                foreach ($datePosted as $k => $v) {
                    if ($v['status'] == 1) {
                        $query->where('created_at', '>', 0);
                        switch ($k) {
                            case 0:
                                break;
                            case 3: // 1-10 dage
                                if ($idx == 0) {
                                    $query->where('created_at', '<=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 1)));
                                    $query->where('created_at', '>=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 10)));
                                } else {
                                }
                                break;
                            case 4: // 11-20 dage
                                if ($idx == 0) {
                                    $query->where('created_at', '<=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 11)));
                                    $query->where('created_at', '>=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 20)));
                                } else {
                                }
                                break;
                            case 5: // 21-30 dage
                                if ($idx == 0) {
                                    $query->where('created_at', '<=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 21)));
                                    $query->where('created_at', '>=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 30)));
                                } else {
                                }
                                break;
                        }
                        $idx++;
                    }
                }
            })
            ->skip($currentSkip)->take($pagePerSize)->get();



        $rows_jobs_count = Job::with(['job_location', 'job_location.location', 'JobCategory'])
            ->when($location, function ($query) use ($location) {
                $query->whereHas('job_location.location', function ($query) use ($location) {
                    if ($location == "all") {
                    } else {
                        $query_location = [];
                        foreach ($location as $v) {
                            if ($v->region) {
                                $query_location = array_merge($query_location, $this->special_region_search($v->name, $v->zip));
                            } else {
                                $query_location = array_merge($query_location, $this->special_search($v->name, $v->zip));
                            }
                        }
                        if (sizeof($query_location)) {
                            $query->whereIn('zip', $query_location);
                        }
                    }
                });
            })
            ->when($postcode_condition, function ($query) use ($filter_cities) {
                $query->whereHas('job_location.location', function ($query) use ($filter_cities) {
                    $query_location = [];
                    foreach ($filter_cities as $v) {
                        $query_location[] = $v->zip;
                    }
                    if (sizeof($query_location)) {
                        $query->whereIn('zip', $query_location);
                    }
                });
            })
            ->when($category_condition, function ($query) use ($category_condition) {
                $query->whereHas('JobCategory', function ($query) use ($category_condition) {
                    if ($category_condition == "all") {
                    } else {
                        $query->whereIn('category_id', $category_condition);
                    }
                });
            })
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->where(function ($query) use ($keyword) {
                if ($keyword == "all") {
                } else {
                    $query->where('title', 'like', '%' . $keyword . '%');
                    // $query->orWhere('company', 'like', '%' . $keyword . '%');
                }
            })
            ->where(function ($query) use ($datePosted, $job_type) {

                $job_idx = 0;
                foreach ($job_type as $k => $v) {
                    if ($v['status'] == 1) {
                        switch ($k) {
                            case 0:
                                if ($job_idx == 0) {
                                    $query->where('job_type_id', 1);
                                } else {
                                    $query->orWhere('job_type_id', 1);
                                }
                                break;
                            case 1:
                                if ($job_idx == 0) {
                                    $query->where('job_type_id', 2);
                                } else {
                                    $query->orWhere('job_type_id', 2);
                                }
                                break;
                            case 2:
                                if ($job_idx == 0) {
                                    $query->where('job_type_id', 3);
                                } else {
                                    $query->orWhere('job_type_id', 3);
                                }
                                break;
                            case 3:
                                if ($job_idx == 0) {
                                    $query->where('job_type_id', 4);
                                } else {
                                    $query->orWhere('job_type_id', 4);
                                }
                                break;
                        }
                        $job_idx++;
                    }
                }

                $idx = 0;
                foreach ($datePosted as $k => $v) {
                    if ($v['status'] == 1) {
                        $query->where('created_at', '>', 0);
                        switch ($k) {
                            case 0:
                                break;
                            case 3: // 1-10 dage
                                if ($idx == 0) {
                                    $query->where('created_at', '<=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 1)));
                                    $query->where('created_at', '>=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 10)));
                                } else {
                                }
                                break;
                            case 4: // 11-20 dage
                                if ($idx == 0) {
                                    $query->where('created_at', '<=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 11)));
                                    $query->where('created_at', '>=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 20)));
                                } else {
                                }
                                break;
                            case 5: // 21-30 dage
                                if ($idx == 0) {
                                    $query->where('created_at', '<=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 21)));
                                    $query->where('created_at', '>=', date('Y-m-d', (strtotime(date("Y/m/d H:i:s")) - 3600 * 24 * 30)));
                                } else {
                                }
                                break;
                        }
                        $idx++;
                    }
                }
            })->count();


        return response()->json(['jobs' => $rows_jobs, 'count' => $rows_jobs_count], 200);
    }
    /**
     * EndPoint api/get_latest_jobs
     * HTTP SUBMIT : GET
     * Get latest jobs which will be used on home page.
     *
     * @return json with jobs as limit 6
     */
    public function get_latest_jobs(Request $request)
    {
        $rows_jobs = Job::with(['job_location', 'job_location.location', 'JobCategory'])
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->distinct()->take(6)->get();
        return response()->json($rows_jobs, 200);
    }
    /**
     * EndPoint api/update_job
     * HTTP SUBMIT : POST
     * Update Job with job details
     *
     * @return json with success or failed.
     */
    public function update_job(Request $request)
    {
        $job = $request->post('job');
        $job = json_decode($job);


        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;


        if (!Job::where('id', $job->id)->where('user_id', $user_id)->count()) {
            return response()->json(['result' => 'error'], 200);
        }

        $job_logo = 'empty';
        $job_logo_real_name = 'empty.png';
        $job_logo_file_size = '3143';

        if (!empty($_FILES['upload_job_logo_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_job_logo_file']['tmp_name'])) {


                $job_logo_real_name = $_FILES['upload_job_logo_file']['name'];
                $job_logo_file_size = $_FILES['upload_job_logo_file']['size'];

                $job_logo = md5(time() . rand()) . '_job_logo';
                $sTempFileName = public_path() . '/images/company_logo/' . $job_logo . '.png';
                if (move_uploaded_file($_FILES['upload_job_logo_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                    //ImageOptimizer::optimize($sTempFileName);
                } else {
                }
            }
        }


        $job_description = '';
        $description_real_name = '';
        $description_file_size = '';
        $description_file_type = '';
        if (!empty($_FILES['upload_job_description_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_job_description_file']['tmp_name'])) {


                $type = pathinfo($_FILES['upload_job_description_file']['name'], PATHINFO_EXTENSION);
                $description_file_type = $type;


                $description_real_name = $_FILES['upload_job_description_file']['name'];
                $description_file_size = $_FILES['upload_job_description_file']['size'];


                $job_description = md5(time() . rand()) . '_job_description.' . $type;
                $sTempFileName = public_path() . '/images/job_description/' . $job_description;
                if (move_uploaded_file($_FILES['upload_job_description_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                } else {
                }
            }
        }



        $exist_job = [];

        $exist_job['title'] = $job->title;
        $src = ['oe', 'aa', 'ae'];
        $dst = ['ø', 'å', 'æ'];
        $exist_job['seo'] = str_slug(str_replace($dst, $src, $job->title));
        $exist_job['company'] = $job->company;


        if (isset($job->education_id)) {
            $exist_job['education_id'] = $job->education_id->id;
        }

        $exist_job['description'] = $job->description;
        $exist_job['description_html'] = $job->description_html;


        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
        $job->job_deadline_date = str_replace($find_arr, $replace_arr, $job->job_deadline_date);
        $job->job_start_date = str_replace($find_arr, $replace_arr, $job->job_start_date);


        $exist_job['job_deadline_date'] = GeneralHelper::md_picker_parse_date($job->job_deadline_date);
        $exist_job['job_start_date'] = GeneralHelper::md_picker_parse_date($job->job_start_date);

        $exist_job['contact_person'] = $job->contact_person;
        $exist_job['contact_email'] = $job->contact_email;
        $exist_job['contact_phone'] = $job->contact_phone;


        if (isset($job->job_type_id)) {
            $exist_job['job_type_id'] = $job->job_type_id->id;
        }


        $experience = 0;
        if (isset($job->experience)) {
            $exist_job['experience'] = $job->experience;
        }

        if ($job_logo != 'empty') {
            $exist_job['logo'] = $job_logo . '.png';
            $exist_job['logo_real_name'] = $job_logo_real_name;
            $exist_job['logo_size'] = $job_logo_file_size;
        }

        if ($job_description != '') {

            if (JobDescriptionFile::where('job_id', $job->id)->first()) {
                JobDescriptionFile::where('job_id', $job->id)->update([
                    'description_name' => $job_description,
                    'description_real_name' => $description_real_name,
                    'description_file_size' => $description_file_size,
                    'description_file_type' => $description_file_type
                ]);
            } else {
                $new_job_description_file = new JobDescriptionFile();
                $new_job_description_file->job_id = $job->id;
                $new_job_description_file->description_name = $job_description;
                $new_job_description_file->description_real_name = $description_real_name;
                $new_job_description_file->description_file_size = $description_file_size;
                $new_job_description_file->description_file_type = $description_file_type;
                $new_job_description_file->save();
            }
        }


        Job::where('id', $job->id)->update($exist_job);

        JobCategory::where('job_id', $job->id)->delete();
        foreach ($job->job_category as $k => $v) {
            $new_job_categories = new JobCategory();
            $new_job_categories->job_id = $job->id;
            if (empty($v->id)) {
                $selected_category_id = GeneralHelper::insert_new_category($v->name);
            } else {
                $selected_category_id = $v->id;
            }
            $new_job_categories->category_id = $selected_category_id;
            $new_job_categories->save();
        }
        JobSkill::where('job_id', $job->id)->delete();
        foreach ($job->job_skill as $k => $v) {
            $new_job_skills = new JobSkill();
            $new_job_skills->job_id = $job->id;
            if (empty($v->id)) {
                $selected_skill_id = GeneralHelper::insert_new_skill($v->name);
            } else {
                $selected_skill_id = $v->id;
            }
            $new_job_skills->skill_id = $selected_skill_id;
            $new_job_skills->save();
        }
        JobLocation::where('job_id', $job->id)->delete();
        foreach ($job->job_location as $k => $v) {
            $new_job_locations = new JobLocation();
            $new_job_locations->job_id = $job->id;
            if (empty($v->id)) {
                $selected_location_id = GeneralHelper::insert_new_location($v->name);
            } else {
                $selected_location_id = $v->id;
            }
            $new_job_locations->location_id = $selected_location_id;
            $new_job_locations->save();
        }

        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/create_job
     * HTTP SUBMIT : POST
     * Create Job with job details and after checking job details, if matched with job seeker agent details, then matched users will be received notifcation.
     *
     * @param
     * job : json object with job details
     * @return json with success or failed.
     */
    public function create_job(Request $request)
    {
        $job = $request->post('job');
        $job = json_decode($job);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;


        if (!JobPermission::where('user_id', $user_id)->where('active', 1)->count()) {
            return response()->json(['result' => 'error'], 200);
        }

        $job_logo = 'empty';
        $job_logo_real_name = 'empty.png';
        $job_logo_file_size = '3143';

        if (!empty($_FILES['upload_job_logo_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_job_logo_file']['tmp_name'])) {

                $job_logo_real_name = $_FILES['upload_job_logo_file']['name'];
                $job_logo_file_size = $_FILES['upload_job_logo_file']['size'];

                $job_logo = md5(time() . rand()) . '_job_logo';
                $sTempFileName = public_path() . '/images/company_logo/' . $job_logo . '.png';
                if (move_uploaded_file($_FILES['upload_job_logo_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                    //ImageOptimizer::optimize($sTempFileName);
                } else {
                }
            }
        }

        $job_description = '';
        $description_real_name = '';
        $description_file_size = '';
        $description_file_type = '';

        if (!empty($_FILES['upload_job_description_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_job_description_file']['tmp_name'])) {


                $type = pathinfo($_FILES['upload_job_description_file']['name'], PATHINFO_EXTENSION);
                $description_file_type = $type;


                $description_real_name = $_FILES['upload_job_description_file']['name'];

                $description_file_size = $_FILES['upload_job_description_file']['size'];

                $job_description = md5(time() . rand()) . '_job_description.' . $type;
                $sTempFileName = public_path() . '/images/job_description/' . $job_description;
                if (move_uploaded_file($_FILES['upload_job_description_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                } else {
                }
            }
        }


        $title = $job->title;
        $src = ['oe', 'aa', 'ae'];
        $dst = ['ø', 'å', 'æ'];
        $seo = str_slug(str_replace($dst, $src, $title));
        $company = $job->company;

        $education_id = 0;
        if (isset($job->education_id)) {
            $education_id = $job->education_id->id;
        }

        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
        $job->job_deadline_date = str_replace($find_arr, $replace_arr, $job->job_deadline_date);
        $job->job_start_date = str_replace($find_arr, $replace_arr, $job->job_start_date);

        $description = $job->description;
        $description_html = $job->description_html;
        Log::info('create job deadline date');
        Log::info($job->job_deadline_date);
        $job_deadline_date = GeneralHelper::md_picker_parse_date($job->job_deadline_date);
        $job_start_date = GeneralHelper::md_picker_parse_date($job->job_start_date);

        $contact_person = $job->contact_person;
        $contact_email = $job->contact_email;
        $contact_phone = $job->contact_phone;
        $permission = $job->permission;

        $job_type_id = 0;
        if (isset($job->job_type_id)) {
            $job_type_id = $job->job_type_id->id;
        }


        $experience = 0;
        if (isset($job->experience)) {
            $experience = $job->experience;
        }

        $new_job = new Job();
        $new_job->user_id = $user_id;
        $new_job->contact_person = $contact_person;
        $new_job->contact_email = $contact_email;
        $new_job->contact_phone = $contact_phone;
        $new_job->title = $title;
        $new_job->seo = $seo;
        $new_job->company = $company;
        $new_job->experience = $experience;
        $new_job->education_id = $education_id;
        $new_job->description = $description;
        $new_job->description_html = $description_html;
        $new_job->job_type_id = $job_type_id;
        $new_job->job_start_date = $job_start_date;
        $new_job->job_deadline_date = $job_deadline_date;
        $new_job->logo = $job_logo . '.png';
        $new_job->logo_real_name = $job_logo_real_name;
        $new_job->logo_size = $job_logo_file_size;
        $new_job->permission = $permission;


        $new_job->is_redirect = 1;

        if ($new_job->save()) {

            if (Company::where('company_name', $company)->first()) {
            } else {
                $new_company = new Company();
                $new_company->company_name = $company;
                $new_company->save();
            }

            if ($job_description) {
                $new_job_description_file = new JobDescriptionFile();
                $new_job_description_file->job_id = $new_job->id;
                $new_job_description_file->description_name = $job_description;
                $new_job_description_file->description_real_name = $description_real_name;
                $new_job_description_file->description_file_size = $description_file_size;
                $new_job_description_file->description_file_type = $description_file_type;
                $new_job_description_file->save();
            }


            $categoryArr = [];
            foreach ($job->categories as $k => $v) {
                $new_job_categories = new JobCategory();
                $new_job_categories->job_id = $new_job->id;

                if (empty($v->id)) {
                    $selected_category_id = GeneralHelper::insert_new_category($v->name);
                } else {
                    $selected_category_id = $v->id;
                }
                $new_job_categories->category_id = $selected_category_id;
                $new_job_categories->save();
                $categoryArr[] = $selected_category_id;
            }

            foreach ($job->skills as $k => $v) {
                $new_job_skills = new JobSkill();
                $new_job_skills->job_id = $new_job->id;
                if (empty($v->id)) {
                    $selected_skill_id = GeneralHelper::insert_new_skill($v->name);
                } else {
                    $selected_skill_id = $v->id;
                }
                $new_job_skills->skill_id = $selected_skill_id;
                $new_job_skills->save();
            }

            $locationArr = [];
            foreach ($job->location as $k => $v) {
                $new_job_locations = new JobLocation();
                $new_job_locations->job_id = $new_job->id;

                if (empty($v->id)) {
                    $selected_location_id = GeneralHelper::insert_new_location($v->name);
                } else {
                    $selected_location_id = $v->id;
                }
                $new_job_locations->location_id = $selected_location_id;
                $new_job_locations->save();
                $locationArr[] = $selected_location_id;
            }

            JobPermission::where('user_id', $user_id)->update(['active' => 0]);


            $matched_users = [];
            //company matched
            $jobagentA = JobAgent::with(['agent_company', 'agent_company.company'])
                ->whereHas('agent_company.company', function ($query) use ($company) {
                    $query->where('company_name', $company);
                })
                ->where('type', 1)
                ->get();

            $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentA));

            if ($job_type_id) { //exist jobtype


                //company and jobtype matched
                $jobagentAA = JobAgent::with(['agent_company', 'agent_company.company'])
                    ->whereHas('agent_company.company', function ($query) use ($company) {
                        $query->where('company_name', $company);
                    })
                    ->where('job_type_id', $job_type_id)
                    ->where('type', 2)
                    ->get();

                $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentAA));



                //job type matched
                $jobagentB = JobAgent::where('job_type_id', $job_type_id)->where('type', 1)->get();
                $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentB));
            }


            if (sizeof($categoryArr) && sizeof($locationArr)) {

                //category and location Matched
                $jobagentZ = JobAgent::with(['agent_location', 'agent_location.location', 'agent_category', 'agent_category.category'])
                    ->whereHas('agent_category.category', function ($query) use ($categoryArr) {
                        $query->whereIn('id', $categoryArr);
                    })
                    ->whereHas('agent_location.location', function ($query) use ($locationArr) {
                        $query->whereIn('id', $locationArr);
                    })
                    ->where('type', 2)->get();
                $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentZ));

                //category and location and company matched
                $jobagentZZ = JobAgent::with(['agent_company', 'agent_company.company', 'agent_location', 'agent_location.location', 'agent_category', 'agent_category.category'])
                    ->whereHas('agent_category.category', function ($query) use ($categoryArr) {
                        $query->whereIn('id', $categoryArr);
                    })
                    ->whereHas('agent_location.location', function ($query) use ($locationArr) {
                        $query->whereIn('id', $locationArr);
                    })
                    ->whereHas('agent_company.company', function ($query) use ($company) {
                        $query->where('company_name', $company);
                    })
                    ->where('type', 3)->get();
                $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentZZ));


                if ($job_type_id) {
                    //cateogry and company and jobtype matched
                    $jobagentZZZ = JobAgent::with(['agent_company', 'agent_company.company', 'agent_location', 'agent_location.location', 'agent_category', 'agent_category.category'])
                        ->whereHas('agent_category.category', function ($query) use ($categoryArr) {
                            $query->whereIn('id', $categoryArr);
                        })
                        ->whereHas('agent_location.location', function ($query) use ($locationArr) {
                            $query->whereIn('id', $locationArr);
                        })
                        ->whereHas('agent_company.company', function ($query) use ($company) {
                            $query->where('company_name', $company);
                        })
                        ->where('job_type_id', $job_type_id)
                        ->where('type', 4)->get();
                    $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentZZZ));
                }
            } else if (sizeof($categoryArr)) {

                //categoryMatched
                $jobagentC = JobAgent::with(['agent_category', 'agent_category.category'])
                    ->whereHas('agent_category.category', function ($query) use ($categoryArr) {
                        $query->whereIn('id', $categoryArr);
                    })
                    ->where('type', 1)->get();
                $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentC));

                //category and company matched
                $jobagentCC = JobAgent::with(['agent_company', 'agent_company.company', 'agent_category', 'agent_category.category'])
                    ->whereHas('agent_category.category', function ($query) use ($categoryArr) {
                        $query->whereIn('id', $categoryArr);
                    })
                    ->whereHas('agent_company.company', function ($query) use ($company) {
                        $query->where('company_name', $company);
                    })
                    ->where('type', 2)->get();
                $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentCC));

                if ($job_type_id) {
                    //cateogry and company and jobtype matched
                    $jobagentCCC = JobAgent::with(['agent_company', 'agent_company.company', 'agent_category', 'agent_category.category'])
                        ->whereHas('agent_category.category', function ($query) use ($categoryArr) {
                            $query->whereIn('id', $categoryArr);
                        })
                        ->whereHas('agent_company.company', function ($query) use ($company) {
                            $query->where('company_name', $company);
                        })
                        ->where('job_type_id', $job_type_id)
                        ->where('type', 3)->get();
                    $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentCCC));
                }
            } else if (sizeof($locationArr)) {

                //location matched
                $jobagentD = JobAgent::with(['agent_location', 'agent_location.location'])
                    ->whereHas('agent_location.location', function ($query) use ($locationArr) {
                        $query->whereIn('id', $locationArr);
                    })
                    ->where('type', 1)->get();
                $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentD));

                //location and company matched
                $jobagentDD = JobAgent::with(['agent_company', 'agent_company.company', 'agent_location', 'agent_location.location'])
                    ->whereHas('agent_company.company', function ($query) use ($company) {
                        $query->where('company_name', $company);
                    })
                    ->whereHas('agent_location.location', function ($query) use ($locationArr) {
                        $query->whereIn('id', $locationArr);
                    })
                    ->where('type', 2)->get();
                $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentDD));


                if ($job_type_id) {
                    //location and company and jobtype matched
                    $jobagentDDD = JobAgent::with(['agent_company', 'agent_company.company', 'agent_location', 'agent_location.location'])
                        ->whereHas('agent_company.company', function ($query) use ($company) {
                            $query->where('company_name', $company);
                        })
                        ->whereHas('agent_location.location', function ($query) use ($locationArr) {
                            $query->whereIn('id', $locationArr);
                        })
                        ->where('job_type_id', $job_type_id)
                        ->where('type', 3)->get();
                    $matched_users = array_merge($matched_users, $this->generate_notification($new_job->id, $jobagentDDD));
                }
            }


            $matched_users =  collect($matched_users)->unique();
            $final_json = [];
            foreach ($matched_users as $k => $v) {
                $final_json[] = $v;
            }
            return response()->json(['result' => 'success', 'matched_users' => $final_json], 200);
        } else {
            return response()->json(['result' => 'failed', 'matched_users' => []], 200);
        }
    }
    /**
     * Generate notification with given params, when job posted, it will be used for send notifcation to users who matched with agent condition.
     * @param
     * job_id : id of job which should be deleted
     * jobagent : array of agent
     * @return array with agent user ids
     */
    public function generate_notification($job_id, $jobagent)
    {

        $return_ids = [];
        foreach ($jobagent as $k => $v) {
            $new_notification = new Notification();
            $new_notification->user_id = $v->user_id;
            $new_notification->name = $job_id;
            $new_notification->sender = $v->id;
            $new_notification->type = 10;
            $new_notification->save();
            $return_ids[] = $v->user_id;
        }
        return $return_ids;
    }
    /**
     * EndPoint api/apply_job
     * HTTP SUBMIT : POST
     * Apply Job - submit applications with given job id and send email with attachment with them to employer email and added notification
     * @param
     * message : application message,
     * job_id : the id of job
     * company_user_id : the id of user who had posted this job
     * @return json with status as success or failed
     */
    public function apply_job(Request $request)
    {
        $message = $request->post('message');
        $job_id = $request->post('job_id');
        $company_user_id = $request->post('company_user_id');

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_type = $user->type;
        if ($user_type == 2) {
            return response()->json(['result' => 'error'], 200);
        }



        $cv_real_type = '';
        $cv_real_name = '';
        $cv_name = '';
        if (!empty($_FILES['upload_cv_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_cv_file']['tmp_name'])) {
                $type = pathinfo($_FILES['upload_cv_file']['name'], PATHINFO_EXTENSION);
                if ($type == 'pdf')
                    $cv_real_type = 'pdf';
                else if ($type == 'doc')
                    $cv_real_type = 'doc';
                else {
                }
                $cv_real_name = $_FILES['upload_cv_file']['name'];

                $cv_name = md5(time() . rand()) . '_apply_cv.' . $type;

                $sTempFileName = public_path() . '/images/job_apply_cv_document/' . $cv_name;

                if (move_uploaded_file($_FILES['upload_cv_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                } else {
                }
            }
        }

        $job_apply_video_type = '';
        $job_apply_video_real_name = '';
        $job_apply_video_name = '';
        if (!empty($_FILES['upload_video_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_video_file']['tmp_name'])) {

                $job_apply_video_type = pathinfo($_FILES['upload_video_file']['name'], PATHINFO_EXTENSION);

                $job_apply_video_real_name = $_FILES['upload_video_file']['name'];

                $job_apply_video_name = md5(time() . rand()) . '_apply_video.' . $job_apply_video_type;

                $sTempFileName = public_path() . '/images/job_apply_video/' . $job_apply_video_name;
                if (move_uploaded_file($_FILES['upload_video_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                } else {
                }
            }
        }



        $apply = UserJobsApply::where('user_id', $user_id)->where('job_id', $job_id)->first();

        if ($apply) {
            return response()->json(['result' => 'failed'], 200);
        }

        $new_apply = new UserJobsApply();
        $new_apply->user_id = $user_id;
        $new_apply->job_id = $job_id;
        $new_apply->employer_id = $company_user_id;

        if ($new_apply->save()) {

            $new_notification = new Notification();
            $new_notification->user_id = $user_id;
            $new_notification->sender = 1;
            $new_notification->type = 5;
            $new_notification->name = 0;
            $new_notification->save();

            $new_notification = new Notification();
            $new_notification->user_id = $company_user_id;
            $new_notification->sender = 1;
            $new_notification->type = 12;
            $new_notification->name = $user_id;
            $new_notification->save();

            $toEmail = User::where('id', $company_user_id)->first()->email;
            $employer_name = User::where('id', $company_user_id)->first()->name;


            $subject = 'Modtaget en meddelelse';

            $data['employer_name'] = $employer_name;

            $messageBody = view('email.received-job-application', ['data' => $data]);

            $this->send_notification_to_customer_email($toEmail, $subject, $messageBody);


            $new_apply_attach = new ApplyAttachFile();
            $new_apply_attach->apply_id = $new_apply->id;
            $new_apply_attach->message = $message;
            if ($cv_name != '') {
                $new_apply_attach->cv_real_type = $cv_real_type;
                $new_apply_attach->cv_real_name = $cv_real_name;
                $new_apply_attach->cv_name = $cv_name;
            }
            if ($job_apply_video_name != '') {
                $new_apply_attach->apply_video_type = $job_apply_video_type;
                $new_apply_attach->apply_video_real_name = $job_apply_video_real_name;
                $new_apply_attach->apply_video_name = $job_apply_video_name;
            }
            $new_apply_attach->save();
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/reject_candidate
     * HTTP SUBMIT : POST
     * Reject candidate
     * @param
     * candidate_id : id of candidate who will be rejected,
     * @return json with status as success or failed and employer name and seeker name
     */
    public function reject_candidate(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $candidate_id = $request->post('candidate_id');
        $job_id = $request->post('job_id');
        if (UserJobsApply::where('job_id', $job_id)->where('user_id', $candidate_id)->first()) {
            UserJobsApply::where('job_id', $job_id)->where('user_id', $candidate_id)->update(['reject' => 1]);

            $jobseeker_name = User::where('id', $candidate_id)->first()->name;
            $employer_name = User::where('id', $user_id)->first()->name;

            return response()->json(['result' => 'success', 'employer_name' => $employer_name, 'jobseeker_name' => $jobseeker_name], 200);
        } else {
            return response()->json(['result' => 'failed', 'employer_name' => '', 'jobseeker_name' => ''], 200);
        }
    }
    /**
     * EndPoint api/pending_candidate
     * HTTP SUBMIT : POST
     * Pending candidate
     * @param
     * candidate_id : id of candidate who will be pending,
     * @return json with status as success or failed and employer name and seeker name
     */
    public function pending_candidate(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $candidate_id = $request->post('candidate_id');
        $job_id = $request->post('job_id');
        if (UserJobsApply::where('job_id', $job_id)->where('user_id', $candidate_id)->first()) {
            UserJobsApply::where('job_id', $job_id)->where('user_id', $candidate_id)->update(['reject' => 2]);

            $jobseeker_name = User::where('id', $candidate_id)->first()->name;
            $employer_name = User::where('id', $user_id)->first()->name;

            return response()->json(['result' => 'success', 'employer_name' => $employer_name, 'jobseeker_name' => $jobseeker_name], 200);
        } else {
            return response()->json(['result' => 'failed', 'employer_name' => '', 'jobseeker_name' => ''], 200);
        }
    }
    /**
     * EndPoint api/update_candidate_note
     * HTTP SUBMIT : POST
     * Update note of candidate with given candidate id
     * @param
     * candidate_id : id of candidate,
     * @return json with status as success or failed
     */
    public function update_candidate_note(Request $request)
    {
        $candidate_id = $request->post('candidate_id');
        $note = $request->post('note');
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        if (Note::where('employer_id', $user_id)->where('user_id', $candidate_id)->first()) {
            Note::where('employer_id', $user_id)->where('user_id', $candidate_id)->update(['note' => $note]);
            return response()->json(['result' => 'success'], 200);
        } else {
            $new_note = new Note();
            $new_note->employer_id = $user_id;
            $new_note->user_id = $candidate_id;
            $new_note->note = $note;
            if ($new_note->save()) {
                return response()->json(['result' => 'success'], 200);
            } else {
                return response()->json(['result' => 'failed'], 200);
            }
        }
    }
    /**
     * EndPoint api/get_job_application
     * HTTP SUBMIT : POST
     * Update note of candidate with given candidate id
     * @param
     * candidate_id : id of candidate,
     * @return json with status as success or failed
     */
    public function get_job_application(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;
        $job_id = $request->post('job_id');

        if (!Job::where('user_id', $user_id)->where('id', $job_id)->count()) {
            return response()->json(['applications' => [], 'job' => null], 200);
        }

        $applications = UserJobsApply::with(['user', 'attach'])->where('job_id', $job_id)->get();
        $job = Job::where('user_id', $user_id)->where('id', $job_id)->first();
        foreach ($applications as $k => $v) {

            $v['interview_id'] = 0;
            $v['interview_time'] = '';
            $v['interview_seeker_id'] = '';
            $v['ready'] = 0;
            if ($interview = Interview::where('app_id', $v->id)->orderBy('created_at', 'desc')->first()) {
                $v['interview_id'] = explode('#', $interview->uuid)[0];
                $v['interview_time'] = $interview->interview_time;
                $v['interview_seeker_id'] = $interview->seeker_id;
                $v['ready'] = $interview->ready;
            }



            $v['note'] = null;
            if (Note::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()) {
                $v['note'] = Note::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()->note;
            }
            $v['rating_point'] = 0;
            if (Rating::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()) {
                $v['rating_point'] = Rating::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()->rating;
            }
            $job_answers = JobQuestions::with(['answer'])->where('job_id', $job_id)->where('user_id', $v->user_id)->get();

            $questions = JobQuestions::with(['answer'])->where('job_id', $job_id)->where('user_id', $v->user_id)->get();
            $final_questions = [];
            foreach ($questions as $key => $value) {
                $item['question_id'] = $value->id;
                $item['question'] = $value->question;
                $item['activated'] = false;

                if ($value->answer_type == 3) {
                    $item['text'] = true;
                    $item['video'] = true;
                } else {
                    if ($value->answer_type == 2) {
                        $item['video'] = true;
                        $item['text'] = false;
                    } else if ($value->answer_type == 1) {
                        $item['video'] = false;
                        $item['text'] = true;
                    } else {
                    }
                }
                $item['answer'] = $value->answer;
                $final_questions[] = $item;
            }
            $v['questions'] = $final_questions;
            $v['answers'] = $job_answers;

            $applications[$k] = $v;
        }

        return response()->json(['applications' => $applications, 'job' => $job], 200);
    }
    public function get_my_jobs_test(Request $request, $user_id)
    {
        // $jobs = Job::with(['job_apply', 'job_apply_reject', 'job_visit'])->withCount(['job_apply', 'job_apply_reject', 'job_visit'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->where('is_active', 1)->get();
        // $count = Job::with(['job_apply', 'job_apply_reject', 'job_visit'])->withCount(['job_apply', 'job_apply_reject', 'job_visit'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->where('is_active', 1)->count();

        $jobs = Job::with(['job_apply', 'job_apply_reject', 'job_visit'])->withCount(['job_apply', 'job_apply_reject', 'job_visit'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();
        $count = Job::with(['job_apply', 'job_apply_reject', 'job_visit'])->withCount(['job_apply', 'job_apply_reject', 'job_visit'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->count();

        $total_apply_cnt = 0;
        foreach ($jobs as $key => $job) {
            $job_id = $job->id;
            $jobs[$key]['applications'] = [];

            $applications = UserJobsApply::with(['user', 'attach'])->where('job_id', $job_id)->get();

            foreach ($applications as $k => $v) {

                $v['interview_id'] = 0;
                $v['interview_date'] = '';
                $v['interview_time'] = '';
                $v['interview_seeker_id'] = '';
                $v['ready'] = 0;
                if ($interview = Interview::where('app_id', $v->id)->orderBy('created_at', 'desc')->first()) {
                    $v['interview_id'] = explode('#', $interview->uuid)[0];
                    $v['interview_date'] = $interview->interview_time;
                    $v['interview_time'] = $interview->start_hour . ":" . $interview->start_minute . " ~ " . $interview->end_hour . ":" . $interview->end_minute;
                    $v['interview_seeker_id'] = $interview->seeker_id;
                    $v['ready'] = $interview->ready;
                }



                $v['note'] = null;
                if (Note::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()) {
                    $v['note'] = Note::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()->note;
                }
                $v['rating_point'] = 0;
                if (Rating::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()) {
                    $v['rating_point'] = Rating::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()->rating;
                }
                $job_answers = JobQuestions::with(['answer'])->where('job_id', $job_id)->where('user_id', $v->user_id)->get();

                $questions = JobQuestions::with(['answer'])->where('job_id', $job_id)->where('user_id', $v->user_id)->get();
                $final_questions = [];
                foreach ($questions as $value) {
                    $item['question_id'] = $value->id;
                    $item['question'] = $value->question;
                    $item['activated'] = false;

                    if ($value->answer_type == 3) {
                        $item['text'] = true;
                        $item['video'] = true;
                    } else {
                        if ($value->answer_type == 2) {
                            $item['video'] = true;
                            $item['text'] = false;
                        } else if ($value->answer_type == 1) {
                            $item['video'] = false;
                            $item['text'] = true;
                        } else {
                        }
                    }
                    $item['answer'] = $value->answer;
                    $final_questions[] = $item;
                }
                $v['questions'] = $final_questions;
                $v['answers'] = $job_answers;

                $applications[$k] = $v;
            }

            $jobs[$key]['applications'] = $applications;
            $jobs[$key]['opened'] = false;
            $total_apply_cnt += $job->job_apply_count;
        }

        $job_apply_reject_cnt = 0;
        foreach ($jobs as $job) {
            $job_apply_reject_cnt += $job->job_apply_reject_count;
        }
        return response()->json(['result' => 'success', 'jobs' => $jobs, 'count' => $count, 'total_apply_cnt' => $total_apply_cnt, 'total_reject_cnt' => $job_apply_reject_cnt], 200);
    }

    /**
     * EndPoint api/get_my_jobs
     * HTTP SUBMIT : GET
     * Get jobs details and applied job count and declind job count posted by given user
     *
     * @return json with first name, last name, email
     */
    public function get_my_jobs(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        // $jobs = Job::with(['job_apply', 'job_apply_reject', 'job_visit'])->withCount(['job_apply', 'job_apply_reject', 'job_visit'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->where('is_active', 1)->get();
        // $count = Job::with(['job_apply', 'job_apply_reject', 'job_visit'])->withCount(['job_apply', 'job_apply_reject', 'job_visit'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->where('is_active', 1)->count();

        $jobs = Job::with(['job_apply', 'job_apply_reject', 'job_visit'])->withCount(['job_apply', 'job_apply_reject', 'job_visit'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();
        $count = Job::with(['job_apply', 'job_apply_reject', 'job_visit'])->withCount(['job_apply', 'job_apply_reject', 'job_visit'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->count();

        $total_apply_cnt = 0;
        foreach ($jobs as $key => $job) {
            $job_id = $job->id;
            $jobs[$key]['applications'] = [];

            $applications = UserJobsApply::with(['user', 'attach'])->where('job_id', $job_id)->get();

            foreach ($applications as $k => $v) {

                $v['interview_id'] = 0;
                $v['interview_date'] = '';
                $v['interview_time'] = '';
                $v['interview_seeker_id'] = '';
                $v['ready'] = 0;
                if ($interview = Interview::where('app_id', $v->id)->orderBy('created_at', 'desc')->first()) {
                    $v['interview_id'] = explode('#', $interview->uuid)[0];
                    $v['interview_date'] = $interview->interview_time;
                    $v['interview_time'] = $interview->start_hour . ":" . $interview->start_minute . " ~ " . $interview->end_hour . ":" . $interview->end_minute;
                    $v['interview_seeker_id'] = $interview->seeker_id;
                    $v['ready'] = $interview->ready;
                }



                $v['note'] = null;
                if (Note::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()) {
                    $v['note'] = Note::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()->note;
                }
                $v['rating_point'] = 0;
                if (Rating::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()) {
                    $v['rating_point'] = Rating::where('employer_id', $user_id)->where('user_id', $v->user_id)->first()->rating;
                }
                $job_answers = JobQuestions::with(['answer'])->where('job_id', $job_id)->where('user_id', $v->user_id)->get();

                $questions = JobQuestions::with(['answer'])->where('job_id', $job_id)->where('user_id', $v->user_id)->get();
                $final_questions = [];
                foreach ($questions as $value) {
                    $item['question_id'] = $value->id;
                    $item['question'] = $value->question;
                    $item['activated'] = false;

                    if ($value->answer_type == 3) {
                        $item['text'] = true;
                        $item['video'] = true;
                    } else {
                        if ($value->answer_type == 2) {
                            $item['video'] = true;
                            $item['text'] = false;
                        } else if ($value->answer_type == 1) {
                            $item['video'] = false;
                            $item['text'] = true;
                        } else {
                        }
                    }
                    $item['answer'] = $value->answer;
                    $final_questions[] = $item;
                }
                $v['questions'] = $final_questions;
                $v['answers'] = $job_answers;

                $applications[$k] = $v;
            }
            Log::info(json_encode($applications));

            $jobs[$key]['applications'] = $applications;
            $jobs[$key]['opened'] = false;
            $total_apply_cnt += $job->job_apply_count;
        }

        $job_apply_reject_cnt = 0;
        foreach ($jobs as $job) {
            $job_apply_reject_cnt += $job->job_apply_reject_count;
        }
        return response()->json(['result' => 'success', 'jobs' => $jobs, 'count' => $count, 'total_apply_cnt' => $total_apply_cnt, 'total_reject_cnt' => $job_apply_reject_cnt], 200);
    }
    /**
     * EndPoint api/delete_job
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Delete job from given job id
     * @param
     * job_id : id of job which should be deleted
     * @return json with status with success or failed
     */
    public function delete_job(Request $request, $job_id)
    {
        if (Job::where('id', $job_id)->update(['is_active' => 0])) {
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/get_job_form
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get Job form details from given job id
     * @param  job_id
     * @return json with status with job form details
     */
    public function get_job_form(Request $request, $job_id)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;


        if (Job::where('id', $job_id)->where('user_id', $user_id)->first()) {
            $job = Job::with(['JobCategory', 'JobCategory.category', 'job_location', 'job_location.location', 'JobSkill', 'JobSkill.skill', 'job_desc_file'])->where('id', $job_id)->where('user_id', $user_id)->first();
            return response()->json(['result' => 'success', 'job' => $job], 200);
        } else {
            return response()->json(['result' => 'failed', 'job' => null], 200);
        }
    }
    /**
     * EndPoint api/get_job_detail_not_authed
     * HTTP SUBMIT : GET
     * Get Job details from given job id on guest way
     * @param
     * job_id : the id of job
     * @return json with status with job details
     */
    public function get_job_detail_not_authed(Request $request, $job_id)
    {
        if (Job::with(['job_degree', 'JobCategory', 'JobCategory.category', 'job_location', 'job_location.location', 'JobSkill', 'JobSkill.skill'])->where('id', $job_id)->first()) {
            $job = Job::with(['user', 'user.user_company', 'job_degree', 'JobCategory', 'JobCategory.category', 'job_location', 'job_location.location', 'JobSkill', 'JobSkill.skill', 'job_desc_file'])->where('id', $job_id)->first();

            $post_logo = '';
            if ($job->is_redirect) {
                $post_logo = $job->user->user_company->post_logo;
            } else {
                if (Company::where('company_name', '=', $job->company)->first()) {
                    $post_logo = Company::where('company_name', '=', $job->company)->first()->post_logo;
                } else {
                }
            }
            if ($post_logo == null) {
                $post_logo = 'sample.png';
            }
            return response()->json(['result' => 'success', 'job' => $job, 'post_logo' => $post_logo], 200);
        } else {
            return response()->json(['result' => 'failed', 'job' => null, 'post_logo' => null], 200);
        }
    }
    /**
     * EndPoint api/get_job_detail
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get Job details from given job id in authed
     * @param
     * job_id : the id of job
     * @return json with status with job details
     */
    public function get_job_detail(Request $request, $job_id)
    {

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $is_job_saved = false;
        if ($user->type == 1) {
            if (JobVisit::where('job_id', $job_id)->where('visitor_id', $user_id)->count()) {
            } else {
                $new_job_visit = new JobVisit();
                $new_job_visit->job_id = $job_id;
                $new_job_visit->visitor_id = $user_id;
                $new_job_visit->save();
            }


            if (UserJobSaved::where('user_id', $user_id)->where('job_id', $job_id)->first()) {
                $is_job_saved = true;
            }
        }

        if (Job::with(['job_degree', 'JobCategory', 'JobCategory.category', 'job_location', 'job_location.location', 'JobSkill', 'JobSkill.skill'])->where('id', $job_id)->first()) {
            $job = Job::with(['user', 'user.user_company', 'job_degree', 'JobCategory', 'JobCategory.category', 'job_location', 'job_location.location', 'JobSkill', 'JobSkill.skill', 'job_desc_file'])->where('id', $job_id)->first();
            $post_logo = '';
            if ($job->is_redirect) {
                $post_logo = $job->user->user_company->post_logo;
            } else {
                if (Company::where('company_name', '=', $job->company)->first()) {
                    $post_logo = Company::where('company_name', '=', $job->company)->first()->post_logo;
                } else {
                }
            }
            if ($post_logo == null) {
                $post_logo = 'sample.png';
            }

            return response()->json(['result' => 'success', 'job' => $job, 'post_logo' => $post_logo, 'is_job_saved' => $is_job_saved], 200);
        } else {
            return response()->json(['result' => 'failed', 'job' => null, 'post_logo' => null, 'is_job_saved' => false], 200);
        }
    }
    /**
     * Send email to given email with subject and message
     * @param
     * to_email : the email which will be sent to
     * subject : email subject
     * message : email content
     * @return json with status with job details
     */
    public function send_notification_to_customer_email($to_email, $subject, $message)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = 'utf-8';
            $mail->SMTPAuth = true;
            $mail->Host = 'smtpout.secureserver.net';
            $mail->Port = 465;
            $mail->SMTPSecure = 'ssl';
            $mail->SMTPAuth   = true;
            $mail->Username = 'info@jobbyen.dk';
            $mail->Password = '6Buq9eRCw4L7qOg0BEj2';


            $mail->SetFrom('info@jobbyen.dk', 'Jobbyen ApS');
            $mail->addAddress($to_email, 'Jobbyen.dk');

            $mail->IsHTML(true);

            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = $message;

            if (!$mail->send()) {
            } else {
            }
        } catch (phpmailerException $e) {
            dd($e);
        } catch (Exception $e) {
            dd($e);
        }
    }


    /**
     * EndPoint api/submit_common_answer
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Submit answer to given job id and employer id and send email with these answer
     * @param
     * job_id : id of job which should answer
     * employer_id : the id of employer who had post this job
     * question_ids : questions of json array
     * @return json with status with success
     */
    public function submit_common_answer(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $employer_id = $request->post('employer_id');

        $question_ids = $request->post('question_ids');
        $question_ids = json_decode($question_ids);

        $text_answers_arr = $request->post('text_answers_arr');
        $text_answers_arr = json_decode($text_answers_arr);

        $employer_name = '';
        $employer_email = '';
        if (User::where('id', $employer_id)->first()) {
            $employer_name = User::where('id', $employer_id)->first()->name;
            $employer_email = User::where('id', $employer_id)->first()->email;
        }


        $bAnswered = false;

        foreach ($text_answers_arr as $k => $v) {
            if (CommonAnswer::where('question_id', $v->id)->first()) {
                CommonAnswer::where('question_id', $v->id)->update(['text_answer' => $v->text_answer]);
            } else {
                $bAnswered = true;
                $new_common_answer = new CommonAnswer();
                $new_common_answer->question_id = $v->id;
                $new_common_answer->text_answer = $v->text_answer;
                $new_common_answer->save();
            }
        }

        $videos = [];
        foreach ($question_ids as $k => $v) {

            if (!empty($_FILES['upload_video_file_' . $v]['tmp_name'])) {

                if (is_uploaded_file($_FILES['upload_video_file_' . $v]['tmp_name'])) {

                    $type = pathinfo($_FILES['upload_video_file_' . $v]['name'], PATHINFO_EXTENSION);

                    $item['question_id'] = $v;
                    $item['type'] = $type;

                    $video_name = md5(time() . rand()) . '_video_answer.' . $type;

                    $item['video_path'] = $video_name;

                    $item['video_real_name'] = $_FILES['upload_video_file_' . $v]['name'];
                    $item['video_real_size'] = $_FILES['upload_video_file_' . $v]['size'];

                    $sTempFileName = public_path() . '/images/video_answers/' . $video_name;
                    if (move_uploaded_file($_FILES['upload_video_file_' . $v]['tmp_name'], $sTempFileName)) {
                        @chmod($sTempFileName, 0755);
                        $videos[] = $item;
                    } else {
                    }
                }
            }
        }

        foreach ($videos as $key => $value) {
            if (CommonAnswer::where('question_id', $value['question_id'])->first()) {
                CommonAnswer::where('question_id', $value['question_id'])->update(['video_answer' => $value['video_path'], 'video_answer_real_name' => $value['video_real_name'], 'video_answer_real_size' => $value['video_real_size'], 'video_answer_real_type' => $value['type']]);
            } else {

                $bAnswered = true;
                $new_common_answer = new CommonAnswer();
                $new_common_answer->question_id = $value['question_id'];
                $new_common_answer->video_answer = $value['video_path'];
                $new_common_answer->video_answer_real_name = $value['video_real_name'];
                $new_common_answer->video_answer_real_size = $value['video_real_size'];
                $new_common_answer->video_answer_real_type = $value['type'];
                $new_common_answer->save();
            }
        }

        if ($bAnswered) {
            // $new_notification = new Notification();
            // $new_notification->user_id = $employer_id;
            // $new_notification->sender = $job_id;
            // $new_notification->type = 21;
            // $new_notification->name = $user_id;
            // $new_notification->save();


            if ($employer_email != '') {
                $subject = $user->name . ' sendt svar';
                $data['employer_name'] = $employer_name;
                $data['jobseeker_name'] = $user->name;
                $messageBody = view('email.submit-answer', ['data' => $data]);
                $this->send_notification_to_customer_email($employer_email, $subject, $messageBody);
            }
        }

        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/submit_answer
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Submit answer to given job id and employer id and send email with these answer
     * @param
     * job_id : id of job which should answer
     * employer_id : the id of employer who had post this job
     * question_ids : questions of json array
     * @return json with status with success
     */
    public function submit_answer(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $job_id = $request->post('job_id');
        $employer_id = $request->post('employer_id');

        $question_ids = $request->post('question_ids');
        $question_ids = json_decode($question_ids);

        $text_answers_arr = $request->post('text_answers_arr');
        $text_answers_arr = json_decode($text_answers_arr);

        $employer_name = '';
        $employer_email = '';
        if (User::where('id', $employer_id)->first()) {
            $employer_name = User::where('id', $employer_id)->first()->name;
            $employer_email = User::where('id', $employer_id)->first()->email;
        }


        $bAnswered = false;

        foreach ($text_answers_arr as $k => $v) {
            if (JobAnswer::where('question_id', $v->id)->first()) {
                JobAnswer::where('question_id', $v->id)->update(['text_answer' => $v->text_answer]);
            } else {
                $bAnswered = true;
                $new_job_answer = new JobAnswer();
                $new_job_answer->question_id = $v->id;
                $new_job_answer->text_answer = $v->text_answer;
                $new_job_answer->save();
            }
        }

        $videos = [];
        foreach ($question_ids as $k => $v) {

            if (!empty($_FILES['upload_video_file_' . $v]['tmp_name'])) {

                if (is_uploaded_file($_FILES['upload_video_file_' . $v]['tmp_name'])) {

                    $type = pathinfo($_FILES['upload_video_file_' . $v]['name'], PATHINFO_EXTENSION);

                    $item['question_id'] = $v;
                    $item['type'] = $type;

                    $video_name = md5(time() . rand()) . '_video_answer.' . $type;

                    $item['video_path'] = $video_name;

                    $item['video_real_name'] = $_FILES['upload_video_file_' . $v]['name'];
                    $item['video_real_size'] = $_FILES['upload_video_file_' . $v]['size'];

                    $sTempFileName = public_path() . '/images/video_answers/' . $video_name;
                    if (move_uploaded_file($_FILES['upload_video_file_' . $v]['tmp_name'], $sTempFileName)) {
                        @chmod($sTempFileName, 0755);
                        $videos[] = $item;
                    } else {
                    }
                }
            }
        }

        foreach ($videos as $key => $value) {
            if (JobAnswer::where('question_id', $value['question_id'])->first()) {
                JobAnswer::where('question_id', $value['question_id'])->update(['video_answer' => $value['video_path'], 'video_answer_real_name' => $value['video_real_name'], 'video_answer_real_size' => $value['video_real_size'], 'video_answer_real_type' => $value['type']]);
            } else {

                $bAnswered = true;
                $new_job_answer = new JobAnswer();
                $new_job_answer->question_id = $value['question_id'];
                $new_job_answer->video_answer = $value['video_path'];
                $new_job_answer->video_answer_real_name = $value['video_real_name'];
                $new_job_answer->video_answer_real_size = $value['video_real_size'];
                $new_job_answer->video_answer_real_type = $value['type'];
                $new_job_answer->save();
            }
        }


        if ($bAnswered) {
            $new_notification = new Notification();
            $new_notification->user_id = $employer_id;
            $new_notification->sender = $job_id;
            $new_notification->type = 21;
            $new_notification->name = $user_id;
            $new_notification->save();


            if ($employer_email != '') {
                $subject = $user->name . ' sendt svar';
                $data['employer_name'] = $employer_name;
                $data['jobseeker_name'] = $user->name;
                $messageBody = view('email.submit-answer', ['data' => $data]);
                $this->send_notification_to_customer_email($employer_email, $subject, $messageBody);
            }
        }

        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/get_common_answer
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get common answer from given ask id
     * @param
     * app_id : application id
     * @return json with status with questions which relevant application id
     */
    public function get_common_answer(Request $request, $ask_id)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        if (CommonQuestion::with(['answer'])->where('ask_id', $ask_id)->count()) {
            $employer_id = CommonAsk::where('id', $ask_id)->first()->employer_id;
            $common_questions = CommonQuestion::with(['answer'])->where('ask_id', $ask_id)->get();
            return response()->json(['result' => 'success', 'questions' => $common_questions, 'emp_id' => $employer_id], 200);
        } else {
            return response()->json(['result' => 'failed', 'questions' => [], 'emp_id' => ''], 200);
        }
    }
    /**
     * EndPoint api/get_job_application_answer
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get application answer from given application id
     * @param
     * app_id : application id
     * @return json with status with questions which relevant application id
     */
    public function get_job_application_answer(Request $request, $app_id)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        if (UserJobsApply::where('user_id', $user_id)->where('id', $app_id)->first()) {

            $job_id = UserJobsApply::where('user_id', $user_id)->where('id', $app_id)->first()->job_id;

            $employer_id = UserJobsApply::where('user_id', $user_id)->where('id', $app_id)->first()->employer_id;

            $job_title = Job::where('id', $job_id)->first()->title;

            $job_questions = JobQuestions::with(['answer'])->where('job_id', $job_id)->where('user_id', $user_id)->get();

            return response()->json(['result' => 'success', 'questions' => $job_questions, 'job_title' => $job_title, 'job_id' => $job_id, 'emp_id' => $employer_id], 200);
        } else {
            return response()->json(['result' => 'failed', 'questions' => [], 'job_title' => '', 'job_id' => '', 'emp_id' => ''], 200);
        }
    }
    /**
     * EndPoint api/get_job_applications_from_jobseeker
     * JWT TOKEN which provided by token after user logged in
     * Get applied job applications which submit by given user.
     * @return json with array with applied job applications.
     */
    public function get_job_applications_from_jobseeker(Request $request, $type)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        if ($type == 1) {
            $applications = UserJobsApply::with(['job', 'attach'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();
            foreach ($applications as $k => $v) {
                $count = JobQuestions::where('job_id', $v->job_id)->where('user_id', $v->user_id)->count();
                $v['questions_count'] = $count;
                $applications[$k] = $v;
            }
            return response()->json($applications, 200);
        } else if ($type == 2) {
            $asks = CommonAsk::with(['emp', 'emp.user_company', 'emp.user_company.company_location'])->where('user_id', $user_id)->get();
            return response()->json($asks, 200);
        } else {
        }
    }
    /**
     * EndPoint api/delete_cv_question
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Delete cv question with given question id and selected user id from employer side, if question deleted, then delete also the answer videos.
     * @param
     * question_id : the id of question what will be deleted
     * selected_user_id : the user id which will be received
     * @return json with result success or failed
     */
    public function delete_cv_question(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $selected_user_id = $request->post('selected_user_id');
        $question_id = $request->post('question_id');

        $common_ask = CommonAsk::where('employer_id', $user_id)->where('user_id', $selected_user_id)->first();
        if($common_ask){

        }
        CommonQuestion::where('id', $question_id)->delete();
        if (CommonAnswer::where('question_id', $question_id)->first()) {
            $video_answer = CommonAnswer::where('question_id', $question_id)->first()->video_answer;
            if ($video_answer != '' && $video_answer != null) {
                if (file_exists(public_path() . '/images/video_answers/' . $video_answer)) {
                    @unlink(public_path() . '/images/video_answers/' . $video_answer);
                } else {
                }
            }
            CommonAnswer::where('question_id', $question_id)->delete();
        }

        $questions = [];
        $ask = CommonAsk::where('employer_id', $user_id)->where('user_id', $selected_user_id)->first();
        if($ask){
            $questions = CommonQuestion::with(['answer'])->where('ask_id', $ask->id)->get();
        }

        $final_questions = [];
        foreach ($questions as $value) {
            $item['question_id'] = $value->id;
            $item['question'] = $value->question;
            $item['activated'] = false;

            if ($value->answer_type == 3) {
                $item['text'] = true;
                $item['video'] = true;
            } else {
                if ($value->answer_type == 2) {
                    $item['video'] = true;
                    $item['text'] = false;
                } else if ($value->answer_type == 1) {
                    $item['video'] = false;
                    $item['text'] = true;
                } else {
                }
            }
            $item['answer'] = $value->answer;
            $final_questions[] = $item;
        }




        return response()->json(['result' => 'success', 'questions'=>$final_questions, 'answers'=>$questions], 200);
    }
    /**
     * EndPoint api/delete_job_question
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Delete job question with given question id and selected user id from employer side, if question deleted, then delete also the answer videos.
     * @param
     * job_id : the id of job
     * question_id : the id of question what will be deleted
     * selected_user_id : the user id which will be received
     * @return json with result success or failed
     */
    public function delete_job_question(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $job_id = $request->post('job_id');
        $selected_user_id = $request->post('selected_user_id');
        $question_id = $request->post('question_id');

        JobQuestions::where('id', $question_id)->where('job_id', $job_id)->where('user_id', $selected_user_id)->delete();


        if (JobAnswer::where('question_id', $question_id)->first()) {
            $video_answer = JobAnswer::where('question_id', $question_id)->first()->video_answer;
            if ($video_answer != '' && $video_answer != null) {
                if (file_exists(public_path() . '/images/video_answers/' . $video_answer)) {
                    @unlink(public_path() . '/images/video_answers/' . $video_answer);
                } else {
                }
            }
            JobAnswer::where('question_id', $question_id)->delete();
        }
        return response()->json(['result' => 'success'], 200);
    }

    /**
     * EndPoint api/create_job_question
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create job questions with given job id and question details and send notification to user id
     * @param
     * questions : questions json arry
     * job_id : the id of job which questions should be generated from.
     * selected_user_id : the user id which will be received
     * @return json with result success with created questions
     */
    public function create_job_question(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $questions = $request->post('questions');
        $job_id = $request->post('job_id');
        $selected_user_id = $request->post('selected_user_id');
        $questions = json_decode($questions);


        $jobseeker_email = '';
        $jobseeker_name = '';
        if (User::where('id', $selected_user_id)->first()) {
            $jobseeker_email = User::where('id', $selected_user_id)->first()->email;
            $jobseeker_name = User::where('id', $selected_user_id)->first()->name;
        }


        $bAsked = false;

        foreach ($questions as $k => $v) {
            if (JobQuestions::where('id', $v->question_id)->first()) {
                if ($v->video && $v->text) {
                    $answer_type = 3;
                } else {
                    if ($v->video) {
                        $answer_type = 2;
                    } else if ($v->text) {
                        $answer_type = 1;
                    } else {
                    }
                }
                JobQuestions::where('id', $v->question_id)->update(['question' => $v->question, 'answer_type' => $answer_type]);
            } else {
                $bAsked = true;
                $new_job_question = new JobQuestions();
                $new_job_question->employer_id = $user_id;
                $new_job_question->job_id = $job_id;
                $new_job_question->user_id = $selected_user_id;
                $new_job_question->question = $v->question;
                if ($v->video && $v->text) {
                    $new_job_question->answer_type = 3;
                } else {
                    if ($v->video) {
                        $new_job_question->answer_type = 2;
                    } else if ($v->text) {
                        $new_job_question->answer_type = 1;
                    }
                }
                $new_job_question->save();
            }
        }

        $result_questions = JobQuestions::where('employer_id', $user_id)->where('job_id', $job_id)->where('user_id', $selected_user_id)->get();
        $final_questions = [];
        foreach ($result_questions as $key => $value) {
            $item['question_id'] = $value->id;
            $item['question'] = $value->question;
            $item['activated'] = false;

            if ($value->answer_type == 3) {
                $item['text'] = true;
                $item['video'] = true;
            } else {
                if ($value->answer_type == 2) {
                    $item['video'] = true;
                    $item['text'] = false;
                } else if ($value->answer_type == 1) {
                    $item['video'] = false;
                    $item['text'] = true;
                } else {
                }
            }
            $final_questions[] = $item;
        }


        if ($bAsked) {
            $new_notification = new Notification();
            $new_notification->user_id = $selected_user_id;
            $new_notification->sender = $job_id;
            $new_notification->type = 20;
            $new_notification->name = $user_id;
            $new_notification->save();

            if ($jobseeker_email != '') {
                $subject = $user->name . ' sendte spørgsmål';
                $data['employer_name'] = $user->name;
                $data['jobseeker_name'] = $jobseeker_name;
                $messageBody = view('email.submit-question', ['data' => $data]);
                $this->send_notification_to_customer_email($jobseeker_email, $subject, $messageBody);
            }
        }
        return response()->json(['result' => 'success', 'questions' => $final_questions], 200);
    }


    /**
     * EndPoint api/create_cv_question
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create cv questions with cv user id and question details and send notification to the user
     * @param
     * questions : questions json arry
     * job_id : the id of job which questions should be generated from.
     * selected_user_id : the user id which will be received
     * @return json with result success with created questions
     */
    public function create_cv_question(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $questions = $request->post('questions');
        $selected_user_id = $request->post('selected_user_id');
        $questions = json_decode($questions);


        $jobseeker_email = '';
        $jobseeker_name = '';
        if (User::where('id', $selected_user_id)->first()) {
            $jobseeker_email = User::where('id', $selected_user_id)->first()->email;
            $jobseeker_name = User::where('id', $selected_user_id)->first()->name;
        }


        $bAsked = false;

        if (CommonAsk::where('employer_id', $user_id)->where('user_id', $selected_user_id)->first()) {
            $ask_id = CommonAsk::where('employer_id', $user_id)->where('user_id', $selected_user_id)->first()->id;
        } else {
            $_ask = new CommonAsk();
            $_ask->employer_id = $user_id;
            $_ask->user_id = $selected_user_id;
            $_ask->save();
            $ask_id = $_ask->id;
        }



        foreach ($questions as $k => $v) {
            if (CommonQuestion::where('id', $v->question_id)->first()) {
                if ($v->video && $v->text) {
                    $answer_type = 3;
                } else {
                    if ($v->video) {
                        $answer_type = 2;
                    } else if ($v->text) {
                        $answer_type = 1;
                    } else {
                    }
                }
                CommonQuestion::where('id', $v->question_id)->update(['question' => $v->question, 'answer_type' => $answer_type]);
            } else {
                $bAsked = true;
                $new_common_question = new CommonQuestion();
                $new_common_question->ask_id = $ask_id;
                $new_common_question->question = $v->question;
                if ($v->video && $v->text) {
                    $new_common_question->answer_type = 3;
                } else {
                    if ($v->video) {
                        $new_common_question->answer_type = 2;
                    } else if ($v->text) {
                        $new_common_question->answer_type = 1;
                    }
                }
                $new_common_question->save();
            }
        }


        $questions = [];
        $ask = CommonAsk::where('employer_id', $user_id)->where('user_id', $selected_user_id)->first();
        if($ask){
            $questions = CommonQuestion::with(['answer'])->where('ask_id', $ask->id)->get();
        }

        $final_questions = [];
        foreach ($questions as $value) {
            $item['question_id'] = $value->id;
            $item['question'] = $value->question;
            $item['activated'] = false;

            if ($value->answer_type == 3) {
                $item['text'] = true;
                $item['video'] = true;
            } else {
                if ($value->answer_type == 2) {
                    $item['video'] = true;
                    $item['text'] = false;
                } else if ($value->answer_type == 1) {
                    $item['video'] = false;
                    $item['text'] = true;
                } else {
                }
            }
            $item['answer'] = $value->answer;
            $final_questions[] = $item;
        }
        if ($bAsked) {
            // $new_notification = new Notification();
            // $new_notification->user_id = $selected_user_id;
            // $new_notification->sender = $job_id;
            // $new_notification->type = 20;
            // $new_notification->name = $user_id;
            // $new_notification->save();

            if ($jobseeker_email != '') {
                $subject = $user->name . ' sendte spørgsmål';
                $data['employer_name'] = $user->name;
                $data['jobseeker_name'] = $jobseeker_name;
                $messageBody = view('email.submit-question', ['data' => $data]);
                $this->send_notification_to_customer_email($jobseeker_email, $subject, $messageBody);
            }
        }
        return response()->json(['result' => 'success', 'questions' => $final_questions, 'answers'=>$questions], 200);
    }
}
