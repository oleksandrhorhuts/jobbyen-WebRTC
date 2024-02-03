<?php

namespace App\Http\Controllers;

use App\ApplyAttachFile;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\User;
use App\Company;
use App\Invoice;
use App\Blog;
use App\BlogFiles;
use App\CronSchedule;
use App\Cv;
use App\CvCategory;
use App\CvDocument;
use App\CvEducation;
use App\CvExperience;
use App\CvLanguage;
use App\CvVideo;
use App\CvWish;
use App\CvSkill;
use App\Contact;
use App\JobAgent;
use App\JobAnswer;
use App\JobAgentCategory;
use App\JobAgentLocation;
use App\Interview;
use App\UserJobsApply;
use App\UserJobSaved;
use App\Notification;
use Artisan;
use App\Helpers\GeneralHelper;
use SPDF;
use App\Job;
use App\Message;
use App\Category;
use App\CategorySubCategory;
use App\JobReport;
use App\Skill;

class AdminController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('admin.index');
    }
    /**
     * EndPoint admin/main_login
     * HTTP SUBMIT : POST
     * Login admin with given email and password
     *
     * @param
     * email : admin email
     * password : admin password
     * @return json with JWTTOKEN token with expires
     */
    public function main_login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            // if the credentials are wrong we send an unauthorized error in json format
            return response()->json(['error' => 'Unauthorized'], 401);
        }


        if ($user = User::where('email', $credentials['email'])->where('type', 3)->first()) {
            return response()->json([
                'token' => $token,
                'type' => 'bearer', // you can ommit this
                'expires' => auth('api')->factory()->getTTL() * 7200, // time to expiration
            ]);
        } else {
            return response()->json([
                'token' => '',
                'type' => 'bearer', // you can ommit this
                'expires' => auth('api')->factory()->getTTL() * 7200, // time to expiration
            ]);
        }
    }
    /**
     * EndPoint admin-api/refresh_job_seeker
     * HTTP SUBMIT : POST
     * disable standby and make candidate status as active as 1
     *
     * @param
     * seeker_id : the id of seeker
     * @return json with status as success, and updated seekers list
     */
    public function refresh_job_seeker(Request $request)
    {
        $seeker_id = $request->post('seeker_id');
        User::where('id', $seeker_id)->update(['standby' => 0]);
        if (Cv::where('user_id', $seeker_id)->first()) {
            Cv::where('user_id', $seeker_id)->update(['active' => 1]);
        }
        $users = User::with(['member'])->where('type', 1)->orderBy('created_at', 'desc')->get();
        return response()->json(['result' => 'success', 'seekers' => $users], 200);
    }
    /**
     * EndPoint admin-api/standby_job_seeker
     * HTTP SUBMIT : POST
     * enable standby and make candidate status as active as 0
     *
     * @param
     * seeker_id : the id of seeker who is going to standy
     * @return json with status as success, and updated seekers list
     */
    public function standby_job_seeker(Request $request)
    {
        $seeker_id = $request->post('seeker_id');
        User::where('id', $seeker_id)->update(['standby' => 1]);
        if (Cv::where('user_id', $seeker_id)->first()) {
            Cv::where('user_id', $seeker_id)->update(['active' => 0]);
        }
        $users = User::with(['member'])->where('type', 1)->orderBy('created_at', 'desc')->get();
        return response()->json(['result' => 'success', 'seekers' => $users], 200);
    }
    /**
     * EndPoint admin-api/delete_job_seeker
     * HTTP SUBMIT : POST
     * delete job seeker and relevant its registered datas.
     *
     * @param
     * seeker_id : the id of seeker who is going to delete
     * @return json with status as success, and updated seekers list
     */
    public function delete_job_seeker(Request $request)
    {
        $seeker_id = $request->post('seeker_id');


        if (Cv::where('user_id', $seeker_id)->first()) {
            $cv_id = Cv::where('user_id', $seeker_id)->first()->id;

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

            Cv::where('user_id', $seeker_id)->delete();
        }

        if (CvVideo::where('user_id', $seeker_id)->count()) {

            $videos = CvVideo::where('user_id', $seeker_id)->get();
            foreach ($videos as $v) {
                if (file_exists(public_path() . '/images/video_cv/' . $v->name)) {
                    @unlink(public_path() . '/images/video_cv/' . $v->name);
                } else {
                }
            }
            CvVideo::where('user_id', $seeker_id)->delete();
            return response()->json(['result' => 'success'], 200);
        } else {
        }


        if (JobAgent::where('user_id', $seeker_id)->count()) {
            $job_agents = JobAgent::where('user_id', $seeker_id)->get();
            foreach ($job_agents as $v) {
                JobAgentCategory::where('agent_id', $v->id)->delete();
                JobAgentLocation::where('agent_id', $v->id)->delete();
            }
        }

        if (Interview::where('seeker_id', $seeker_id)->count()) {
            Interview::where('seeker_id', $seeker_id)->delete();
        }

        UserJobSaved::where('user_id', $seeker_id)->delete();

        if (UserJobsApply::where('user_id', $seeker_id)->count()) {
            $applies = UserJobsApply::where('user_id', $seeker_id)->get();
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

        Contact::where('contact_self_id', $seeker_id)->delete();
        Contact::where('contact_users', $seeker_id)->delete();

        Message::where('sender_id', $seeker_id)->delete();
        Message::where('receiver_id', $seeker_id)->delete();

        Notification::where('user_id', $seeker_id)->delete();
        Notification::where('name', $seeker_id)->where('type', 17)->delete();


        User::where('id', $seeker_id)->delete();

        $users = User::with(['member'])->where('type', 1)->orderBy('created_at', 'desc')->get();
        return response()->json(['result' => 'success', 'seekers' => $users], 200);
    }
    /**
     * EndPoint admin-api/get_job_seekers
     * HTTP SUBMIT : GET
     * get all job seekers
     * @return json array with fetched users
     */
    public function get_job_seekers(Request $request)
    {
        $users = User::with(['member'])->where('type', 1)->orderBy('created_at', 'desc')->get();
        return response()->json($users, 200);
    }
    /**
     * EndPoint admin-api/get_companies
     * HTTP SUBMIT : GET
     * get all companies
     * @return json array with fetched companies
     */
    public function get_companies(Request $request)
    {
        $companies = Company::with(['user', 'user.company_job', 'user.video_call', 'user.message', 'user.member', 'company_location'])->where('user_id', '>', 0)->orderBy('created_at', 'desc')->get();
        return response()->json($companies, 200);
    }
    /**
     * EndPoint admin-api/get_employer_transactions
     * HTTP SUBMIT : GET
     * get all employer invoices which paid success
     * @return json array with fetched invoices
     */
    public function get_employer_transactions(Request $request)
    {
        $invoices = Invoice::with(['user'])
            ->whereHas('user', function ($query) {
                $query->where('type', 2);
            })
            ->where('paid_status', 1)
            ->orderBy('created_at', 'desc')
            ->get();
        $result_json = [];

        foreach ($invoices as $k => $v) {
            $item['id'] = $v->id;
            $item['name'] = $v->user->name;
            $item['description'] = $v->description;
            $item['sub_total'] = $v->sub_total;
            $item['vat'] = $v->vat;
            $item['total'] = $v->total;
            $item['paid_status'] = $v->paid_status;
            $item['created_at'] = $v->created_at;
            $result_json[] = $item;
        }
        return response()->json($result_json, 200);
    }
    /**
     * EndPoint admin-api/get_jobseeker_transactions
     * HTTP SUBMIT : GET
     * get all jobseeker invoices which paid success
     * @return json array with fetched invoices
     */
    public function get_jobseeker_transactions(Request $request)
    {
        $invoices = Invoice::with(['user'])
            ->whereHas('user', function ($query) {
                $query->where('type', 1);
            })->orderBy('created_at', 'desc')->get();
        $result_json = [];

        foreach ($invoices as $k => $v) {
            $item['id'] = $v->id;
            $item['name'] = $v->user->name;
            $item['description'] = $v->description;
            $item['sub_total'] = $v->sub_total;
            $item['vat'] = $v->vat;
            $item['total'] = $v->total;
            $item['card_last'] = 'xxxx-xxxx-xxxx-' . $v->card_last;
            $item['client_ip'] = $v->client_ip;
            $item['paid_status'] = $v->paid_status;
            $item['created_at'] = $v->created_at;
            $result_json[] = $item;
        }
        return response()->json($result_json, 200);
    }
    /**
     * EndPoint admin-api/get_jobseeker_detail
     * HTTP SUBMIT : GET
     * get job seeker details with given user id
     * @param
     * user_id : the id of user
     * @return json with fetched user detail
     */
    public function get_jobseeker_detail(Request $request, $user_id)
    {
        $user = User::with(['cv', 'cv.doc', 'member', 'location'])->where('id', $user_id)->first();
        return response()->json($user, 200);
    }
    /**
     * EndPoint admin-api/get_company_detail
     * HTTP SUBMIT : GET
     * get company details with given company id
     * @param
     * company_id : the id of company
     * @return json with fetched company details
     */
    public function get_company_detail(Request $request, $company_id)
    {
        $company = Company::with(['user', 'company_location'])->where('company_id', $company_id)->first();
        return response()->json($company, 200);
    }
    /**
     * EndPoint admin-api/get_blog
     * HTTP SUBMIT : GET
     * get all blogs
     * @return json array with fetech blogs
     */
    public function get_blog(Request $request)
    {
        $blogs = Blog::orderBy('created_at', 'desc')->get();
        return response()->json($blogs, 200);
    }
    /**
     * EndPoint admin-api/get_blog_detail
     * HTTP SUBMIT : GET
     * get blog details with given blog id
     * @param
     * blog_id : the id of blog
     * @return json with blog detail
     */
    public function get_blog_detail(Request $request, $blog_id)
    {
        $blog_detail = Blog::with(['detail'])->where('id', $blog_id)->first();
        return response()->json($blog_detail, 200);
    }
    /**
     * EndPoint admin-api/delete_blog
     * HTTP SUBMIT : POST
     * delete blog with given blog id
     * @param
     * blog_id : the id of blog
     * @return json with success or failed.
     */
    public function delete_blog(Request $request)
    {
        $blog_id = $request->post('blog_id');
        if (Blog::where('id', $blog_id)->first()) {
            Blog::where('id', $blog_id)->delete();

            $files = BlogFiles::where('blog_id', $blog_id)->get();
            foreach ($files as $k => $v) {
                if (file_exists(public_path() . '/images/blogs/' . $v->blog_file_name . '.png')) {
                    @unlink(public_path() . '/images/blogs/' . $v->blog_file_name . '.png');
                } else {
                }
            }

            BlogFiles::where('blog_id', $blog_id)->delete();

            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint admin-api/delete_blog_file
     * HTTP SUBMIT : POST
     * delete blog file with given blog id and blog name
     * @param
     * blog_id : the id of blog
     * blog_name : the name of blog
     * @return json with success or failed.
     */
    public function delete_blog_file(Request $request)
    {
        $blog_name = $request->post('blog_name');
        $blog_id = $request->post('blog_id');

        if (BlogFiles::where('blog_id', $blog_id)->where('blog_file_name', $blog_name)->first()) {
            BlogFiles::where('blog_id', $blog_id)->where('blog_file_name', $blog_name)->delete();

            if (file_exists(public_path() . '/images/blogs/' . $blog_name . '.png')) {
                @unlink(public_path() . '/images/blogs/' . $blog_name . '.png');
            } else {
            }
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint admin-api/update_blog
     * HTTP SUBMIT : POST
     * Update blog with given blog details and given blog id
     * @param
     * blog_id : the id of blog
     * blog : json object with blog
     * @return json with success or failed.
     */
    public function update_blog(Request $request)
    {

        $blog_id = $request->post('blog_id');
        $blog = $request->post('blog');
        $blog = json_decode($blog);

        $_name = $blog->name;
        $_seo_name = str_slug($blog->name);
        $_description = $blog->description;

        $file_cnt = $request->post('file_cnt');

        $blog_names = [];

        for ($idx = 0; $idx < $file_cnt; $idx++) {
            if (!empty($_FILES['upload_photo_file_' . $idx]['tmp_name'])) {
                if (is_uploaded_file($_FILES['upload_photo_file_' . $idx]['tmp_name'])) {
                    $blog_name = md5(time() . rand()) . '_blog_' . $idx;
                    $sTempFileName = public_path() . '/images/blogs/' . $blog_name . '.png';
                    if (move_uploaded_file($_FILES['upload_photo_file_' . $idx]['tmp_name'], $sTempFileName)) {
                        @chmod($sTempFileName, 0755);
                        $blog_names[] = $blog_name;
                    } else {
                    }
                }
            }
        }

        if (Blog::where('id', $blog_id)->first()) {
            Blog::where('id', $blog_id)->update(['name' => $_name, 'seo' => $_seo_name, 'description' => $_description]);

            foreach ($blog_names as $k => $v) {
                $new_blog_file = new BlogFiles();
                $new_blog_file->blog_id = $blog_id;
                $new_blog_file->blog_file_name = $v;
                $new_blog_file->save();
            }
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint admin-api/create_blog
     * HTTP SUBMIT : POST
     * Create blog with given blog details
     * @param
     * blog : json object with blog
     * @return json with success or failed.
     */
    public function create_blog(Request $request)
    {
        $blog = $request->post('blog');
        $blog = json_decode($blog);

        $file_cnt = $request->post('file_cnt');

        $blog_names = [];

        for ($idx = 0; $idx < $file_cnt; $idx++) {
            if (!empty($_FILES['upload_photo_file_' . $idx]['tmp_name'])) {
                if (is_uploaded_file($_FILES['upload_photo_file_' . $idx]['tmp_name'])) {
                    $blog_name = md5(time() . rand()) . '_blog_' . $idx;
                    $sTempFileName = public_path() . '/images/blogs/' . $blog_name . '.png';
                    if (move_uploaded_file($_FILES['upload_photo_file_' . $idx]['tmp_name'], $sTempFileName)) {
                        @chmod($sTempFileName, 0755);
                        $blog_names[] = $blog_name;
                    } else {
                    }
                }
            }
        }

        $new_blog = new Blog();
        $new_blog->seo = str_slug($blog->name);
        $new_blog->name = $blog->name;
        $new_blog->description = $blog->description;
        if ($new_blog->save()) {
            $new_inserted_id = $new_blog->id;
            foreach ($blog_names as $k => $v) {
                $new_blog_file = new BlogFiles();
                $new_blog_file->blog_id = $new_inserted_id;
                $new_blog_file->blog_file_name = $v;
                $new_blog_file->save();
            }
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
    /**
     * EndPoint admin-api/get_cron_api
     * HTTP SUBMIT : GET
     * Get all crons
     * @return json array with crons
     */
    public function get_cron_api(Request $request)
    {
        $crons = CronSchedule::withCount(['job', 'today_job'])->where('active', 1)->orderBy('updated_at', 'desc')->get();
        return response()->json($crons, 200);
    }
    /**
     * EndPoint admin-api/update_initial_pagination
     * HTTP SUBMIT : POST
     * Update pagination 1 from given cron id
     * @param
     * id : the cron id
     * @return json array with crons
     */
    public function update_initial_pagination(Request $request)
    {
        $id = $request->post('id');
        CronSchedule::where('id', $id)->update(['pagination' => 1]);
        $crons = CronSchedule::withCount(['job', 'today_job'])->where('active', 1)->orderBy('updated_at', 'desc')->get();
        return response()->json(['result' => 'success', 'crons' => $crons], 200);
    }
    /**
     * EndPoint admin-api/run_pagination
     * HTTP SUBMIT : POST
     * Run pagination with given cron id
     * @param
     * id : the cron id
     * @return json array with updated crons
     */
    public function run_pagination(Request $request)
    {
        $id = $request->post('id');
        $cron = CronSchedule::where('id', $id)->first();
        if ($cron) {
            $command = $cron->command;

            if ($command) {
                Artisan::call($command);
            }
        }

        $crons = CronSchedule::withCount(['job', 'today_job'])->where('active', 1)->orderBy('updated_at', 'desc')->get();
        return response()->json(['result' => 'success', 'crons' => $crons], 200);
    }
    /**
     * EndPoint admin-api/refresh_initial_pagination
     * HTTP SUBMIT : POST
     * Initialize all pagination number as 1
     * @return json array with updated crons
     */
    public function refresh_initial_pagination(Request $request)
    {
        CronSchedule::where('pagination', '>', 1)->update(['pagination' => 1]);
        $crons = CronSchedule::withCount(['job', 'today_job'])->where('active', 1)->orderBy('updated_at', 'desc')->get();
        return response()->json(['result' => 'success', 'crons' => $crons], 200);
    }
    /**
     * EndPoint admin-api/make_permission
     * HTTP SUBMIT : POST
     * enable employer video free permission
     * @param
     * user_id : employer id
     * @return json array with updated companies list
     */
    public function make_permission(Request $request)
    {
        $user_id = $request->post('user_id');
        User::where('id', $user_id)->update(['user_level' => 1]);
        $companies = Company::with(['user', 'user.company_job', 'user.video_call', 'user.message', 'user.member', 'company_location'])->where('user_id', '>', 0)->orderBy('created_at', 'desc')->get();
        return response()->json($companies, 200);
    }
    /**
     * EndPoint admin-api/close_permission
     * HTTP SUBMIT : POST
     * disable employer video free permission
     * @param
     * user_id : employer id
     * @return json array with updated companies list
     */
    public function close_permission(Request $request)
    {
        $user_id = $request->post('user_id');
        User::where('id', $user_id)->update(['user_level' => 0]);
        $companies = Company::with(['user', 'user.company_job', 'user.video_call', 'user.message', 'user.member', 'company_location'])->where('user_id', '>', 0)->orderBy('created_at', 'desc')->get();
        return response()->json($companies, 200);
    }
    /**
     * EndPoint admin-api/get_transaction_detail
     * HTTP SUBMIT : GET
     * Get invoice detail with given transaction id
     * @param
     * transaction_id : transaction id
     * @return json with invoice detail
     */
    public function get_transaction_detail(Request $request, $transaction_id)
    {

        if (Invoice::where('id', $transaction_id)->count()) {
            $invoice = Invoice::where('id', $transaction_id)->first();

            if (User::where('id', $invoice->user_id)->first()) {
                $user_email = User::where('id', $invoice->user_id)->first()->email;
                $user_name = User::where('id', $invoice->user_id)->first()->name;
            }
            $company = null;
            if (Company::where('user_id', $invoice->user_id)->first()) {
                $company = Company::with(['company_location'])->where('user_id', $invoice->user_id)->first();
            }

            return response()->json(['result' => 'success', 'invoice' => $invoice, 'user_email' => $user_email, 'user_name' => $user_name, 'company' => $company], 200);
        } else {
            return response()->json(['result' => 'failed', 'invoice' => null, 'user_email' => null, 'user_name' => null, 'company' => null], 200);
        }
    }

    /**
     * EndPoint admin-api/make_invoice_pdf
     * HTTP SUBMIT : POST
     * Create Invoice Pdf with given invoice id and and generate invoice and return generated pdf url
     * @param
     * invoice_id : invoice id
     * @return json with generated pdf url
     */
    public function make_invoice_pdf(Request $request)
    {
        $invoice_id = $request->post('invoice_id');
        $type = $request->post('type');

        if ($type == 0) { //employer
            if (Invoice::where('id', $invoice_id)->first()) {
                $invoice = Invoice::where('id', $invoice_id)->first();

                // if (User::where('id', $invoice->user_id)->first()) {
                //     $user_email = User::where('id', $invoice->user_id)->first()->email;
                //     $user_name = User::where('id', $invoice->user_id)->first()->name;
                // }
                $company = null;
                if (Company::where('user_id', $invoice->user_id)->first()) {
                    $company = Company::with(['company_location'])->where('user_id', $invoice->user_id)->first();
                }

                $data['invoice'] = $invoice;
                $data['company'] = $company;


                $item['text'] = GeneralHelper::get_employer_package_name($invoice['plan']);
                $item['price'] = number_format(GeneralHelper::get_employer_package_price($invoice['plan']), 2, ',', '.') . ' kr';

                $data['invoice_lines'][] = $item;

                if ($invoice->extra_1) {
                    $item['text'] = 'LinkedIN';
                    $item['price'] = number_format(395, 2, ',', '.') . ' kr';
                    $data['invoice_lines'][] = $item;
                }
                if ($invoice->extra_2) {
                    $item['text'] = 'Facebook';
                    $item['price'] = number_format(395, 2, ',', '.') . ' kr';
                    $data['invoice_lines'][] = $item;
                }
                if ($invoice->extra_3) {
                    $item['text'] = 'Youtube';
                    $item['price'] = number_format(995, 2, ',', '.') . ' kr';
                    $data['invoice_lines'][] = $item;
                }
                if ($invoice->extra_4) {
                    $item['text'] = 'Managing job seekers applications';
                    $item['price'] = number_format(7995, 2, ',', '.') . ' kr';
                    $data['invoice_lines'][] = $item;
                }

                $data['sub_total'] = number_format($invoice['sub_total'], 2, ',', '.') . ' kr';
                $data['vat'] = number_format($invoice['vat'], 2, ',', '.') . ' kr';
                $data['total'] = number_format($invoice['total'], 2, ',', '.') . ' kr';

                $pdf_name = GeneralHelper::generateRandomString(30) . '.pdf';
                $invoice_pdf_url = md5(uniqid(rand(), true)) . '.pdf';

                SPDF::loadView('pdf.employer_invoice', ['data' => $data])->setPaper('a4')->save(storage_path('invoice_pdf/' . $pdf_name));
                exec('mv ' . storage_path('invoice_pdf/' . $pdf_name) . ' ' . public_path('/images/invoice_pdf/' . $invoice_pdf_url));
                exec('rm -rf ' . storage_path('invoice_pdf/' . $pdf_name));


                return response()->json(['result' => 'success', 'pdf_url' => $invoice_pdf_url]);
            }
        } else {
            if (Invoice::where('id', $invoice_id)->first()) {
                $invoice = Invoice::where('id', $invoice_id)->first();

                if (User::where('id', $invoice->user_id)->first()) {
                    $user_email = User::where('id', $invoice->user_id)->first()->email;
                    $user_name = User::where('id', $invoice->user_id)->first()->name;
                }


                $data['invoice'] = $invoice;
                $item['text'] = GeneralHelper::get_seeker_package_name($invoice['plan']);
                $item['price'] = number_format(GeneralHelper::get_seeker_package_price($invoice['plan']), 2, ',', '.') . ' kr';

                $data['invoice_lines'][] = $item;


                $data['email'] = $user_email;
                $data['name'] = $user_name;

                $data['sub_total'] = number_format($invoice['sub_total'], 2, ',', '.') . ' kr';
                $data['total'] = number_format($invoice['total'], 2, ',', '.') . ' kr';

                $pdf_name = GeneralHelper::generateRandomString(30) . '.pdf';
                $invoice_pdf_url = md5(uniqid(rand(), true)) . '.pdf';

                SPDF::loadView('pdf.seeker_invoice', ['data' => $data])->setPaper('a4')->save(storage_path('invoice_pdf/' . $pdf_name));
                exec('mv ' . storage_path('invoice_pdf/' . $pdf_name) . ' ' . public_path('/images/invoice_pdf/' . $invoice_pdf_url));
                exec('rm -rf ' . storage_path('invoice_pdf/' . $pdf_name));


                return response()->json(['result' => 'success', 'pdf_url' => $invoice_pdf_url]);
            }
        }
    }
    /**
     * EndPoint admin-api/get_statistics
     * HTTP SUBMIT : POST
     * Get dashboard statistics
     * @return json with statistic details
     */
    public function get_statistics(Request $request)
    {
        $userA = User::with(['member'])->where('type', 1)->get();

        $dashboard['jobseekers'] = sizeof($userA);
        $dashboard['jobseeker_level_1'] = 0;
        $dashboard['jobseeker_level_2'] = 0;
        $dashboard['cv_users'] = 0;
        $dashboard['paid_jobseekers'] = 0;
        foreach ($userA as $v) {
            if ($v->member) {
                if ($v->member->plan == 1) {
                    $dashboard['jobseeker_level_1']++;
                } else if ($v->member->plan == 2) {
                    $dashboard['jobseeker_level_2']++;
                } else {
                }
                $dashboard['paid_jobseekers']++;
            } else {
            }

            if ($v->cv_ready) {
                $dashboard['cv_users']++;
            }
        }
        $dashboard['jobseeker_rate'] = intval(($dashboard['jobseeker_level_1'] + $dashboard['jobseeker_level_2']) / $dashboard['jobseekers'] * 100);



        $userB = User::with(['member'])->where('type', 2)->get();

        $dashboard['employers'] = sizeof($userB);
        $dashboard['employer_level_1'] = 0;
        $dashboard['employer_level_2'] = 0;
        $dashboard['employer_level_3'] = 0;
        $dashboard['paid_employers'] = 0;

        foreach ($userB as $v) {
            if ($v->member) {
                if ($v->member->plan == 1) {
                    $dashboard['employer_level_1']++;
                } else if ($v->member->plan == 2) {
                    $dashboard['employer_level_2']++;
                } else if ($v->member->plan == 3) {
                    $dashboard['employer_level_3']++;
                } else {
                }
                $dashboard['paid_employers']++;
            } else {
            }
        }
        $dashboard['employer_rate'] = intval(($dashboard['employer_level_1'] + $dashboard['employer_level_2'] + $dashboard['employer_level_3']) / $dashboard['employers']  * 100);

        $dashboard['total_jobs'] = Job::where('is_active', 1)->count();
        $dashboard['company_active_jobs'] = Job::where('user_id', '>', 0)->where('is_active', 1)->count();
        $dashboard['company_pending_jobs'] = Job::where('user_id', '>', 0)->where('is_active', 0)->count();
        $dashboard['cron_jobs'] = $dashboard['total_jobs'] - $dashboard['company_active_jobs'];

        $dashboard['interviews'] = Interview::count();
        $dashboard['messages'] = Message::count();



        return response()->json($dashboard, 200);
    }
    /**
     * EndPoint admin-api/get_job_categories
     * HTTP SUBMIT : GET
     * Get job categories according to given object format
     * @return json array with fetched job categories
     */
    public function get_job_categories(Request $request)
    {
        $final_json = [];
        $result = Category::where('level', 2)->orderBy('name', 'asc')->get();

        foreach ($result as $key => $value) {

            $item['id'] = $value['id'];
            $item['level'] = $value['level'];
            $item['name'] = $value['name'];
            $item['seo'] = $value['seo'];
            array_push($final_json, $item);
        }

        return response()->json($final_json, 200);
    }
    /**
     * EndPoint admin-api/update_category
     * HTTP SUBMIT : POST
     * Update category with given id and name
     * @param
     * id : category id
     * name : category name
     * @return json with success
     */
    public function update_category(Request $request)
    {
        $id = $request->post('id');
        $name = $request->post('name');

        Category::where('id', $id)->update(['name' => $name]);
        return response()->json(['result' => 'success']);
    }
    /**
     * EndPoint admin-api/create_category
     * HTTP SUBMIT : POST
     * Create category with given id and name
     * @param
     * name : category name
     * @return json with success or failed
     */
    public function create_category(Request $request)
    {
        $name = $request->post('name');

        if (Category::where('name', $name)->where('level', 2)->first()) {
            return response()->json(['result' => 'error']);
        } else {
            $new_category = new Category();
            $new_category->name = $name;
            $new_category->level = 2;
            $new_category->seo = strtolower($name);
            if ($new_category->save()) {
                $new_subCategory = new CategorySubCategory();
                $new_subCategory->category_id = 128;
                $new_subCategory->subcategory_id = $new_category->id;
                $new_subCategory->display_order = CategorySubCategory::where('category_id', 128)->count() + 1;
                $new_subCategory->save();
                return response()->json(['result' => 'success']);
            } else {
                return response()->json(['result' => 'failed']);
            }
        }
    }
    /**
     * EndPoint admin-api/get_skills
     * HTTP SUBMIT : GET
     * Get skills
     * @return json array with skills
     */
    public function get_skills(Request $request)
    {
        $skills = Skill::orderBy('da_name', 'asc')->get();
        return response()->json($skills, 200);
    }
    /**
     * EndPoint admin-api/update_skill
     * HTTP SUBMIT : POST
     * Update skill with given id and name
     * @param
     * id : skill id
     * name : skill name
     * @return json with result as success
     */
    public function update_skill(Request $request)
    {
        $id = $request->post('id');
        $name = $request->post('name');

        Skill::where('id', $id)->update(['da_name' => $name]);
        return response()->json(['result' => 'success']);
    }
    /**
     * EndPoint admin-api/create_skill
     * HTTP SUBMIT : POST
     * Create skill with given id and name
     * @param
     * name : skill name
     * @return json with result as success or failed
     */
    public function create_skill(Request $request)
    {
        $name = $request->post('name');

        if (Skill::where('da_name', $name)->first()) {
            return response()->json(['result' => 'error']);
        } else {
            $new_skill = new Skill();
            $new_skill->name = $name;
            $new_skill->da_name = $name;
            $new_skill->is_active = 1;
            if ($new_skill->save()) {
                return response()->json(['result' => 'success']);
            } else {
                return response()->json(['result' => 'failed']);
            }
        }
    }
    /**
     * EndPoint admin-api/get_job_reports
     * HTTP SUBMIT : GET
     * Get Job Reports
     * @return json array with fetched all job reports
     */
    public function get_job_reports(Request $request)
    {
        $job_reports = JobReport::with(['job'])->orderBy('created_at', 'desc')->get();
        return response()->json($job_reports, 200);
    }
}
