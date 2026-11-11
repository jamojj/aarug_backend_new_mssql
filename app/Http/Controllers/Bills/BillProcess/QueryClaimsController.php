<?php

namespace App\Http\Controllers\Bills\BillProcess;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillVet;
use App\Models\ClaimsBatch;
use App\Models\LineItems;
use App\Models\MemberDiagnosis;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\SmartBillReturns;
use App\Models\TariffYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDOException;

class QueryClaimsController extends Controller
{
    //fetch claims by claim no
    public function fetchClaims(Request $request)
    {
        //  return $request;
        $response = array();
        $total_inv_amt = $total_amt_payable = 0;
        $claim_no = 'CLM' . ' ' . $request->claim;
        switch ($request->task_selected) {
            case "1":
                if (isset($request->claim)) {
                    $claims = Bills::select(
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                        DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                        DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                        'bills.claim_no',
                        'bills.invoice_no',
                        'provider.provider',
                        'provider.code',
                        'bills.member_no',
                        'service.code',
                        'service.service',
                        'bills.invoice_date',
                        'bills.deduction_amount',
                        'bills.deduction_reason',
                        'deduction_reason.deduct_reason',
                        'bills.voucher_no',
                        'bills.batch_no',
                        'bills.payee',
                        'bills.pre_auth_no',
                        'bills.id',
                        't_product_name.code',
                        't_product_name.product_name',
                        'bills.smart_bill_id',
                        'benefit.code',
                        'benefit.benefit',
                        'bills.anniv',
                        'bills.date_entered',
                        'bills.integ',
                        'bills.user_id',
                        'bills_payment.cheque_no',
                        'bills_payment.cheque_date',

                    )
                        ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
                        ->leftJoin('service', 'bills.service', '=', 'service.code')
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
                        ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
                        ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
                        ->leftJoin('deduction_reason', 'bills.deduction_reason', '=', 'deduction_reason.code')
                        ->where('bills.claim_no', $claim_no)
                        ->get();

                    foreach ($claims as $claim) {

                        $total_inv_amt += $claim->invoiced_amount;
                        $total_amt_payable += $claim->amount_payable;
                    }

                    $data = [
                        'claims' => $claims,
                        'total_inv_amt' => number_format($total_inv_amt, 2),
                        'total_amt_payable' => number_format($total_amt_payable, 2)
                    ];
                    array_push($response, $data);
                    return $response;
                }
                break;
            case "2":
                if (isset($request->invoice)) {
                    $claims = Bills::select(
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                        DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                        DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                        'bills.claim_no',
                        'bills.invoice_no',
                        'provider.provider',
                        'provider.code',
                        'bills.member_no',
                        'service.code',
                        'service.service',
                        'bills.invoice_date',
                        'bills.deduction_amount',
                        'bills.deduction_reason',
                        'deduction_reason.deduct_reason',
                        'bills.voucher_no',
                        'bills.batch_no',
                        'bills.payee',
                        'bills.pre_auth_no',
                        'bills.id',
                        't_product_name.code',
                        't_product_name.product_name',
                        'bills.smart_bill_id',
                        'benefit.code',
                        'benefit.benefit',
                        'bills.anniv',
                        'bills.date_entered',
                        'bills.integ',
                        'bills.user_id',
                        'bills_payment.cheque_no',
                        'bills_payment.cheque_date',

                    )
                        ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
                        ->leftJoin('service', 'bills.service', '=', 'service.code')
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
                        ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
                        ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
                        ->leftJoin('deduction_reason', 'bills.deduction_reason', '=', 'deduction_reason.code')
                        ->where('bills.invoice_no', $request->invoice)
                        ->get();

                    foreach ($claims as $claim) {
                        $total_inv_amt += $claim->invoiced_amount;
                        $total_amt_payable += $claim->amount_payable;
                    }

                    $data = [
                        'claims' => $claims,
                        'total_inv_amt' => number_format($total_inv_amt, 2),
                        'total_amt_payable' => number_format($total_amt_payable, 2)
                    ];
                    array_push($response, $data);
                    return $response;
                }
                break;
        }
    }

