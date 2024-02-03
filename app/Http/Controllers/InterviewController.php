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
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

use App\Contact;
use App\Message;
use App\Interview;
use App\CompanyInterview;
use App\Company;
use App\InterviewInformation;
use App\UserJobsApply;
use PHPMailer\PHPMailer\PHPMailer;

class InterviewController extends Controller
{

    /**
     * get interview details from given user id and user type and current year and current week
     * @param
     * user_id : the id of user which joined stream
     * user_type : the type of user, 1 : jobseeker, 2 : employer
     * current_year : the current year which interview scheduled
     * current_week : the current week which interview scheduled
     * @return
     * interview details
     */
    public function get_interviews_internal($user_id, $user_type, $current_year, $current_week)
    {
        if ($user_type == 1) { //jobseeker
            $interviews = Interview::with(['employer', 'employer.user_company'])
                ->where('week', '=', $current_week)
                ->where('year', '<=', $current_year)
                ->where('seeker_id', $user_id)
                ->orderBy('interview_time', 'asc')->get();
        } else if ($user_type == 2) { //employer
            $interviews = Interview::with(['seeker'])
                ->where('week', '=', $current_week)
                ->where('year', '<=', $current_year)
                ->where('employer_id', $user_id)
                ->orderBy('interview_time', 'asc')->get();
        } else {
        }
        return $interviews;
    }
    /**
     * EndPoint api/finish_video_interview
     * JWT TOKEN which provided by token after user logged in
     * finish video interview from given stream id
     * @param
     * stream_id : id of stream which will be finish
     * @return json with status as success or failed
     */
    public function finish_video_interview(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $user_type = $user->type;

        $stream_id = $request->post('stream_id');
        $interview_seconds = $request->post('interview_seconds');

        if (Interview::where('uuid', $stream_id)->first()) {


            $app_id = Interview::where('uuid', $stream_id)->first()->app_id;
            UserJobsApply::where('id', $app_id)->update(['v_status' => 5]); //video interview finished

            Interview::where('uuid', $stream_id)->update(['ready' => 4, 'interview_duration' => $interview_seconds]);
            if ($user_type == 1) {
                $employer_id = Interview::where('uuid', $stream_id)->first()->employer_id;
                $selected_user = User::where('id', $employer_id)->first();

                $new_message = new Message();
                $new_message->sender_id = $user_id;
                $new_message->receiver_id = $employer_id;
                $new_message->text = 'Du har haft et videointerview med ' . $selected_user->name . ' <br> ' . date("d.m.Y H:i");
                $new_message->is_attach = 4;
                $new_message->uuid = md5(time() . rand());
                if ($new_message->save()) {
                    return response()->json(['result' => 'success'], 200);
                } else {
                    return response()->json(['result' => 'failed'], 200);
                }
            } else {
                $seeker_id = Interview::where('uuid', $stream_id)->first()->seeker_id;
                $selected_user = User::where('id', $seeker_id)->first();

                $new_message = new Message();
                $new_message->sender_id = $user_id;
                $new_message->receiver_id = $seeker_id;
                $new_message->text = 'Du har haft et videointerview med ' . $selected_user->name . ' <br> ' . date("d.m.Y H:i");
                $new_message->is_attach = 4;
                $new_message->uuid = md5(time() . rand());
                if ($new_message->save()) {
                    return response()->json(['result' => 'success'], 200);
                } else {
                    return response()->json(['result' => 'failed'], 200);
                }
            }
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/get_interviews
     * JWT TOKEN which provided by token after user logged in
     * Get Interviews from given week, year and user id and user type
     * @param
     * current_week : calendar of current week
     * current_year : calendar of current year
     * @return json with interviews array
     */
    public function get_interviews(Request $request)
    {
        $current_week = $request->post('current_week');
        $current_year = $request->post('current_year');
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_type = $user->type;

        return response()->json($this->get_interviews_internal($user_id, $user_type, $current_year, $current_week), 200);
    }
    /**
     * EndPoint api/get_interview_detail
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Get interview details from given stream id
     * @param
     * stream_id : the id of stream which connected with specific interview
     * @return json with fetched interview details
     */
    public function get_interview_detail(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $timezone = $user->timezone;

        $stream_id = $request->post('stream_id');

        if ($user->type == 1) { //seeker
            if (Interview::where('uuid', $stream_id)->where('seeker_id', $user_id)->first()) {

                $interview = Interview::with(['employer', 'employer.user_company'])->where('uuid', $stream_id)->where('seeker_id', $user_id)->first();
                $result_message_json = [];
                if ($interview) {
                    $contact_users = $interview->employer->id;
                    Message::where(['sender_id' => $contact_users, 'receiver_id' => $user_id])
                        ->update(['is_seen' => 1]);
                    $messages_row = Message::where(['sender_id' => $user_id, 'receiver_id' => $contact_users])
                        ->orWhere(function ($query) use ($user_id, $contact_users) {
                            $query->where('receiver_id', $user_id)->where('sender_id', $contact_users);
                        })
                        ->orderBy('messages.created_at', 'asc')->get();


                    $next_date = '2000-01-01 00:00:00';
                    foreach ($messages_row as $key => $value) {
                        if ($value->sender_id == $user_id) {
                            $item['type'] = 'send';
                            $item['profile_pic'] = 'empty';
                            $item['user_name'] = $user->name;
                        }

                        if ($value->receiver_id == $user_id) { //receiver
                            $item['type'] = 'receive';
                            $item['profile_pic'] = 'empty';
                            $item['user_name'] = 'empty';
                        }

                        $item['day_shows'] = '';
                        $item['day_shows_option'] = 0;
                        if (Carbon::parse($next_date)->timezone($timezone)->format('Y-m-d') === Carbon::parse($value->created_at)->timezone($timezone)->format('Y-m-d')) {
                        } else {
                            if ($next_date != null) {
                                $diffInHours = Carbon::parse($value->created_at)->diffInHours(Carbon::now());

                                if ($diffInHours < 24) {
                                    $item['day_shows'] = 'I dag';
                                    $item['day_shows_option'] = 1;
                                } elseif ($diffInHours > 24 && $diffInHours < 40) {
                                    $item['day_shows'] = 'I går';
                                    $item['day_shows_option'] = 1;
                                } else {
                                    $item['day_shows'] = Carbon::parse($value->created_at)->timezone($timezone);
                                    $item['day_shows_option'] = 2;
                                }
                            }
                        }

                        $item['message'] = $value->text;
                        $item['message_type'] = $value->is_attach;
                        $item['uuid'] = $value->uuid;

                        $item['message_time'] = Carbon::parse($value->created_at)->timezone($timezone)->format('H:i');


                        $next_date = $value->created_at;
                        $result_message_json[] = $item;
                    }
                }

                return response()->json(['result' => 'success', 'interview' => $interview, 'result_message_json' => $result_message_json, 'name' => $user->name, 'avatar' => $user->avatar], 200);
            } else {
                return response()->json(['result' => 'failed', 'interview' => null, 'result_message_json' => null, 'name' => $user->name, 'avatar' => $user->avatar], 200);
            }
        } else if ($user->type == 2) { //employer

            if (Interview::where('uuid', $stream_id)->where('employer_id', $user_id)->first()) {

                $interview = Interview::with(['seeker'])->where('uuid', $stream_id)->where('employer_id', $user_id)->first();
                $result_message_json = [];
                if ($interview) {
                    $contact_users = $interview->seeker->id;
                    Message::where(['sender_id' => $contact_users, 'receiver_id' => $user_id])
                        ->update(['is_seen' => 1]);
                    $messages_row = Message::where(['sender_id' => $user_id, 'receiver_id' => $contact_users])
                        ->orWhere(function ($query) use ($user_id, $contact_users) {
                            $query->where('receiver_id', $user_id)->where('sender_id', $contact_users);
                        })
                        ->orderBy('messages.created_at', 'asc')->get();




                    $next_date = '2000-01-01 00:00:00';
                    foreach ($messages_row as $key => $value) {
                        if ($value->sender_id == $user_id) {
                            $item['type'] = 'send';
                            $item['profile_pic'] = 'empty';
                            $item['user_name'] = $user->name;
                        }

                        if ($value->receiver_id == $user_id) { //receiver
                            $item['type'] = 'receive';
                            $item['profile_pic'] = 'empty';
                            $item['user_name'] = 'empty';
                        }

                        $item['day_shows'] = '';
                        $item['day_shows_option'] = 0;
                        if (Carbon::parse($next_date)->timezone($timezone)->format('Y-m-d') === Carbon::parse($value->created_at)->timezone($timezone)->format('Y-m-d')) {
                        } else {
                            if ($next_date != null) {
                                $diffInHours = Carbon::parse($value->created_at)->diffInHours(Carbon::now());


                                if ($diffInHours < 24) {
                                    $item['day_shows'] = 'I dag';
                                    $item['day_shows_option'] = 1;
                                } elseif ($diffInHours > 24 && $diffInHours < 40) {
                                    $item['day_shows'] = 'I går';
                                    $item['day_shows_option'] = 1;
                                } else {
                                    $item['day_shows'] = Carbon::parse($value->created_at)->timezone($timezone);
                                    $item['day_shows_option'] = 2;
                                }
                            }
                        }

                        $item['message'] = $value->text;
                        $item['message_type'] = $value->is_attach;
                        $item['uuid'] = $value->uuid;

                        $item['message_time'] = Carbon::parse($value->created_at)->timezone($timezone)->format('H:i');


                        $next_date = $value->created_at;
                        $result_message_json[] = $item;
                    }
                }

                return response()->json(['result' => 'success', 'interview' => $interview, 'result_message_json' => $result_message_json, 'name' => $user->name, 'avatar' => $user->avatar], 200);
            } else {
                return response()->json(['result' => 'failed', 'interview' => null, 'result_message_json' => null, 'name' => $user->name, 'avatar' => $user->avatar], 200);
            }
        } else {
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
        } catch (phpmailerException $e) {
            dd($e);
        } catch (Exception $e) {
            dd($e);
        }
    }
    public function send_email_from_eser($to_email, $subject, $message)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = 'utf-8';
            $mail->SMTPAuth = true;
            $mail->Host = 'smtpout.secureserver.net';
            $mail->Port = 465;
            $mail->SMTPSecure = 'ssl';
            $mail->Username = 'info@jobbyen.dk';
            $mail->Password = '6Buq9eRCw4L7qOg0BEj2';


            $mail->SetFrom('info@jobbyen.dk', 'Eser Arslan');
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
        } catch (phpmailerException $e) {
            dd($e);
        } catch (Exception $e) {
            dd($e);
        }
    }
    public function send_email_from_tina($to_email, $subject, $message)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->CharSet = 'utf-8';
            $mail->SMTPAuth = true;
            $mail->Host = 'smtpout.secureserver.net';
            $mail->Port = 465;
            $mail->SMTPSecure = 'ssl';
            $mail->Username = 'info@jobbyen.dk';
            $mail->Password = '6Buq9eRCw4L7qOg0BEj2';


