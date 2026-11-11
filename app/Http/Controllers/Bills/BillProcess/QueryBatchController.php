<?php

namespace App\Http\Controllers\Bills\BillProcess;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillVet;
use App\Models\ClaimForm;
use App\Models\Corporate\CorpGroups;
use App\Models\MemberDiagnosis;
use App\Models\MemberProcedure;
use App\Models\PreAuthorization;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\SmartBillReturns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueryBatchController extends Controller
{
    //get all batches to popultae in dropdown - bills payment - query batch
    public function fetchAllBatchNumbers()
    {
        $batch_numbers = Bills::select('batch_no')
            ->distinct()
            ->where(DB::raw('len(trim(batch_no))'), '>', 0)
            ->orderBy('batch_no')
            ->get();

        return $batch_numbers;
    }

    //fetch data on query batch by batch number
    public function queryBatch($batch_no)
    {
        $total_invoiced_amt = $total_amount_payable = 0;
        $batch_no = 'BAT-' . $batch_no;

        $claims = Bills::select(
            'bills.id',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.provider as provider_code',
            'provider.provider as provider',
            'bills.service as service_code',
            'service.service as service',
            'bills.member_no',
            DB::raw('isnull(bills.invoiced_amount, 0) as invoiced_amount'),
            DB::raw('isnull(bills.amount_payable, 0) as amount_payable'),
            'bills.invoice_date',
            'bills.anniv',
            'bills.claim_nature as benefit_code',
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
            'bills.member_no',
            'bills.vet_status',
            'bills.payee',
            'bills.voucher_no',
            'bills_payment.cheque_no',
            'bills_payment.cheque_date',
            'bills.pre_auth_no',
            'bills.batch_no'
        )
            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('bills_payment', 'bills_payment.payment_no', '=', 'bills.payment_no')
            ->where('bills.batch_no', $batch_no)
            ->orderBy('bills.id', 'ASC')
            ->get();

        foreach ($claims as $claim) {
            $total_invoiced_amt += $claim->invoiced_amount;
            $total_amount_payable += $claim->amount_payable;
        }

        return [
            'claims' => json_encode($claims, JSON_INVALID_UTF8_IGNORE),
            'totals' => [
                'total_invoiced_amt' => $total_invoiced_amt,
                'total_amount_payable' => $total_amount_payable
            ],

        ];
    }

    //fetch product name from benefit selected
    public function fetchProductName(Request $request)
    {
        //return $request;
        $message_arr = array();
        $corp_id = MemberInfo::where('member_info.member_no', $request->member_no)
            ->get('corp_id');
        $corp_id = $corp_id[0]['corp_id'];

        //check if bene if suspended
        $ll_susp = MemberBenefits::where('anniv', $request->anniv)
            ->where('benefit', $request->benefit)
            ->where('member_no', $request->member_no)
            ->pluck('suspended');

        //check if member has pre auths
        $pre_auth = PreAuthorization::select('*')
            ->where('member_no', $request->member_no)
            ->where('provider', $request->provider)
            ->where('authority_type', $request->benefit)
            ->count();

        //determine if benefit is in fund
        $category = PrincipalApplicant::select('principal_applicant.category')
            ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->where('member_info.member_no', $request->member_no)
            ->get();
        $category = $category[0]['category'];

        $fund = CorpGroups::select(DB::raw('isnull(fund, 0) as fund'))
            ->where('anniv', $request->anniv)
            ->where('benefit', $request->benefit)
            ->where('category', $category)
            ->where('corp_id', $corp_id)
            ->get();
        $fund = $fund[0]['fund'];

        //determine product name
        $product_name = MemberBenefits::select(
            'member_benefits.product_name as product_code',
            't_product_name.product_name'
        )
            ->distinct()
            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_benefits.product_name')
            ->where('member_benefits.member_no', $request->member_no)
            ->where('member_benefits.anniv', $request->anniv)
            ->get();
        $product_name = $product_name[0];

        //sub bene and waiting period
        $sub_benefit_data = MemberBenefits::select(
            'member_benefits.sub_limit_of',
            DB::raw('isnull(member_benefits.waiting_period, 0) as waiting_period')
        )
            ->where('member_benefits.member_no', $request->member_no)
            ->where('member_benefits.anniv', $request->anniv)
            ->where('member_benefits.benefit', $request->benefit)
            ->get();

        //ensure the member is claiming after the benefits waiting period
        $waiting_period = $sub_benefit_data[0]['waiting_period'];
        $member_start_date = MemberAnniversary::select('start_date')
            ->where('anniv', $request->anniv)
            ->where('member_no', $request->member_no)
            ->get();
        $member_start_date = $member_start_date[0]['start_date'];

        return [
            'suspended' => $ll_susp,
            'product' => $product_name,
            'pre_auth' => $pre_auth,
            'fund' => $fund,
            'waiting_period' => intval($waiting_period),
            'cover_start_date' => $member_start_date
        ];
    }

    //fetch member preAuths
    public function fetchMemberPreauthsQueryBatch(Request $request)
    {
        $pre_auths = PreAuthorization::select(
            'pre_authorization.code',
            'pre_authorization.provider as provider_code',
            'provider.provider',
            'pre_authorization.date_authorized',
            'pre_authorization.authority_type',
            'pre_authorization.available_limit',
            'pre_authorization.admit_days',
            'pre_authorization.reserve',
            'pre_authorization.member_no',
            'pre_authorization.authorized_by',
            'pre_authorization.auth_batch_no'
        )
            ->leftJoin('provider', 'provider.code', '=', 'pre_authorization.provider')
            ->where('pre_authorization.authority_type', $request->benefit)
            ->where('pre_authorization.provider', $request->provider)
            ->where('pre_authorization.member_no', $request->member_no)
            ->get();

        return $pre_auths;
    }

    //save / update query batch
    public function updateQueryBatch(Request $request)
    {
        //return $request;
        $today = date('Y-m-d');
        $ll_cnt = Bills::select('payment_no')
            ->where('id', $request->id)
            ->get();
        $ll_cnt = $ll_cnt[0]['payment_no'];

        
        if ($ll_cnt > 0) {
            //bill already paid
            return ['Notice ! Cannot save changes.... Bill already paid !'];
        } else {
            try {
                DB::transaction(function () use ($request, $today) {
                    //set inserted, updated, and deleted items
                    $inserted_item = null;
                    $updated_item = "id : $request->id, claim_no : $request->inv_claim_no, provider : $request->inv_provider, inv_amt : $request->vet_invoiced_amount, ded_amt : $request->vet_deduction_amount, original_amt_payable : $request->original_amount_payable, final_amt_payable : $request->vet_amount_payable, original_benefit : $request->original_benefit, final_benefit : $request->inv_benefit, vet_status : $request->vet_status, original_user : $request->user";
                    $deleted_item = null;

                    Bills::where(
                        [
                            'id' => $request->id
                        ]
                    )->update([
                        'INVOICE_NO' => $request->inv_invoice_no,
                        'PROVIDER' => $request->inv_provider,
                        'MEMBER_NO' => $request->inv_member_no,
                        'SERVICE' => $request->inv_service,
                        'INVOICE_DATE' => $request->inv_invoice_date,
                        'INVOICED_AMOUNT' => $request->vet_invoiced_amount,
                        'DEDUCTION_AMOUNT' => $request->vet_deduction_amount,
                        'DEDUCTION_REASON' => $request->vet_deduction_reason,
                        'AMOUNT_PAYABLE' => $request->vet_amount_payable,
                        'DEDUCTION_NOTES' => $request->vet_deduction_notes,
                        'DATE_ENTERED' => $today,
                        'USER_ID' => $request->user,
                        'vet_status' => intval($request->vet_status),
                    ]);

                    //update claim nature for all claims with the claim no 
                    Bills::where(
                        'claim_no',
                        $request->inv_claim_no
                    )
                        ->update([
                            'claim_nature' => $request->inv_benefit,
                        ]);

                    //update claim form
                    ClaimForm::where([
                        'claim_no' => $request->inv_claim_no
                    ])->update([
                        'VISIT_DATE' => $request->clm_visit_date,
                        'DOCTOR_SIGN' => $request->clm_doctor_sign,
                        'DOCTOR_DATE' => $request->clm_doctor_date,
                        'CLAIM_FORM_SIGNED' => $request->clm_patient_sign,
                        'DATE_ADMITTED' => $request->clm_date_admitted,
                        'DATE_DISCHARGED' => $request->clm_date_discharged,
                        'USER_ID' => $request->user,
                        'DATE_ENTERED' => $today,
                    ]);

                    //update bill vet
                    BillVet::where([
                        'id' => intval($request->bill_vet_id)
                    ])->update([
                        'invoice_no' => $request->inv_invoice_no,
                        'service' => $request->inv_service,
                        'provider' => $request->inv_provider,
                        'vet_status' => intval($request->vet_status),
                        'reasons' => $request->vet_bill_reason,
                        'remarks' => $request->vet_remarks,
                        'user_id' => $request->user,
                        'date_entered' => $today,
                    ]);

                    //update member diagnosis
                    $member_diag_ll_cnt = MemberDiagnosis::where('claim_no', $request->inv_claim_no)->count();
                    if ($member_diag_ll_cnt > 0) {
                        MemberDiagnosis::where([
                            'claim_no' => $request->inv_claim_no,
                        ])->update([
                            'member_no' =>  $request->inv_member_no
                        ]);
                    }

                    //update member procedure
                    $member_proc_ll_cnt = MemberProcedure::where('claim_no', $request->inv_claim_no)->count();
                    if ($member_proc_ll_cnt > 0) {
                        MemberProcedure::where([
                            'claim_no' => $request->inv_claim_no,
                        ])->update([
                            'member_no' =>  $request->inv_member_no
                        ]);
                    }


                    //save in smart bills return
                    if ($request->inv_benefit !== $request->original_benefit) {
                        $family_no = explode("-", $request->inv_member_no);
                        $family_no = $family_no[0];
                        $corp_id = PrincipalApplicant::select('corp_id')->where('family_no', $family_no)->get();
                        $corp_id =  count($corp_id) !== 0 ?  $corp_id[0]->corp_id : null;

                        //save original bill credit
                        SmartBillReturns::create([
                            'return_amt' => $request->original_amount_payable,
                            'return_code' => 7,
                            'return_reason' => "Benefit Change",
                            'date_entered' => $today,
                            'smart_picked' => 0,
                            'member_no' => $request->inv_member_no,
                            'smart_benefit' => $request->original_benefit,
                            'anniv' => $request->inv_anniv,
                            'service' => (int)$request->vet_service,
                            'provider' => (int)$request->inv_provider,
                            'invoice_no' => $request->inv_invoice_no,
                            'action_code' => 1,
                            'action_name' => "Credit/add",
                            'smart_provider' => (int)$request->inv_provider,
                            'benefit' => $request->original_benefit,
                            'user_id' => $request->change_benefit_user,
                            'sent_to_smart' => 0,
                            'product_name' => $request->inv_product_name,
                            'corp_id' => $corp_id,
                            'family_no' => $family_no,
                            'invoice_date' => $request->inv_invoice_date,
                            'change_benefit_module_name' => $request->module,
                            'claim_changed' => $request->claim_no,
                        ]);

                        //save new bill debit
                        SmartBillReturns::create([
                            'return_amt' => $request->vet_amount_payable,
                            'return_code' => 9,
                            'return_reason' => "Benefit Change",
                            'date_entered' => $today,
                            'smart_picked' => 0,
                            'member_no' => $request->inv_member_no,
                            'smart_benefit' => $request->inv_benefit,
                            'anniv' => $request->inv_anniv,
                            'service' => (int)$request->vet_service,
                            'provider' => (int)$request->inv_provider,
                            'invoice_no' => $request->inv_invoice_no,
                            'action_code' => 2,
                            'action_name' => "Debit/minus",
                            'smart_provider' => (int)$request->inv_provider,
                            'benefit' => $request->inv_benefit,
                            'user_id' => $request->change_benefit_user,
                            'sent_to_smart' => 0,
                            'product_name' => $request->inv_product_name,
                            'corp_id' => $corp_id,
                            'family_no' => $family_no,
                            'invoice_date' => $request->inv_invoice_date,
                            'change_benefit_module_name' => $request->module,
                        ]);
                    }

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                });
                return ['Batch updated successfully!'];
            } catch (\Exception $e) {
                return ['Error' => $e->getMessage()];
            }
        }
    }
    /*
    * *
    query bill payment batch
    * *
    */
    //query batch
    public function billPaymentQueryBatch($batch_no)
    {
        ini_set('max_execution_time', '0');
        $total_invoiced_amt = $total_amount_payable = $total_deduction_amount = 0;
        $batch_no = 'BAT-' . $batch_no;       

        $claims = DB::select("SELECT claim_no, invoice_no,   
         (select provider from provider where code = bills.provider),
		(select service from service where code = bills.service),
         (select benefit from benefit where code = bills.claim_nature),
		bills.member_no,
		(select surname||' '||first_name||' '||other_names from member_info where member_info.member_no = bills.member_no) member_names,
		invoice_date, isnull(bills.invoiced_amount, 0) as invoiced_amount, isnull(bills.deduction_amount, 0) as deduction_amount,
		(select deduct_reason from deduction_reason where code = bills.deduction_reason) deduction_reason,
		deduction_notes, isnull(bills.amount_payable, 0) as amount_payable, pre_auth_no, voucher_no,
		(select cheque_no from bills_payment where bills_payment.payment_no = bills.payment_no),
		(select cheque_date from bills_payment where bills_payment.payment_no = bills.payment_no),
		case 
			when vet_status = 2 then (select resend_reason from t_resend_bill_reason where code =(select top 1 reasons from bill_vet where service = bills.service and claim_no = bills.claim_no order by reasons asc))
		END as reject_reason,
		
		case 
			when vet_status = 2 then (select top 1 remarks from bill_vet where service = bills.service and claim_no = bills.claim_no order by remarks asc)
		END as reject_remarks,

		case	
			when isnull(vet_status, 0) = 0 then 'ENTRY'
			when vet_status = 2 then 'VETTED REJECTED'
			when vet_status = 3 then 'VETTED PEND'
			when vet_status = 5 then 'PENDING APPROVAL'
			when vet_status = 1 and (select payment_no from bills where id = claim_id) > 0 then 'PAID'
			when vet_status = 1 and (select len(trim(voucher_no)) from bills where id = claim_id) > 0 then 'VOUCHERED'
			else 'VETTED APPROVED'
		END as status, bills.id claim_id
    FROM bills
   WHERE batch_no = '$batch_no' ORDER BY claim_no ASC");

        foreach ($claims as $claim) {
            $total_invoiced_amt += $claim['invoiced_amount'];
            $total_amount_payable += $claim['amount_payable'];
            $total_deduction_amount += $claim['deduction_amount'];
        }

        return [
            'claims' => json_encode($claims, JSON_INVALID_UTF8_IGNORE),
            'claims_count' => count($claims),
            'totals' => [
                'total_invoiced_amt' => $total_invoiced_amt,
                'total_amount_payable' => $total_amount_payable,
                'total_deduction_amount' => $total_deduction_amount
            ],

        ];
    }
}
