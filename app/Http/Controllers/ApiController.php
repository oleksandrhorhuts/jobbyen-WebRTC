<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\GeneralHelper;
use App\User;
use Carbon\Carbon;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Nexmo;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\Job;
use App\Interview;
use App\CompanyInterview;
use App\ProfileVisit;
use App\UserJobsApply;

use App\Company;
use App\JobAgent;
use App\SeekerAgent;

use PDF;
use App\JobPermission;
use App\JobReport;
use App\Blog;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use App\ForgotLink;
use App\Notification;

use App\Cv;
use App\Rating;
use App\Message;
use App\TempMembership;
use Goutte;
use App\Place;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use App\JobLocation;
use App\City;
use App\CvDocument;
use App\CvVideo;
use App\CvCategory;
use App\CvEducation;
use App\CvExperience;
use App\CvLanguage;
use App\CvSkill;
use App\CvWish;
use App\JobAgentCategory;
use App\JobAgentLocation;

use App\UserJobSaved;
use App\ApplyAttachFile;
use App\Contact;
use App\JobCategory;
use App\JobVisit;

use App\InterviewInformation;

class ApiController extends Controller
{
    //
    protected $user_id = null;
    /**
     * EndPoint api/social_main_login
     * HTTP SUBMIT : POST
     * Login user with social, instead of default login.
     *
     * @param  timezone tz, email, password
     * @return json with JWTTOKEN, user_id, membership, unread_count for message, notification status, prepared cv for candidate.
     */
    public function social_main_login(Request $request)
    {
        $tz = GeneralHelper::get_local_time();
        $credentials = $request->only('email');
        $user = User::with(['member', 'unread', 'notification'])->withCount(['notification'])->withCount(['unread'])->where('email', $credentials['email'])->first();

        if (!$token = JWTAuth::fromUser($user)) {
            return response()->json([
                'token' => '',
                'type' => 'bearer', // you can ommit this
                'expires' => auth('api')->factory()->getTTL() * 72000, // time to expiration
                'ttl' => -1,
                'user_id' => 0,
                'membership' => null,
                'unread_count' => 0,
                'notification_status' => 0,
                'prepared_cv' => 0
            ]);
        } else {

            User::where('email', $credentials['email'])->update(['timezone' => $tz, 'last_login' => date('Y-m-d H:i:s', Carbon::now()->getTimestamp())]);
            return response()->json([
                'token' => $token,
                'type' => 'bearer', // you can ommit this
                'expires' => auth('api')->factory()->getTTL() * 72000, // time to expiration
                'ttl' => $user->type,
                'user_id' => $user->id,
                'membership' => $user->member,
                'unread_count' => $user->unread_count,
                'notification_status' => $user->notification_count,
                'prepared_cv' => $user->cv_ready
            ]);
        }
    }
    /**
     * EndPoint api/main_login
     * HTTP SUBMIT : POST
     * Login user as a default, instead of social
     *
     * @param  timezone tz, email, password
     * @return json with JWTTOKEN, user_id, membership, unread_count for message, notification status, prepared cv for candidate.
     */
    public function main_login(Request $request)
    {
        $tz = GeneralHelper::get_local_time();
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            // if the credentials are wrong we send an unauthorized error in json format
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (User::where('email', $credentials['email'])->first()) {
            $user = User::with(['member', 'unread', 'notification'])->withCount(['notification'])->withCount(['unread'])->where('email', $credentials['email'])->first();

            User::where('email', $credentials['email'])->update(['timezone' => $tz, 'last_login' => date('Y-m-d H:i:s', Carbon::now()->getTimestamp())]);
            return response()->json([
                'token' => $token,
                'type' => 'bearer', // you can ommit this
                'expires' => auth('api')->factory()->getTTL() * 72000, // time to expiration
                'ttl' => $user->type,
                'user_id' => $user->id,
                'membership' => $user->member,
                'unread_count' => $user->unread_count,
                'notification_status' => $user->notification_count,
                'prepared_cv' => $user->cv_ready
            ]);
        } else {
            return response()->json([
                'token' => '',
                'type' => 'bearer', // you can ommit this
                'expires' => auth('api')->factory()->getTTL() * 72000, // time to expiration
                'ttl' => -1,
                'user_id' => 0,
                'membership' => null,
                'unread_count' => 0,
                'notification_status' => 0,
                'prepared_cv' => 0
            ]);
        }
    }
    /**
     * EndPoint api/user_register_from_linkedin
     * HTTP SUBMIT : POST
     * Register user from linkedin
     *
     * @param  timezone tz, email, password
     * @return json with JWTTOKEN, user_id, membership, unread_count for message, notification status, prepared cv for candidate.
     */
    public function user_register_from_linkedin(Request $request)
    {
        $user_type = $request->post('user_type');
        $first_name = $request->post('first_name');
        $last_name = $request->post('last_name');
        $user_email = $request->post('user_email');
        $user_pwd = $request->post('user_pwd');

        if (User::where('email', $user_email)->first()) {
            return response()->json(['result' => 'error']);
        } else {
            $new_user = new User();
            $new_user->name = $first_name . " " . $last_name;
            $new_user->first_name = $first_name;
            $new_user->last_name = $last_name;
            $new_user->email = $user_email;
            $new_user->password = Hash::make($user_pwd);
            $new_user->type = $user_type;
            $new_user->avatar = 'avatar';
            $new_user->avatar_real_name = 'empty.png';
            $new_user->avatar_real_size = '7168';
            $new_user->user_specify = GeneralHelper::generateRandomString(30);
            if ($new_user->save()) {
                return response()->json(['result' => 'success']);
            } else {
                return response()->json(['result' => 'failed']);
            }
        }
    }
    /**
     * EndPoint api/user_register
     * HTTP SUBMIT : POST
     * Register user with facebook access data
     *
     * @param user_type, user name, user type[1 : 'jobseeker, 2 : employer], user email, user password
     * @return json with SUCCESS, FAILED
     */
    public function user_register(Request $request)
    {
        $user_type = $request->post('user_type');
        $user_name = $request->post('user_name');
        $user_email = $request->post('user_email');
        $user_pwd = $request->post('user_pwd');


        if (User::where('email', $user_email)->first()) {
            return response()->json(['result' => 'error']);
        } else {
            $new_user = new User();
            $new_user->name = $user_name;
            $new_user->email = $user_email;
            $new_user->password = Hash::make($user_pwd);
            $new_user->type = $user_type;
            $new_user->avatar = 'avatar';
            $new_user->avatar_real_name = 'empty.png';
            $new_user->avatar_real_size = '7168';
            $new_user->user_specify = GeneralHelper::generateRandomString(30);
            if ($new_user->save()) {
                return response()->json(['result' => 'success']);
            } else {
                return response()->json(['result' => 'failed']);
            }
        }
    }
    /**
     * EndPoint api/user_default_register
     * HTTP SUBMIT : POST
     * Register user as a default register.
     *
     * @param user_type, user name, user type[1 : 'jobseeker, 2 : employer], user email, user password, user company name
     * @return json with SUCCESS, FAILED
     */
    public function user_default_register(Request $request)
    {
        $user_type = $request->post('user_type');
        $user_name = $request->post('user_name');
        $user_email = $request->post('user_email');
        $user_pwd = $request->post('user_pwd');
        $user_company_name = $request->post('user_company_name');



        if (User::where('email', $user_email)->first()) {
            return response()->json(['result' => 'error']);
        } else {
            $new_user = new User();
            $new_user->name = $user_name;
            $new_user->email = $user_email;
            $new_user->password = Hash::make($user_pwd);
            $new_user->type = $user_type;
            $new_user->avatar = 'avatar';
            $new_user->avatar_real_name = 'empty.png';
            $new_user->avatar_real_size = '7168';
            $new_user->user_specify = GeneralHelper::generateRandomString(30);
            if ($new_user->save()) {

                if ($user_type == 2) {
                    $new_company = new Company();
                    $new_company->user_id = $new_user->id;
                    $new_company->company_name = $user_company_name;
                    $new_company->company_email = $user_email;
                    $new_company->company_logo = 'new_company';
                    $new_company->company_logo_real_name = 'new_company';
                    $new_company->company_logo_real_size = '9268';
                    $new_company->save();
                }
                return response()->json(['result' => 'success']);
            } else {
                return response()->json(['result' => 'failed']);
            }
        }
    }
    public function send_email($to_email, $subject, $message)
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
                return 'error';
            } else {
                return 'success';
            }
        } catch (Exception $e) {
            return 'error';
        }
    }
    /**
     * EndPoint api/reset_password
     * HTTP SUBMIT : POST
     * Reset password with given new password and token
     * @param
     * token : requested token from email, this will be identificated email of user who request for reset password.
     * new_password : password which will be updated
     * @return json with status as success or failed
     */
    public function reset_password(Request $request)
    {
        $token = $request->post('token');
        $new_password = $request->post('new_password');
        $repeat_password = $request->post('repeat_password');

        if (ForgotLink::where('random_links', $token)->first()) {

            $email = ForgotLink::where('random_links', $token)->first()->user_email;

            User::where('email', $email)->update(['password' => Hash::make($new_password)]);

            ForgotLink::where('random_links', $token)->delete();

            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * HTTP SUBMIT : GET
     * Verify token provided from reset password email
     * @param
     * token : requested token from email, this will be identificated email of user who request for reset password.
     * @return json with status as success or failed
     */
    public function getVerifyToken(Request $request, $token)
    {
        if (ForgotLink::where('random_links', $token)->first()) {
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/get_all_notification/${type}
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get Notifcations according to given user id and type
     * @param
     * type : 0 : all notifications, 1 : recent notifications
     * @return json with saved jobs and its count
     */
    public function get_all_notification(Request $request, $type)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        if ($type == 0) {
            $notifications = Notification::with(['type_11', 'type_3', 'type_10', 'agent_10', 'type_12', 'agent_16', 'type_16', 'agent_17', 'type_17', 'agent_20', 'type_20', 'agent_21', 'type_21'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();
        } else if ($type == 1) {
            $notifications = Notification::with(['type_11', 'type_3', 'type_10', 'agent_10', 'type_12', 'agent_16', 'type_16', 'agent_17', 'type_17', 'agent_20', 'type_20', 'agent_21', 'type_21'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->take(5)->get();
        } else {
            $notifications = [];
        }
        return response()->json($notifications, 200);
    }
    /**
     * EndPoint api/make_forgot_password_links
     * HTTP SUBMIT : POST
     * Generate random token and send forgot password link to the email of given user with token
     * @param
     * email : the email what forgot password link will be sent to
     * @return json with result with success and failed
     */
    public function make_forgot_password_links(Request $request)
    {
        $email = $request->post('email');
        $hash = Hash::make($email . date('Y-m-d H:i:s'));
        $hash = str_replace('/', '', $hash);

        if (!User::where('email', $email)->first()) {
            return response()->json(['result' => 'error'], 200);
        }

        ForgotLink::where('user_email', $email)->delete();
        $new_forgot_link = new ForgotLink();
        $new_forgot_link->user_email = $email;
        $new_forgot_link->random_links = $hash;
        $new_forgot_link->confirmed = 1;
        if ($new_forgot_link->save()) {

            $name = User::where('email', $email)->first()->name;


            $data['name'] = $name;
            $data['hash'] = $hash;

            $body = view('email.forgot-password', ['data' => $data]);
            $subject = 'Nulstil adgangskode';

            if ($this->send_email($email, $subject, $body) == 'success') {
                return response()->json(['result' => 'success'], 200);
            } else {
                return response()->json(['result' => 'error'], 200);
            }
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/get_cities
     * HTTP SUBMIT : GET
     * Get all cities
     * @param
     * @return json array with fetched all cities
     */
    public function get_cities(Request $request)
    {
        return response()->json(GeneralHelper::get_region_filter(), 200);
    }
    /**
     * EndPoint api/get_cities_with_query/{$query}
     * HTTP SUBMIT : GET
     * Get cities with given query [city prefix or suffix]
     * @param
     * query : city name
     * @return json array with fetched cities
     */
    public function get_cities_with_query(Request $request, $query)
    {
        return response()->json(GeneralHelper::get_region_filter_with_query($query), 200);
    }
    /**
     * EndPoint api/get_companies
     * HTTP SUBMIT : GET
     * Get all companies with pair id and name
     * @return json array with fetched companies
     */
    public function get_companies(Request $request)
    {
        $companies = Company::get();

        $result = [];
        foreach ($companies as $k => $v) {
            $item['id'] = $v->company_id;
            $item['name'] = $v->company_name;
            $result[] = $item;
        }

        return response()->json($result, 200);
    }
    /**
     * EndPoint api/update_password
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Update password from given current password and new password, if given current password is not matched with old password, then it will be failed.
     * @param
     * current_password : current password
     * new_password : new password
     * @return json with success or error if failed with Hash check with current password
     */
    public function update_password(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $current_password = $request->post('current_password');
        $new_password = $request->post('new_password');

        $user = User::where('id', '=', $user_id)->first();
        if ($user === null) {
            return response()->json(['result' => 'failed']);
        } else {
            if (Hash::check($current_password, $user->password)) {
                $updates = [
                    'password' => Hash::make($new_password),
                ];
                if (User::where('id', '=', $user_id)->update($updates)) {
                    return response()->json(['result' => 'success']);
                }
            } else {
                return response()->json(['result' => 'error']);
            }
        }
    }
    /**
     * EndPoint api/employer_dashboard
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get Dashboard details such as interview count, CV, posted jobs count, job lists with limit, job seeker agent
     * @return json with dashboard details
     */
    public function employer_dashboard(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_level = $user->user_level;

        if ($user->type == 2) {
            $jobs = Job::with(['job_apply', 'job_apply.user'])->withCount(['job_apply'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->take(5)->get();
            $interviews_cnt = Interview::where('employer_id', $user_id)->count();


            $interviews = Interview::with(['seeker'])->where('employer_id', $user_id)->orderBy('interview_time', 'asc')->where('interview_time', '>=', date('Y-m-d 00:00:00'))->where('ready', '<', 3)->take(10)->get();

            $final_json = [];
            foreach ($interviews as $k => $v) {
                $item['seeker_name'] = $v->seeker->name;
                $item['interview_time'] = $v->interview_time;
                $item['ready'] = $v->ready;
                $item['uuid'] = $v->uuid;


                $date1 = strtotime($v->interview_time);
                $date2 = strtotime(date('Y-m-d H:i:s'));

                if ($date1 > $date2) {
                    $diff = abs($date2 - $date1);
                } else {
                    $diff = 0;
                }


                $item['interview_count_up'] = $diff;

                $final_json[] = $item;
            }


            $company_interviews = CompanyInterview::where('employer_id', $user_id)->orderBy('interview_time', 'asc')->where('ready', '<', 3)->get();

            $final_json_c_interviews = [];
            foreach ($company_interviews as $k => $v) {
                $item['company_email'] = $v->company_email;
                $item['interview_time'] = $v->interview_time;
                $item['ready'] = $v->ready;
                $item['meeting_link'] = $v->meeting_link;


                $date1 = strtotime($v->interview_time);
                $date2 = strtotime(date('Y-m-d H:i:s'));

                if ($date1 > $date2) {
                    $diff = abs($date2 - $date1);
                } else {
                    $diff = 0;
                }


                $item['interview_count_up'] = $diff;

                $final_json_c_interviews[] = $item;
            }


            $seeker_agents = SeekerAgent::where('user_id', $user_id)->orderBy('created_at', 'desc')->get();

            $response['jobs'] = $jobs;
            $response['interviews'] = $final_json;
            $response['c_interviews'] = $final_json_c_interviews;
            $response['interviews_cnt'] = $interviews_cnt;
            $response['seeker_agents'] = $seeker_agents;

            $total_apply_cnt = 0;
            foreach ($jobs as $k => $v) {
                $total_apply_cnt += $v->job_apply_count;
            }
            $response['apply_cnt'] = $total_apply_cnt;


            $jobIDs = Job::where('user_id', $user_id)->pluck('id');


            $applyUsers = UserJobsApply::with(['user'])->whereIn('job_id', $jobIDs)->orderBy('created_at', 'desc')->take(5)->get();

            $response['applyUsers'] = $applyUsers;


            $response['user_level'] = $user_level;

            return response()->json($response, 200);
        }
    }
    /**
     * EndPoint api/get_job_permission
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get Job Permission which the employer can be able to post job ad or not.
     * @return json with success or failed - if job permission is not activev
     */
    public function get_job_permission(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        if (Company::where('user_id', $user_id)->first()) {
            $company_name = Company::where('user_id', $user_id)->first()->company_name;

            if (JobPermission::where('user_id', $user_id)->where('active', 1)->count()) {
                $plan = JobPermission::where('user_id', $user_id)->where('active', 1)->first()->plan;
                return response()->json(['result' => 'success', 'permission' => $plan, 'company_name' => $company_name], 200);
            } else {
                return response()->json(['result' => 'failed', 'permission' => 0, 'company_name' => $company_name], 200);
            }
        } else {
            return response()->json(['result' => 'error'], 200);
        }
    }
    /**
     * EndPoint api/candidate_dashboard
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get Dashboard details such as interview count, profile image, CV, applied jobs, job agent
     * @return json with dashboard details
     */
    public function candidate_dashboard(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        if ($user->type == 1) {

            $applied_jobs = UserJobsApply::with(['job'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();
            $interviews_cnt = Interview::where('seeker_id', $user_id)->count();

            $interviews = Interview::with(['employer'])->where('seeker_id', $user_id)->orderBy('interview_time', 'asc')->where('interview_time', '>=', date('Y-m-d 00:00:00'))->where('ready', '<', 3)->take(10)->get();

            $final_json = [];
            foreach ($interviews as $k => $v) {
                $item['employer_name'] = $v->employer->name;
                $item['interview_time'] = $v->interview_time;
                $item['ready'] = $v->ready;
                $item['uuid'] = $v->uuid;

                $date1 = strtotime($v->interview_time);
                $date2 = strtotime(date('Y-m-d H:i:s'));

                if ($date1 > $date2) {
                    $diff = abs($date2 - $date1);
                } else {
                    $diff = 0;
                }

                $item['interview_count_up'] = $diff;

                $final_json[] = $item;
            }


            $profile_visit_cnt = ProfileVisit::where('user_id', $user_id)->count();

            $job_agents = JobAgent::where('user_id', $user_id)->orderBy('created_at', 'desc')->get();

            $cv_video = CvVideo::where('user_id', $user_id)->orderBy('created_at', 'desc')->first();

            if ($cv_video) {
                $item = [];
                $item['cv_video'] = $cv_video->name;
                $item['cv_video_real_name'] = $cv_video->realname;
                $item['cv_video_real_size'] = $cv_video->size;
                $item['cv_video_real_type'] = $cv_video->type;
                $response['cv_video'] = $item;
            }


            $cv_document = CvDocument::where('user_id', $user_id)->first();
            if ($cv_document) {
                $item = [];
                $item['cv_document_id'] = $cv_document->document_id;
                $item['cv'] = $cv_document->url;
                $item['cv_real_name'] = $cv_document->realname;
                $item['cv_real_size'] = $cv_document->size;
                $item['cv_real_type'] = $cv_document->type;
                $response['cv_document'] = $item;
            }


            $item = [];
            $item['avatar'] = $user->avatar;
            $item['avatar_real_name'] = $user->avatar_real_name;
            $item['avatar_real_size'] = $user->avatar_real_size;
            $response['profile_image'] = $item;


            $response['profile_views'] = $profile_visit_cnt;
            $response['interviews_cnt'] = $interviews_cnt;
            $response['interviews'] = $final_json;
            $response['applied_jobs'] = $applied_jobs;
            $response['job_agents'] = $job_agents;

            return response()->json($response, 200);
        }
    }
    /**
     * EndPoint api/get_access_token
     * HTTP SUBMIT : GET
     * Get access data from linkedin access token endpoint
     *
     * @param  linkedin client id, secret, redirect uri
     * @return json with first name, last name, email
     */
    public function get_access_token(Request $request)
    {
        $authorization_code = $request->post('code');

        $client_id     = config('linkedin.client_id');
        $client_secret = config('linkedin.client_secret');
        $redirect_uri  = config('linkedin.redirect_uri');

        // get access_token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.linkedin.com/oauth/v2/accessToken");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=authorization_code&code=" . $authorization_code . "&redirect_uri=" . $redirect_uri . "&client_id=" . $client_id . "&client_secret=" . $client_secret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);

        $jsonArrayResponse = json_decode($server_output);

        // access token
        $access_token = $jsonArrayResponse->access_token;

        // get first/last name
        $headers = array(
            'Authorization: Bearer ' . $access_token
        );

        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_URL, "https://api.linkedin.com/v2/me");
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch1, CURLOPT_HEADER, false);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        $server_output1 = curl_exec($ch1);
        curl_close($ch1);

        $jsonArrayResponse1 = json_decode($server_output1);

        // first/last name
        $first_name = $jsonArrayResponse1->localizedFirstName;
        $last_name  = $jsonArrayResponse1->localizedLastName;

        // get email
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, "https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))");
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch2, CURLOPT_HEADER, false);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        $server_output2 = curl_exec($ch2);
        curl_close($ch2);

        $server_output2 = str_replace('handle~', 'email', $server_output2);
        $jsonArrayResponse2 = json_decode($server_output2);

        // email
        $email = $jsonArrayResponse2->elements[0]->email->emailAddress;

        $responseData = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email
        ];

        return response()->json(['result' => $responseData]);
    }
    public function delete_all_job(Request $request)
    {
        Job::where('job_type_id', '=', 10)->update(['job_type_id' => 1]);
        Job::where('job_type_id', '=', 0)->update(['job_type_id' => 1]);
    }
    /**
     * EndPoint api/get_user_check
     * HTTP SUBMIT : POST
     * Check email whether its exist or not.
     *
     * @param  email
     * @return json with SUCCESS, FAILED
     */
    public function get_user_check(Request $request)
    {
        $email = $request->post('email');
        if (User::where('email', $email)->first()) {
            $user = User::where('email', $email)->first();
            return response()->json(['result' => 'success', 'password' => $user->password], 200);
        } else {
            return response()->json(['result' => 'failed', 'password' => null], 200);
        }
    }
    /**
     * EndPoint api/get_user_specify
     * HTTP SUBMIT : POST
     * Get user specific link from given user id
     * @param
     * user_id : the id of user
     * @return json with specific link of given user
     */
    public function get_user_specify(Request $request)
    {
        $user_id = $request->post('user_id');
        if (User::where('id', $user_id)->first()) {
            $user_specify = User::where('id', $user_id)->first()->user_specify;
            return response()->json(['result' => 'success', 'user_specify' => $user_specify], 200);
        } else {
            return response()->json(['result' => 'failed', 'user_specify' => ''], 200);
        }
    }
    // public function make_geocode(Request $request)
    // {
    //     $cities = City::where('zip', '>', 0)->whereNull('lat')->take(20)->get();
    //     foreach ($cities as $k => $v) {
    //         $client = new Client();

    //         $crawler = $client->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $v->name . '&components=country:DK&key=AIzaSyCmVA7ahqpzHleSexldlha8cFXUp3-5AO4');
    //         $response = json_decode($client->getResponse()->getContent());

    //         if ($response->results) {
    //             $place_id = $response->results[0]->place_id;
    //             $crawler = $client->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json?place_id=' . $place_id . '&key=AIzaSyCmVA7ahqpzHleSexldlha8cFXUp3-5AO4');
    //             if ($response->results) {
    //                 $lat = $response->results[0]->geometry->location->lat;
    //                 $lng = $response->results[0]->geometry->location->lng;
    //                 City::where('id', $v->id)->update(['lat' => $lat, 'lng' => $lng]);
    //             } else {
    //             }
    //         }
    //     }
    // }
    /**
     * EndPoint api/get_search_keyword
     * HTTP SUBMIT : GET
     * Get title of jobs matched with given keyword as limit 10
     * @param
     * keyword : query job title
     * @return json array with title of fetched jobs
     */
    public function get_search_keyword(Request $request, $keyword)
    {
        if (strlen($keyword) > 2) {
            $jobs = Job::where('title', 'like', '%' . $keyword . '%')->where('is_active', 1)->take(10)->get();
            $result = [];
            foreach ($jobs as $k => $v) {
                $item['title'] = $v->title;
                $result[] = $item;
            }
        } else {
            $result = [];
        }

        return response()->json($result);
    }
    /**
     * EndPoint api/get_cities_from_zipcode
     * HTTP SUBMIT : GET
     * Get cities matched with given query as zip or city title as limit 6
     * @param
     * keyword : query keyword
     * @return json array with fetched cities
     */
    public function get_cities_from_zipcode(Request $request, $query)
    {
        $cities = City::where('zip', 'like',  $query . '%')
            ->orWhere('name', 'like', $query . '%')
            ->take(6)->get();
        return response()->json($cities);
    }
    /**
     * EndPoint api/create_report_job_ad
     * HTTP SUBMIT : POST
     * Report job ad with given options
     * @param
     * job_id : job id which will be reported,
     * report : json value {report email, report description}
     * report_options : json array with report option [Jobbet er ikke ledigt, Der er fejl i teksten, Der er tekniske fejl/noget der ikke virker, Andet]
     * @return json with jobs and jobs of count
     */
    public function create_report_job_ad(Request $request)
    {
        $job_id = $request->post('job_id');
        $report = $request->post('report');
        $report_options = $request->post('report_options');

        $email = $report['report_email'];
        $report_description = $report['report_description'];

        $new_report = new JobReport();
        $new_report->job_id = $job_id;
        $new_report->report_description = $report_description;
        $new_report->email = $email;
        $new_report->report_option = $report_options;
        if ($new_report->save()) {
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/get_all_blogs
     * HTTP SUBMIT : GET
     * Get Blogs according given type
     * @param
     * type : 0 : all blogs, 1 : blogs as limit 3
     * @return json array with fetched blogs
     */
    public function get_all_blogs(Request $request, $type)
    {
        if ($type == 0) {
            $blogs = Blog::with(['detail'])->orderBy('created_at', 'desc')->get();
        } else {
            $blogs = Blog::with(['detail'])->orderBy('created_at', 'desc')->take(3)->get();
        }
        return response()->json($blogs, 200);
    }
    /**
     * EndPoint api/get_blog_detail
     * HTTP SUBMIT : GET
     * Get Blog detail from given blog id
     * @param
     * blog_id : id of blog
     * @return json with fetched blog details
     */
    public function get_blog_detail(Request $request, $blog_id)
    {
        $blog = Blog::with(['detail'])->where('id', $blog_id)->first();
        return response()->json($blog, 200);
    }
    /**
     * EndPoint api/send_contact
     * HTTP SUBMIT : POST
     * Send contact email with given details
     * @param
     * full_name : full name of contact
     * email : email of contact
     * subject : subject of email
     * message : message of email
     * @return json with status
     */
    public function send_contact(Request $request)
    {
        $contact = $request->post('contact');
        $contact = json_decode($contact);

        $full_name = $contact->full_name;
        $email = $contact->email;
        $subject = $contact->subject;
        $message = $contact->message;

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


            $mail->SetFrom($email, $full_name);
            $mail->addAddress('info@jobbyen.dk', 'ToEmail');

            $mail->IsHTML(true);

            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = $message;

            if (!$mail->send()) {
                return response()->json(['result' => 'error'], 200);
            } else {
                return response()->json(['result' => 'success'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['result' => 'error'], 200);
        }

        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/upload_image_to_server
     * HTTP SUBMIT : POST
     * Upload image on chat
     * @param
     * upload_image_file : the image which will be uploaded
     * @return json with status with success or failed and uploaded file name
     */
    public function upload_image_to_server(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $chat_attachment_image_file_name = '';
        if (!empty($_FILES['upload_image_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_image_file']['tmp_name'])) {
                $chat_attachment_image_file_name = md5(time() . rand()) . '_chat_image';
                $sTempFileName = public_path() . '/images/chat_files/' . $chat_attachment_image_file_name . '.png';
                if (move_uploaded_file($_FILES['upload_image_file']['tmp_name'], $sTempFileName)) {

                    @chmod($sTempFileName, 0755);
                    return response()->json(['result' => 'success', 'file_name' => $chat_attachment_image_file_name], 200);
                } else {
                }
            }
        }
        return response()->json(['result' => 'failed', 'file_name' => null], 200);
    }
    /**
     * EndPoint api/upload_doc_to_server
     * HTTP SUBMIT : POST
     * Upload document on chat
     * @param
     * upload_doc_file : the document which will be uploaded
     * @return json with status with success or failed and uploaded document file name
     */
    public function upload_doc_to_server(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $upload_doc_real_type = '';

        $chat_attachment_doc_file_name = '';
        if (!empty($_FILES['upload_doc_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_doc_file']['tmp_name'])) {


                $type = pathinfo($_FILES['upload_doc_file']['name'], PATHINFO_EXTENSION);
                if ($type == 'pdf')
                    $upload_doc_real_type = 'pdf';
                else if ($type == 'doc')
                    $upload_doc_real_type = 'doc';
                else {
                }
                $chat_attachment_doc_file_name = md5(time() . rand()) . '_chat_doc.' . $upload_doc_real_type;

                $sTempFileName = public_path() . '/images/chat_files/' . $chat_attachment_doc_file_name;

                if (move_uploaded_file($_FILES['upload_doc_file']['tmp_name'], $sTempFileName)) {

                    @chmod($sTempFileName, 0755);
                    return response()->json(['result' => 'success', 'file_name' => $chat_attachment_doc_file_name, 'file_type' => $upload_doc_real_type], 200);
                } else {
                }
            }
        }
        return response()->json(['result' => 'failed', 'file_name' => null, 'file_type' => null], 200);
    }
    /**
     * EndPoint api/set_notification_status
     * HTTP SUBMIT : POST
     * set notification status as read
     * @return json with status with success or failed
     */
    public function set_notification_status(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        Notification::where('user_id', $user_id)->update(['read' => 1]);
        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/set_interview_request_email_to_seeker
     * HTTP SUBMIT : POST
     * Send email to seeker for interview request and create interview with given interview details
     * @param
     * app_id : application id
     * seeker : id of candidate who will be received
     * title : interview title
     * description : interview description
     * schedule : json object with schedule details. schedule_start, schedule_end
     * date : the date of schedule
     * @return json with status with created interview id
     */
    public function set_interview_request_email_to_seeker(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_name = $user->name;

        $app_id = $request->post('app_id');
        $seeker = $request->post('seeker');

        $title = $request->post('title');
        $description = $request->post('description');
        $schedule = $request->post('schedule');


        $date = $schedule['date'];
        $start_time = $schedule['start_time'];
        $end_time = $schedule['end_time'];

        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
        $date = str_replace($find_arr, $replace_arr, $date);

        $interview_time = date('Y-m-d', strtotime($date));

        if ($end_time == '24:00') {
            $interview_next_time = date('Y-m-d', strtotime("+1 day", strtotime($date)));
            $interview_end_time = $interview_next_time . ' ' . '00:00:00';
        } else {
            $interview_end_time = $interview_time . ' ' . $end_time . ':00';
        }
        $interview_time = $interview_time . ' ' . $start_time . ':00';


        $current_year = Carbon::parse($interview_time)->startOfWeek()->format('Y');
        $current_week = date('W', strtotime($interview_time));
        $day_idx = date('w', strtotime($interview_time));

        if ($day_idx == 0) {
            $day_idx = 7;
        }


        $exist_interview = Interview::where('employer_id', $user_id)
            ->where('year', $current_year)
            ->where('week', $current_week)
            ->where('day_idx', $day_idx)
            ->where('interview_time', '<', $interview_time)->where('interview_end_time', '>', $interview_time)
            ->orWhere(function ($query) use ($interview_end_time) {
                $query->where('interview_time', '<', $interview_end_time)->where('interview_end_time', '>=', $interview_end_time);
            })
            ->orWhere(function ($query) use ($interview_time, $interview_end_time) {
                $query->where('interview_time', '>', $interview_time)->where('interview_end_time', '<', $interview_end_time);
            })->first();


        if ($exist_interview) {
            return response()->json(['result' => 'error', 'new_interview_id' => 0], 200);
        }


        $new_interview = new Interview();
        $new_interview->employer_id = $user_id;
        $new_interview->seeker_id = $seeker;
        $new_interview->interview_time = $interview_time;
        $new_interview->interview_end_time = $interview_end_time;

        $new_interview->interview_title = $title;
        $new_interview->interview_desc = $description;

        $new_interview->year = $current_year;
        $new_interview->week = $current_week;
        $new_interview->day_idx = $day_idx;
        $new_interview->start_hour = explode(":", $start_time)[0];
        $new_interview->start_minute = explode(":", $start_time)[1];
        $new_interview->end_hour = explode(":", $end_time)[0];
        $new_interview->end_minute = explode(":", $end_time)[1];
        $new_interview->uuid = GeneralHelper::generateRandomString(30);
        $new_interview->ready = 0;
        $new_interview->app_id = $app_id;
        $new_interview->save();


        $new_interview_id = $new_interview->id;



        $seeker_email = User::where('id', $seeker)->first()->email;
        $seeker_name = User::where('id', $seeker)->first()->name;

        $subject = $user_name . ' ønsker et videointerview møde med dig';


        UserJobsApply::where('id', $app_id)->update(['v_status' => 1]);


        $data['seeker_email'] = $seeker_email;
        $data['seeker_name'] = $seeker_name;
        $data['employer_name'] = $user_name;

        $data['schedule'] = $schedule;


        $messageBody = view('email.interview_from_employer_to_seeker', ['data' => $data]);

        $result = $this->send_email($seeker_email, $subject, $messageBody);
        if ($result == 'success') {
            return response()->json(['result' => 'success', 'new_interview_id' => $new_interview_id], 200);
        } else if ($result == 'error') {
            return response()->json(['result' => 'failed', 'new_interview_id' => $new_interview_id], 200);
        } else {
            return response()->json(['result' => 'failed', 'new_interview_id' => $new_interview_id], 200);
        }
    }
    /**
     * EndPoint api/set_postpone_email_to_employer_on_dashboard
     * HTTP SUBMIT : POST
     * Send email to employer with postpone reason
     * @param
     * reason : the reason of postpone
     * uuid : uuid of interview
     * @return json with status
     */
    public function set_postpone_email_to_employer_on_dashboard(Request $request)
    {

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $reason = $request->post('reason');
        $uuid = $request->post('uuid');


        $current_interview = Interview::where('uuid', $uuid)->first();

        if (!$current_interview) {
            return response()->json(['result' => 'failed', 'sender_name' => '', 'receiver_name' => ''], 200);
        }

        if (UserJobsApply::where('id', $current_interview->app_id)->where('user_id', $user_id)->first()) {
            $v_status = UserJobsApply::where('id', $current_interview->app_id)->where('user_id', $user_id)->first()->v_status;
            if ($v_status == 1) {
                UserJobsApply::where('id', $current_interview->app_id)->where('user_id', $user_id)->update(['v_status' => 3]);
            }
        }

        Message::where('is_attach', 2)->where('uuid', $uuid)->delete();

        $employer_email = User::where('id', $current_interview->employer_id)->first()->email;
        $employer_name = User::where('id', $current_interview->employer_id)->first()->name;

        $subject = $user->name . '  har anmodet om et nyt videointerview møde';

        $data['seeker_name'] = $user->name;
        $data['employer_name'] = $employer_name;
        $data['reason'] = $reason;

        $messageBody = view('email.postpone_from_seeker_to_employer', ['data' => $data]);

        $result = $this->send_email($employer_email, $subject, $messageBody);


        Interview::where('uuid', $uuid)->delete();


        $interviews = Interview::with(['employer'])->where('seeker_id', $user_id)->orderBy('interview_time', 'asc')->where('interview_time', '>=', date('Y-m-d 00:00:00'))->where('ready', '<', 3)->take(10)->get();

        $final_json = [];
        foreach ($interviews as $k => $v) {
            $item['employer_name'] = $v->employer->name;
            $item['interview_time'] = $v->interview_time;
            $item['ready'] = $v->ready;
            $item['uuid'] = $v->uuid;

            $date1 = strtotime($v->interview_time);
            $date2 = strtotime(date('Y-m-d H:i:s'));

            if ($date1 > $date2) {
                $diff = abs($date2 - $date1);
            } else {
                $diff = 0;
            }

            $item['interview_count_up'] = $diff;

            $final_json[] = $item;
        }

        if ($result == 'success') {
            return response()->json(['result' => 'success', 'sender_name' => $user->name, 'receiver_name' => $employer_name, 'interviews'=>$final_json], 200);
        } else if ($result == 'error') {
            return response()->json(['result' => 'failed', 'sender_name' => $user->name, 'receiver_name' => $employer_name, 'interviews'=>[]], 200);
        } else {
            return response()->json(['result' => 'failed', 'sender_name' => $user->name, 'receiver_name' => $employer_name, 'interviews'=>[]], 200);
        }
    }
    /**
     * EndPoint api/set_postpone_email_to_employer_on_chat
     * HTTP SUBMIT : POST
     * Send email to employer with postpone reason
     * @param
     * employer_id : id of employer
     * reason : the reason of postpone
     * uuid : mixed uuid with chat specific id and interview id
     * @return json with status
     */
    public function set_postpone_email_to_employer_on_chat(Request $request)
    {

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $employer_id = $request->post('employer_id');
        $reason = $request->post('reason');
        $uuid = $request->post('uuid');

        $interview_id = 0;
        if (sizeof(explode('#', $uuid)) > 1) {
            $interview_id = explode('#', $uuid)[1];
        }


        if (!$interview_id) {
            return response()->json(['result' => 'failed', 'sender_name' => '', 'receiver_name' => ''], 200);
        }

        $current_interview = Interview::where('id', $interview_id)->first();

        if (!$current_interview) {
            return response()->json(['result' => 'failed', 'sender_name' => '', 'receiver_name' => ''], 200);
        }

        if (UserJobsApply::where('id', $current_interview->app_id)->where('user_id', $user_id)->first()) {
            $v_status = UserJobsApply::where('id', $current_interview->app_id)->where('user_id', $user_id)->first()->v_status;
            if ($v_status == 1) {
                UserJobsApply::where('id', $current_interview->app_id)->where('user_id', $user_id)->update(['v_status' => 3]);
            }
        }

        Message::where('is_attach', 2)->where('uuid', $uuid)->delete();

        $employer_email = User::where('id', $employer_id)->first()->email;
        $employer_name = User::where('id', $employer_id)->first()->name;

        $subject = $user->name . '  har anmodet om et nyt videointerview møde';

        $data['seeker_name'] = $user->name;
        $data['employer_name'] = $employer_name;
        $data['reason'] = $reason;

        $messageBody = view('email.postpone_from_seeker_to_employer', ['data' => $data]);

        $result = $this->send_email($employer_email, $subject, $messageBody);

        Interview::where('id', $interview_id)->delete();

        if ($result == 'success') {
            return response()->json(['result' => 'success', 'sender_name' => $user->name, 'receiver_name' => $employer_name], 200);
        } else if ($result == 'error') {
            return response()->json(['result' => 'failed', 'sender_name' => $user->name, 'receiver_name' => $employer_name], 200);
        } else {
            return response()->json(['result' => 'failed', 'sender_name' => $user->name, 'receiver_name' => $employer_name], 200);
        }
    }
    /**
     * EndPoint api/set_rating
     * HTTP SUBMIT : POST
     * Set rating to given user
     * @param
     * user_id : id of user
     * rating_point : rating point 1 ~ 5
     * @return json with status
     */
    public function set_rating(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $employer_id = $user->id;

        $rating_point = $request->post('rating_point');
        $user_id = $request->post('user_id');


        if (Rating::where('employer_id', $employer_id)->where('user_id', $user_id)->first()) {
            Rating::where('employer_id', $employer_id)->where('user_id', $user_id)->update(['rating' => $rating_point]);
            return response()->json(['result' => 'success'], 200);
        } else {
            $new_rating = new Rating();
            $new_rating->user_id = $user_id;
            $new_rating->employer_id = $employer_id;
            $new_rating->rating = $rating_point;
            $new_rating->save();
            return response()->json(['result' => 'success'], 200);
        }
    }
    /**
     * EndPoint api/get_seeker_agent_result
     * JWT TOKEN which provided by token after user logged in
     * HTTP SUBMIT : POST
     * Get results from given agent with agent_id
     * @param
     * agent_id : id of agent
     * @return json with status
     */
    public function get_seeker_agent_result(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $agent_id = $request->post('agent_id');

        $results = SeekerAgent::with(['agent_result1', 'agent_result1.user', 'agent_result1.user.location', 'agent_result1.user.cv', 'agent_result1.user.cv.cv_city', 'agent_result1.user.cv.CvWish', 'agent_result1.user.cv.CvWish.job_title',  'agent_job_type', 'agent_location', 'agent_location.location', 'agent_category', 'agent_category.category', 'agent_language', 'agent_language.language', 'agent_education'])->where('user_id', $user_id)->where('id', $agent_id)->first();

        return response()->json($results, 200);
    }
    /**
     * EndPoint api/remove_notifications
     * JWT TOKEN which provided by token after user logged in
     * HTTP SUBMIT : POST
     * Delete all notifications from given user id
     * @return json with status
     */
    public function remove_notifications(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        Notification::where('user_id', $user_id)->delete();
        return response()->json(['result' => 'success', 200]);
    }
    /**
     * EndPoint api/invite_email
     * HTTP SUBMIT : POST
     * Invite users video stream with given link and email
     * @param
     * email : email
     * link : video stream link
     * @return json with status
     */
    public function invite_email(Request $request)
    {
        $email = $request->post('email');
        $link = $request->post('link');


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
            $mail->addAddress($email, 'ToEmail');

            $mail->IsHTML(true);

            $mail->Subject = 'Invite';
            $mail->Body    = 'Stream Invite Link: ' . $link;
            $mail->AltBody = 'Stream Invite Link: ' . $link;

            if (!$mail->send()) {
                return response()->json(['result' => 'error'], 200);
            } else {
                return response()->json(['result' => 'success'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['result' => 'error'], 200);
        }
    }
    /**
     * EndPoint api/verify_document_id
     * HTTP SUBMIT : POST
     * Update cv document with random hash token, here, token used for verify document to see document in pdf view.
     * @param
     * document_id : id of document
     * @return json with generated hash
     */
    public function verify_document_id(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $email = $user->email;
        $document_id = $request->post('document_id');
        $hash = Hash::make($email . date('Y-m-d H:i:s'));
        $hash = str_replace('/', '', $hash);
        CvDocument::where('document_id', $document_id)->update(['token' => $hash]);
        return response()->json(['token' => $hash], 200);
    }
    /**
     * HTTP SUBMIT : GET
     * return document with blade view
     * @param
     * token : token of document
     * @return
     * blade view of document view.
     */
    public function view_documents(Request $request, $token)
    {
        if ($cv_document = CvDocument::where('token', $token)->first()) {
            $path = $cv_document->url;
            $type = $cv_document->type;
            $data['path'] = $path;
            $data['type'] = $type;
            return view('document.index', ['data' => $data]);
        }
    }
    /**
     * EndPoint api/deactivate_account
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Deactive account from given user id
     * just make user status as standby as 1 and its CV status as 0 if CV exist.
     * @return json with SUCCESS status
     */
    public function deactivate_account(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        User::where('id', $user_id)->update(['standby' => 1]);
        if (Cv::where('user_id', $user_id)->first()) {
            Cv::where('user_id', $user_id)->update(['active' => 0]);
        }

        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/remove_account
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Remove account from given user id
     * Category, Document, Education, Experience, Language, SKill, Wish, CV, these will be removed.
     * JobAgent, Interview, SavedJob, AppliedJob, Contact, Message, Notification which matched with this one user will be removed
     * @return json with SUCCESS status
     */
    public function remove_account(Request $request)
    {

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        if (Cv::where('user_id', $user_id)->first()) {
            $cv_id = Cv::where('user_id', $user_id)->first()->id;

            CvCategory::where('cv_id', $cv_id)->delete();
            if (CvDocument::where('cv_id', $cv_id)->first()) {
                $cv_document = CvDocument::where('cv_id', $cv_id)->first()->url;
                if (file_exists(public_path() . '/images/documents/' . $cv_document)) {
                    @unlink(public_path() . '/images/documents/' . $cv_document);
                } else {
                }
                CvDocument::where('cv_id', $cv_id)->delete();
            }
            CvEducation::where('cv_id', $cv_id)->delete();
            CvExperience::where('cv_id', $cv_id)->delete();
            CvLanguage::where('cv_id', $cv_id)->delete();
            CvSkill::where('cv_id', $cv_id)->delete();
            CvWish::where('cv_id', $cv_id)->delete();

            Cv::where('user_id', $user_id)->delete();
        }

        if (CvVideo::where('user_id', $user_id)->count()) {

            $videos = CvVideo::where('user_id', $user_id)->get();
            foreach ($videos as $v) {
                if (file_exists(public_path() . '/images/video_cv/' . $v->name)) {
                    @unlink(public_path() . '/images/video_cv/' . $v->name);
                } else {
                }
            }
            CvVideo::where('user_id', $user_id)->delete();
            return response()->json(['result' => 'success'], 200);
        } else {
        }


        if (JobAgent::where('user_id', $user_id)->count()) {
            $job_agents = JobAgent::where('user_id', $user_id)->get();
            foreach ($job_agents as $v) {
                JobAgentCategory::where('agent_id', $v->id)->delete();
                JobAgentLocation::where('agent_id', $v->id)->delete();
            }
        }

        if (Interview::where('seeker_id', $user_id)->count()) {
            Interview::where('seeker_id', $user_id)->delete();
        }

        UserJobSaved::where('user_id', $user_id)->delete();

        if (UserJobsApply::where('user_id', $user_id)->count()) {
            $applies = UserJobsApply::where('user_id', $user_id)->get();
            foreach ($applies as $v) {
                if (ApplyAttachFile::where('apply_id', $v->id)->first()) {
                    $cv_name = ApplyAttachFile::where('apply_id', $v->id)->first()->cv_name;
                    $apply_video_name = ApplyAttachFile::where('apply_id', $v->id)->first()->apply_video_name;


                    if ($cv_name != '') {
                        if (file_exists(public_path() . '/images/job_apply_cv_document/' . $cv_name)) {
                            @unlink(public_path() . '/images/job_apply_cv_document/' . $cv_name);
                        } else {
                        }
                    }


                    if ($apply_video_name != '') {
                        if (file_exists(public_path() . '/images/job_apply_video/' . $apply_video_name)) {
                            @unlink(public_path() . '/images/job_apply_video/' . $apply_video_name);
                        } else {
                        }
                    }
                } else {
                }
            }
        }

        Contact::where('contact_self_id', $user_id)->delete();
        Contact::where('contact_users', $user_id)->delete();

        Message::where('sender_id', $user_id)->delete();
        Message::where('receiver_id', $user_id)->delete();

        Notification::where('user_id', $user_id)->delete();
        Notification::where('name', $user_id)->where('type', 17)->delete();


        User::where('id', $user_id)->delete();

        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/get_stream_permission
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Check account whether employer has video free permission or not
     * @return json with status
     */
    public function get_stream_permission(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        if ($user->user_level) {
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }


} //End class
