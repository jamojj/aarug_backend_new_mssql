<?php

namespace App\Http\Controllers\Bills\BillProcess;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\Bills;
use App\Models\ClaimForm;
use App\Models\ClaimsBatch;
use App\Models\Corporate\CorpGroups;
use App\Models\LastNumber;
use App\Models\Provider;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberCancellation;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\Service;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Type\Integer;

class ImportHospitalBillsController extends Controller
{
    //validate imported data and return errors
    public function validateImportedHospitalBills(Request $request)
    {
        $corp_id = $family_no = $category = $relation_to_principal = $max_date_can = $cancelled =
            $ll_anniv = $start_date = $end_date = $ll_fund_category = $ll_corp_id = $ll_family_no =
            $fund = $sub_benefit_checkbox = $anniv = null;

        $response_arr = $request_data = $errors_arr = $ls_member_data_arr = array();
        $counts = $cancelled_counts = null;
        //check for errors

        for ($i = 0; $i < count($request->invoice_no); $i++) {
            //check if member exists
            $ls_mem_no_count = MemberInfo::where('member_no', $request->member_no[$i])
                ->get();

            if (count($ls_mem_no_count) === 0) {
                array_push($errors_arr, 'NO SUCH MEMBER EXISTS IN THE SYSTEM');
                continue;
            }

            //fetch provider and corp_id
            $ls_member_data = MemberInfo::select(
                'principal_applicant.corp_id',
                'principal_applicant.family_no',
                'principal_applicant.category',
                'member_info.relation_to_principal'
            )
                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->where('member_info.member_no', $request->member_no[$i])
                ->get();
            $corp_id = count($ls_member_data) === 0 ? null  : $ls_member_data[0]->corp_id;
            $family_no = count($ls_member_data) === 0 ? null  : $ls_member_data[0]->family_no;
            $category = count($ls_member_data) === 0 ? null  : $ls_member_data[0]->category;
            $relation_to_principal = count($ls_member_data) === 0 ? null  : $ls_member_data[0]->relation_to_principal;

            //check if claim is a duplicate
            $bill_duplicate_cnt = Bills::where('invoice_no', $request->invoice_no[$i])
                ->where('provider', $request->provider[$i])
                ->where('service', $request->service[$i])
                ->where('member_no', $request->member_no[$i])
                ->get();


            if (count($bill_duplicate_cnt) >= 1) {
                array_push($errors_arr, 'THIS IS A DUPLICATE BILL');
                continue;
            }

            //check for member cancellation
            $cancel_status = MemberCancellation::select(
                DB::raw('max(date_can) as max_date_can'),
                'cancelled'
            )
                ->where('member_no', $request->member_no[$i])
                ->groupBy('date_can', 'cancelled')
                ->get();
            $max_date_can = count($cancel_status) === 0 ? null :  $cancel_status[0]->max_date_can;
            $cancelled = count($cancel_status) === 0 ? null :  $cancel_status[0]->cancelled;
            if ($cancelled == "1") {
                array_push($errors_arr, 'MEMBER WAS / IS CANCELLED');
                continue;
            }


            //assign bill anniv - get all annivs first
            $cover_dates = MemberAnniversary::select('start_date', 'end_date')
                ->where('anniv', $request->anniv[$i])
                ->where('member_no', $request->member_no[$i])
                ->get();
            $start_date = count($cover_dates) === 0 ? null : $cover_dates[0]->start_date;
            $end_date = count($cover_dates) === 0 ? null : $cover_dates[0]->end_date;

            if (! (date('Y-m-d', strtotime($request->invoice_date[$i])) >= date('Y-m-d', strtotime($start_date)) &&
                date('Y-m-d', strtotime($request->invoice_date[$i])) <= date('Y-m-d', strtotime($end_date)))) {
                array_push($errors_arr, 'BILL OUTSIDE MEMBER COVER DATES');
                continue;
            }

            //pick corp_id
            $principal_details = PrincipalApplicant::select('corp_id', 'family_no', 'category')
                ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                ->where('member_info.member_no', $request->member_no[$i])
                ->get();

            $corp_id = count($principal_details) === 0 ? null : $principal_details[0]->corp_id;
            $family_no = count($principal_details) === 0 ? null : $principal_details[0]->family_no;
            $category = count($principal_details) === 0 ? null : $principal_details[0]->category;


            //determine if benefit is under fund
            $ll_fund = CorpGroups::select('fund')
                ->where('anniv', $anniv)
                ->where('benefit', intval($request->claim_nature[$i]))
                ->where('category', $category)
                ->where('corp_id', $corp_id)
                ->get();
            $ll_fund = count($ll_fund) === 0 ? null : $ll_fund[0]->fund;
            if (empty($ll_fund)) {
                $fund = 0;
            } else {
                $fund = 1;
            }


            //validate claim nature - sub benefit
            $ll_sub_benefit = MemberBenefits::select(
                'sub_limit_of_count'
            )
                ->where('benefit', $request->claim_nature[$i])
                ->where('anniv', $anniv)
                ->where('member_no', $request->member_no[$i])
                ->get();
            $sub_limit_of  = count($ll_sub_benefit) == 0 ? null :  $ll_sub_benefit[0]->sub_limit_of_count;
            if (empty($sub_limit_of)) {
                $sub_benefit_checkbox = null;
            } else {
                $sub_benefit_checkbox = 1;
            }
            //check claim nature
            $ll_claim_nature_count = MemberBenefits::where('benefit', $request->claim_nature[$i])
                ->where('anniv', $anniv)
                ->where('member_no', $request->member_no[$i])
                ->get();


            if (count($ll_claim_nature_count) === 0) {
                array_push($errors_arr, 'SPECIFIED CLAIM BENEFIT NOT VALID FOR THIS MEMBER');
                continue;
            }

            //check if batch exists
            $ll_batch_count = ClaimsBatch::where('provider', $request->provider[$i])
                ->where('batch_no', $request->batch_no[$i])
                ->get();
            if (count($ll_batch_count) === 0) {
                array_push($errors_arr, 'NO SUCH BATCH EXISTS FOR THE PROVIDER!');
                continue;
            }

            $request_data = [
                'invoice_no' => $request->invoice_no[$i],
                'provider' => $request->provider[$i],
                'service' => $request->service[$i],
                'claim_nature' => $request->claim_nature[$i],
                'invoiced_amt' => $request->invoiced_amt[$i],
                'invoice_date' => $request->invoice_date[$i],
                'date_received' => $request->date_received[$i],
                'member_no' => $request->member_no[$i],
                'link' => $request->link[$i],
                'batch_no' => $request->batch_no[$i],
                'user' => $request->user[$i],
                'date_entered' => $request->date_entered[$i],
                'error' => $errors_arr[$i],
                'corp_id' => $corp_id,
                'family_no' => $family_no,
                'category' => $category,
                'relation_to_principal' => $relation_to_principal,
                'bill_duplicate_cnt' => $counts,
                'cancelled_counts' => $cancelled_counts,
                'max_date_can' => $max_date_can,
                'cancelled' => $cancelled,
                'anniv' => $anniv,
                'll_anniv' => $ll_anniv,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'invoice_anniv' => $anniv,
                'fund' => $fund,
                'sub_benefit' => $sub_benefit_checkbox,
            ];

            array_push($response_arr, $request_data);
        }
        return $response_arr;
    }

