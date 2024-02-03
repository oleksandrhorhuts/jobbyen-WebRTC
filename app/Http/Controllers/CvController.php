<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Cv;
use App\CvCategory;
use App\CvEducation;
use App\CvExperience;
use App\CvWish;
use App\CvSkill;
use App\CvLanguage;
use App\Helpers\GeneralHelper;
use App\CvDocument;
use App\UserCvsSaved;
use App\User;
use App\ProfileVisit;
use App\SeekerAgent;
use App\SeekerAgentCategory;
use App\SeekerAgentLocation;
use App\SeekerAgentLanguage;
use App\CvVideo;
use App\City;
use App\CommonQuestion;
use App\Notification;
use App\SeekerAgentResult;
use App\CommonAsk;

class CvController extends Controller
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
     * EndPoint api/save_resume
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Save resume with given resume id
     * @param
     * user_id : the id of user who is going to save
     * @return json with status as success, failed
     */
    public function save_resume(Request $request)
    {
        $resume_user_id = $request->post('user_id');
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $resume_id = $request->post('resume_id');
        $count_resume_saved = UserCvsSaved::where('user_id', $user_id)->where('resume_user_id', $resume_user_id)->count();

        if ($count_resume_saved)
            return response()->json(['result' => 'error'], 200);
        else {
            $row = new UserCvsSaved();
            $row->user_id = $user_id;
            $row->resume_user_id = $resume_user_id;
            if ($row->save()) {
                return response()->json(['result' => 'success'], 200);
            } else {
                return response()->json(['result' => 'failed'], 200);
            }
        }
    }
    /**
     * EndPoint api/getSavedResume
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get saved resume
     * @return json array with saved cv details
     */
    public function getSavedResume(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $cvs = UserCvsSaved::with(['user', 'user.cv', 'user.cv.cv_city', 'user.cv.CvCategory', 'user.cv.CvCategory.category', 'user.cv.CvSkill', 'user.cv.CvSkill.skill', 'user.cv.CvWish', 'user.cv.CvWish.job_title', 'user.cv.CvEducation', 'user.cv.CvEducation.degree', 'user.cv.CvExperience', 'user.cv.user', 'user.location'])->where('user_id', $user_id)->get();
        return response()->json($cvs, 200);
    }
    /**
     * EndPoint api/get_cv_detail
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get CV details from given specific url which relevant cv id
     * @return json with cv details and video cv
     */
    public function get_cv_detail(Request $request, $url)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $visitor_id = $user->id;

        if (User::where('user_specify', $url)->first()) {
            $user_id = User::where('user_specify', $url)->first()->id;
            if (User::where('user_specify', $url)->where('cv_ready', 1)->first()) {

                if (ProfileVisit::where('user_id', $user_id)->where('visitor_id', $visitor_id)->first()) {
                } else {
                    $new_profile_visit = new ProfileVisit();
                    $new_profile_visit->user_id = $user_id;
                    $new_profile_visit->visitor_id = $visitor_id;
                    $new_profile_visit->save();
                }

                $new_notification = new Notification();
                $new_notification->user_id = $user_id;
                $new_notification->type = 11;
                $new_notification->name = $visitor_id;
                $new_notification->save();


                $cv = Cv::with(['jobtype', 'cv_city', 'cv_document', 'CvCategory', 'CvCategory.category', 'CvSkill', 'CvSkill.skill', 'CvWish', 'CvWish.job_title', 'cv_language', 'cv_language.language', 'CvEducation', 'CvEducation.degree', 'CvExperience'])->where('user_id', $user_id)->first();
                $profile_visit = ProfileVisit::where('user_id', $user_id)->count();
                $cv_videos = CvVideo::where('user_id', $user_id)->get();

                $videos = [];
                foreach ($cv_videos as $k => $v) {
                    $item['name'] = 'images/video_cv/' . $v->name;
                    $item['type'] = $v->type;
                    $videos[] = $item;
                }


                $query_1 = null;
                $cv_category_list_id_arr = [];
                foreach ($cv->CvCategory as $k => $v) {
                    $cv_category_list_id_arr[] = $v->category_id;
                    $query_1 = $v->category_id;
                }

                $cv_skill_list_id_arr = [];
                $query_2 = null;
                foreach ($cv->CvSkill as $k => $v) {
                    $cv_skill_list_id_arr[] = $v->skill_id;
                    $query_2 = $v->skill_id;
                }

                $cv_wish_list_id_arr = [];
                $query_3 = null;
                foreach ($cv->CvWish as $k => $v) {
                    $cv_wish_list_id_arr[] = $v->job_title_id;
                    $query_3 = $v->job_title_id;
                }

                // $query_similar_idx = 0;
                // $similar_cvs = Cv::with(['user', 'jobtype', 'CvCategory', 'CvCategory.category', 'CvSkill', 'CvSkill.skill', 'CvWish', 'CvWish.job_title'])
                //     ->when($query_1, function ($query) use ($cv_category_list_id_arr, $query_similar_idx, $user_id) {
                //         $query->whereHas('CvCategory.category', function ($query) use ($cv_category_list_id_arr) {
                //             $query->whereIn('id', $cv_category_list_id_arr);
                //         });
                //         $query->where('user_id', '!=', $user_id);
                //         $query_similar_idx++;
                //     })
                //     ->when($query_2, function ($query) use ($cv_skill_list_id_arr, $query_similar_idx, $user_id) {
                //         if ($query_similar_idx == 0) {
                //             $query->orWhereHas('CvSkill.skill', function ($query) use ($cv_skill_list_id_arr) {
                //                 $query->whereIn('id', $cv_skill_list_id_arr);
                //             });
                //             $query->where('user_id', '!=', $user_id);
                //         } else {
                //             $query->whereHas('CvSkill.skill', function ($query) use ($cv_skill_list_id_arr) {
                //                 $query->whereIn('id', $cv_skill_list_id_arr);
                //             });
                //             $query->where('user_id', '!=', $user_id);
                //         }
                //         $query_similar_idx++;
                //     })
                //     ->when($query_3, function ($query) use ($cv_wish_list_id_arr, $query_similar_idx, $user_id) {
                //         if ($query_similar_idx == 0) {
                //             $query->orWhereHas('CvWish.job_title', function ($query) use ($cv_wish_list_id_arr) {
                //                 $query->whereIn('id', $cv_wish_list_id_arr);
                //             });
                //             $query->where('user_id', '!=', $user_id);
                //         } else {
                //             $query->whereHas('CvWish.job_title', function ($query) use ($cv_wish_list_id_arr) {
                //                 $query->whereIn('id', $cv_wish_list_id_arr);
                //             });
                //             $query->where('user_id', '!=', $user_id);
                //         }
                //         $query_similar_idx++;
                //     })
                //     ->take(5)->get();


                $questions = [];
                $ask = CommonAsk::where('employer_id', $visitor_id)->where('user_id', $user_id)->first();
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
                return response()->json(['result' => 'success', 'cv' => $cv, 'videos' => $videos, 'profile_visit' => $profile_visit, 'similar_cvs' => [], 'cv_ready' => 1, 'final_questions'=>$final_questions, 'answers'=>$questions], 200);
            } else {
                $cv = [];
                $real_name = User::where('user_specify', $url)->first()->name;
                $first_name = User::where('user_specify', $url)->first()->first_name;
                $last_name = User::where('user_specify', $url)->first()->last_name;
                $avatar = User::where('user_specify', $url)->first()->avatar;
                $location_id = User::where('user_specify', $url)->first()->city;

                $location = '';
                if ($location_id) {
                    $location = City::where('id', $location_id)->first()->name;
                    $cv['cv_city']['name'] = $location;
                } else {
                    $cv['cv_city'] = null;
                }

                if ($first_name && $last_name) {
                    $cv['first_name'] = $first_name;
                    $cv['last_name'] = $last_name;
                } else {
                    $cv['first_name'] = $real_name;
                    $cv['last_name'] = '';
                }


                $cv['profile_pic'] = $avatar;
                $cv['name'] = $real_name;
                $cv['user_id'] = $user_id;
                $cv['cv_wish'] = null;
                $cv_videos = CvVideo::where('user_id', $user_id)->get();

                $videos = [];
                foreach ($cv_videos as $k => $v) {
                    $item['name'] = 'images/video_cv/' . $v->name;
                    $item['type'] = $v->type;
                    $videos[] = $item;
                }

                $new_notification = new Notification();
                $new_notification->user_id = $user_id;
                $new_notification->sender = 0;
                $new_notification->type = 11;
                $new_notification->name = $visitor_id;
                $new_notification->save();


                $questions = [];
                $ask = CommonAsk::where('employer_id', $visitor_id)->where('user_id', $user_id)->first();
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

                return response()->json(['result' => 'success', 'cv' => $cv, 'videos' => $videos, 'profile_visit' => 0, 'similar_cvs' => [], 'cv_ready' => 0, 'final_questions'=>$final_questions, 'answers'=>$questions], 200);
            }
        } else {
            return response()->json(['result' => 'failed', 'cv' => [], 'videos' => [], 'profile_visit' => 0, 'similar_cvs' => [], 'cv_ready' => 0, 'final_questions'=>[], 'answers'=>[]], 200);
        }
    }
    /**
     * EndPoint api/get_cv
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get CV of given user which provided user id
     * @return json with CV, CV document, CV Videos, status as success or failed.
     */
    public function get_cv(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        if (Cv::where('user_id', $user_id)->first()) {
            $cv = Cv::with(['jobtype', 'cv_city', 'cv_document', 'CvCategory', 'CvCategory.category', 'CvSkill', 'CvSkill.skill', 'CvWish', 'CvWish.job_title', 'cv_language', 'cv_language.language', 'CvEducation', 'CvEducation.degree', 'CvExperience'])->where('user_id', $user_id)->first();
            $cv_videos = CvVideo::where('user_id', $user_id)->get();
            $cv_document = CvDocument::where('user_id', $user_id)->first();

            $cv_image = null;
            if (User::where('id', $user_id)->first()) {
                $avatar = User::where('id', $user_id)->first()->avatar;
                if ($avatar != 'avatar') {
                    $item = [];
                    $item['avatar'] = User::where('id', $user_id)->first()->avatar;
                    $item['avatar_real_name'] = User::where('id', $user_id)->first()->avatar_real_name;
                    $item['avatar_real_size'] = User::where('id', $user_id)->first()->avatar_real_size;
                    $cv_image = $item;
                }
            }

            $videos = [];
            foreach ($cv_videos as $k => $v) {
                $item['name'] = 'images/video_cv/' . $v->name;
                $item['type'] = $v->type;
                $videos[] = $item;
            }
            return response()->json(['result' => 'success', 'cv' => $cv, 'cv_image' => $cv_image, 'cv_document' => $cv_document, 'videos' => $videos], 200);
        } else {
            $cv_document = CvDocument::where('user_id', $user_id)->first();

            $cv_image = null;
            if (User::where('id', $user_id)->first()) {
                $avatar = User::where('id', $user_id)->first()->avatar;
                if ($avatar != 'avatar') {
                    $item = [];
                    $item['avatar'] = User::where('id', $user_id)->first()->avatar;
                    $item['avatar_real_name'] = User::where('id', $user_id)->first()->avatar_real_name;
                    $item['avatar_real_size'] = User::where('id', $user_id)->first()->avatar_real_size;
                    $cv_image = $item;
                }
            }
            return response()->json(['result' => 'failed', 'cv' => null, 'cv_image' => $cv_image, 'cv_document' => $cv_document, 'videos' => []], 200);
        }
    }
    /**
     * EndPoint api/update_cv
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Update CV with cv details and add notification what update cv.
     * @param 
     * cv : json array, 
     * first_name : first name,
     * last_name : last name,
     * gender : gender,
     * birthday : birthday, type : 12, July, 1990, it will be parsed from Carbon
     * about_me : about me,
     * location : location id
     * job_type : Job Type
     * cv_category : choosed CV cateogry json array
     * cv_wish : choosed CV wishes json array
     * cv_language : choosed CV languages json array
     * cv_skill : choosed CV skills json array
     * cv_education : choosed CV educations json array
     * cv_experience :  CV experiences json array
     * @return json with SUCCESS or FAILED as cv status
     */
    public function update_cv(Request $request)
    {
        $cv = $request->post('cv');
        $cv = json_decode($cv);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
        $cv_birthday = str_replace($find_arr, $replace_arr, $cv->birthday);

        Log::info($cv->birthday);
        Log::info('***********************************');
        Log::info($cv_birthday);
        $cv_birthday = trim($cv_birthday);

        if (Cv::where('user_id', $user_id)->first()) {


            $first_name = $cv->first_name;
            $last_name = $cv->last_name;
            $gender = $cv->gender;
            $birth = GeneralHelper::md_picker_parse_date($cv_birthday);
            Log::info('carbon birth');
            Log::info($birth);
            $about_me = $cv->about_me;
            $age = Carbon::parse($birth)->diff(Carbon::now())->y;
            Log::info($age);

            $location = $cv->location;
            $job_type = $cv->job_type;

            $categories = $cv->cv_category;
            $wishes = $cv->cv_wish;
            $languages = $cv->cv_language;
            $skills = $cv->cv_skill;
            $educations = $cv->cv_education;
            $experiences = $cv->cv_experience;

            $avatar_name = '';
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
                        //ImageOptimizer::optimize($sTempFileName);
                    } else {
                    }
                }
            } else {
                if (User::where('id', $user_id)->first()) {
                    $avatar_name = User::where('id', $user_id)->first()->avatar;
                    $avatar_real_name = User::where('id', $user_id)->first()->avatar_real_name;
                    $avatar_real_size = User::where('id', $user_id)->first()->avatar_real_size;
                }
            }

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


            $cv_id = Cv::where('user_id', $user_id)->first()->id;
            if ($cv_name != '') {
                if (CvDocument::where('cv_id', $cv_id)->first()) {
                    CvDocument::where('cv_id', $cv_id)->update(['user_id' => $user_id, 'url' => $cv_name, 'type' => $cv_real_type, 'realname' => $cv_real_name, 'size' => $cv_size]);
                } else {
                    $cv_document = new CvDocument();
                    $cv_document->cv_id = $cv_id;
                    $cv_document->user_id = $user_id;
                    $cv_document->url = $cv_name;
                    $cv_document->type = $cv_real_type;
                    $cv_document->realname = $cv_real_name;
                    $cv_document->size = $cv_size;
                    $cv_document->save();
                }
            }

            if ($avatar_name == '') {
                Cv::where('user_id', $user_id)->update(['name' => $first_name . " " . $last_name, 'first_name' => $first_name, 'last_name' => $last_name, 'gender' => $gender, 'birthday' => $birth, 'about_me' => $about_me, 'age' => $age, 'location_id' => $location == '' ? '' : $location->id, 'job_type' => $job_type == '' ? '' : $job_type->id]);
            } else {
                Cv::where('user_id', $user_id)->update(['name' => $first_name . " " . $last_name, 'first_name' => $first_name, 'last_name' => $last_name, 'gender' => $gender, 'birthday' => $birth, 'about_me' => $about_me, 'age' => $age, 'profile_pic' => $avatar_name, 'profile_pic_real_name' => $avatar_real_name, 'profile_pic_real_size' => $avatar_real_size, 'location_id' => $location == '' ? '' : $location->id, 'job_type' => $job_type == '' ? '' : $job_type->id]);

                User::where('id', $user_id)->update(['avatar' => $avatar_name, 'avatar_real_name' => $avatar_real_name, 'avatar_real_size' => $avatar_real_size]);
            }

            $new_notification = new Notification();
            $new_notification->user_id = $user_id;
            $new_notification->sender = 1;
            $new_notification->type = 2;
            $new_notification->name = 0;
            $new_notification->save();

            CvCategory::where('cv_id', $cv_id)->delete();
            foreach ($categories as $v) {
                $new_category = new CvCategory();
                $new_category->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_category_id = GeneralHelper::insert_new_category($v->name);
                } else {
                    $selected_category_id = $v->id;
                }
                $new_category->category_id = $selected_category_id;
                $new_category->save();
            }
            CvWish::where('cv_id', $cv_id)->delete();
            foreach ($wishes as $v) {
                $new_wish = new CvWish();
                $new_wish->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_jobtitle_id = GeneralHelper::insert_new_jobtitle($v->name);
                } else {
                    $selected_jobtitle_id = $v->id;
                }
                $new_wish->job_title_id = $selected_jobtitle_id;
                $new_wish->save();
            }
            CvLanguage::where('cv_id', $cv_id)->delete();
            foreach ($languages as $v) {
                $new_language = new CvLanguage();
                $new_language->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_language_id = GeneralHelper::insert_new_language($v->name);
                } else {
                    $selected_language_id = $v->id;
                }
                $new_language->language_id = $selected_language_id;
                $new_language->save();
            }
            CvSkill::where('cv_id', $cv_id)->delete();
            foreach ($skills as $v) {
                $new_skill = new CvSkill();
                $new_skill->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_skill_id = GeneralHelper::insert_new_skill($v->name);
                } else {
                    $selected_skill_id = $v->id;
                }
                $new_skill->skill_id = $selected_skill_id;
                $new_skill->save();
            }
            CvEducation::where('cv_id', $cv_id)->delete();
            foreach ($educations as $v) {
                $new_education = new CvEducation();
                $new_education->cv_id = $cv_id;
                $new_education->course_name = $v->course_name->id;
                $new_education->education_name = $v->education_name;
                $new_education->education_start_year = intval($v->education_start_year);
                $new_education->education_end_year = intval($v->education_end_year);
                $new_education->education_description = $v->education_description;
                $new_education->save();
            }
            CvExperience::where('cv_id', $cv_id)->delete();

            $exp = [];
            foreach ($experiences as $v) {
                $new_experience = new CvExperience();
                $new_experience->cv_id = $cv_id;
                $new_experience->title = $v->title;
                $new_experience->company_name = $v->company_name;
                $new_experience->experience_description = $v->experience_description;
                $new_experience->experience_start_year = intval($v->experience_start_year);
                $new_experience->experience_start_month = intval($v->experience_start_month);

                if ($v->present) {
                    $new_experience->experience_end_year = intval(Carbon::now()->year);
                    $new_experience->experience_end_month = intval(Carbon::now()->month);
                } else {
                    $new_experience->experience_end_year = $v->experience_end_year == '' ? 0 : intval($v->experience_end_year);
                    $new_experience->experience_end_month = $v->experience_end_month == '' ? 0 : intval($v->experience_end_month);
                }

                $new_experience->experience_year = intval($new_experience->experience_end_year - $new_experience->experience_start_year);


                $exp[] = $this->calculate_month($new_experience->experience_start_year, $new_experience->experience_start_month, $new_experience->experience_end_year, $new_experience->experience_end_month);

                $new_experience->present = $v->present;
                $new_experience->save();
            }
            if ($exp) {
                $exp_number_array = [];
                foreach ($exp as $exp_key => $exp_item) {
                    $exp_number_array[] = intval($exp_item['number']);
                }

                $experience = max($exp_number_array);

                $experience_format = '';
                foreach ($exp as $exp_key => $exp_item) {
                    if ($experience == $exp_item['number']) {
                        $experience_format = $exp_item['format'];
                        break;
                    }
                }
                Cv::where('user_id', $user_id)->update(['experience' => $experience, 'experience_format' => $experience_format]);
            }

            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    // public function cal_year(Request $request)
    // {
    //     $fromYear = 2017;
    //     $fromMonth = 5;
    //     $toYear = 2017;
    //     $toMonth = 12;

    //     $from = Carbon::parse($fromYear . '-' . $fromMonth);
    //     $to = Carbon::parse($toYear . '-' . $toMonth);
    //     $diff = $from->diffForHumans($to, true, false, 2);
    //     echo $from->diffInMonths($to);
    //     echo '<br>';
    //     echo $diff;
    // }
    /**
     * EndPoint api/save_draft_cv
     * JWT TOKEN which provided by token after user logged in
     * Save draft CV with cv details, but not published.
     * @param 
     * cv : json array, 
     * first_name : first name,
     * last_name : last name,
     * gender : gender,
     * birthday : birthday, type : 12, July, 1990, it will be parsed from Carbon
     * about_me : about me,
     * location : location id
     * job_type : Job Type
     * cv_category : choosed CV cateogry json array
     * cv_wish : choosed CV wishes json array
     * cv_language : choosed CV languages json array
     * cv_skill : choosed CV skills json array
     * cv_education : choosed CV educations json array
     * cv_experience :  CV experiences json array
     * @return json with sucess or failed as status
     */
    public function save_draft_cv(Request $request)
    {
        $cv = $request->post('cv');
        $cv = json_decode($cv);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;


        $first_name = $cv->first_name;
        $last_name = $cv->last_name;
        $gender = $cv->gender;

        $birth = GeneralHelper::md_picker_parse_date($cv->birthday);



        $about_me = $cv->about_me;
        $age = Carbon::parse($birth)->diff(Carbon::now())->y;
        $location = $cv->location;
        $job_type = $cv->job_type;

        $categories = $cv->cv_category;
        $wishes = $cv->cv_wish;
        $languages = $cv->cv_language;
        $skills = $cv->cv_skill;
        $educations = $cv->cv_education;
        $experiences = $cv->cv_experience;

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

        if (Cv::where('user_id', $user_id)->first()) {


            $cv_id = Cv::where('user_id', $user_id)->first()->id;
            if ($cv_name != '') {
                if (CvDocument::where('cv_id', $cv_id)->first()) {
                    CvDocument::where('cv_id', $cv_id)->update(['user_id' => $user_id, 'url' => $cv_name, 'type' => $cv_real_type, 'realname' => $cv_real_name, 'size' => $cv_size]);
                } else {
                    $cv_document = new CvDocument();
                    $cv_document->cv_id = $cv_id;
                    $cv_document->user_id = $user_id;
                    $cv_document->url = $cv_name;
                    $cv_document->type = $cv_real_type;
                    $cv_document->realname = $cv_real_name;
                    $cv_document->size = $cv_size;
                    $cv_document->save();
                }
            }

            if ($avatar_name == 'empty') {
                Cv::where('user_id', $user_id)->update(['name' => $first_name . " " . $last_name, 'first_name' => $first_name, 'last_name' => $last_name, 'gender' => $gender, 'birthday' => $birth, 'about_me' => $about_me, 'age' => $age, 'location_id' => $location == '' ? '' : $location->id, 'job_type' => $job_type == '' ? '' : $job_type->id]);
            } else {
                Cv::where('user_id', $user_id)->update(['name' => $first_name . " " . $last_name, 'first_name' => $first_name, 'last_name' => $last_name, 'gender' => $gender, 'birthday' => $birth, 'about_me' => $about_me, 'age' => $age, 'profile_pic' => $avatar_name, 'profile_pic_real_name' => $avatar_real_name, 'profile_pic_real_size' => $avatar_real_size, 'location_id' => $location == '' ? '' : $location->id, 'job_type' => $job_type == '' ? '' : $job_type->id]);
                User::where('id', $user_id)->update(['avatar' => $avatar_name, 'avatar_real_name' => $avatar_real_name, 'avatar_real_size' => $avatar_real_size]);
            }

            CvCategory::where('cv_id', $cv_id)->delete();
            foreach ($categories as $v) {
                $new_category = new CvCategory();
                $new_category->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_category_id = GeneralHelper::insert_new_category($v->name);
                } else {
                    $selected_category_id = $v->id;
                }
                $new_category->category_id = $selected_category_id;
                $new_category->save();
            }
            CvWish::where('cv_id', $cv_id)->delete();
            foreach ($wishes as $v) {
                $new_wish = new CvWish();
                $new_wish->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_jobtitle_id = GeneralHelper::insert_new_jobtitle($v->name);
                } else {
                    $selected_jobtitle_id = $v->id;
                }
                $new_wish->job_title_id = $selected_jobtitle_id;
                $new_wish->save();
            }
            CvLanguage::where('cv_id', $cv_id)->delete();
            foreach ($languages as $v) {
                $new_language = new CvLanguage();
                $new_language->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_language_id = GeneralHelper::insert_new_language($v->name);
                } else {
                    $selected_language_id = $v->id;
                }
                $new_language->language_id = $selected_language_id;
                $new_language->save();
            }
            CvSkill::where('cv_id', $cv_id)->delete();
            foreach ($skills as $v) {
                $new_skill = new CvSkill();
                $new_skill->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_skill_id = GeneralHelper::insert_new_skill($v->name);
                } else {
                    $selected_skill_id = $v->id;
                }
                $new_skill->skill_id = $selected_skill_id;
                $new_skill->save();
            }
            CvEducation::where('cv_id', $cv_id)->delete();
            foreach ($educations as $v) {
                $new_education = new CvEducation();
                $new_education->cv_id = $cv_id;
                $new_education->course_name = $v->course_name->id;
                $new_education->education_name = $v->education_name;
                $new_education->education_start_year = intval($v->education_start_year);
                $new_education->education_end_year = intval($v->education_end_year);
                $new_education->education_description = $v->education_description;
                $new_education->save();
            }
            CvExperience::where('cv_id', $cv_id)->delete();

            $exp = [];
            foreach ($experiences as $v) {
                $new_experience = new CvExperience();
                $new_experience->cv_id = $cv_id;
                $new_experience->title = $v->title;
                $new_experience->company_name = $v->company_name;
                $new_experience->experience_description = $v->experience_description;
                $new_experience->experience_start_year = intval($v->experience_start_year);
                $new_experience->experience_start_month = intval($v->experience_start_month);

                if ($v->present) {
                    $new_experience->experience_end_year = intval(Carbon::now()->year);
                    $new_experience->experience_end_month = intval(Carbon::now()->month);
                } else {
                    $new_experience->experience_end_year = $v->experience_end_year == '' ? 0 : intval($v->experience_end_year);
                    $new_experience->experience_end_month = $v->experience_end_month == '' ? 0 : intval($v->experience_end_month);
                }

                $new_experience->experience_year = intval($new_experience->experience_end_year - $new_experience->experience_start_year);


                $exp[] = $this->calculate_month($new_experience->experience_start_year, $new_experience->experience_start_month, $new_experience->experience_end_year, $new_experience->experience_end_month);

                $new_experience->present = $v->present;
                $new_experience->save();
            }
            if ($exp) {
                $exp_number_array = [];
                foreach ($exp as $exp_key => $exp_item) {
                    $exp_number_array[] = intval($exp_item['number']);
                }

                $experience = max($exp_number_array);

                $experience_format = '';
                foreach ($exp as $exp_key => $exp_item) {
                    if ($experience == $exp_item['number']) {
                        $experience_format = $exp_item['format'];
                        break;
                    }
                }
                Cv::where('user_id', $user_id)->update(['experience' => $experience, 'experience_format' => $experience_format]);
            }

            return response()->json(['result' => 'success'], 200);
        } else {

            $new_cv = new Cv();
            $new_cv->user_id = $user_id;
            $new_cv->name = $first_name . " " . $last_name;
            $new_cv->first_name = $first_name;
            $new_cv->last_name = $last_name;
            $new_cv->url =  GeneralHelper::generateRandomString(30);
            $new_cv->gender = $gender;
            $new_cv->birthday = $birth;
            $new_cv->about_me = $about_me;
            $new_cv->age = $age;
            $new_cv->profile_pic = $avatar_name;

            $new_cv->profile_pic_real_name = $avatar_real_name;
            $new_cv->profile_pic_real_size = $avatar_real_size;

            $new_cv->location_id = $location == '' ? '' : $location->id;
            $new_cv->job_type = $job_type == '' ? '' : $job_type->id;

            if ($avatar_name != 'empty') {
                User::where('id', $user_id)->update(['avatar' => $avatar_name, 'avatar_real_name' => $avatar_real_name, 'avatar_real_size' => $avatar_real_size]);
            }

            if ($new_cv->save()) {


                if ($cv_name != '') {
                    $cv_document = new CvDocument();
                    $cv_document->cv_id = $new_cv->id;
                    $cv_document->user_id = $user_id;
                    $cv_document->url = $cv_name;
                    $cv_document->type = $cv_real_type;
                    $cv_document->realname = $cv_real_name;
                    $cv_document->size = $cv_size;
                    $cv_document->save();
                }

                $categoryArr = [];
                foreach ($categories as $v) {
                    $new_category = new CvCategory();
                    $new_category->cv_id = $new_cv->id;
                    if (empty($v->id)) {
                        $selected_category_id = GeneralHelper::insert_new_category($v->name);
                    } else {
                        $selected_category_id = $v->id;
                    }
                    $new_category->category_id = $selected_category_id;
                    $new_category->save();
                    $categoryArr[] = $selected_category_id;
                }

                foreach ($wishes as $v) {
                    $new_wish = new CvWish();
                    $new_wish->cv_id = $new_cv->id;
                    if (empty($v->id)) {
                        $selected_jobtitle_id = GeneralHelper::insert_new_jobtitle($v->name);
                    } else {
                        $selected_jobtitle_id = $v->id;
                    }
                    $new_wish->job_title_id = $selected_jobtitle_id;
                    $new_wish->save();
                }

                $languageArr = [];
                foreach ($languages as $v) {
                    $new_language = new CvLanguage();
                    $new_language->cv_id = $new_cv->id;
                    if (empty($v->id)) {
                        $selected_language_id = GeneralHelper::insert_new_language($v->name);
                    } else {
                        $selected_language_id = $v->id;
                    }
                    $new_language->language_id = $selected_language_id;
                    $new_language->save();
                    $languageArr[] = $selected_language_id;
                }

                foreach ($skills as $v) {
                    $new_skill = new CvSkill();
                    $new_skill->cv_id = $new_cv->id;
                    if (empty($v->id)) {
                        $selected_skill_id = GeneralHelper::insert_new_skill($v->name);
                    } else {
                        $selected_skill_id = $v->id;
                    }
                    $new_skill->skill_id = $selected_skill_id;
                    $new_skill->save();
                }

                $educationArr = [];
                foreach ($educations as $v) {
                    $new_education = new CvEducation();
                    $new_education->cv_id = $new_cv->id;
                    $educationArr[] = $v->course_name->id;
                    $new_education->course_name = $v->course_name->id;
                    $new_education->education_name = $v->education_name;
                    $new_education->education_start_year = intval($v->education_start_year);
                    $new_education->education_end_year = intval($v->education_end_year);
                    $new_education->education_description = $v->education_description;
                    $new_education->save();
                }
                $exp = [];
                foreach ($experiences as $v) {
                    $new_experience = new CvExperience();
                    $new_experience->cv_id = $new_cv->id;
                    $new_experience->title = $v->title;
                    $new_experience->company_name = $v->company_name;
                    $new_experience->experience_description = $v->experience_description;
                    $new_experience->experience_start_year = intval($v->experience_start_year);
                    $new_experience->experience_start_month = intval($v->experience_start_month);

                    if ($v->present) {
                        $new_experience->experience_end_year = intval(Carbon::now()->year);
                        $new_experience->experience_end_month = intval(Carbon::now()->month);
                    } else {
                        $new_experience->experience_end_year = $v->experience_end_year == '' ? 0 : intval($v->experience_end_year);
                        $new_experience->experience_end_month = $v->experience_end_month == '' ? 0 : intval($v->experience_end_month);
                    }

                    $new_experience->experience_year = intval($new_experience->experience_end_year - $new_experience->experience_start_year);
                    $exp[] = $this->calculate_month($new_experience->experience_start_year, $new_experience->experience_start_month, $new_experience->experience_end_year, $new_experience->experience_end_month);

                    $new_experience->present = $v->present;
                    $new_experience->save();
                }
                if ($exp) {
                    $exp_number_array = [];
                    foreach ($exp as $exp_key => $exp_item) {
                        $exp_number_array[] = intval($exp_item['number']);
                    }

                    $experience = max($exp_number_array);

                    $experience_format = '';
                    foreach ($exp as $exp_key => $exp_item) {
                        if ($experience == $exp_item['number']) {
                            $experience_format = $exp_item['format'];
                            break;
                        }
                    }
                    Cv::where('user_id', $user_id)->update(['experience' => $experience, 'experience_format' => $experience_format]);
                }


                return response()->json(['result' => 'success', 'matched_users' => []], 200);
            } else {
                if ($cv_name != '') {
                    if (CvDocument::where('user_id', $user_id)->first()) {
                        CvDocument::where('user_id', $user_id)->update(['user_id' => $user_id, 'url' => $cv_name, 'type' => $cv_real_type, 'realname' => $cv_real_name, 'size' => $cv_size]);
                    } else {
                        $cv_document = new CvDocument();
                        $cv_document->cv_id = 0;
                        $cv_document->user_id = $user_id;
                        $cv_document->url = $cv_name;
                        $cv_document->type = $cv_real_type;
                        $cv_document->realname = $cv_real_name;
                        $cv_document->size = $cv_size;
                        $cv_document->save();
                    }
                }

                if ($avatar_name != 'empty') {
                    User::where('id', $user_id)->update(['avatar' => $avatar_name, 'avatar_real_name' => $avatar_real_name, 'avatar_real_size' => $avatar_real_size]);
                }

                return response()->json(['result' => 'failed', 'matched_users' => []], 200);
            }
        }
    }

    /**
     * EndPoint api/create_cv
     * JWT TOKEN which provided by token after user logged in
     * Create CV with cv details and add notification what created cv, and added user_id of cv to seeker agent result db if this one is matched with job seeker agent condition
     * @param 
     * cv : json array, 
     * first_name : first name,
     * last_name : last name,
     * gender : gender,
     * birthday : birthday, type : 12, July, 1990, it will be parsed from Carbon
     * about_me : about me,
     * location : location id
     * job_type : Job Type
     * cv_category : choosed CV cateogry json array
     * cv_wish : choosed CV wishes json array
     * cv_language : choosed CV languages json array
     * cv_skill : choosed CV skills json array
     * cv_education : choosed CV educations json array
     * cv_experience :  CV experiences json array
     * @return json with sucess or failed as status
     */
    public function create_cv(Request $request)
    {
        $cv = $request->post('cv');
        $cv = json_decode($cv);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;


        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
        $cv->birthday = str_replace($find_arr, $replace_arr, $cv->birthday);

        $first_name = $cv->first_name;
        $last_name = $cv->last_name;
        $gender = $cv->gender;


        $birth = GeneralHelper::md_picker_parse_date($cv->birthday);
        $about_me = $cv->about_me;
        $age = Carbon::parse($birth)->diff(Carbon::now())->y;
        $location = $cv->location;
        $job_type = $cv->job_type;

        $categories = $cv->cv_category;
        $wishes = $cv->cv_wish;
        $languages = $cv->cv_language;
        $skills = $cv->cv_skill;
        $educations = $cv->cv_education;
        $experiences = $cv->cv_experience;

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
                    //ImageOptimizer::optimize($sTempFileName);
                } else {
                }
            }
        } else {
            if (User::where('id', $user_id)->first()) {
                $avatar_name = User::where('id', $user_id)->first()->avatar;
                $avatar_real_name = User::where('id', $user_id)->first()->avatar_real_name;
                $avatar_real_size = User::where('id', $user_id)->first()->avatar_real_size;
            }
        }

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

        if (Cv::where('user_id', $user_id)->first()) {


            $cv_id = Cv::where('user_id', $user_id)->first()->id;
            if ($cv_name != '') {
                if (CvDocument::where('cv_id', $cv_id)->first()) {
                    CvDocument::where('cv_id', $cv_id)->update(['user_id' => $user_id, 'url' => $cv_name, 'type' => $cv_real_type, 'realname' => $cv_real_name, 'size' => $cv_size]);
                } else {
                    $cv_document = new CvDocument();
                    $cv_document->cv_id = $cv_id;
                    $cv_document->user_id = $user_id;
                    $cv_document->url = $cv_name;
                    $cv_document->type = $cv_real_type;
                    $cv_document->realname = $cv_real_name;
                    $cv_document->size = $cv_size;
                    $cv_document->save();
                }
            }

            if ($avatar_name == 'empty') {
                Cv::where('user_id', $user_id)->update(['name' => $first_name . " " . $last_name, 'first_name' => $first_name, 'last_name' => $last_name, 'gender' => $gender, 'birthday' => $birth, 'about_me' => $about_me, 'age' => $age, 'location_id' => $location == '' ? '' : $location->id, 'job_type' => $job_type == '' ? '' : $job_type->id, 'published' => 1]);
            } else {
                Cv::where('user_id', $user_id)->update(['name' => $first_name . " " . $last_name, 'first_name' => $first_name, 'last_name' => $last_name, 'gender' => $gender, 'birthday' => $birth, 'about_me' => $about_me, 'age' => $age, 'profile_pic' => $avatar_name, 'profile_pic_real_name' => $avatar_real_name, 'profile_pic_real_size' => $avatar_real_size, 'location_id' => $location == '' ? '' : $location->id, 'job_type' => $job_type == '' ? '' : $job_type->id, 'published' => 1]);

                User::where('id', $user_id)->update(['avatar' => $avatar_name, 'avatar_real_name' => $avatar_real_name, 'avatar_real_size' => $avatar_real_size]);
            }


            CvVideo::where('user_id', $user_id)->update(['cv_id' => $cv_id]);
            SeekerAgentResult::where('matched_user_id', $user_id)->where('type', 1)->update(['type' => 0]);

            $new_notification = new Notification();
            $new_notification->user_id = $user_id;
            $new_notification->sender = 1;
            $new_notification->type = 1;
            $new_notification->name = 0;
            $new_notification->save();

            User::where('id', $user_id)->update(['cv_ready' => 1]);

            CvCategory::where('cv_id', $cv_id)->delete();
            foreach ($categories as $v) {
                $new_category = new CvCategory();
                $new_category->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_category_id = GeneralHelper::insert_new_category($v->name);
                } else {
                    $selected_category_id = $v->id;
                }
                $new_category->category_id = $selected_category_id;
                $new_category->save();
            }
            CvWish::where('cv_id', $cv_id)->delete();
            foreach ($wishes as $v) {
                $new_wish = new CvWish();
                $new_wish->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_jobtitle_id = GeneralHelper::insert_new_jobtitle($v->name);
                } else {
                    $selected_jobtitle_id = $v->id;
                }
                $new_wish->job_title_id = $selected_jobtitle_id;
                $new_wish->save();
            }
            CvLanguage::where('cv_id', $cv_id)->delete();
            foreach ($languages as $v) {
                $new_language = new CvLanguage();
                $new_language->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_language_id = GeneralHelper::insert_new_language($v->name);
                } else {
                    $selected_language_id = $v->id;
                }
                $new_language->language_id = $selected_language_id;
                $new_language->save();
            }
            CvSkill::where('cv_id', $cv_id)->delete();
            foreach ($skills as $v) {
                $new_skill = new CvSkill();
                $new_skill->cv_id = $cv_id;
                if (empty($v->id)) {
                    $selected_skill_id = GeneralHelper::insert_new_skill($v->name);
                } else {
                    $selected_skill_id = $v->id;
                }
                $new_skill->skill_id = $selected_skill_id;
                $new_skill->save();
            }
            CvEducation::where('cv_id', $cv_id)->delete();
            foreach ($educations as $v) {
                $new_education = new CvEducation();
                $new_education->cv_id = $cv_id;
                $new_education->course_name = $v->course_name->id;
                $new_education->education_name = $v->education_name;
                $new_education->education_start_year = intval($v->education_start_year);
                $new_education->education_end_year = intval($v->education_end_year);
                $new_education->education_description = $v->education_description;
                $new_education->save();
            }
            CvExperience::where('cv_id', $cv_id)->delete();

            $exp = [];
            foreach ($experiences as $v) {
                $new_experience = new CvExperience();
                $new_experience->cv_id = $cv_id;
                $new_experience->title = $v->title;
                $new_experience->company_name = $v->company_name;
                $new_experience->experience_description = $v->experience_description;
                $new_experience->experience_start_year = intval($v->experience_start_year);
                $new_experience->experience_start_month = intval($v->experience_start_month);

                if ($v->present) {
                    $new_experience->experience_end_year = intval(Carbon::now()->year);
                    $new_experience->experience_end_month = intval(Carbon::now()->month);
                } else {
                    $new_experience->experience_end_year = $v->experience_end_year == '' ? 0 : intval($v->experience_end_year);
                    $new_experience->experience_end_month = $v->experience_end_month == '' ? 0 : intval($v->experience_end_month);
                }

                $new_experience->experience_year = intval($new_experience->experience_end_year - $new_experience->experience_start_year);


                $exp[] = $this->calculate_month($new_experience->experience_start_year, $new_experience->experience_start_month, $new_experience->experience_end_year, $new_experience->experience_end_month);

                $new_experience->present = $v->present;
                $new_experience->save();
            }
            if ($exp) {
                $exp_number_array = [];
                foreach ($exp as $exp_key => $exp_item) {
                    $exp_number_array[] = intval($exp_item['number']);
                }

                $experience = max($exp_number_array);

                $experience_format = '';
                foreach ($exp as $exp_key => $exp_item) {
                    if ($experience == $exp_item['number']) {
                        $experience_format = $exp_item['format'];
                        break;
                    }
                }
                Cv::where('user_id', $user_id)->update(['experience' => $experience, 'experience_format' => $experience_format]);
            }

            return response()->json(['result' => 'success'], 200);
        } else {

            $new_cv = new Cv();
            $new_cv->user_id = $user_id;
            $new_cv->name = $first_name . " " . $last_name;
            $new_cv->first_name = $first_name;
            $new_cv->last_name = $last_name;
            $new_cv->url =  GeneralHelper::generateRandomString(30);
            $new_cv->gender = $gender;
            $new_cv->birthday = $birth;
            $new_cv->about_me = $about_me;
            $new_cv->age = $age;



            $new_cv->profile_pic = $avatar_name;

            $new_cv->profile_pic_real_name = $avatar_real_name;
            $new_cv->profile_pic_real_size = $avatar_real_size;

            $new_cv->location_id = $location == '' ? '' : $location->id;
            $new_cv->job_type = $job_type == '' ? '' : $job_type->id;
            $new_cv->published = 1;


            if ($avatar_name != 'empty') {
                User::where('id', $user_id)->update(['avatar' => $avatar_name, 'avatar_real_name' => $avatar_real_name, 'avatar_real_size' => $avatar_real_size]);
            }

            if ($new_cv->save()) {

                CvVideo::where('user_id', $user_id)->update(['cv_id' => $new_cv->id]);
                SeekerAgentResult::where('matched_user_id', $user_id)->where('type', 1)->update(['type' => 0]);

                $new_notification = new Notification();
                $new_notification->user_id = $user_id;
                $new_notification->sender = 1;
                $new_notification->type = 1;
                $new_notification->name = 0;
                $new_notification->save();

                User::where('id', $user_id)->update(['cv_ready' => 1]);


                if ($cv_name != '') {
                    $cv_document = new CvDocument();
                    $cv_document->cv_id = $new_cv->id;
                    $cv_document->user_id = $user_id;
                    $cv_document->url = $cv_name;
                    $cv_document->type = $cv_real_type;
                    $cv_document->realname = $cv_real_name;
                    $cv_document->size = $cv_size;
                    $cv_document->save();
                }

                $categoryArr = [];
                foreach ($categories as $v) {
                    $new_category = new CvCategory();
                    $new_category->cv_id = $new_cv->id;
                    if (empty($v->id)) {
                        $selected_category_id = GeneralHelper::insert_new_category($v->name);
                    } else {
                        $selected_category_id = $v->id;
                    }
                    $new_category->category_id = $selected_category_id;
                    $new_category->save();
                    $categoryArr[] = $selected_category_id;
                }

                foreach ($wishes as $v) {
                    $new_wish = new CvWish();
                    $new_wish->cv_id = $new_cv->id;
                    if (empty($v->id)) {
                        $selected_jobtitle_id = GeneralHelper::insert_new_jobtitle($v->name);
                    } else {
                        $selected_jobtitle_id = $v->id;
                    }
                    $new_wish->job_title_id = $selected_jobtitle_id;
                    $new_wish->save();
                }

                $languageArr = [];
                foreach ($languages as $v) {
                    $new_language = new CvLanguage();
                    $new_language->cv_id = $new_cv->id;
                    if (empty($v->id)) {
                        $selected_language_id = GeneralHelper::insert_new_language($v->name);
                    } else {
                        $selected_language_id = $v->id;
                    }
                    $new_language->language_id = $selected_language_id;
                    $new_language->save();
                    $languageArr[] = $selected_language_id;
                }

                foreach ($skills as $v) {
                    $new_skill = new CvSkill();
                    $new_skill->cv_id = $new_cv->id;
                    if (empty($v->id)) {
                        $selected_skill_id = GeneralHelper::insert_new_skill($v->name);
                    } else {
                        $selected_skill_id = $v->id;
                    }
                    $new_skill->skill_id = $selected_skill_id;
                    $new_skill->save();
                }

                $educationArr = [];
                foreach ($educations as $v) {
                    $new_education = new CvEducation();
                    $new_education->cv_id = $new_cv->id;
                    $educationArr[] = $v->course_name->id;
                    $new_education->course_name = $v->course_name->id;
                    $new_education->education_name = $v->education_name;
                    $new_education->education_start_year = intval($v->education_start_year);
                    $new_education->education_end_year = intval($v->education_end_year);
                    $new_education->education_description = $v->education_description;
                    $new_education->save();
                }
                $exp = [];
                foreach ($experiences as $v) {
                    $new_experience = new CvExperience();
                    $new_experience->cv_id = $new_cv->id;
                    $new_experience->title = $v->title;
                    $new_experience->company_name = $v->company_name;
                    $new_experience->experience_description = $v->experience_description;
                    $new_experience->experience_start_year = intval($v->experience_start_year);
                    $new_experience->experience_start_month = intval($v->experience_start_month);

                    if ($v->present) {
                        $new_experience->experience_end_year = intval(Carbon::now()->year);
                        $new_experience->experience_end_month = intval(Carbon::now()->month);
                    } else {
                        $new_experience->experience_end_year = $v->experience_end_year == '' ? 0 : intval($v->experience_end_year);
                        $new_experience->experience_end_month = $v->experience_end_month == '' ? 0 : intval($v->experience_end_month);
                    }

                    $new_experience->experience_year = intval($new_experience->experience_end_year - $new_experience->experience_start_year);
                    $exp[] = $this->calculate_month($new_experience->experience_start_year, $new_experience->experience_start_month, $new_experience->experience_end_year, $new_experience->experience_end_month);

                    $new_experience->present = $v->present;
                    $new_experience->save();
                }
                if ($exp) {
                    $exp_number_array = [];
                    foreach ($exp as $exp_key => $exp_item) {
                        $exp_number_array[] = intval($exp_item['number']);
                    }

                    $experience = max($exp_number_array);

                    $experience_format = '';
                    foreach ($exp as $exp_key => $exp_item) {
                        if ($experience == $exp_item['number']) {
                            $experience_format = $exp_item['format'];
                            break;
                        }
                    }
                    Cv::where('user_id', $user_id)->update(['experience' => $experience, 'experience_format' => $experience_format]);
                }


                return response()->json(['result' => 'success', 'matched_users' => []], 200);
            } else {
                return response()->json(['result' => 'failed', 'matched_users' => []], 200);
            }
        }
    }
    // public function generate_notification($cv_id, $user_id, $seekerAgent)
    // {

    //     $return_ids = [];
    //     foreach ($seekerAgent as $k => $v) {
    //         $new_notification = new Notification();
    //         $new_notification->user_id = $v->user_id;
    //         $new_notification->name = $cv_id;
    //         $new_notification->sender = $v->id;
    //         $new_notification->type = 16;
    //         $new_notification->save();


    //         if (!SeekerAgentResult::where('agent_id', $v->id)->where('matched_user_id', $user_id)->first()) {
    //             $new_seeker_agent_result = new SeekerAgentResult();
    //             $new_seeker_agent_result->agent_id = $v->id;
    //             $new_seeker_agent_result->matched_user_id = $user_id;
    //             $new_seeker_agent_result->save();
    //         }

    //         $return_ids[] = $v->user_id;
    //     }
    //     return $return_ids;
    // }
    /**
     * EndPoint api/remove_cv_video
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * delete video cv with given video id and user id
     * @return json with status as success or failed
     */
    public function remove_cv_video(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $video_id = $request->post('video_id');
        if (CvVideo::where('user_id', $user_id)->where('id', $video_id)->first()) {
            $name = CvVideo::where('user_id', $user_id)->where('id', $video_id)->first()->name;
            if (file_exists(public_path() . '/images/video_cv/' . $name)) {
                @unlink(public_path() . '/images/video_cv/' . $name);
            } else {
            }
            CvVideo::where('id', $video_id)->delete();
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/delete_video_cv
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * delete all video cv with given user id
     * @return json with status as success or failed
     */
    public function delete_video_cv(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        if (CvVideo::where('user_id', $user_id)->first()) {
            $name = CvVideo::where('user_id', $user_id)->first()->name;
            if (file_exists(public_path() . '/images/video_cv/' . $name)) {
                @unlink(public_path() . '/images/video_cv/' . $name);
            } else {
            }
            CvVideo::where('user_id', $user_id)->delete();
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/get_cv_video
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * get all video cv with given user id
     * @return json array with videos
     */
    public function get_cv_video(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $videos = CvVideo::where('user_id', $user_id)->get();
        return response()->json($videos, 200);
    }
    /**
     * EndPoint api/add_video_cv
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * create video cv with upload video and notify this to employer and added notification
     * @return json with created all videos and matched employers who seeking video cv as agent condition
     */
    public function add_video_cv(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $cv_real_type = '';
        $cv_real_name = '';
        $cv_name = '';
        $cv_real_size = '';

        if (!empty($_FILES['video']['tmp_name'])) {
            if (is_uploaded_file($_FILES['video']['tmp_name'])) {

                $cv_real_type = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                $cv_real_name = $_FILES['video']['name'];
                $cv_real_size = $_FILES['video']['size'];

                $cv_name = md5(time() . rand()) . '_cv_video.' . $cv_real_type;

                $sTempFileName = public_path() . '/images/video_cv/' . $cv_name;

                if (move_uploaded_file($_FILES['video']['tmp_name'], $sTempFileName)) {
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
     * Caluate differ from given start of year, month and end of year, month
     * @return with format and result as months
     */
    public function calculate_month($start_year, $start_month, $end_year, $end_month)
    {
        $result = [];

        $from = Carbon::parse($start_year . '-' . $start_month);
        $to = Carbon::parse($end_year . '-' . $end_month);
        $result['format'] = $from->diffForHumans($to, true, false, 2);
        $result['number'] = $from->diffInMonths($to);
        return $result;
    }
/**
     * EndPoint api/search_cv_pagination_with_demo
     * HTTP SUBMIT : POST
     * Get CVS according the given condition for demo
     * @param 
     * keyword : user name or etc, 
     * pageNumber : current page number
     * pagePerSize : current size per page. as a default 16
     * job_type : json array with job type
     * educations : json array with educations filter
     * experiences : json array with experiences filter
     * extras : CV or video presentation
     * language : language filter
     * min_age : minimum age, default : 15
     * max_age : maximum age, default : 66
     * @return json with cvs and its count
     */
    public function search_cv_pagination_with_demo(Request $request)
    {

        $keyword = $request->post('keyword');
        $pageNumber = $request->post('pageNumber');
        $pagePerSize = $request->post('pagePerSize');
        $job_type = $request->post('job_type');
        $educations = $request->post('educations');
        $experiences = $request->post('experiences');
        $extras = $request->post('extras');
        $language = $request->post('language');

        $min_age = $request->post('min_age');
        $max_age = $request->post('max_age');

        $result_json = [];
        $extra_condition = 0;
        foreach ($job_type as $k => $v) {
            if ($v['status'] == 1) {
                $extra_condition = 1;
                break;
            }
        }


        $degree_search = null;
        foreach ($educations as $k => $v) {
            if ($v['status'] == 1) {
                $degree_search = $v['id'];
                $extra_condition = 1;
                break;
            }
        }

        foreach ($experiences as $k => $v) {
            if ($v['status'] == 1) {
                $extra_condition = 1;
                break;
            }
        }

        $videpresentation = null;
        $finished_cv = null;
        foreach ($extras as $k => $v) {
            if ($v['status'] == 1) {
                if ($k == 0) {
                    $finished_cv = 1;
                    $extra_condition = 1;
                } else if ($k == 1) {
                    $videpresentation = 1;
                    $extra_condition = 0;
                }
            }
        }

        $language_condition = null;
        if ($language != '') {
            $extra_condition = 1;
            $language_condition = 1;
        }


        if ($min_age == 15 && $max_age == 66) {
        } else {
            $extra_condition = 1;
        }


        $cvs = Cv::with(['CvCategory', 'CvCategory.category', 'CvWish', 'CvWish.job_title', 'cv_city', 'CvEducation', 'CvEducation.degree', 'CvExperience', 'user', 'cv_language', 'cv_language.language'])
            ->when($degree_search, function ($query) use ($educations) {
                $query->whereHas('CvEducation', function ($query) use ($educations) {
                    $e_idx = 0;
                    foreach ($educations as $k => $v) {
                        if ($v['status'] == 1) {
                            switch ($k) {
                                case 0:
                                case 1:
                                case 2:
                                case 3:
                                case 4:
                                case 5:
                                case 6:
                                    if ($e_idx == 0) {
                                        $query->where('course_name', $k + 1);
                                    } else {
                                        $query->orWhere('course_name', $k + 1);
                                    }
                                    break;
                            }
                            $e_idx++;
                        }
                    }
                });
            })
            ->when($videpresentation, function ($query) {
                $query->whereHas('cv_video', function ($query) {
                });
            })
            ->when($language_condition, function ($query) use ($language) {
                $query->whereHas('cv_language.language', function ($query) use ($language) {
                    $query->where('name', 'like', '%' . $language . '%');
                });
            })
            ->where(function ($query) use ($keyword, $job_type, $experiences, $min_age, $max_age) {
                $idx = 0;

                if ($keyword == 'all') {
                } else {
                    // $query->where('name', 'like', '%' . $keyword . '%');

                    $query->whereRaw("concat(first_name, ' ', last_name) like '%" . $keyword . "%' ")->where('age', '>=', $min_age)->where('age', '<=', $max_age);

                    $query->orWhereHas('CvWish.job_title', function ($query) use ($keyword, $min_age, $max_age) {
                        $query->where('name', 'like', '%' . $keyword . '%')->where('age', '>=', $min_age)->where('age', '<=', $max_age);
                    });
                }

                $query->where('age', '>=', $min_age)->where('age', '<=', $max_age);

                foreach ($job_type as $k => $v) {
                    if ($v['status'] == 1) {
                        switch ($k) {
                            case 0:
                            case 1:
                            case 2:
                            case 3:
                                if ($idx == 0) {
                                    $query->where('job_type', $k + 1);
                                } else {
                                    $query->orWhere('job_type', $k + 1);
                                }
                                break;
                        }
                        $idx++;
                    }
                }
                $exp_idx = 0;
                foreach ($experiences as $k => $v) {
                    if ($v['status'] == 1) {
                        switch ($k) {
                            case 0: //1 year
                                if ($exp_idx == 0) {
                                    $query->where('experience', '<', 12);
                                } else {
                                    $query->orWhere('experience', '<', 12);
                                }
                                break;
                            case 1: //1 to 3 years
                                if ($exp_idx == 0) {
                                    $query->where('experience', '>=', 12);
                                    $query->where('experience', '<=', 36);
                                } else {
                                    $query->orWhere('experience', '>=', 12);
                                    $query->where('experience', '<=', 36);
                                }
                                break;
                            case 2: // 3 to 6 years
                                if ($exp_idx == 0) {
                                    $query->where('experience', '>=', 36);
                                    $query->where('experience', '<=', 72);
                                } else {
                                    $query->orWhere('experience', '>=', 36);
                                    $query->where('experience', '<=', 72);
                                }
                                break;
                            case 3: // 6 years
                                if ($exp_idx == 0) {
                                    $query->where('experience', '>=', 72);
                                } else {
                                    $query->orWhere('experience', '>=', 72);
                                }
                                break;
                        }
                        $exp_idx++;
                    }
                }
            })
            ->where('published', 1)
            ->where('active', 1)
            ->orderBy('created_at', 'desc')->get();


        foreach ($cvs as $k => $v) {
            $v['cv_type'] = 1;
            if ($k % 2 == 0) {
                $v['heart'] = 1;
            } else {
                $v['heart'] = 0;
            }
            $result_json[] = $v;
        }

        if ($extra_condition) {
            $userA = [];
        } else {
            $userA = User::with(['location', 'videocv'])
                ->when($videpresentation, function ($query) {
                    $query->whereHas('videocv', function ($query) {
                    });
                })
                // ->whereHas('location')
                ->doesnthave('cv')
                ->where('type', 1)
                ->where('standby', 0)
                ->where(function ($query) use ($keyword) {
                    if ($keyword == "all") {
                        $query->where('name', '!=', '');
                    } else {
                        $query->orWhereRaw("name like '%" . $keyword . "%' ");
                    }
                })
                ->orderBy('created_at', 'asc');

            $userB = User::with(['location', 'videocv', 'cv'])
                ->when($videpresentation, function ($query) {
                    $query->whereHas('videocv', function ($query) {
                    });
                })
                // ->whereHas('location')
                ->whereHas('cv', function ($query) {
                    $query->where('published', 0);
                })
                ->where('type', 1)
                ->where('standby', 0)
                ->where(function ($query) use ($keyword) {
                    if ($keyword == "all") {
                        $query->where('name', '!=', '');
                    } else {
                        $query->orWhereRaw("name like '%" . $keyword . "%' ");
                    }
                })
                ->orderBy('created_at', 'asc');

            $userA = $userA->union($userB)->get();
        }


        foreach ($userA as $k => $v) {
            $item['user_id'] = $v->id;
            $item['first_name'] = $v->name;
            $item['last_name'] = '';

            if ($v->location) {
                $item['city'] = $v->location->name;
            } else {
                $item['city'] = '';
            }
            $item['cv_type'] = 0;
            $item['avatar'] = $v->avatar;
            $item['user_specify'] = $v->user_specify;
            $item['heart'] = 0;

            if ($k % 2 == 0) {
                $item['heart'] = 1;
            } else {
                $item['heart'] = 0;
            }
            $result_json[] = $item;
        }

        $totalSize = sizeof($result_json);

        $result_json = collect($result_json)->paginate($pagePerSize, $totalSize, $pageNumber);
        return response()->json(['cvs' => $result_json, 'count' => $totalSize], 200);
    }
    /**
     * EndPoint api/search_cv_pagination
     * HTTP SUBMIT : POST
     * Get CVS according the given condition.
     * @param 
     * keyword : user name or etc, 
     * pageNumber : current page number
     * pagePerSize : current size per page. as a default 16
     * job_type : json array with job type
     * educations : json array with educations filter
     * experiences : json array with experiences filter
     * extras : CV or video presentation
     * language : language filter
     * min_age : minimum age, default : 15
     * max_age : maximum age, default : 66
     * @return json with cvs and its count
     */
    public function search_cv_pagination(Request $request)
    {

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $keyword = $request->post('keyword');
        $pageNumber = $request->post('pageNumber');
        $pagePerSize = $request->post('pagePerSize');
        $job_type = $request->post('job_type');
        $educations = $request->post('educations');
        $experiences = $request->post('experiences');
        $extras = $request->post('extras');
        $language = $request->post('language');

        $min_age = $request->post('min_age');
        $max_age = $request->post('max_age');

        $result_json = [];
        $extra_condition = 0;
        foreach ($job_type as $k => $v) {
            if ($v['status'] == 1) {
                $extra_condition = 1;
                break;
            }
        }


        $degree_search = null;
        foreach ($educations as $k => $v) {
            if ($v['status'] == 1) {
                $degree_search = $v['id'];
                $extra_condition = 1;
                break;
            }
        }

        foreach ($experiences as $k => $v) {
            if ($v['status'] == 1) {
                $extra_condition = 1;
                break;
            }
        }

        $videpresentation = null;
        $finished_cv = null;
        foreach ($extras as $k => $v) {
            if ($v['status'] == 1) {
                if ($k == 0) {
                    $finished_cv = 1;
                    $extra_condition = 1;
                } else if ($k == 1) {
                    $videpresentation = 1;
                    $extra_condition = 0;
                }
            }
        }

        $language_condition = null;
        if ($language != '') {
            $extra_condition = 1;
            $language_condition = 1;
        }


        if ($min_age == 15 && $max_age == 66) {
        } else {
            $extra_condition = 1;
        }


        $cvs = Cv::with(['CvCategory', 'CvCategory.category', 'CvWish', 'CvWish.job_title', 'cv_city', 'CvEducation', 'CvEducation.degree', 'CvExperience', 'user', 'cv_language', 'cv_language.language'])
            ->when($degree_search, function ($query) use ($educations) {
                $query->whereHas('CvEducation', function ($query) use ($educations) {
                    $e_idx = 0;
                    foreach ($educations as $k => $v) {
                        if ($v['status'] == 1) {
                            switch ($k) {
                                case 0:
                                case 1:
                                case 2:
                                case 3:
                                case 4:
                                case 5:
                                case 6:
                                    if ($e_idx == 0) {
                                        $query->where('course_name', $k + 1);
                                    } else {
                                        $query->orWhere('course_name', $k + 1);
                                    }
                                    break;
                            }
                            $e_idx++;
                        }
                    }
                });
            })
            ->when($videpresentation, function ($query) {
                $query->whereHas('cv_video', function ($query) {
                });
            })
            ->when($language_condition, function ($query) use ($language) {
                $query->whereHas('cv_language.language', function ($query) use ($language) {
                    $query->where('name', 'like', '%' . $language . '%');
                });
            })
            ->where(function ($query) use ($keyword, $job_type, $experiences, $min_age, $max_age) {
                $idx = 0;

                if ($keyword == 'all') {
                } else {
                    // $query->where('name', 'like', '%' . $keyword . '%');

                    $query->whereRaw("concat(first_name, ' ', last_name) like '%" . $keyword . "%' ")->where('age', '>=', $min_age)->where('age', '<=', $max_age);

                    $query->orWhereHas('CvWish.job_title', function ($query) use ($keyword, $min_age, $max_age) {
                        $query->where('name', 'like', '%' . $keyword . '%')->where('age', '>=', $min_age)->where('age', '<=', $max_age);
                    });
                }

                $query->where('age', '>=', $min_age)->where('age', '<=', $max_age);

                foreach ($job_type as $k => $v) {
                    if ($v['status'] == 1) {
                        switch ($k) {
                            case 0:
                            case 1:
                            case 2:
                            case 3:
                                if ($idx == 0) {
                                    $query->where('job_type', $k + 1);
                                } else {
                                    $query->orWhere('job_type', $k + 1);
                                }
                                break;
                        }
                        $idx++;
                    }
                }
                $exp_idx = 0;
                foreach ($experiences as $k => $v) {
                    if ($v['status'] == 1) {
                        switch ($k) {
                            case 0: //1 year
                                if ($exp_idx == 0) {
                                    $query->where('experience', '<', 12);
                                } else {
                                    $query->orWhere('experience', '<', 12);
                                }
                                break;
                            case 1: //1 to 3 years
                                if ($exp_idx == 0) {
                                    $query->where('experience', '>=', 12);
                                    $query->where('experience', '<=', 36);
                                } else {
                                    $query->orWhere('experience', '>=', 12);
                                    $query->where('experience', '<=', 36);
                                }
                                break;
                            case 2: // 3 to 6 years
                                if ($exp_idx == 0) {
                                    $query->where('experience', '>=', 36);
                                    $query->where('experience', '<=', 72);
                                } else {
                                    $query->orWhere('experience', '>=', 36);
                                    $query->where('experience', '<=', 72);
                                }
                                break;
                            case 3: // 6 years
                                if ($exp_idx == 0) {
                                    $query->where('experience', '>=', 72);
                                } else {
                                    $query->orWhere('experience', '>=', 72);
                                }
                                break;
                        }
                        $exp_idx++;
                    }
                }
            })
            ->where('published', 1)
            ->where('active', 1)
            ->orderBy('created_at', 'desc')->get();


        foreach ($cvs as $k => $v) {
            $v['cv_type'] = 1;
            if (UserCvsSaved::where('user_id', $user_id)->where('resume_user_id', $v->user_id)->first()) {
                $v['heart'] = 1;
            } else {
                $v['heart'] = 0;
            }
            $result_json[] = $v;
        }

        if ($extra_condition) {
            $userA = [];
        } else {
            $userA = User::with(['location', 'videocv'])
                ->when($videpresentation, function ($query) {
                    $query->whereHas('videocv', function ($query) {
                    });
                })
                // ->whereHas('location')
                ->doesnthave('cv')
                ->where('type', 1)
                ->where('standby', 0)
                ->where(function ($query) use ($keyword) {
                    if ($keyword == "all") {
                        $query->where('name', '!=', '');
                    } else {
                        $query->orWhereRaw("name like '%" . $keyword . "%' ");
                    }
                })
                ->orderBy('created_at', 'asc');

            $userB = User::with(['location', 'videocv', 'cv'])
                ->when($videpresentation, function ($query) {
                    $query->whereHas('videocv', function ($query) {
                    });
                })
                // ->whereHas('location')
                ->whereHas('cv', function ($query) {
                    $query->where('published', 0);
                })
                ->where('type', 1)
                ->where('standby', 0)
                ->where(function ($query) use ($keyword) {
                    if ($keyword == "all") {
                        $query->where('name', '!=', '');
                    } else {
                        $query->orWhereRaw("name like '%" . $keyword . "%' ");
                    }
                })
                ->orderBy('created_at', 'asc');

            $userA = $userA->union($userB)->get();
        }


        foreach ($userA as $k => $v) {
            $item['user_id'] = $v->id;
            $item['first_name'] = $v->name;
            $item['last_name'] = '';

            if ($v->location) {
                $item['city'] = $v->location->name;
            } else {
                $item['city'] = '';
            }
            $item['cv_type'] = 0;
            $item['avatar'] = $v->avatar;
            $item['user_specify'] = $v->user_specify;
            $item['heart'] = 0;

            if (UserCvsSaved::where('user_id', $user_id)->where('resume_user_id', $v->id)->first()) {
                $item['heart'] = 1;
            } else {
                $item['heart'] = 0;
            }
            $result_json[] = $item;
        }

        $totalSize = sizeof($result_json);

        $result_json = collect($result_json)->paginate($pagePerSize, $totalSize, $pageNumber);
        return response()->json(['cvs' => $result_json, 'count' => $totalSize], 200);
    }
    /**
     * EndPoint api/delete_saved_resume
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Delete saved cv with given resume user id
     * @param
     * resume_user_id : the user id
     * @return json with result as success, failed
     */
    public function delete_saved_resume(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $resume_user_id = $request->post('resume_user_id');
        if (UserCvsSaved::where('resume_user_id', $resume_user_id)->where('user_id', $user_id)->delete()) {
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/create_seeker_agent
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Employer create job Seeker agent from given agent details and added status to notification
     * @return json with result as success, failed
     */
    public function create_seeker_agent(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $agent = $request->post('agent');
        $agent = json_decode($agent);

        $title = $agent->title;
        $job_type = $agent->job_type;
        $categories = $agent->categories;
        $location = $agent->location;
        $education = $agent->education;
        $languages = $agent->languages;
        $video_presentation = $agent->video_presentation;
        $min_age = $agent->min_age;
        $max_age = $agent->max_age;


        $new_seeker_agent = new SeekerAgent();
        $new_seeker_agent->name = $title;
        $new_seeker_agent->user_id = $user_id;

        $type = 0;
        if ($job_type != '') {
            $new_seeker_agent->job_type_id = $job_type->id;
            $type++;
        }

        $video_presentation_condition = null;
        if ($video_presentation) {
            $video_presentation_condition = 1;
            $new_seeker_agent->video_presentation = $video_presentation;
            $type++;
        }

        $education_condition = null;
        $educationArr = [];
        if ($education != '') {
            $education_condition = 1;
            $new_seeker_agent->education_id =  $education->id;
            $type++;
            $educationArr[] = $education->id;
        }

        if ($min_age == 15 && $max_age == 66) {
        } else {
            $new_seeker_agent->min_age = $min_age;
            $new_seeker_agent->max_age = $max_age;
        }



        if ($new_seeker_agent->save()) {

            $categoryArr = [];
            $category_condition = null;
            if ($categories != '') {
                foreach ($categories as $k => $v) {
                    $category_condition = 1;
                    $seeker_agent_category = new SeekerAgentCategory();
                    $seeker_agent_category->agent_id = $new_seeker_agent->id;

                    if (empty($v->id)) {
                        $selected_category_id = GeneralHelper::insert_new_category($v->name);
                    } else {
                        $selected_category_id = $v->id;
                    }
                    $seeker_agent_category->category_id = $selected_category_id;
                    $seeker_agent_category->save();
                    $categoryArr[] = $selected_category_id;
                }
                if (sizeof($categories)) {
                    $type++;
                }
            }

            $languageArr = [];
            $language_condition = null;
            if ($languages != '') {
                foreach ($languages as $k => $v) {
                    $language_condition = 1;
                    $seeker_agent_language = new SeekerAgentLanguage();
                    $seeker_agent_language->agent_id = $new_seeker_agent->id;

                    if (empty($v->id)) {
                        $selected_language_id = GeneralHelper::insert_new_language($v->name);
                    } else {
                        $selected_language_id = $v->id;
                    }
                    $seeker_agent_language->language_id = $selected_language_id;
                    $seeker_agent_language->save();
                    $languageArr[] = $selected_language_id;
                }
                if (sizeof($languages)) {
                    $type++;
                }
            }
            $locationArr = [];
            if ($location != '') {
                foreach ($location as $k => $v) {
                    $seeker_agent_location = new SeekerAgentLocation();
                    $seeker_agent_location->agent_id = $new_seeker_agent->id;

                    if (empty($v->id)) {
                        $selected_location_id = GeneralHelper::insert_new_location($v->name);
                    } else {
                        $selected_location_id = $v->id;
                    }
                    $seeker_agent_location->location_id = $selected_location_id;
                    $seeker_agent_location->save();
                    $locationArr[] = $selected_location_id;
                }
                if (sizeof($location)) {
                    $type++;
                }
            }

            SeekerAgent::where('id', $new_seeker_agent->id)->update(['type' => $type]);

            $new_notification = new Notification();
            $new_notification->user_id = $user_id;
            $new_notification->sender = 1;
            $new_notification->type = 13;
            $new_notification->name = $new_seeker_agent->id;
            $new_notification->save();


            return  response()->json(['result' => 'success'], 200);
        } else {
            return  response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/update_seeker_agent
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Update Job Seeker agent from given agent details and added status to notification
     * @return json with result as success, failed
     */
    public function update_seeker_agent(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $agent = $request->post('agent');
        $agent = json_decode($agent);

        $agent_id = $request->post('agent_id');

        $title = $agent->name;
        $job_type = $agent->agent_job_type;
        $categories = $agent->agent_category;
        $location = $agent->agent_location;

        $education = $agent->agent_education;
        $languages = $agent->agent_language;
        $video_presentation = $agent->video_presentation;
        $min_age = $agent->min_age;
        $max_age = $agent->max_age;

        if (SeekerAgent::where('id', $agent_id)->first()) {


            $type = 0;
            if ($job_type != '') {
                $type++;
            }

            if ($video_presentation) {
                $type++;
            }

            if ($education) {
                $type++;
            }

            SeekerAgent::where('id', $agent_id)->update([
                'name' => $title,
                'job_type_id' => $job_type != '' ? $job_type->id : 0,
                'video_presentation' => $video_presentation,
                'min_age' => $min_age,
                'max_age' => $max_age,
                'education_id' => $education != '' ? $education->id : 0
            ]);

            if ($categories != '') {
                SeekerAgentCategory::where('agent_id', $agent_id)->delete();
                foreach ($categories as $k => $v) {
                    $seeker_agent_category = new SeekerAgentCategory();
                    $seeker_agent_category->agent_id = $agent_id;
                    if (empty($v->id)) {
                        $selected_category_id = GeneralHelper::insert_new_category($v->name);
                    } else {
                        $selected_category_id = $v->id;
                    }
                    $seeker_agent_category->category_id = $selected_category_id;
                    $seeker_agent_category->save();
                }
                if (sizeof($categories)) {
                    $type++;
                }
            }


            if ($languages != '') {
                SeekerAgentLanguage::where('agent_id', $agent_id)->delete();

                foreach ($languages as $k => $v) {
                    $seeker_agent_language = new SeekerAgentLanguage();
                    $seeker_agent_language->agent_id = $agent_id;
                    if (empty($v->id)) {
                        $selected_language_id = GeneralHelper::insert_new_language($v->name);
                    } else {
                        $selected_language_id = $v->id;
                    }
                    $seeker_agent_language->language_id = $selected_language_id;
                    $seeker_agent_language->save();
                }
                if (sizeof($languages)) {
                    $type++;
                }
            }


            if ($location != '') {
                SeekerAgentLocation::where('agent_id', $agent_id)->delete();
                foreach ($location as $k => $v) {
                    $seeker_agent_location = new SeekerAgentLocation();
                    $seeker_agent_location->agent_id = $agent_id;
                    if (empty($v->id)) {
                        $selected_location_id = GeneralHelper::insert_new_location($v->name);
                    } else {
                        $selected_location_id = $v->id;
                    }
                    $seeker_agent_location->location_id = $selected_location_id;
                    $seeker_agent_location->save();
                }
                if (sizeof($location)) {
                    $type++;
                }
            }

            SeekerAgent::where('id', $agent_id)->update(['type' => $type]);

            $new_notification = new Notification();
            $new_notification->user_id = $user_id;
            $new_notification->sender = 1;
            $new_notification->type = 14;
            $new_notification->name = $agent_id;
            $new_notification->save();

            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/delete_seeker_agent
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Delete Seeker agent from given agent id and added status to notification.
     * @return json with result as success, failed and updated agents.
     */
    public function delete_seeker_agent(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $agent_id = $request->post('agent_id');
        if (SeekerAgent::where('id', $agent_id)->first()) {
            SeekerAgent::where('id', $agent_id)->delete();
            SeekerAgentCategory::where('agent_id', $agent_id)->delete();
            SeekerAgentLocation::where('agent_id', $agent_id)->delete();
            SeekerAgentLanguage::where('agent_id', $agent_id)->delete();

            $seeker_agents = SeekerAgent::with(['agent_job_type', 'agent_location', 'agent_category', 'agent_category.category'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();

            $new_notification = new Notification();
            $new_notification->user_id = $user_id;
            $new_notification->sender = 1;
            $new_notification->type = 15;
            $new_notification->name = 0;
            $new_notification->save();

            Notification::where('user_id', $user_id)->where('type', 16)->where('sender', $agent_id)->delete();

            return response()->json(['result' => 'success', 'agents' => $seeker_agents], 200);
        } else {
            return response()->json(['result' => 'failed', 'agents' => []], 200);
        }
    }
    /**
     * EndPoint api/get_seeker_agent
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * GET Seeker Agent details with given user id
     * @return json array with agents details
     */
    public function get_seeker_agent(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;

        $seeker_agents = SeekerAgent::with(['agent_job_type', 'agent_location', 'agent_category', 'agent_category.category'])->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();

        return response()->json($seeker_agents, 200);
    }
    /**
     * EndPoint api/get_seeker_agent_by_id
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * GET Seeker Agent details with given agent id and user id
     * @return json with agents details, null if agent is empty
     */
    public function get_seeker_agent_by_id(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);
        $user_id = $user->id;
        $agent_id = $request->post('agent_id');
        if (SeekerAgent::where('id', $agent_id)->where('user_id', $user_id)->first()) {
            $job_agent = SeekerAgent::with(['agent_job_type', 'agent_location', 'agent_location.location', 'agent_category', 'agent_category.category', 'agent_language', 'agent_language.language', 'agent_education'])->where('id', $agent_id)->where('user_id', $user_id)->first();
            return response()->json($job_agent, 200);
        } else {
            return response()->json(null, 200);
        }
    }
    /**
     * EndPoint api/get_cv_users
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get array with users who has cv
     * @return json array with id, name
     */
    public function get_cv_users(Request $request)
    {
        $cvs = Cv::get();

        $result = [];
        foreach ($cvs as $k => $v) {
            $item['id'] = $v->user_id;
            $item['name'] = $v->name;
            $result[] = $item;
        }

        return response()->json($result, 200);
    }
}
