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

use App\Contact;
use App\Message;


class MessageController extends Controller
{
    /**
     * EndPoint api/get_recent_history_contact
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get received recent message
     * @return json array with fetched message
     */
    public function get_recent_history_contact(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        if (!$token) {
            return response()->json([], 200);
        }

        $user_id = $user->id;
        $user_type = $user->type;


        $messages_row = Message::with(['sender'])->where('receiver_id', $user_id)->get();

        $results = collect($messages_row)->groupBy('sender_id');


        $idx = 0;
        $result_json = [];
        foreach ($results as $k => $v) {
            $v1 = collect($v)->sortBy(function ($item) {
                return $item['created_at'];
            });
            $row = $v1[sizeof($v1) - 1];
            $item['user_id'] = $row->sender->id;
            $item['name'] = $row->sender->name;
            $item['avatar'] = $row->sender->avatar;
            $item['created_at'] = $row->created_at;
            $item['message_type'] = $row->is_attach;
            $item['message'] = $row->text;
            $item['unread'] = $row->is_seen;
            $result_json[] = $item;
            $idx++;
        }


        $result_json = collect($result_json)->sortByDesc(function ($contact) {
            return $contact['created_at'];
        });


        $final_json = [];
        foreach ($result_json as $k => $val) {
            $final_json[] = $val;
        }

        return response()->json($final_json, 200);
    }
    /**
     * EndPoint api/getChatContact
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Get all contacts will be added to message page.
     * @return json array with fetched contacts
     */
    public function getChatContact(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_type = $user->type;

        if ($user_type == 1) {
            $contacts = Contact::with(['user'])->where('contact_self_id', $user_id)->get();
        } else if ($user_type == 2) {
            $contacts = Contact::with(['user', 'user.cv', 'user.cv.CvWish', 'user.cv.CvWish.job_title'])->where('contact_self_id', $user_id)->get();
        } else {
        }


        $result_json = [];
        foreach ($contacts as $v) {
            $item['user_id'] = $v->user->id;
            $item['name'] = $v->user->name;
            $item['active'] = false;

            $v_user_id = $v->user->id;

            $messages_row = Message::where(['sender_id' => $user_id, 'receiver_id' => $v_user_id])
                ->orWhere(function ($query) use ($user_id, $v_user_id) {
                    $query->where('receiver_id', $user_id)->where('sender_id', $v_user_id);
                })
                ->latest()->first();

            $item['last_message'] = '';
            $item['last_message_time'] = '';

            if ($messages_row) {
                if ($messages_row->is_attach == 0) {
                    $item['last_message'] = $messages_row->text;
                } else if ($messages_row->is_attach == 10) {
                    $item['last_message'] = 'Sendt fil';
                } else if ($messages_row->is_attach == 20) {
                    $item['last_message'] = 'Billede';
                } else if ($messages_row->is_attach == 30) {
                    $item['last_message'] = 'Dokument';
                } else if ($messages_row->is_attach == 40) {
                    $item['last_message'] = 'PDF';
                } else {
                    $item['last_message'] = 'Interviewanmodning';
                }

                $item['last_message_time'] = date('d/m/Y H:i', strtotime($messages_row->created_at));

                $item['is_seen'] = $messages_row->is_seen;
            }

            $item['unread_cnt'] = Message::where(['sender_id' => $v_user_id, 'receiver_id' => $user_id])->where('is_seen', 0)->count();
            $item['profile_pic'] = 'empty';

            if ($user_type == 1) {
                $item['detail'] = '';
            } else if ($user_type == 2) {
                $detail = '';
                if ($v->user->cv) {
                    $item['profile_pic'] = $v->user->cv->profile_pic;
                    if ($v->user->cv->CvWish) {
                        foreach ($v->user->cv->CvWish as $_val) {
                            $detail .= $_val->job_title->name;
                            $detail .= ', ';
                        }
                    }
                }
                $detail = substr($detail, 0, strlen($detail) - 2);
                $item['detail'] = $detail;
            }
            $result_json[] = $item;
        }

        $results = collect($result_json)->sortByDesc(function ($contact) {
            return $contact['last_message_time'];
        });

        $ordered_results = [];
        foreach ($results as $v) {
            $ordered_results[] = $v;
        }

        return response()->json($ordered_results, 200);
    }
    /**
     * EndPoint api/confirmUnread
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * update unread message as read
     * @return json with status
     */
    public function confirmUnread(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $contact_users = $request->post('contact_users');

        Message::where(['sender_id' => $contact_users, 'receiver_id' => $user_id])
            ->update(['is_seen' => 1]);
        return response()->json(['result' => 'success'], 200);
    }
    /**
     * EndPoint api/delete_chat
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * delete chat history with selected user 
     * @param
     * selected_user_id : id of user who is going to remove chat history
     * @return json with status
     */
    public function delete_chat(Request $request)
    {
        $selected_user_id = $request->post('selected_user_id');
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_type = $user->type;

        if ($user_type == 1) {
            Message::where('sender_id', $selected_user_id)->where('receiver_id', $user_id)->update(['deleted_seeker' => 1]);
            Message::where('sender_id', $user_id)->where('receiver_id', $selected_user_id)->update(['deleted_seeker' => 1]);
            return response()->json(['result' => 'success'], 200);
        } else if ($user_type == 2) {
            Message::where('sender_id', $selected_user_id)->where('receiver_id', $user_id)->update(['deleted_employer' => 1]);
            Message::where('sender_id', $user_id)->where('receiver_id', $selected_user_id)->update(['deleted_employer' => 1]);
            return response()->json(['result' => 'success'], 200);
        } else {
        }
    }
    /**
     * EndPoint api/getMessageHistoryWithContact
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Get Chat history with given selected user on chat page
     * @param
     * contact_users : id of user
     * @return json array with message details with unread message count
     */
    public function getMessageHistoryWithContact(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;


        $contact_users = $request->post('contact_users');

        Message::where(['sender_id' => $contact_users, 'receiver_id' => $user_id])
            ->update(['is_seen' => 1]);


        $unread_count = Message::where('receiver_id', $user_id)->where('is_seen', 0)->count();


        $timezone = $user->timezone;

        if ($user->type == 1) {
            $messages_row = Message::where(['sender_id' => $user_id, 'receiver_id' => $contact_users, 'deleted_seeker' => 0])
                ->orWhere(function ($query) use ($user_id, $contact_users) {
                    $query->where('receiver_id', $user_id)->where('sender_id', $contact_users)->where('deleted_seeker', 0);
                })
                ->orderBy('messages.created_at', 'asc')->get();
        } else if ($user->type == 2) {
            $messages_row = Message::where(['sender_id' => $user_id, 'receiver_id' => $contact_users, 'deleted_employer' => 0])
                ->orWhere(function ($query) use ($user_id, $contact_users) {
                    $query->where('receiver_id', $user_id)->where('sender_id', $contact_users)->where('deleted_employer', 0);
                })
                ->orderBy('messages.created_at', 'asc')->get();
        } else {
        }



        $result_message_json = [];

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
                $item['user_name'] = User::where('id', $user_id)->first()->name;
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
                        // $item['day_shows'] = Carbon::parse($value->created_at)->timezone($timezone)->format('l, M d, Y');
                    }
                }
            }

            $item['message'] = $value->text;
            $item['message_type'] = $value->is_attach;
            $item['uuid'] = $value->uuid;

            if ($value->is_attach == 70) {
                $item['receiver_name'] = User::where('id', $user_id)->first()->name;
                $item['sender_name'] = User::where('id', $value->sender_id)->first()->name;
            } else if ($value->is_attach == 80) {
                $item['receiver_name'] = User::where('id', $value->receiver_id)->first()->name;
                $item['sender_name'] = User::where('id', $value->sender_id)->first()->name;
            } else {
                $item['receiver_name'] = User::where('id', $value->receiver_id)->first()->name;
                $item['sender_name'] = User::where('id', $value->sender_id)->first()->name;
            }

            $item['message_time'] = Carbon::parse($value->created_at)->timezone($timezone)->format('H:i');


            $next_date = $value->created_at;
            $result_message_json[] = $item;
        }
        return response()->json(['result_message_json' => $result_message_json, 'unread_count' => $unread_count], 200);
    }
} //End class