    public function fetchClaimRowById($id)
    {

        $claim = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("isnull(bills.paid, 0) as paid"),
            DB::raw("FORMAT(bills.date_entered, 'yyyy-mm-dd') as date_entered"),
            DB::raw("FORMAT(bill_vet.date_entered, 'yyyy-mm-dd') as vet_date"),
            DB::raw("FORMAT(bills.date_received, 'yyyy-mm-dd') as date_received"),
            DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            DB::raw("FORMAT(claim_form.visit_date, 'yyyy-mm-dd') as visit_date"),
            DB::raw("FORMAT(claim_form.doctor_date, 'yyyy-mm-dd') as doctor_date"),
            DB::raw("FORMAT(claim_form.date_admitted, 'yyyy-mm-dd') as date_admitted"),
            DB::raw("FORMAT(claim_form.date_discharged, 'yyyy-mm-dd') as date_discharged"),
            'bills.service as service_code',
            'service.service',
            'bills.provider as provider_code',
            'provider.provider',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.member_no',
            'bills.invoiced_amount',
            'bills.amount_payable',
            'bills.refund',
            'bills.payee',
            'bills.voucher_no',
            'bills.payment_no',
            'bills.id',
            't_product_name.code',
            'bills.product_name as product_code',
            't_product_name.product_name',
            'bills.batch_no',
            'bills.smart_bill_id',
            'bills.pre_auth_no',
            'bills.claim_nature as benefit_code',
            'benefit.code',
            'benefit.benefit',
            'bills.anniv',
            'bills.integ',
            'bills.user_id as bills_user_id',
            'bill_vet.user_id as bill_vet_user_id',
            'bills.id',
            'bills.claim_form',
            'member_info.gender as gender_code',
            't_gender.gender',
            'family_relation.relation',
            'member_info.relation_to_principal as family_title_code',
            'bills.fund',
            'claim_form.doctor_sign',
            'claim_form.claim_form_signed as patient_signed',
            'bills.vet_status',
            'bills.deduction_amount',
            'bills.deduction_reason',
            'deduction_reason.deduct_reason',
            'bills.deduction_notes',
            't_resend_bill_reason.resend_reason',
            't_resend_bill_reason.code as reason_code',
            'bill_vet.remarks'
        )
            ->leftJoin('bill_vet', 'bill_vet.claim_no', '=', 'bills.claim_no')
            ->leftJoin('t_resend_bill_reason', 't_resend_bill_reason.code', '=', 'bill_vet.reasons')
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
            ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
            ->leftJoin('claim_form', 'bills.claim_no', '=', 'claim_form.claim_no')
            ->leftJoin('deduction_reason', 'bills.deduction_reason', '=', 'deduction_reason.code')
            ->where('bills.id', $id)
            ->get();