    //generate claim no for all the links
    public function generateClaimNumbers(Request $request)
    {
        //return $request;
        $response_arr = $request_data = array();
        $il_prev_link = null;
        $today = date('Y-m-d');

        for ($i = 0; $i < count($request->invoice_no); $i++) {
            $il_curr_link = intval($request->link[$i]);
            //get latest claim no from last number

            $il_last_clm_no = LastNumber::select('last_no')
                ->where('task', 4)
                ->get();
            $il_last_clm_no = intval($il_last_clm_no[0]['last_no']);
            $il_last_clm_no = $il_last_clm_no + 1;
            //formulate claim no
            $claim_no = 'CLM ' . $il_last_clm_no;
            if ($i == 0) {
                array_push($request_data, $claim_no);
                $request_data = [
                    'claim_no' => $claim_no,
                    'visit_date' => $request->invoice_date[$i],
                    'doctor_sign' => 1,
                    'doctor_date' => $request->invoice_date[$i],
                    'claim_form_signed' => 1,
                    'user_id' => $request->user[0],
                    'date_entered' => $today,
                    'date_admitted' => $request->date_admitted[$i],
                    'date_discharged' => $request->date_discharged[$i],
                    'link' => $request->link[$i],
                    '$il_last_clm_no' => $il_last_clm_no,
                ];
                array_push($response_arr, $request_data);

                $ld_date_admitted = $request->date_admitted[$i];
                $ld_date_discharged = $request->date_discharged[$i];

                $il_prev_link = $il_curr_link;
                $claim_no = $claim_no;
            } else {
                //same batch bills do not to generate claim no for same batch bills
                if ($il_prev_link == $il_curr_link) {
                    $request_data = [
                        'claim_no' => $claim_no,
                        'visit_date' => $request->invoice_date[$i],
                        'doctor_sign' => 1,
                        'doctor_date' => $request->invoice_date[$i],
                        'claim_form_signed' => 1,
                        'user_id' => $request->user[0],
                        'date_entered' => $today,
                        'date_admitted' => $request->date_admitted[$i],
                        'date_discharged' => $request->date_discharged[$i],
                        'link' => $request->link[$i],
                        '$il_last_clm_no' => $il_last_clm_no,
                    ];
                    array_push($response_arr, $request_data);
                    $claim_no = $claim_no;
                    $il_prev_link = $il_curr_link;
                } elseif ($il_prev_link != $il_curr_link) {
                    //increment last claim no
                    $il_last_clm_no = $il_last_clm_no + 1;
                    //formulate new claim no
                    $claim_no = 'CLM ' . $il_last_clm_no;

                    $il_prev_link = $il_curr_link;
                    array_push($request_data, $claim_no);
                    $request_data = [
                        'claim_no' => $claim_no,
                        'visit_date' => $request->invoice_date[$i],
                        'doctor_sign' => 1,
                        'doctor_date' => $request->invoice_date[$i],
                        'claim_form_signed' => 1,
                        'user_id' => $request->user[0],
                        'date_entered' => $today,
                        'date_admitted' => $request->date_admitted[$i],
                        'date_discharged' => $request->date_discharged[$i],
                        'link' => $il_curr_link,
                        '$il_last_clm_no' => $il_last_clm_no,
                    ];

                    array_push($response_arr, $request_data);

                    $il_prev_link = $il_curr_link;
                }
            }
        }
        return $response_arr;
    }

