<?php

namespace App\Http\Controllers;

use App;

use App\User;
use Carbon\Carbon;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ImageOptimizer;
use Nexmo;
use App\CategorySubCategory;
use App\Category;
use App\CategoryEn;
use PDF;
use App\Skill;
use App\JobTitle;

use App\Membership;
use Tymon\JWTAuth\Facades\JWTAuth;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;
use Exception;
use App\Invoice;
use App\JobPermission;
use App\TempMembership;
use App\PaymentIntegration;
use App\Company;

class MembershipController extends Controller
{
    /**
     * EndPoint api/save_membership_plan
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Save membership as temporaily before made payment, so after refreshed on payment page, it will be kept the current selected membership and its details.
     * @param
     * selected_plan : plan id 1 : basic pakke, 2 : pro pakke[candidate]
     * extra_package : json array with extra package status
     * @return json with saved jobs and its count
     */
    public function save_membership_plan(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;

        $selected_plan = $request->post('selected_plan');
        $extra_package = $request->post('extra_package');

        if (TempMembership::where('user_id', $user_id)->first()) {
            TempMembership::where('user_id', $user_id)->update(['plan' => $selected_plan, 'extra_1' => $extra_package[0]['status'], 'extra_2' => $extra_package[1]['status'], 'extra_3' => $extra_package[2]['status'], 'extra_4' => $extra_package[3]['status']]);
            return response()->json(['result' => 'success'], 200);
        } else {
            $new_membership = new TempMembership();
            $new_membership->user_id = $user_id;
            $new_membership->plan = $selected_plan;
            $new_membership->extra_1 = $extra_package[0]['status'];
            $new_membership->extra_2 = $extra_package[1]['status'];
            $new_membership->extra_3 = $extra_package[2]['status'];
            $new_membership->extra_4 = $extra_package[3]['status'];
            if ($new_membership->save()) {
                return response()->json(['result' => 'success'], 200);
            } else {
                return response()->json(['result' => 'failed'], 200);
            }
        }
    }
    /**
     * EndPoint api/get_membership_plan
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get membership plan as temporaily for continue payment
     * @return json with kept temp membership plan
     */
    public function get_membership_plan(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_email = $user->email;
        $user_name = $user->name;
        if (TempMembership::where('user_id', $user_id)->first()) {
            return response()->json(['result' => 'success', 'plan' => TempMembership::where('user_id', $user_id)->first(), 'user_name' => $user_name, 'user_email' => $user_email], 200);
        } else {
            return response()->json(['result' => 'failed', 'plan' => null, 'user_name' => null, 'user_email' => null], 200);
        }
    }
    /**
     * EndPoint api/get_transaction_detail/{transaction_id}
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get invoice from given transaction id
     * @param
     * transaction_id : id of transaction
     * @return json with fetched invoice details
     */
    public function get_transaction_detail(Request $request, $transaction_id)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_email = $user->email;
        $user_name = $user->name;
        if (Invoice::where('id', $transaction_id)->where('user_id', $user_id)->count()) {
            $invoice = Invoice::where('id', $transaction_id)->where('user_id', $user_id)->first();

            $company = null;
            if (Company::where('user_id', $user_id)->first()) {
                $company = Company::with(['company_location'])->where('user_id', $user_id)->first();
            }

            return response()->json(['result' => 'success', 'invoice' => $invoice, 'user_email' => $user_email, 'user_name' => $user_name, 'company' => $company], 200);
        } else {
            return response()->json(['result' => 'failed', 'invoice' => null, 'user_email' => null, 'user_name' => null, 'company' => null], 200);
        }
    }
    /**
     * EndPoint api/get_transaction
     * HTTP SUBMIT : GET
     * JWT TOKEN which provided by token after user logged in
     * Get all transaction
     * @return json with fetched all transactions
     */
    public function get_transaction(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $invoices = Invoice::where('user_id', $user_id)->orderBy('created_at', 'desc')->get();
        return response()->json($invoices, 200);
    }
    /**
     * EndPoint api/paymentwithIntentConfirm
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create new intent with payment intent data according stripe 3d new integration
     * it will be set selected membership plan and if payment is success, job permission will allocate with user type is employer
     * @param
     * payment_method_id : payment method id which submit by stripe create payment method endpoint
     * price : current selected price of membership
     * vat : vat of membership
     * sub_total : sub total which sum up by membership and extra package price.
     * description : description of membership, it will be submit to stripe.
     * @return json with saved jobs and its count
     */
    public function paymentwithIntentConfirm(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_type = $user->type;
        $user_email = $user->email;

        $payment_intent_id = $request->post('payment_intent_id');
        $invoice_id = $request->post('invoice_id');

        if (TempMembership::where('user_id', $user_id)->first()) {
            $plan = TempMembership::where('user_id', $user_id)->first()->plan;
            $extra_1 = TempMembership::where('user_id', $user_id)->first()->extra_1;
            $extra_2 = TempMembership::where('user_id', $user_id)->first()->extra_2;
            $extra_3 = TempMembership::where('user_id', $user_id)->first()->extra_3;
            $extra_4 = TempMembership::where('user_id', $user_id)->first()->extra_4;
        }

        $secret_value = PaymentIntegration::where('type', 'private')->first()->value;
        \Stripe\Stripe::setApiKey($secret_value);

        try {
            $intent = \Stripe\PaymentIntent::retrieve(
                $payment_intent_id,
            );
            $intent->confirm();
            if (
                $intent['status'] == 'requires_source_action' &&
                $intent['next_action']['type'] == 'use_stripe_sdk'
            ) {
            } else if ($intent['status'] == 'succeeded') {
                # The payment didn’t need any additional actions and completed!
                # Handle post-payment fulfillment


                if (Membership::where('user_id', $user_id)->first()) {
                    Membership::where('user_id', $user_id)->update(['plan' => $plan, 'extra_1' => $extra_1, 'extra_2' => $extra_2, 'extra_3' => $extra_3, 'extra_4' => $extra_4, 'status' => 1]);
                } else {
                    $new_membership = new Membership();
                    $new_membership->user_id = $user_id;
                    $new_membership->plan = $plan;
                    $new_membership->extra_1 = $extra_1;
                    $new_membership->extra_2 = $extra_2;
                    $new_membership->extra_3 = $extra_3;
                    $new_membership->extra_4 = $extra_4;
                    $new_membership->status = 1;
                    $new_membership->save();
                }

                if (Invoice::where('id', $invoice_id)->first()) {
                    Invoice::where('id', $invoice_id)->update(['paid_status' => 1]);
                }

                if ($user_type == 2) {
                    if (!JobPermission::where('user_id', $user_id)->where('active', 1)->first()) {
                        $new_job_permission = new JobPermission();
                        $new_job_permission->user_id = $user_id;
                        $new_job_permission->plan = $plan;
                        $new_job_permission->invoice = $invoice_id;
                        $new_job_permission->active = 1;
                        $new_job_permission->save();
                    }
                }
                $user = User::with(['member'])->where('id', $user_id)->first();
                return response()->json(['result' => 'success', 'message' => 'Betalt', 'membership' => $user->member], 200);
            } else {
                # Invalid status
                http_response_code(500);
                Invoice::where('id', $invoice_id)->update(['error_message' => 'Invalid PaymentIntent status']);
                return response()->json(['result' => 'failed', 'message' => 'Invalid PaymentIntent status', 'membership' => null], 200);
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            # Display error on client
            Invoice::where('id', $invoice_id)->update(['error_message' => $e->getMessage()]);
            echo json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }
    /**
     * EndPoint api/createPaymentIntentForApple
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create new intent with payment intent data for apple and google pay
     * @param
     * price : current selected price of membership
     * description : description of membership, it will be submit to stripe.
     * @return json with saved jobs and its count
     */
    public function createPaymentIntentForApple(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $email = $user->email;

        $description = $request->post('description');
        $price = $request->post('price');

        $secret_value = PaymentIntegration::where('type', 'private')->first()->value;
        \Stripe\Stripe::setApiKey($secret_value);

        try {
            $payment_intent_data = [
                'amount' => $price * 100,
                'currency' => 'DKK',
                'description' => $description,
                'metadata' => ['integration_check' => 'accept_a_payment'],
            ];

            $customer = \Stripe\Customer::create(['email' => $email]);
            $payment_intent_data['customer'] = $customer->id;
            $paymentIntent  = \Stripe\PaymentIntent::create($payment_intent_data);

            return response()->json(['clientSecret' => $paymentIntent->client_secret], 200);
        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()], 200);
        }
    }
    public function applePaySuccess(Request $request){

        $description = $request->post('description');
        $price = $request->post('price');
        $payment_intent_id = $request->post('payment_intent_id');
        $vat = $request->post('vat');
        $sub_total = $request->post('sub_total');

        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_type = $user->type;

        $secret_value = PaymentIntegration::where('type', 'private')->first()->value;
        \Stripe\Stripe::setApiKey($secret_value);


        $new_invoice_id = 0;
        if (TempMembership::where('user_id', $user_id)->first()) {
            $plan = TempMembership::where('user_id', $user_id)->first()->plan;
            $extra_1 = TempMembership::where('user_id', $user_id)->first()->extra_1;
            $extra_2 = TempMembership::where('user_id', $user_id)->first()->extra_2;
            $extra_3 = TempMembership::where('user_id', $user_id)->first()->extra_3;
            $extra_4 = TempMembership::where('user_id', $user_id)->first()->extra_4;


            $invoice = new Invoice();
            $invoice->user_id = $user_id;
            $invoice->description = $description;
            $invoice->plan = $plan;
            $invoice->extra_1 = $extra_1;
            $invoice->extra_2 = $extra_2;
            $invoice->extra_3 = $extra_3;
            $invoice->extra_4 = $extra_4;
            $invoice->sub_total = $sub_total;
            $invoice->vat = $vat;
            $invoice->total = $price;
            $invoice->card_last = '';
            $invoice->client_ip = '';
            if ($invoice->save()) {
                $new_invoice_id = $invoice->id;
            } else {
            }
        }


        if (Membership::where('user_id', $user_id)->first()) {
            Membership::where('user_id', $user_id)->update(['plan' => $plan, 'extra_1' => $extra_1, 'extra_2' => $extra_2, 'extra_3' => $extra_3, 'extra_4' => $extra_4, 'status' => 1]);
        } else {
            $new_membership = new Membership();
            $new_membership->user_id = $user_id;
            $new_membership->plan = $plan;
            $new_membership->extra_1 = $extra_1;
            $new_membership->extra_2 = $extra_2;
            $new_membership->extra_3 = $extra_3;
            $new_membership->extra_4 = $extra_4;
            $new_membership->status = 1;
            $new_membership->save();
        }

        if (Invoice::where('id', $new_invoice_id)->first()) {
            Invoice::where('id', $new_invoice_id)->update(['paid_status' => 1]);
        }

        if ($user_type == 2) {
            if (!JobPermission::where('user_id', $user_id)->where('active', 1)->first()) {
                $new_job_permission = new JobPermission();
                $new_job_permission->user_id = $user_id;
                $new_job_permission->plan = $plan;
                $new_job_permission->invoice = $new_invoice_id;
                $new_job_permission->active = 1;
                $new_job_permission->save();
            }
        }
        $user = User::with(['member'])->where('id', $user_id)->first();
        return response()->json(['result' => 'success', 'message' => 'Betalt', 'membership' => $user->member], 200);

    }
    /**
     * EndPoint api/paymentwithIntent
     * HTTP SUBMIT : POST
     * JWT TOKEN which provided by token after user logged in
     * Create new intent with payment intent data according stripe 3d new integration
     * it will be set selected membership plan and if payment is success, job permission will allocate with user type is employer
     * @param
     * payment_method_id : payment method id which submit by stripe create payment method endpoint
     * price : current selected price of membership
     * vat : vat of membership
     * sub_total : sub total which sum up by membership and extra package price.
     * description : description of membership, it will be submit to stripe.
     * @return json with saved jobs and its count
     */
    public function paymentwithIntent(Request $request)
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::toUser($token);

        $user_id = $user->id;
        $user_type = $user->type;
        $user_email = $user->email;

        $payment_method_id = $request->post('payment_method_id');


        $price = $request->post('price');
        $vat = $request->post('vat');
        $sub_total = $request->post('sub_total');
        $description = $request->post('description');

        $new_invoice_id = 0;
        if (TempMembership::where('user_id', $user_id)->first()) {
            $plan = TempMembership::where('user_id', $user_id)->first()->plan;
            $extra_1 = TempMembership::where('user_id', $user_id)->first()->extra_1;
            $extra_2 = TempMembership::where('user_id', $user_id)->first()->extra_2;
            $extra_3 = TempMembership::where('user_id', $user_id)->first()->extra_3;
            $extra_4 = TempMembership::where('user_id', $user_id)->first()->extra_4;


            $invoice = new Invoice();
            $invoice->user_id = $user_id;
            $invoice->description = $description;
            $invoice->plan = $plan;
            $invoice->extra_1 = $extra_1;
            $invoice->extra_2 = $extra_2;
            $invoice->extra_3 = $extra_3;
            $invoice->extra_4 = $extra_4;
            $invoice->sub_total = $sub_total;
            $invoice->vat = $vat;
            $invoice->total = $price;
            $invoice->card_last = '';
            $invoice->client_ip = '';
            if ($invoice->save()) {
                $new_invoice_id = $invoice->id;
            } else {
            }
        }

        $secret_value = PaymentIntegration::where('type', 'private')->first()->value;
        \Stripe\Stripe::setApiKey($secret_value);


        try {

            $payment_intent_data = [
                'amount' => $price * 100,
                'currency' => 'DKK',
                'payment_method' => $payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'description' => $description
            ];


            $customer = \Stripe\Customer::create(['email' => $user_email]);
            Invoice::where('id', $new_invoice_id)->update(['cus_id' => $customer->id]);

            $payment_intent_data['customer'] = $customer->id;
            // $payment_intent_data['setup_future_usage'] = 'off_session';

            $intent = \Stripe\PaymentIntent::create($payment_intent_data);

            if (
                $intent['next_action']['type'] == 'use_stripe_sdk'
            ) {

                # Tell the client to handle the action
                echo json_encode([
                    'intent' => $intent,
                    'requires_action' => true,
                    'payment_intent_client_secret' => $intent['client_secret'],
                    'invoice_id' => $new_invoice_id
                ]);
            } else if ($intent['status'] == 'succeeded') {
                # The payment didn’t need any additional actions and completed!
                # Handle post-payment fulfillment


                if (Membership::where('user_id', $user_id)->first()) {
                    Membership::where('user_id', $user_id)->update(['plan' => $plan, 'extra_1' => $extra_1, 'extra_2' => $extra_2, 'extra_3' => $extra_3, 'extra_4' => $extra_4, 'status' => 1]);
                } else {
                    $new_membership = new Membership();
                    $new_membership->user_id = $user_id;
                    $new_membership->plan = $plan;
                    $new_membership->extra_1 = $extra_1;
                    $new_membership->extra_2 = $extra_2;
                    $new_membership->extra_3 = $extra_3;
                    $new_membership->extra_4 = $extra_4;
                    $new_membership->status = 1;
                    $new_membership->save();
                }

                if (Invoice::where('id', $new_invoice_id)->first()) {
                    Invoice::where('id', $new_invoice_id)->update(['paid_status' => 1]);
                }

                if ($user_type == 2) {
                    if (!JobPermission::where('user_id', $user_id)->where('active', 1)->first()) {
                        $new_job_permission = new JobPermission();
                        $new_job_permission->user_id = $user_id;
                        $new_job_permission->plan = $plan;
                        $new_job_permission->invoice = $new_invoice_id;
                        $new_job_permission->active = 1;
                        $new_job_permission->save();
                    }
                }
                $user = User::with(['member'])->where('id', $user_id)->first();
                return response()->json(['result' => 'success', 'message' => 'Betalt', 'membership' => $user->member], 200);
            } else {
                # Invalid status
                http_response_code(500);
                return response()->json(['result' => 'failed', 'message' => 'Invalid PaymentIntent status', 'membership' => null], 200);
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            # Display error on client
            Invoice::where('id', $new_invoice_id)->update(['error_message' => $e->getMessage()]);
            echo json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }
} //End class
