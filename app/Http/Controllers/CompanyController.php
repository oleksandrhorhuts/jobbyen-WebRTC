<?php

namespace App\Http\Controllers;

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
use App\CvDocument;
use App\UserCvsSaved;
use App\Company;

class CompanyController extends Controller
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
     * EndPoint api/get_company_profile
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Get company details from given company id
     * @param
     * company_id : the id of company
     * @return json with fetched company details
     */
    public function get_company_profile(Request $request, $company_id)
    {
        $company = Company::with(['company_location'])->where('company_id', $company_id)->first();
        return response()->json(['result' => 'success', 'company' => $company], 200);
    }
    /**
     * EndPoint api/get_company_from_user
     * HTTP SUBMIT : POST
     * Get company id from given user id 
     * @param
     * user_id : the id of user who has company
     * @return json with status, fetched company id
     */
    public function get_company_from_user(Request $request)
    {
        $user_id = $request->post('user_id');
        if ($company = Company::where('user_id', $user_id)->first()) {
            return response()->json(['result' => $company->company_id], 200);
        } else {
            return response()->json(['result' => 0], 200);
        }
    }
    /**
     * EndPoint api/get_employer_profile
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get employer details from given employer id
     * @return json with fetched employer details
     */
    public function get_employer_profile(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $company = Company::with(['company_location'])->where('user_id', $user_id)->first();
        return response()->json(['result' => 'success', 'company' => $company], 200);
    }
    /**
     * EndPoint api/create_company
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create company with given company details
     * @param
     * company : json object with company details
     * @return json with status as success or failed
     */
    public function create_company(Request $request)
    {

        $company = $request->post('company');
        $company = json_decode($company);

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $company_logo = 'new_company';
        $company_logo_real_name = '';
        $company_logo_real_size = '';

        if (!empty($_FILES['upload_company_logo_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_company_logo_file']['tmp_name'])) {

                $company_logo_real_name = $_FILES['upload_company_logo_file']['name'];
                $company_logo_real_size = $_FILES['upload_company_logo_file']['size'];

                $company_logo = md5(time() . rand()) . '_company_logo';
                $sTempFileName = public_path() . '/images/company_logo/' . $company_logo . '.png';
                if (move_uploaded_file($_FILES['upload_company_logo_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                    //ImageOptimizer::optimize($sTempFileName);
                } else {
                }
            }
        }

        $company_video = '';
        $company_video_real_name = '';
        $company_video_real_type = '';
        $company_video_real_size = '';

        if (!empty($_FILES['upload_company_video_file']['tmp_name'])) {
            if (is_uploaded_file($_FILES['upload_company_video_file']['tmp_name'])) {


                $type = pathinfo($_FILES['upload_company_video_file']['name'], PATHINFO_EXTENSION);

                $company_video_real_type = $type;

                $company_video = md5(time() . rand()) . '_company_video.' . $type;
                $company_video_real_name = $_FILES['upload_company_video_file']['name'];
                $company_video_real_size = $_FILES['upload_company_video_file']['size'];


                $sTempFileName = public_path() . '/images/company_video/' . $company_video;
                if (move_uploaded_file($_FILES['upload_company_video_file']['tmp_name'], $sTempFileName)) {
                    @chmod($sTempFileName, 0755);
                } else {
                }
            }
        }


        if (Company::where('user_id', $user_id)->first()) {
            $exist_company = [];
            foreach ($company as $k => $v) {
                if ($k == 'company_city') {
                    $exist_company[$k] = $v->id;
                } else if ($k == 'company_location') {
                    continue;
                } else {
                    $exist_company[$k] = $v;
                }
            }

            if ($company_logo != 'new_company') {
                $exist_company['company_logo'] = $company_logo;
                $exist_company['company_logo_real_name'] = $company_logo_real_name;
                $exist_company['company_logo_real_size'] = $company_logo_real_size;
            }

            if ($company_video != '') {
                $exist_company['company_video_resume'] = $company_video;
                $exist_company['company_video_real_type'] = $company_video_real_type;
                $exist_company['company_video_real_size'] = $company_video_real_size;
                $exist_company['company_video_real_name'] = $company_video_real_name;
            }

            Company::where('user_id', $user_id)->update($exist_company);
            return response()->json(['result' => 'success'], 200);
        }

        $new_company = new Company();
        $new_company->user_id = $user_id;
        $new_company->company_logo = $company_logo;
        $new_company->company_video_resume = $company_video != '' ? $company_video : '';
        foreach ($company as $k => $v) {
            if ($k == 'company_city') {
                $new_company->$k = $v->id;
            } else {
                $new_company->$k = $v;
            }
        }
        if ($new_company->save()) {
            return response()->json(['result' => 'success'], 200);
        } else {
            return response()->json(['result' => 'failed'], 200);
        }
    }
}