    //save imported hospital cash bills
    public function saveImportedHospitalBills(Request $request)
    {
        //return $request;
        try {
            $response = [];
            $today = date('Y-m-d');
            $member_no = 'null';
            $max_id = Bills::max(DB::raw("isnull(id, 0)"));
            $last_no_claims = LastNumber::select('last_no')->where('task', 4)->get();
            $last_no_claims = count($last_no_claims) === 0 ? 1 : $last_no_claims[0]->last_no;
            $claim_no = '';
            $error_count = 0;

            for ($i = 0; $i < count($request->index); $i++) {

                //validate required input 
                $validator = Validator::make($request->all(), [
                    "index.$i" => 'required',
                    "invoice_no.$i" => 'required',
                    "provider.$i" => 'required',
                    "service.$i" => 'required',
                    "claim_nature.$i" => 'required',
                    "invoiced_amt.$i" => 'required',
                    "invoice_date.$i" => 'required',
                    "date_received.$i" => 'required',
                    "member_no.$i" => 'required',
                    "batch_no.$i" => 'required',
                    "user.$i" => 'required',
                    "date_entered.$i" => 'required',
                    "anniv.$i" => 'required',
                    "category.$i" => 'required',
                    "product_name.$i" => 'required'
                ]);

                if ($validator->fails()) {
                    // Collect all error messages
                    $errors = $validator->errors();

                    // Flatten the error messages and transform them to a string
                    $errorString = collect($errors->all())->map(function ($message) {
                        return str_replace(['.0', '.1', '.2', '.3', '.4', '.5', '.6', '.7', '.8', '.9', 'field is required.', 'The'], '', $message);
                    })->join(', ');
                    $errorString = "Error : (" .  $errorString . ") is required!";

                    $dt = [
                        'claim_no' => '',
                        'index' => $request->index[$i],
                        'res' => $errorString
                    ];
                    array_push($response, $dt);
                    $error_count++;
                    continue;
                }


                //validate data
                $member_exists = MemberInfo::where('member_no', $request->member_no[$i])->get();
                if (count($member_exists) === 0) {
                    $dt = [
                        'claim_no' => '',
                        'index' => $request->index[$i],
                        'res' => 'Error Member does not exist'
                    ];
                    array_push($response, $dt);
                    $error_count++;
                    continue;
                }
                $provider_exists = Provider::where('code', $request->provider[$i])->get();
                if (count($provider_exists) === 0) {
                    $dt = [
                        'claim_no' => '',
                        'index' => $request->index[$i],
                        'res' => 'Error Provider does not exist'
                    ];
                    array_push($response, $dt);
                    $error_count++;
                    continue;
                }
                $member_benefit_exists = MemberBenefits::where('benefit', $request->claim_nature[$i])->where('anniv', $request->anniv[$i])->where('member_no', $request->member_no[$i])->get();
                if (count($member_benefit_exists) === 0) {
                    $dt = [
                        'claim_no' => '',
                        'index' => $request->index[$i],
                        'res' => 'Error Member benefit does not exist for the member'
                    ];
                    array_push($response, $dt);
                    $error_count++;
                    continue;
                }
                $service_exists = Service::where('code', $request->service[$i])->get();
                if (count($service_exists) === 0) {
                    $dt = [
                        'claim_no' => '',
                        'index' => $request->index[$i],
                        'res' => 'Error Service does not exist'
                    ];
                    array_push($response, $dt);
                    $error_count++;
                    continue;
                }
            }
            if ($error_count !== 0) {
                return ['response' => $response, 'message' => 'Data has errors, correct and try again!'];
            }
            //if all is well save claim
            for ($i = 0; $i < count($request->index); $i++) {

                $family_no = explode('-', $member_no);
                $family_no = $family_no[0];
                $corp_id = PrincipalApplicant::select('corp_id')->where('family_no', $family_no)->get();
                $corp_id = count($corp_id) === 0 ? '' :  $corp_id[0]->corp_id;
                $max_id++;
                //same claim no per member for all services
                if ($member_no !== $request->member_no[$i]) { //unique claim no
                    $last_no_claims++;
                    $claim_no = 'CLM ' . $last_no_claims;
                    DB::transaction(function () use ($request, $max_id, $claim_no, $today, $family_no, $corp_id, $i) {
                        Bills::create([
                            'id' => $max_id,
                            'CLAIM_NO' => $claim_no,
                            'INVOICE_NO' => $request->invoice_no[$i],
                            'CLAIM_FORM_NO' => $claim_no,
                            'PROVIDER' => $request->provider[$i],
                            'MEMBER_NO' => $request->member_no[$i],
                            'SERVICE' => $request->service[$i],
                            'CLAIM_NATURE' => $request->claim_nature[$i],
                            'INVOICE_DATE' => $request->invoice_date[$i],
                            'INVOICED_AMOUNT' => $request->invoiced_amt[$i],
                            'BATCH_NO' => $request->batch_no[$i],
                            'USER_ID' => $request->user[$i],
                            'DATE_ENTERED' => $today,
                            'anniv' => $request->anniv[$i],
                            'date_received' => $request->date_received[$i],
                            'family_no' => $family_no,
                            'corp_id' => $corp_id,
                            'category' => $request->category[$i],
                            'product_name' => $request->product_name[$i],
                            'claim_form' => $claim_no,
                        ]);

                        ClaimForm::create([
                            'CLAIM_NO' => $claim_no,
                            'VISIT_DATE' => $request->invoice_date[$i],
                            'USER_ID' => $request->user[$i],
                            'DATE_ENTERED' => $today,
                        ]);
                        //increment last claim no in last number
                        LastNumber::where('task', 4)->increment('last_no');
                    });
                } else { //same person so same claim no
                    $claim_no = 'CLM ' . $last_no_claims;
                    DB::transaction(function () use ($request, $max_id, $claim_no, $today, $family_no, $corp_id, $i) {
                        Bills::create([
                            'id' => $max_id,
                            'CLAIM_NO' => $claim_no,
                            'INVOICE_NO' => $request->invoice_no[$i],
                            'CLAIM_FORM_NO' => $claim_no,
                            'PROVIDER' => $request->provider[$i],
                            'MEMBER_NO' => $request->member_no[$i],
                            'SERVICE' => $request->service[$i],
                            'CLAIM_NATURE' => $request->claim_nature[$i],
                            'INVOICE_DATE' => $request->invoice_date[$i],
                            'INVOICED_AMOUNT' => $request->invoiced_amt[$i],
                            'BATCH_NO' => $request->batch_no[$i],
                            'USER_ID' => $request->user[$i],
                            'DATE_ENTERED' => $today,
                            'anniv' => $request->anniv[$i],
                            'date_received' => $request->date_received[$i],
                            'family_no' => $family_no,
                            'corp_id' => $corp_id,
                            'category' => $request->category[$i],
                            'product_name' => $request->product_name[$i],
                            'claim_form' => $claim_no,
                        ]);
                    });
                }

                $dt = [
                    'claim_no' => $claim_no,
                    'index' => $request->index[$i],
                    'res' => 'success'
                ];
                array_push($response, $dt);
                $member_no = $request->member_no[$i];
            }
            return ['response' => $response, 'max_id' => $max_id, 'claim_no' => $claim_no, 'message' => 'Claims saved Successfully. '];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