            $mail->SetFrom('tina@jobbyen.dk', 'Tina Andersen');
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
        } catch (phpmailerException $e) {
            dd($e);
        } catch (Exception $e) {
            dd($e);
        }
    }
    /**
     * EndPoint api/create_new_schedule_with_userid
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create new interview schedule with given user id
     * @param
     * schedule : json object with schedule details - schedule start Date, start time, end time, interview title, interview description
     * @return json with status as success or failed with created interview id
     */
    public function create_new_schedule_with_userid(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $schedule = $request->post('schedule');


        $title = $schedule['title'];
        $description = $schedule['description'];
        $startDate = $schedule['startDate'];
        $start_time = $schedule['start_time'];
        $end_time = $schedule['end_time'];
        $selected_user_id = $schedule['user_id'];



        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
        $startDate = str_replace($find_arr, $replace_arr, $startDate);

        $interview_time = date('Y-m-d', strtotime($startDate));
        $interview_end_time = $interview_time . ' ' . $end_time . ':00';
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
        $new_interview->seeker_id = $selected_user_id;
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
        $new_interview->app_id = 0;
        if ($new_interview->save()) {
            return response()->json(['result' => 'success', 'new_interview_id' => $new_interview->id], 200);
        } else {
            return response()->json(['result' => 'failed', 'new_interview_id' => 0], 200);
        }
    }
    /**
     * EndPoint api/delete_schedule
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Delete schedule from given interview id
     * @param
     * interview_id : the id of interview which will be removed
     * @return json with status as success
     */
    public function delete_schedule(Request $request)
    {
        $interview_id = $request->post('interview_id');
        Interview::where('id', $interview_id)->delete();
        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/create_new_schedule
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create new interview schedule with given user on new schedule dialog on calendar page.
     * @param
     * schedule : json object with schedule details - schedule start Date, start time, end time, interview title, interview description
     * @return json with status as success or failed with created interview id
     */
    public function create_new_schedule(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $schedule = $request->post('schedule');

        $title = $schedule['title'];
        $description = $schedule['description'];
        $startDate = $schedule['startDate'];
        $start_time = $schedule['start_time'];
        $end_time = $schedule['end_time'];
        $selected_user = $schedule['user'];
        if (empty($selected_user['id'])) {
            return response()->json(['result' => 'failed', 'new_interview_id' => 0], 200);
        }

        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
        $startDate = str_replace($find_arr, $replace_arr, $startDate);

        $interview_time = date('Y-m-d', strtotime($startDate));

        if ($end_time == '24:00') {
            $interview_next_time = date('Y-m-d', strtotime("+1 day", strtotime($startDate)));
            $interview_end_time = $interview_next_time . ' ' . '00:00:00';
        } else {
            $interview_end_time = $interview_time . ' ' . $end_time . ':00';
        }
        $interview_time = $interview_time . ' ' . $start_time . ':00';

        Log::info('create new schedule with start time and end time');
        Log::info($interview_end_time);
        Log::info($interview_time);


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
        $new_interview->seeker_id = $selected_user['id'];
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
        $new_interview->app_id = 0;
        if ($new_interview->save()) {
            return response()->json(['result' => 'success', 'new_interview_id' => $new_interview->id], 200);
        } else {
            return response()->json(['result' => 'failed', 'new_interview_id' => 0], 200);
        }
    }
    /**
     * EndPoint api/create_new_schedule_for_company ------------ for tina
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create new interview schedule with given company email
     * @param
     * schedule : json object with schedule details - schedule start Date, start time, end time, interview title, interview description
     * @return json with status as success or failed with created interview id
     */
    public function create_new_schedule_for_company(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $schedule = $request->post('schedule');

        $title = $schedule['title'];
        $description = $schedule['description'];
        $startDate = $schedule['startDate'];
        $start_time = $schedule['start_time'];
        $end_time = $schedule['end_time'];
        $company_email = $schedule['company_email'];

        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
        $startDate = str_replace($find_arr, $replace_arr, $startDate);

        $interview_time = date('Y-m-d', strtotime($startDate));

        $company_interview_time = date('d.m.Y', strtotime($startDate));

        if ($end_time == '24:00') {
            $interview_next_time = date('Y-m-d', strtotime("+1 day", strtotime($startDate)));
            $interview_end_time = $interview_next_time . ' ' . '00:00:00';
        } else {
            $interview_end_time = $interview_time . ' ' . $end_time . ':00';
        }

        $interview_time = $interview_time . ' ' . $start_time . ':00';



        Log::info('create new schedule with start time and end time');
        Log::info($interview_end_time);
        Log::info($interview_time);


        $current_year = Carbon::parse($interview_time)->startOfWeek()->format('Y');
        $current_week = date('W', strtotime($interview_time));
        $day_idx = date('w', strtotime($interview_time));

        if ($day_idx == 0) {
            $day_idx = 7;
        }


        $new_interview = new CompanyInterview();
        $new_interview->employer_id = $user_id;
        $new_interview->company_email = $company_email;
        $new_interview->interview_time = $interview_time;
        $new_interview->interview_end_time = $interview_end_time;

        $new_interview->interview_title = $title;
        $new_interview->interview_desc = $description;

        $new_interview->year = $current_year;
        $new_interview->week = $current_week;
        $new_interview->day_idx = $day_idx;

        $meeting_link = GeneralHelper::generateRandomString(30);

        $new_interview->start_hour = explode(":", $start_time)[0];
        $new_interview->start_minute = explode(":", $start_time)[1];
        $new_interview->end_hour = explode(":", $end_time)[0];
        $new_interview->end_minute = explode(":", $end_time)[1];
        $new_interview->meeting_link = $meeting_link;
        $new_interview->ready = 2;

        $display_date = $company_interview_time;

        if($user_id == 1){
            $subject = 'Du har et møde med Eser Arslan';
            $data['display_date'] = $display_date;
            $data['start_time'] = $start_time;
            $data['hash'] = $meeting_link;

            $messageBody = view('email.company_interview_with_eser', ['data' => $data]);
            $result = $this->send_email_from_eser($company_email, $subject, $messageBody);

        } else {
            $subject = 'Du har et møde med Tina Andersen';
            $data['display_date'] = $display_date;
            $data['start_time'] = $start_time;
            $data['hash'] = $meeting_link;

            $messageBody = view('email.company_interview_with_tina', ['data' => $data]);
            $result = $this->send_email_from_tina($company_email, $subject, $messageBody);

        }





        if ($new_interview->save()) {
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint api/acceptInterview
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Update interview as accept status on candidate side with given uuid and make message with accepted status and send email to employer
     * @param
     * uuid : interview uuid
     * @return json with status as success or failed
     */
    public function accept(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $uuid = $request->post('uuid');

        $selected_interview = Interview::where('uuid', $uuid)->first();
        if (!$selected_interview) {
            return response()->json(['result' => 'failed'], 200);
        }

        if (UserJobsApply::where('id', $selected_interview->app_id)->where('user_id', $user_id)->first()) {
            $v_status = UserJobsApply::where('id', $selected_interview->app_id)->where('user_id', $user_id)->first()->v_status;
            if ($v_status == 1 || $v_status == 6) {
                UserJobsApply::where('id', $selected_interview->app_id)->where('user_id', $user_id)->update(['v_status' => 2]);
            }
        }

        $employer_name = '';
        $employer_email = '';

        if (User::where('id', $selected_interview->employer_id)->first()) {
            $employer_name = User::where('id', $selected_interview->employer_id)->first()->name;
            $employer_email = User::where('id', $selected_interview->employer_id)->first()->email;
        }

        $display_date = date('d.m.Y', strtotime($selected_interview->interview_time)) . " " . $selected_interview->start_hour . ':' . $selected_interview->start_minute . "  -  " . $selected_interview->end_hour . ':' . $selected_interview->end_minute;

        $subject = $user->name . ' har valgt denne dato';
        $data['seeker_name'] = $user->name;
        $data['employer_name'] = $employer_name;
        $data['display_date'] = $display_date;

        $messageBody = view('email.accept_interview_from_seeker_to_employer', ['data' => $data]);
        $result = $this->send_email($employer_email, $subject, $messageBody);


        if (Interview::where('uuid', $uuid)->first()) {
            Interview::where('uuid', $uuid)->update(['ready' => 1]);
        }

        Message::where('is_attach', 2)->delete();
        Message::where('is_attach', 90)->delete();

        $new_message_row = new Message();
        $new_message_row->sender_id = $selected_interview->employer_id;
        $new_message_row->receiver_id = $user_id;
        $new_message_row->text = $user->name . ' accepterede din videointerview anmodning <br> ' . date("d.m.Y H:i") . '#' . 'Du har accepteret videointerview anmodning fra ' . $employer_name . ' <br> ' . date("d.m.Y H:i");
        $new_message_row->is_seen = 1;
        $new_message_row->is_attach = 3;
        $new_message_row->uuid = md5(time() . rand());

        $response_message = 'Du har accepteret videointerview anmodning fra ' . $employer_name . ' <br> ' . date("d.m.Y H:i");
        $sending_reply_message = $user->name . ' accepterede din videointerview anmodning <br> ' . date("d.m.Y H:i");


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

        if ($new_message_row->save()) {
            return response()->json(['result' => 'success', 'response_message' => $response_message, 'sending_reply_message' => $sending_reply_message, 'app_id' => $selected_interview->app_id, 'interview_time' => $display_date, 'real_uuid' => 0, 'employer_id' => $selected_interview->employer_id, 'interviews' => $final_json], 200);
        } else {
            return response()->json(['result' => 'failed', 'response_message' => '', 'sending_reply_message' => '', 'app_id' => 0, 'interview_time' => null, 'real_uuid' => 0, 'employer_id' => $selected_interview->employer_id, 'interviews' => []], 200);
        }
    }
    /**
     * EndPoint api/createInterview
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Update interview as accept status on candidate side with given uuid and make message with accepted status and send email to employer
     * @param
     * selected_start_time : start time of interview
     * selected_end_time : end time of interview
     * selected_date : date of interview
     * interview_type : postpone type 90, as default 2
     * uuid : mixed uuid with chat uuid and interview id
     * @return json with status as success or failed
     */
    public function create(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $selected_start_time = $request->post('selected_start_time');
        $selected_end_time = $request->post('selected_end_time');

        $selected_date = $request->post('selected_date');
        $interview_type = $request->post('interview_type');


        $uuid = $request->post('uuid');

        $interview_id = 0;
        if (sizeof(explode('#', $uuid)) > 1) {
            $interview_id = explode('#', $uuid)[1];
        }

        $app_id = Interview::where('id', $interview_id)->first()->app_id;
        $employer_id = Interview::where('id', $interview_id)->first()->employer_id;

        $selected_date = Interview::where('id', $interview_id)->first()->interview_time;


        $start_hour = Interview::where('id', $interview_id)->first()->start_hour;
        $start_minute = Interview::where('id', $interview_id)->first()->start_minute;

        $end_hour = Interview::where('id', $interview_id)->first()->end_hour;
        $end_minute = Interview::where('id', $interview_id)->first()->end_minute;

        $find_arr = ['marts', 'maj', 'juni', 'juli', 'oktober', 'februar', 'januar'];
        $replace_arr = ['march', 'may', 'june', 'july', 'october', 'february', 'january'];
        $selected_date = str_replace($find_arr, $replace_arr, $selected_date);

        $display_date = date('d.m.Y', strtotime($selected_date)) . " " . $start_hour . ':' . $start_minute . "  -  " . $end_hour . ':' . $end_minute;


        if (UserJobsApply::where('id', $app_id)->where('user_id', $user_id)->first()) {
            $v_status = UserJobsApply::where('id', $app_id)->where('user_id', $user_id)->first()->v_status;
            if ($v_status == 1 || $v_status == 6) {
                UserJobsApply::where('id', $app_id)->where('user_id', $user_id)->update(['v_status' => 2]);
            }
        }


        $employer_name = '';
        $employer_email = '';

        if (User::where('id', $employer_id)->first()) {
            $employer_name = User::where('id', $employer_id)->first()->name;
            $employer_email = User::where('id', $employer_id)->first()->email;
        }


        $subject = $user->name . ' har valgt denne dato';
        $data['seeker_name'] = $user->name;
        $data['employer_name'] = $employer_name;
        $data['display_date'] = $display_date;

        $messageBody = view('email.accept_interview_from_seeker_to_employer', ['data' => $data]);
        $result = $this->send_email($employer_email, $subject, $messageBody);

        // $real_uuid = 0;
        // if ($uuid) {
        //     if (sizeof(explode('#', $uuid)) > 1) {
        //         $real_uuid = explode('#', $uuid)[0];
        //     }
        // }

        $current_year = Carbon::parse($selected_date)->startOfWeek()->format('Y');
        $current_week = date('W', strtotime($selected_date));
        $day_idx = date('w', strtotime($selected_date));

        if ($interview_type == 90) {
            // if (Interview::where('uuid', $real_uuid)->first()) {
            //     Interview::where('uuid', $real_uuid)->update([
            //         'interview_time' => $date,
            //         'year' => $current_year,
            //         'week' => $current_week,
            //         'day_idx' => $day_idx,
            //         'start_hour' => explode(":", $selected_start_time)[0],
            //         'start_minute' => explode(":", $selected_start_time)[1],
            //         'end_hour' => explode(":", $selected_end_time)[0],
            //         'end_minute' => explode(":", $selected_end_time)[1],
            //         'ready' => 0
            //     ]);
            // }
        } else {
            if (Interview::where('id', $interview_id)->first()) {
                Interview::where('id', $interview_id)->update(['ready' => 1]);
            }
        }



        Message::where('is_attach', 2)->delete();
        Message::where('is_attach', 90)->delete();

        $new_message_row = new Message();
        $new_message_row->sender_id = $employer_id;
        $new_message_row->receiver_id = $user_id;
        $new_message_row->text = $user->name . ' accepterede din videointerview anmodning <br> ' . date("d.m.Y H:i") . '#' . 'Du har accepteret videointerview anmodning fra ' . $employer_name . ' <br> ' . date("d.m.Y H:i");
        $new_message_row->is_seen = 1;
        $new_message_row->is_attach = 3;
        $new_message_row->uuid = md5(time() . rand());

        $response_message = 'Du har accepteret videointerview anmodning fra ' . $employer_name . ' <br> ' . date("d.m.Y H:i");
        $sending_reply_message = $user->name . ' accepterede din videointerview anmodning <br> ' . date("d.m.Y H:i");

        if ($new_message_row->save()) {
            return response()->json(['result' => 'success', 'response_message' => $response_message, 'sending_reply_message' => $sending_reply_message, 'app_id' => $app_id, 'interview_time' => $display_date, 'real_uuid' => 0], 200);
        } else {
            return response()->json(['result' => 'failed', 'response_message' => '', 'sending_reply_message' => '', 'app_id' => 0, 'interview_time' => null, 'real_uuid' => 0], 200);
        }
    }
} //End class
