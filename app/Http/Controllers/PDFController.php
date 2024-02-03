<?php

namespace App\Http\Controllers;

use Goutte;
use App\Job;
use App\Place;
use App\City;
use File;
use DB;
use App\JobCategory;
use App\Category;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use App\JobLocation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Clue\React\Buzz\Browser;
use SPDF;
use App\Invoice;
use App\Company;
use App\Helpers\GeneralHelper;
use App\User;

class PDFController extends Controller
{
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
} //End class
