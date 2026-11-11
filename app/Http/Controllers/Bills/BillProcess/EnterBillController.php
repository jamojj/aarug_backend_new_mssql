<?php

namespace App\Http\Controllers\Bills\BillProcess;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\ClaimForm;
use App\Models\ClaimsBatch;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\LastNumber;
use App\Models\LineItems;
use App\Models\MemberDiagnosis;
use App\Models\PreAuthorization;
use App\Models\Provider;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberCancellation;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\Service;
use App\Models\TariffYear;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnterBillController extends Controller
{
    //search members enter bill
    public function searchEnterBillMembers(Request $request)
    {
        //return $request;
        switch ($request->select_radio_option) {
            case 1:
                $data = MemberInfo::select(
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'member_info.family_no',
                    'member_info.member_no',
                    'member_info.dob',
                    'member_info.gender',
                )
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    //->where('principal_applicant.corp_id', $request->corp_id)
                    ->where(function ($name) use ($request) {
                        $name->where('member_info.surname', 'like', '%' . $request->search_value . '%')
                            ->orWhere('member_info.first_name', 'like', '%' . $request->search_value . '%')
                            ->orWhere('member_info.other_names', 'like', '%' . $request->search_value . '%');
                    })
                    ->get();
                $data = json_encode($data, JSON_INVALID_UTF8_IGNORE);
                return $data;
                break;
            case 2:
                $data = MemberInfo::select(
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'member_info.family_no',
                    'member_info.member_no',
                    'member_info.gender',
                    'member_info.dob'
                )
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->where('member_info.member_no', $request->search_value)
                    ->get();
                $data = json_encode($data, JSON_INVALID_UTF8_IGNORE);
                return $data;
                break;
        }
    }

    //fetch selected member details
    public function fetchMemberDetails($member_no)
    {

        $data = MemberInfo::select(
            'member_info.family_no',
            'member_info.member_no',
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            'member_info.relation_to_principal',
            'member_info.family_title',
            'corporate.corporate'
        )
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where('member_info.member_no', $member_no)
            ->get();

        return $data;
    }

    //fetch benefits for selected member in current cover dates
    public function fetchMemberBenefits(Request $request)
    {

        $benefits_data = array();
        $anniv = MemberAnniversary::select('anniv')
            ->where('member_no', $request->member_no)
            ->where(function ($dates) use ($request) {
                $dates->where('start_date', '<=', date('Y-m-d', strtotime($request->invoice_date)))
                    ->where('end_date', '>=', date('Y-m-d', strtotime($request->invoice_date)));
            })
            ->get();

        if (count($anniv) > 0) {
            $benefits_data = MemberBenefits::select(
                'member_benefits.member_no',
                'member_benefits.category',
                'member_benefits.benefit as benefit_code',
                'benefit.benefit',
                'member_benefits.anniv',
                'member_benefits.limit',
                'member_benefits.sub_limit_of as sub_limit_of_code',
                DB::raw('(select benefit from benefit where member_benefits.sub_limit_of = benefit.code) as sub_limit_of'),
                't_product_name.product_name',
                'benefit_sharing.sharing'
            )
                ->leftJoin('benefit', 'benefit.code', '=', 'member_benefits.benefit')
                ->leftJoin('benefit_sharing', 'benefit_sharing.code', '=', 'member_benefits.sharing')
                ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_benefits.product_name')
                ->where('member_no', $request->member_no)
                ->where('member_benefits.anniv', $anniv[0]['anniv'])
                ->orderBy('benefit.benefit', 'asc')
                ->get();

            //member info  cancelation status
            $member_info_cancelation_status = MemberInfo::select(DB::raw("isnull(cancelled,0) as cancelled"))
                ->where('member_no', $request->member_no)
                ->get();
            $member_info_cancelation_status =  $member_info_cancelation_status[0]->cancelled;
            $cancelled_date = $ever_cancelled_status = $current_cancelled_status = 0;
            if ($member_info_cancelation_status != 0) {
                $ever_cancelled_status = MemberCancellation::select(DB::raw('count(*) as cancelled'))
                    ->where('member_no', $request->member_no)
                    ->get();
                $ever_cancelled_status = $ever_cancelled_status[0]->cancelled;
                if (intval($ever_cancelled_status) > 0) {
                    $cancelled_date = MemberCancellation::select(DB::raw('max(date_can) as date_can'), 'cancelled')
                        ->where('member_no', $request->member_no)
                        ->groupBy('cancelled')
                        ->get();
                    $current_cancelled_status = $cancelled_date[0]->cancelled;
                    $cancelled_date = $cancelled_date[0]->date_can;
                }
            }

            //provider cancellation status 
            $provider_status = '';
            $provider_data = Provider::select('fax_no', 'end_date', 'cancel_suspend_date')->where('code', $request->provider)->get();
            $provider_cancelled_status = count($provider_data) != 0 ? $provider_data[0]->fax_no : 0;
            $provider_cancel_suspend_date = count($provider_data) != 0 ? $provider_data[0]->cancel_suspend_date   : '';
            $provider_end_date = count($provider_data) != 0 ? date("Y-m-d", strtotime($provider_data[0]->end_date . "+1 day")) : '';
            if ($provider_cancelled_status != 'ACTIVE' && !empty($provider_cancel_suspend_date)) {
                if ($request->invoice_date > $provider_cancel_suspend_date) {
                    $provider_status = '2';
                } else {
                    $provider_status = '1';
                }
            } else {
                $provider_status = '1';
            }

            return [
                'anniv' => $anniv[0]['anniv'],
                'benefits' => $benefits_data,
                'ever_cancelled_status' => $ever_cancelled_status,
                'current_cancelled_status' => $current_cancelled_status,
                'cancelled_date' => $cancelled_date,
                'provider_status' => $provider_status,
                'provider_cancel_suspend_date' => $provider_cancel_suspend_date,
            ];
        } else {
            return [
                // 'message' => 'Member has no cover dates. Change Invoice Date',
                'message' => 'Invoice date falls when member was not on cover!',
                'dat' => date('Y-m-d', strtotime($request->invoice_date)),
                'dats' => $request->invoice_date,
                'anniv' => $anniv,
                'benefits' => $benefits_data
            ];
        }
    }

    //fetch all batch number for selected provider
    public function fetchProviderBatches($provider)
    {
        $data_arr = array();
        $data = ClaimsBatch::select('batch_no')
            ->distinct('batch_no')
            ->where('provider', $provider)
            ->get();
        // return $data;

        if (count($data) > 0) {
            foreach ($data as $datum) {
                if (is_null($datum->batch_no) || $datum->batch_no === "0") {
                    array_push($data_arr, null);
                } else {
                    $batch_no = explode('-', $datum->batch_no);
                    if (count($batch_no) < 2) {
                        $batch_no = $datum->batch_no;
                    } else {
                        $batch_no =  $batch_no[1];
                    }
                    array_push($data_arr, $batch_no);
                }
            }
        }
        return $data_arr;
    }

    public function fetchBatchesForProvider($provider)
    {
        $data = ClaimsBatch::select(
            'batch_no',
            DB::raw("CONCATbatch_no, ' (', date_received,')') as batch_details")
        )
            ->where('provider', $provider)
            ->orderBy('date_received', 'desc')
            ->get();
        return $data;
    }

    //fetch batch number date received
    public function fetchBatchNoDateReceived($batch_no, $provider)
    {
        $batch_no = 'BAT-' . $batch_no;
        //return $batch_no;
        $data_arr = [];
        $data = ClaimsBatch::select('date_received')
            ->where('batch_no', $batch_no)
            ->where('provider', $provider)
            ->distinct()
            ->get();

        if (count($data) > 0) {
            return $data;
        } else {
            return $data_arr;
        }
    }
    //fetch batch number date received new
    public function fetchBatchNoDateReceivedNew($batch_no, $provider)
    {
        //return $batch_no;
        $data = ClaimsBatch::select('date_received')
            ->where('batch_no', $batch_no)
            ->where('provider', $provider)
            ->distinct()
            ->get();

        return $data;
    }
    //fetch batch number date received for unresolved
    public function fetchBatchNoDateReceivedUnresolved($batch_no, $provider)
    {
        $data_arr = [];
        $data = ClaimsBatch::select('date_received')
            ->where('batch_no', $batch_no)
            ->where('provider', $provider)
            ->distinct()
            ->get();

        if (count($data) > 0) {
            return $data;
        } else {
            return $data_arr;
        }
    }

    //fetch product name from benefit selected
    public function fetchProductName(Request $request)
    {
        $message_arr = array();
        $corp_id = MemberInfo::where('member_info.member_no', $request->member_no)
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
            ->pluck('principal_applicant.corp_id');

        $product_name = MemberBenefits::select(
            'member_benefits.product_name as product_code',
            't_product_name.product_name',
            'member_benefits.sub_limit_of',
            DB::raw('isnull(member_benefits.suspended, 0) as suspended'),
            DB::raw('isnull(member_benefits.waiting_period, 0) as waiting_period')
        )
            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_benefits.product_name')
            ->where('member_benefits.member_no', $request->member_no)
            ->where('member_benefits.anniv', $request->anniv)
            ->where('member_benefits.benefit', $request->benefit)
            ->get();


        //check if member has pre auths
        $pre_auth = PreAuthorization::select('*')
            ->where('member_no', $request->member_no)
            ->where('provider', $request->provider)
            ->where('authority_type', $request->benefit)
            ->where('anniv', $request->anniv)
            ->get();

        //determine if benefit is in fund
        $category = PrincipalApplicant::select('principal_applicant.category')
            ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->where('member_info.member_no', $request->member_no)
            ->get();

        $fund_data = CorpGroups::select(DB::raw('isnull(fund, 0) as fund'))
            ->where('anniv', $request->anniv)
            ->where('benefit', $request->benefit)
            ->where('category', $category[0]['category'])
            ->where('corp_id', $corp_id[0])
            ->get();

        $fund = count($fund_data) != 0 ?  $fund_data[0]['fund'] : null;

        //ensure the member is claiming after the benefits waiting period
        $waiting_period = $product_name[0]['waiting_period'];

        //ensure the provider was not cancelled when claiming 


        $member_anniv_date = MemberAnniversary::select('start_date')
            ->where('anniv', $request->anniv)
            ->where('member_no', $request->member_no)
            ->get();


        return [
            'product' => $product_name[0],
            'pre_auth' => $pre_auth,
            'fund' => $fund,
            'waiting_period' => intval($waiting_period),
            'cover_start_date' => $member_anniv_date[0]['start_date']
        ];
    }

    //fetch frames
    public function fetchFrames(Request $request)
    {
        //return $request;
        $bill_max_anniv = Bills::select(DB::raw("max(anniv) as anniv"))
            ->where('member_no', $request->member_no)
            //->where('service', 40)
            ->where('claim_nature', 36)
            ->get();
        $bill_max_anniv = $bill_max_anniv[0]->anniv;

        if ($bill_max_anniv != null) {
            $frames = $bill_max_anniv - number_format($request->anniv);

            if ($frames <= 1) {
                return [
                    'message' => 'Notice, Frames Cannot be given in two consecutive anniversary.
                    This member received frames in anniv ' . $bill_max_anniv
                ];
            }
        } else {
            return [
                'message' => 'Notice, This member has not received frames in anniv ' . number_format($request->anniv) . '... Proceed'
            ];
        }
    }

    //save enter bill
    public function saveEnterBill(Request $request)
    {
        // return $request;
        try {
            //check if entry exist
            $service = Bills::select('*')
                ->where('service', $request->service)
                ->where('invoice_no', $request->invoice_no)
                ->where('invoice_date', $request->invoice_date)
                ->where('member_no', $request->member_no)
                ->get();

            if (count($service) !== 0) {
                return ['Notice! A similar invoice exists..cannot proceed. Please rectify'];
            } else {
                $ll_last = LastNumber::where('task', 4)->get('last_no');
                $ll_last = count($ll_last) !== 0 ? $ll_last[0]->last_no : 0;
                $ll_last += 1;


                //create batch no
                $batch_no = $request->batch;

                $family_no = explode('-', $request->member_no);
                $family_no = $family_no[0];
                $corp_id = PrincipalApplicant::select('corp_id')->where('family_no', $family_no)->get();
                $corp_id = count($corp_id) !== 0 ? $corp_id[0]->corp_id : '';
                $max_id = Bills::max('id') + 1;

                /********************SAVING MANUAL BILL********************/
                //saving a line item on same claim 
                if ($request->claim_no != '') {
                    $claim_no = $request->claim_no;
                    DB::transaction(function () use ($request, $claim_no, $batch_no, $family_no, $corp_id, $max_id) {
                        //set inserted, updated, and deleted items
                        $inserted_item = $claim_no;
                        $updated_item =  null;
                        $deleted_item = null;

                        Bills::create([
                            'CLAIM_NO' => $claim_no,
                            'INVOICE_NO' => $request->invoice_no,
                            'CLAIM_FORM_NO' => $claim_no,
                            'PROVIDER' => $request->provider,
                            'MEMBER_NO' => $request->member_no,
                            'SERVICE' => $request->service,
                            'CLAIM_NATURE' => $request->benefit,
                            'INVOICE_DATE' => $request->invoice_date,
                            'INVOICED_AMOUNT' => $request->invoiced_amount,
                            'BATCH_NO' => $batch_no,
                            'USER_ID' => $request->user,
                            'DATE_ENTERED' => today(),
                            'anniv' => $request->anniv,
                            'fund' => $request->fund,
                            'date_received' => $request->date_received,
                            'pre_auth_no' => $request->pre_auth_no,
                            'family_no' => $family_no,
                            'corp_id' => $corp_id,
                            'id' => $max_id,
                            'visit_date' => $request->visit_date,
                            'doc_date' => $request->doctor_date,
                            'claim_form' => $claim_no,
                            // 'integ' => 1,
                            // 'source' => 'Smart',
                            'REFUND' => (int)$request->pay_to,
                        ]);

                        //save access log
                        app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    });
                    return ['Claims Saved Successfully with claim no ' . $claim_no, $claim_no];
                    //saving a new bill
                } else {
                    $claim_no = 'CLM' . ' ' . $ll_last;

                    DB::transaction(function () use ($request, $claim_no, $batch_no, $family_no, $corp_id, $max_id) {
                        //set inserted, updated, and deleted items
                        $inserted_item = $claim_no;
                        $updated_item =  null;
                        $deleted_item = null;

                        Bills::create([
                            'CLAIM_NO' => $claim_no,
                            'INVOICE_NO' => $request->invoice_no,
                            'CLAIM_FORM_NO' => $claim_no,
                            'PROVIDER' => $request->provider,
                            'MEMBER_NO' => $request->member_no,
                            'SERVICE' => $request->service,
                            'CLAIM_NATURE' => $request->benefit,
                            'INVOICE_DATE' => $request->invoice_date,
                            'INVOICED_AMOUNT' => $request->invoiced_amount,
                            'BATCH_NO' => $batch_no,
                            'USER_ID' => $request->user,
                            'DATE_ENTERED' => today(),
                            'anniv' => $request->anniv,
                            'fund' => $request->fund,
                            'date_received' => $request->date_received,
                            'pre_auth_no' => $request->pre_auth_no,
                            'family_no' => $family_no,
                            'corp_id' => $corp_id,
                            'sub_bene',
                            'category',
                            'product_name',
                            'id' => $max_id,
                            'visit_date' => $request->visit_date,
                            'doc_date' => $request->doctor_date,
                            'claim_form' => $claim_no,
                            // 'integ' => 1,
                            // 'source' => 'Smart',
                            'REFUND' => (int)$request->pay_to,
                        ]);

                        //save claims tab into table claim form
                        ClaimForm::create([
                            'CLAIM_NO' => $claim_no,
                            'VISIT_DATE' => $request->visit_date,
                            'DOCTOR_SIGN' => $request->doctor_signed,
                            'DOCTOR_DATE' => $request->doctor_date,
                            'CLAIM_FORM_SIGNED' => $request->patient_signed,
                            'DATE_ADMITTED' => $request->date_admitted,
                            'DATE_DISCHARGED' => $request->date_discharged,
                            'USER_ID' => $request->user,
                            'DATE_ENTERED' => today(),
                        ]);

                        //increment last number
                        LastNumber::where('TASK', 4)->increment('LAST_NO');

                        //save access log
                        app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    });
                    return ['Claims Saved Successfully with claim no ' . $claim_no, $claim_no];
                }
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    //save enter bill
    public function saveEnterBillLineItems(Request $request)
    {
        // return $request;
        try {
            //check if entry exist
            $service = Bills::select('*')
                ->where('service', $request->service)
                ->where(
                    'invoice_no',
                    $request->invoice_no
                )
                ->where('invoice_date', $request->invoice_date)
                ->where('member_no', $request->member_no)
                ->get();

            if (count($service) !== 0) {
                return ['Notice! A similar invoice exists..cannot proceed. Please rectify'];
            } else {
                //check if diagnosis exists
                if (isset($request->diagnosis_idx)) {
                    $ll_last = LastNumber::where('task', 4)->get('last_no');
                    $ll_last = count($ll_last) !== 0 ? $ll_last[0]->last_no : 0;
                    $ll_last += 1;


                    //create batch no
                    $batch_no = $request->batch;

                    $family_no = explode('-', $request->member_no);
                    $family_no = $family_no[0];
                    // $corp_id = MemberInfo::where('member_no', $request->member_no)->get('corp_id');
                    // $corp_id = $corp_id[0]['corp_id'];
                    $corp_id = PrincipalApplicant::select('corp_id')->where('family_no', $family_no)->get();
                    $corp_id = count($corp_id) !== 0 ? $corp_id[0]->corp_id : '';
                    $max_id = Bills::max('id') + 1;

                    /********************SAVING MANUAL BILL********************/
                    //saving a service on same claim 
                    if (
                        $request->claim_no != ''
                    ) {
                        $claim_no = $request->claim_no;
                        DB::transaction(function () use ($request, $claim_no, $batch_no, $family_no, $corp_id, $max_id) {
                            //set inserted, updated, and deleted items
                            $inserted_item = $claim_no;
                            $updated_item =  null;
                            $deleted_item = null;
                            Bills::create([
                                'CLAIM_NO' => $claim_no,
                                'INVOICE_NO' => $request->invoice_no,
                                'CLAIM_FORM_NO' => $claim_no,
                                'PROVIDER' => $request->provider,
                                'MEMBER_NO' => $request->member_no,
                                'SERVICE' => $request->service,
                                'CLAIM_NATURE' => $request->benefit,
                                'INVOICE_DATE' => $request->invoice_date,
                                'INVOICED_AMOUNT' => $request->invoiced_amount,
                                'BATCH_NO' => $batch_no,
                                'USER_ID' => $request->user,
                                'DATE_ENTERED' => today(),
                                'anniv' => $request->anniv,
                                'fund' => $request->fund,
                                'date_received' => $request->date_received,
                                'pre_auth_no' => $request->pre_auth_no,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'id' => $max_id,
                                'visit_date' => $request->visit_date,
                                'doc_date' => $request->doctor_date,
                                'claim_form' => $claim_no,
                                // 'integ' => 1,
                                // 'source' => 'Smart',
                                'REFUND' => (int)$request->pay_to,
                            ]);

                            //saving line items 
                            if (isset($request->item_code_price_list)) {
                                for ($i = 0; $i < count($request->item_code_price_list); $i++) {

                                    $unit_of_measure = is_null($request->unit_of_measure_price_list[$i]) ? '' : $request->unit_of_measure_price_list[$i];
                                    //check if line item exists
                                    $exists = LineItems::where('item_code', $request->item_code_price_list[$i])
                                        ->where('invoice_no', $request->invoice_no_price_list[$i])
                                        ->where('service', $request->service)
                                        ->where('provider', $request->provider)
                                        ->get();
                                    if (count($exists) === 0) {
                                        $year = TariffYear::where('year', $request->year_price_list[$i])->pluck('code');
                                        $year =  $year[0];
                                        LineItems::create([
                                            'item_code' => $request->item_code_price_list[$i],
                                            'invoice_no' => $request->invoice_no_price_list[$i],
                                            'provider' => $request->provider,
                                            'category' => $request->category_price_list[$i],
                                            'service' => $request->service,
                                            'service_items' => $request->service_items_price_list[$i],
                                            'unit_of_measure' => $unit_of_measure,
                                            'invoiced_amt' => $request->invoiced_amt_price_list[$i],
                                            'date_entered' => $request->date_entered_price_list[$i],
                                            // 'item_class' => $request->item_class_price_list[$i],
                                            'user_id' => $request->user,
                                            'year' => $year,
                                            'quantity' => $request->quantity_price_list[$i],
                                            'adjusted_amt' => 0,
                                            'recomm_amt' => 0,
                                            'variation' => 0,
                                            'claim_no' => $claim_no,
                                            'smart_bill_id' => 0,
                                            'auto_vetted' => 0,
                                            'parent_id' => 0
                                        ]);
                                    }
                                }
                            }

                            //save access log
                            app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                        });
                        return ['Claims Saved Successfully with claim no ' . $claim_no, $claim_no];
                        //saving a service on a new bill
                    } else {
                        $claim_no = 'CLM' . ' ' . $ll_last;

                        DB::transaction(function () use ($request, $claim_no, $batch_no, $family_no, $corp_id, $max_id) {
                            //set inserted, updated, and deleted items
                            $inserted_item = $claim_no;
                            $updated_item =  null;
                            $deleted_item = null;

                            Bills::create([
                                'CLAIM_NO' => $claim_no,
                                'INVOICE_NO' => $request->invoice_no,
                                'CLAIM_FORM_NO' => $claim_no,
                                'PROVIDER' => $request->provider,
                                'MEMBER_NO' => $request->member_no,
                                'SERVICE' => $request->service,
                                'CLAIM_NATURE' => $request->benefit,
                                'INVOICE_DATE' => $request->invoice_date,
                                'INVOICED_AMOUNT' => $request->invoiced_amount,
                                'BATCH_NO' => $batch_no,
                                'USER_ID' => $request->user,
                                'DATE_ENTERED' => today(),
                                'anniv' => $request->anniv,
                                'fund' => $request->fund,
                                'date_received' => $request->date_received,
                                'pre_auth_no' => $request->pre_auth_no,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'sub_bene',
                                'category',
                                'product_name',
                                'id' => $max_id,
                                'visit_date' => $request->visit_date,
                                'doc_date' => $request->doctor_date,
                                'claim_form' => $claim_no,
                                // 'integ' => 1,
                                // 'source' => 'Smart',
                                'REFUND' => (int)$request->pay_to,
                            ]);

                            //save claims tab into table claim form
                            ClaimForm::create([
                                'CLAIM_NO' => $claim_no,
                                'VISIT_DATE' => $request->visit_date,
                                'DOCTOR_SIGN' => $request->doctor_signed,
                                'DOCTOR_DATE' => $request->doctor_date,
                                'CLAIM_FORM_SIGNED' => $request->patient_signed,
                                'DATE_ADMITTED' => $request->date_admitted,
                                'DATE_DISCHARGED' => $request->date_discharged,
                                'USER_ID' => $request->user,
                                'DATE_ENTERED' => today(),
                            ]);

                            //saving line items 
                            if (isset($request->item_code_price_list)) {
                                for ($i = 0; $i < count($request->item_code_price_list); $i++) {

                                    $unit_of_measure = is_null($request->unit_of_measure_price_list[$i]) ? '' : $request->unit_of_measure_price_list[$i];
                                    //check if line item exists
                                    $exists = LineItems::where('item_code', $request->item_code_price_list[$i])
                                        ->where('invoice_no', $request->invoice_no_price_list[$i])
                                        ->where('service', $request->service)
                                        ->where('provider', $request->provider)
                                        ->get();

                                    if (count($exists) === 0) {
                                        $year = TariffYear::where('year', $request->year_price_list[$i])->pluck('code');
                                        $year =  $year[0];
                                        LineItems::create([
                                            'item_code' => $request->item_code_price_list[$i],
                                            'invoice_no' => $request->invoice_no_price_list[$i],
                                            'provider' => $request->provider,
                                            'category' => $request->category_price_list[$i],
                                            'service' => $request->service,
                                            'service_items' => $request->service_items_price_list[$i],
                                            'unit_of_measure' => $unit_of_measure,
                                            'invoiced_amt' => $request->invoiced_amt_price_list[$i],
                                            'date_entered' => $request->date_entered_price_list[$i],
                                            // 'item_class' => $request->item_class_price_list[$i],
                                            'user_id' => $request->user,
                                            'year' => $year,
                                            'quantity' => $request->quantity_price_list[$i],
                                            'adjusted_amt' => 0,
                                            'recomm_amt' => 0,
                                            'variation' => 0,
                                            'claim_no' => $claim_no,
                                            'smart_bill_id' => 0,
                                            'auto_vetted' => 0,
                                            'parent_id' => 0
                                        ]);
                                    }
                                }
                            }

                            //save member diagnosis if it exists
                            for ($i = 0; $i < count($request->diagnosis_idx); $i++) {
                                $exists = MemberDiagnosis::where('claim_no', $claim_no)
                                    ->where('member_no', $request->member_no)
                                    ->where('diagnosis', $request->diagnosis_idx[$i])
                                    ->get();

                                if (count($exists) === 0) {
                                    MemberDiagnosis::create([
                                        'claim_no' => $claim_no,
                                        'member_no' => $request->member_no,
                                        'diagnosis' => (int)$request->diagnosis_idx[$i]
                                    ]);
                                }
                            }

                            //increment last number
                            LastNumber::where('TASK', 4)->increment('LAST_NO');

                            //save access log
                            app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                        });
                        return ['Claims Saved Successfully with claim no ' . $claim_no, $claim_no];
                    }
                } else {
                    return ['Please include diagnosis!'];
                }
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function replaceSlash($date)
    {
        return str_replace("/", "-", $date);
    }
}