        return $claim;
    }

    //fetch billvet vet status for each bill id
    public function fetchBillVetStatus(Request $request)
    {
        $ll_vet_stat = BillVet::select('vet_status', 'reasons', 'id')
            ->where('claim_no', $request->claim_no)
            ->where('invoice_no', $request->invoice_no)
            ->where('service', $request->service)
            ->where('provider', $request->provider)
            ->get();
        return $ll_vet_stat;
    }

    public function fetchSearchedMember(Request $request)
    {
        try {
            $member = MemberInfo::select(
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as full_name"),
                'member_info.member_no',
            )
                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->where(function ($q) use ($request) {
                    $q->where('member_info.surname', 'like', '%' . $request->member . '%')
                        ->orWhere('member_info.first_name', 'like', '%' . $request->member . '%')
                        ->orWhere('member_info.other_names', 'like', '%' . $request->member . '%');
                })
                ->where('principal_applicant.corp_id', $request->corp_id)
                ->get();
            return $member;
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function fetchClaimsByMemberNo($member_no)
    {
        $response = array();
        $total_inv_amt = $total_amt_payable = 0;
        $claims = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
            DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
            'bills.claim_no',
            'bills.invoice_no',
            'provider.provider',
            'provider.code',
            'bills.member_no',
            'service.code',
            'service.service',
            'bills.invoice_date',
            'bills.deduction_amount',
            'bills.deduction_reason',
            'deduction_reason.deduct_reason',
            'bills.voucher_no',
            'bills.batch_no',
            'bills.payee',
            'bills.pre_auth_no',
            'bills.id',
            't_product_name.code',
            't_product_name.product_name',
            'bills.smart_bill_id',
            'benefit.code',
            'benefit.benefit',
            'bills.anniv',
            'bills.date_entered',
            'bills.integ',
            'bills.user_id',
            'bills_payment.cheque_no',
            'bills_payment.cheque_date',

        )
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
            ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
            ->leftJoin('deduction_reason', 'bills.deduction_reason', '=', 'deduction_reason.code')
            ->where('bills.member_no', $member_no)
            ->get();

        foreach ($claims as $claim) {
            $total_inv_amt += $claim->invoiced_amount;
            $total_amt_payable += $claim->amount_payable;
        }

        $data = [
            'claims' => $claims,
            'total_inv_amt' => number_format($total_inv_amt, 2),
            'total_amt_payable' => number_format($total_amt_payable, 2)
        ];
        array_push($response, $data);
        return $response;
    }
    //fetch batch number date received
    public function fetchBatchNoDateReceived($batch_no, $provider)
    {
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
    //fetch batch number date received two
    public function fetchBatchNoDateReceivedTwo($batch_no)
    {
        $data = ClaimsBatch::select('date_received', 'provider')
            ->where('batch_no', $batch_no)
            ->get();

        return $data;
    }
    //fetch batch number date received two
    public function fetchBatchNoDateReceivedThree($batch_no)
    {
        $batch_no = 'BAT-' . $batch_no;
        $data = ClaimsBatch::select('date_received', 'provider')
            ->where('batch_no', $batch_no)
            ->get();

        return $data;
    }

    public function saveQueryClaims(Request $request)
    {
        // return $request;
        $date_entered = date("Y-m-d");
        try {
            //get previous service and provider
            $prev_bill_data = Bills::select('service', 'provider')->where('id', $request->id)->get();
            $prev_bill_data = $prev_bill_data[0];

            DB::transaction(function () use ($request, $date_entered, $prev_bill_data) {
                //set inserted, updated, and deleted items
                $inserted_item = null;
                $updated_item = "id : $request->id, claim_no : $request->claim_no, provider : $request->provider, inv_amt : $request->invoiced_amount, ded_amt : $request->deduction_amount, original_amt_payable : $request->original_amount_payable, final_amt_payable : $request->amount_payable, original_benefit : $request->original_benefit, final_benefit : $request->benefit, vet_status : $request->vet_status, original_user : $request->user_id, updating_user : $request->change_benefit_user";
                $deleted_item = null;


                Bills::where(
                    [
                        'id' => $request->id
                    ]
                )
                    ->update([
                        'CLAIM_NO' => $request->claim_no,
                        'INVOICE_NO' => $request->invoice_no,
                        'CLAIM_FORM_NO' => $request->claim_no,
                        'PROVIDER' => $request->provider,
                        'MEMBER_NO' => $request->inv_member_no,
                        'SERVICE' => $request->service,
                        'INVOICE_DATE' => $request->inv_invoice_date,
                        'INVOICED_AMOUNT' => $request->invoiced_amount,
                        'DEDUCTION_AMOUNT' => $request->deduction_amount,
                        'DEDUCTION_REASON' => $request->deduction_reason,
                        'AMOUNT_PAYABLE' => $request->amount_payable,
                        'DEDUCTION_NOTES' => $request->deduction_notes,
                        'BATCH_NO' => $request->batch_no,
                        'USER_ID' => $request->user_id,
                        'anniv' => $request->anniv,
                        'fund' => $request->fund,
                        'date_received' => $request->date_received,
                        'vet_status' => $request->vet_status,
                        'smart_bill_id' => $request->smart_bill_id,
                        'pre_auth_no' => $request->pre_auth_no,
                        'id' => $request->id,
                        'visit_date' => $request->visit_date,
                        'doc_date' => $request->doctor_date,
                    ]);

                //update claim nature for all claims with the claim no 
                Bills::where(
                    'claim_no',
                    $request->claim_no
                )
                    ->update([
                        'claim_nature' => $request->benefit,
                    ]);

                //update member diagnosis  
                if (isset($request->diagnosis_idx)) {
                    for (
                        $i = 0;
                        $i < count($request->diagnosis_idx);
                        $i++
                    ) {
                        $exists = MemberDiagnosis::where('claim_no', $request->claim_no)
                            ->where('member_no', $request->inv_member_no)
                            ->where('diagnosis', $request->diagnosis_idx[$i])
                            ->get();

                        if (count($exists) === 0) {
                            MemberDiagnosis::create([
                                'claim_no' => $request->claim_no,
                                'member_no' => $request->inv_member_no,
                                'diagnosis' => (int)$request->diagnosis_idx[$i]
                            ]);
                        }
                    }
                }

                //update line items
                if (isset($request->line_item_idx)) {
                    if (count($request->line_item_idx) > 0) {
                        for ($i = 0; $i < count($request->line_item_idx); $i++) {
                            $invoiced_amt = $request->line_item_unit_price[$i] * $request->line_item_quantity[$i];
                            LineItems::where('idx', $request->line_item_idx[$i])->update([
                                'invoiced_amt' => $invoiced_amt,
                                'quantity' => $request->line_item_quantity[$i],
                            ]);
                        }
                    }
                }

                //saving newly added line items 
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
                                'claim_no' => $request->claim_no,
                                'smart_bill_id' => 0,
                                'auto_vetted' => 0,
                                'parent_id' => 0
                            ]);
                        }
                    }
                }

                //save in smart bills return
                if ($request->benefit !== $request->original_benefit) {
                    $family_no = explode("-", $request->inv_member_no);
                    $family_no = $family_no[0];
                    $corp_id = PrincipalApplicant::select('corp_id')->where('family_no', $family_no)->get();
                    $corp_id =  count($corp_id) !== 0 ?  $corp_id[0]->corp_id : null;

                    //save original bill credit
                    SmartBillReturns::create([
                        'return_amt' => $request->original_amount_payable,
                        'return_code' => 7,
                        'return_reason' => "Benefit Change",
                        'date_entered' => $date_entered,
                        'smart_picked' => 0,
                        'member_no' => $request->inv_member_no,
                        'smart_benefit' => $request->original_benefit,
                        'anniv' => $request->anniv,
                        'service' => (int)$request->service,
                        'provider' => (int)$request->provider,
                        'invoice_no' => $request->invoice_no,
                        'action_code' => 1,
                        'action_name' => "Credit/add",
                        'smart_provider' => (int)$request->provider,
                        'benefit' => $request->original_benefit,
                        'user_id' => $request->change_benefit_user,
                        'sent_to_smart' => 0,
                        'product_name' => $request->product_name,
                        'corp_id' => $corp_id,
                        'family_no' => $family_no,
                        'invoice_date' => $request->inv_invoice_date,
                        'change_benefit_module_name' => $request->module,
                        'claim_changed' => $request->claim_no,
                    ]);

                    //save new bill debit
                    SmartBillReturns::create([
                        'return_amt' => $request->amount_payable,
                        'return_code' => 9,
                        'return_reason' => "Benefit Change",
                        'date_entered' => $date_entered,
                        'smart_picked' => 0,
                        'member_no' => $request->inv_member_no,
                        'smart_benefit' => $request->benefit,
                        'anniv' => $request->anniv,
                        'service' => (int)$request->service,
                        'provider' => (int)$request->provider,
                        'invoice_no' => $request->invoice_no,
                        'action_code' => 2,
                        'action_name' => "Debit/minus",
                        'smart_provider' => (int)$request->provider,
                        'benefit' => $request->benefit,
                        'user_id' => $request->change_benefit_user,
                        'sent_to_smart' => 0,
                        'product_name' => $request->product_name,
                        'corp_id' => $corp_id,
                        'family_no' => $family_no,
                        'invoice_date' => $request->inv_invoice_date,
                        'change_benefit_module_name' => $request->module,
                    ]);
                }

                //update bill vet
                BillVet::where(['claim_no' => $request->claim_no, 'service' => $prev_bill_data->service])->update([
                    'invoice_no' => $request->invoice_no,
                    'service' => $request->service,
                    'provider' => $request->provider,
                    'vet_status' => $request->vet_status,
                    'remarks' => $request->remarks,
                    'reasons' => $request->bill_reason,
                ]);


                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });
            return  ['Claims Saved Successfully'];
        } catch (PDOException $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
