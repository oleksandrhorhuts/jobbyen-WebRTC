<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\SeekerAgent;
use App\SeekerAgentResult;
use App\Cv;
use App\Notification;
use App\SeekerAgentSocket;

class CheckSeekerAgent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:CheckSeekerAgent';

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
        //
        $agents = SeekerAgent::with(['agent_result', 'agent_category', 'agent_education', 'agent_location'])->withCount(['agent_result'])->get();
        foreach ($agents as $k => $v) {
            $agent_result_count = $v->agent_result_count;
            $agent_id = $v->id;
            $agent_user_id = $v->user_id;

            $video_presentation_condition = null;
            if ($v->video_presentation) {
                $video_presentation_condition = 1;
            }
            $education_condition = null;
            $educationArr = [];
            if ($v->education_id) {
                $education_condition = 1;
                $educationArr[] = $v->education_id;
            }
            $category_condition = null;
            $categoryArr = [];
            if (sizeof($v->agent_category)) {
                $category_condition = 1;
                foreach ($v->agent_category as $key => $value) {
                    $categoryArr[] = $value->category_id;
                }
            }

            $language_condition = null;
            $languageArr = [];
            if (sizeof($v->agent_language)) {
                $language_condition = 1;
                foreach ($v->agent_language as $key => $value) {
                    $languageArr[] = $value->language_id;
                }
            }

            $locationArr = [];
            if (sizeof($v->agent_location)) {
                foreach ($v->agent_location as $key => $value) {
                    $locationArr[] = $value->location_id;
                }
            }

            $job_type_id = $v->job_type_id;
            $min_age = $v->min_age;
            $max_age = $v->max_age;


            $cvs = Cv::with(['cv_video', 'cv_city', 'cv_document', 'CvCategory', 'CvCategory.category', 'CvSkill', 'CvSkill.skill', 'CvWish', 'CvWish.job_title', 'cv_language', 'cv_language.language', 'CvEducation', 'CvEducation.degree', 'CvExperience'])
                ->when($video_presentation_condition, function ($query) {
                    $query->whereHas('cv_video', function ($query) {
                    });
                })
                ->when($education_condition, function ($query) use ($educationArr) {
                    $query->whereHas('CvEducation.degree', function ($query) use ($educationArr) {
                        $query->whereIn('id', $educationArr);
                    });
                })
                ->when($category_condition, function ($query) use ($categoryArr) {
                    $query->whereHas('CvCategory.category', function ($query) use ($categoryArr) {
                        $query->whereIn('id', $categoryArr);
                    });
                })
                ->when($language_condition, function ($query) use ($languageArr) {
                    $query->whereHas('cv_language.language', function ($query) use ($languageArr) {
                        $query->whereIn('id', $languageArr);
                    });
                })
                ->where(function ($query) use ($job_type_id, $min_age, $max_age, $locationArr) {
                    if ($job_type_id) {
                        $query->where('job_type', $job_type_id);
                    }
                    if (sizeof($locationArr)) {
                        $query->whereIn('location_id', $locationArr);
                    }
                    $query->where('age', '>=', $min_age);
                    $query->where('age', '<=', $max_age);
                })
                ->get();

            
            if ($agent_result_count != sizeof($cvs) && sizeof($cvs) >=$agent_result_count) {
                foreach ($cvs as $cv) {
                    $new_notification = new Notification();
                    $new_notification->user_id = $agent_user_id;
                    $new_notification->name = $cv->id;
                    $new_notification->sender = $agent_id;
                    $new_notification->type = 16;
                    $new_notification->save();
                }
                if (SeekerAgentSocket::where('agent_user_id', $agent_user_id)->first()) {
                    SeekerAgentSocket::where('agent_user_id', $agent_user_id)->update(['active' => 1]);
                } else {
                    $new_socket = new SeekerAgentSocket();
                    $new_socket->agent_user_id = $agent_user_id;
                    $new_socket->active = 1;
                    $new_socket->save();
                }
            }
            

            foreach ($cvs as $_k => $_v) {
                if (!SeekerAgentResult::where('agent_id', $agent_id)->where('matched_user_id', $_v->user_id)->first()) {
                    $new_seeker_agent_result = new SeekerAgentResult();
                    $new_seeker_agent_result->agent_id = $agent_id;
                    $new_seeker_agent_result->matched_user_id = $_v->user_id;
                    $new_seeker_agent_result->save();
                }
            }
        }
    }
}
