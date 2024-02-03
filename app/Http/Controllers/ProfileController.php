<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\User;
use App\Cv;
use App\CvDocument;
use App\CvVideo;
use App\SeekerAgent;
use App\Notification;
use App\SeekerAgentResult;

class ProfileController extends Controller
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
     * EndPoint api/user
     * HTTP SUBMIT : GET
     * Get user details with JWT TOKEN which provided by token after user logged in
     * @return json with user details
     */
    public function user(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        if (User::with(['location', 'member'])->where('id', $user->id)->first()) {
            $user = User::with(['location', 'member', 'unread'])->withCount(['unread'])->where('id', $user->id)->first();
            return response()->json($user, 200);
        } else {
            return response()->json($user, 200);
        }
    }
    /**
     * EndPoint api/upload_video_dashboard
     * HTTP SUBMIT : POST
     * upload video cv from given upload video, it will be submit from dashboard. and added notification to matched users
     *
     * @param
     * upload_video_file : video cv file
     * @return json with success or failed.
     */
    public function upload_video_dashboard(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $cv_real_type = '';
        $cv_real_name = '';
        $cv_name = '';
        $cv_real_size = '';

        if (!empty($_FILES['upload_video_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_video_file']['tmp_name'])) {

                $cv_real_type = pathinfo($_FILES['upload_video_file']['name'], PATHINFO_EXTENSION);
                $cv_real_name = $_FILES['upload_video_file']['name'];
                $cv_real_size = $_FILES['upload_video_file']['size'];

                $cv_name = md5(time() . rand()) . '_cv_video.' . $cv_real_type;

                $sTempFileName = public_path() . '/images/video_cv/' . $cv_name;

                if (move_uploaded_file($_FILES['upload_video_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                } else {
                }
            }
        }

        $new_cv_video = new CvVideo();
        $new_cv_video->user_id = $user_id;

        if (Cv::where('user_id', $user_id)->first()) {
            $cv_id = Cv::where('user_id', $user_id)->first()->id;
        } else {
            $cv_id = 0;
        }
        $new_cv_video->cv_id = $cv_id;
        $new_cv_video->name = $cv_name;
        $new_cv_video->type = $cv_real_type;
        $new_cv_video->realname = $cv_real_name;
        $new_cv_video->size = $cv_real_size;
        if ($new_cv_video->save()) {

            $seekerAgents = SeekerAgent::where('video_presentation', 1)->where('type', 1)->get();

            $return_ids = [];
            foreach ($seekerAgents as $k => $v) {
                $new_notification = new Notification();
                $new_notification->user_id = $v->user_id;
                $new_notification->name = $user_id;
                $new_notification->sender = $v->id;
                $new_notification->type = 17;
                $new_notification->save();


                if (!SeekerAgentResult::where('agent_id', $v->id)->where('matched_user_id', $user_id)->first()) {
                    $new_seeker_agent_result = new SeekerAgentResult();
                    $new_seeker_agent_result->agent_id = $v->id;
                    $new_seeker_agent_result->matched_user_id = $user_id;
                    if ($cv_id == 0) {
                        $new_seeker_agent_result->type = 1;
                    }
                    $new_seeker_agent_result->save();
                }

                $return_ids[] = $v->user_id;
            }



            $videos = CvVideo::where('user_id', $user_id)->get();
            return response()->json(['result' => 'success', 'videos' => $videos, 'matched_users' => $return_ids], 200);
        } else {
            return response()->json(['result' => 'failed', 'videos' => [], 'matched_users' => []], 200);
        }
    }
    /**
     * EndPoint api/upload_cv_dashboard
     * HTTP SUBMIT : POST
     * upload cv file from given file, it will be submit from dashboard.
     *
     * @param
     * upload_cv_file : cv document file
     * @return json with success or failed.
     */
    public function upload_cv_dashboard(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $cv_document_id = 0;

        $cv_real_type = '';
        $cv_real_name = '';
        $cv_name = '';
        $cv_size = '';
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

                $cv_name = md5(time() . rand()) . '_cv.' . $type;
                $cv_size = $_FILES['upload_cv_file']['size'];

                $sTempFileName = public_path() . '/images/documents/' . $cv_name;
                if (move_uploaded_file($_FILES['upload_cv_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                } else {
                }
            }
        }

        if ($cv_name != '') {
            if (CvDocument::where('user_id', $user->id)->first()) {
                $cv_id = 0;
                if (Cv::where('user_id', $user->id)->first()) {
                    $cv_id = Cv::where('user_id', $user->id)->first()->id;
                }
                CvDocument::where('user_id', $user->id)->update(['cv_id' => $cv_id, 'url' => $cv_name, 'type' => $cv_real_type, 'realname' => $cv_real_name, 'size' => $cv_size]);

                $cv_document_id = CvDocument::where('user_id', $user->id)->first()->document_id;
            } else {

                $cv_id = 0;
                if (Cv::where('user_id', $user->id)->first()) {
                    $cv_id = Cv::where('user_id', $user->id)->first()->id;
                }
                $cv_document = new CvDocument();
                $cv_document->cv_id = $cv_id;
                $cv_document->user_id = $user->id;
                $cv_document->url = $cv_name;
                $cv_document->type = $cv_real_type;
                $cv_document->realname = $cv_real_name;
                $cv_document->size = $cv_size;
                if ($cv_document->save()) {
                    $cv_document_id = $cv_document->id;
                }
            }
        }

        return response()->json(['result' => 'success', 'cv_document_id' => $cv_document_id], 200);
    }
    /**
     * EndPoint api/upload_photo_dashboard
     * HTTP SUBMIT : POST
     * upload profile image and update cv image, it will be submit from dashboard.
     *
     * @param
     * upload_photo_file : profile image which will be updated.
     * @return json with success or failed.
     */
    public function upload_photo_dashboard(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $avatar_name = 'empty';
        $avatar_real_name = 'empty.png';
        $avatar_real_size = '7168';

        if (!empty($_FILES['upload_photo_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_photo_file']['tmp_name'])) {

                $avatar_real_name = $_FILES['upload_photo_file']['name'];
                $avatar_real_size = $_FILES['upload_photo_file']['size'];

                $avatar_name = md5(time() . rand()) . '_photo';
                $sTempFileName = public_path() . '/images/photos/' . $avatar_name . '.png';
                if (move_uploaded_file($_FILES['upload_photo_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                } else {
                }
            }
        }

        if ($avatar_name != 'empty') {
            User::where('id', $user->id)->update(['avatar' => $avatar_name, 'avatar_real_name' => $avatar_real_name, 'avatar_real_size' => $avatar_real_size]);

            if (Cv::where('user_id', $user->id)->first()) {
                Cv::where('user_id', $user->id)->update(['profile_pic' => $avatar_name, 'profile_pic_real_name' => $avatar_real_name, 'profile_pic_real_size' => $avatar_real_size]);
            }
        }

        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/update_profile
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Update profile from given profile details
     * 
     * @return json with success or failed if user can't update.
     */
    public function update_profile(Request $request)
    {
        $profile = $request->post('profile');
        $profile = json_decode($profile);
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $exist = [];


        $avatar_name = 'empty';
        $avatar_real_name = 'empty.png';
        $avatar_real_size = '7168';

        if (!empty($_FILES['upload_profile_picture']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_profile_picture']['tmp_name'])) {

                $avatar_real_name = $_FILES['upload_profile_picture']['name'];
                $avatar_real_size = $_FILES['upload_profile_picture']['size'];

                $avatar_name = md5(time() . rand()) . '_photo';
                $sTempFileName = public_path() . '/images/photos/' . $avatar_name . '.png';
                if (move_uploaded_file($_FILES['upload_profile_picture']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                } else {
                }
            }
        }



        $exist['first_name'] = $profile->first_name;
        $exist['last_name'] = $profile->last_name;
        $exist['name'] = $profile->first_name . " " . $profile->last_name;
        $exist['city'] = $profile->city->id;
        $exist['address'] = $profile->address;
        $exist['zip'] = $profile->zip;
        $exist['email'] = $profile->email;


        if ($avatar_name != 'empty') {
            $exist['avatar'] = $avatar_name;
            $exist['avatar_real_name'] = $avatar_real_name;
            $exist['avatar_real_size'] = $avatar_real_size;
        }


        if (Cv::where('user_id', $user->id)->first()) {
            Cv::where('user_id', $user->id)->update([
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'name' => $profile->first_name . " " . $profile->last_name
            ]);
        }



        if (isset($profile->phone))
            $exist['phone'] = $profile->phone;

        if (User::where('id', $user->id)->update($exist)) {
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
}
