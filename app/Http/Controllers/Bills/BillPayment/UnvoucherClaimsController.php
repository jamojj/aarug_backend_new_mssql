<?php

namespace App\Http\Controllers\Bills\BillPayment;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\Corporate\Corporate;
use App\Models\LastNumber;
use App\Models\MemberDiagnosis;
use App\Models\Provider;
use App\Models\Registration\PrincipalApplicant;
use App\Models\unvoucheredClaims;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class UnvoucherClaimsController extends Controller
{
    //fetch all voucher numbers
    public function fetchAllVoucherNumbers()
    {
        $vouchers = Bills::select('voucher_no')
            ->distinct()
            ->where(DB::raw('isnull(payment_no, 0)'), 0)
            ->where('voucher_no', '>', '"')
            ->orderBy('voucher_no', 'ASC')
            ->get();
        return $vouchers;
    }

    //fetch all claims to unvoucher
    public function fetchClaimsToUnvoucher($voucher_no)
    {
        $st_prov_mem = [];
        //if refund to provider
        $data = Provider::select('provider.provider', 'bills.provider as prov_code', 'bills.voucher_no')
            ->distinct('provider.provider')
            ->leftJoin('bills', 'bills.provider', '=', 'provider.code')
            ->where(DB::raw('isnull(bills.refund, 0)'), 0)
            ->where('bills.voucher_no', $voucher_no)
            ->get();

        if (count($data) != 0) {
            for ($i = 0; $i < count($data); $i++) {
                array_push($st_prov_mem, $data[$i]['provider']);
            }
        }
        if (isEmpty($st_prov_mem)) {
            //if refund to corporate
            $data = Corporate::select('corporate.corporate')
                ->distinct()
                ->leftJoin('principal_applicant', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                ->leftJoin('bills', 'bills.member_no', '=', 'member_info.member_no')
                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                ->where(DB::raw('isnull(bills.refund, 0)'), 1)
                ->where('bills.voucher_no', $voucher_no)
                ->get();
            if (count($data) != 0) {
                for ($i = 0; $i < count($data); $i++) {
                    array_push($st_prov_mem, $data[$i]['corporate']);
                }
            }
            if (isEmpty($st_prov_mem)) {
                //if refund to member
                $datum = PrincipalApplicant::select(
                    'corporate.corporate',
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as member_names")
                )
                    ->distinct()
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('bills', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                    ->where(DB::raw('isnull(bills.refund, 0)'), 2)
                    ->where('bills.voucher_no', $voucher_no)
                    ->get();


                if (count($datum) != 0) {
                    $data = ['datum' => $datum];
                    //return $data['datum'][0]['member_names']. ' OF '. $data['datum'][0]['corporate'];
                    $dt = $data['datum'][0]['member_names'] . ' OF ' . $data['datum'][0]['corporate'];

                    array_push($st_prov_mem, $dt);
                }
            }
        }


        $total_invoiced_amt = $total_amount_payable = $total_deducted_amt = $total_admin_fee = 0;
        $claims = Bills::select(
            'bills.id',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.service as service_code',
            'service.service',
            'bills.paid',
            'bills.invoiced_amount',
            'bills.deduction_amount',
            'bills.deduction_reason as deduction_reason_code',
            'deduction_reason.deduct_reason',
            'bills.deduction_notes',
            'bills.amount_payable',
            'bills.invoice_date',
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
            'bills.foreigns',
            'bills.currency as currency_code',
            'conversion_rates.currency',
            'bills.conversion_rate as rate',
            't_exchange_rate.rate as conversion_rate',
            'bills.foreign_amt',
            'bills.member_no',
            'bills.admin_fee',
            'bills.provider as prov_code',
            'provider.provider',
            'bills.claim_nature as benefit_code',
            'benefit.benefit',
            'bills.ip_doctor',
            'bills.voucher_no',
            'bills.payment_no',
            'bills.vet_status',
            'bills.pre_auth_no',
            'bills.diag_code',
        )
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->leftJoin('deduction_reason', 'deduction_reason.code', '=', 'bills.deduction_reason')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('conversion_rates', 'conversion_rates.idx', '=', 'bills.currency')
            ->leftJoin('t_exchange_rate', 't_exchange_rate.rate', '=', 'bills.conversion_rate')
            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
            ->where('bills.voucher_no', $voucher_no)
            ->get();

        foreach ($claims as $claim) {
            //fetch diagnosis
            $diag_string = '';
            $diagnosis = MemberDiagnosis::join('diagnosis', 'diagnosis.code', '=', 'member_diagnosis.diagnosis')
                ->where('claim_no', $claim->claim_no)
                ->pluck('diagnosis.diagnosis');

            if (count($diagnosis) !== 0) {
                foreach ($diagnosis as $key => $value) {
                    if ($key == 0) {
                        $diag_string .= $value;
                    } else {
                        $diag_string .= " , $value";
                    }
                }
                $claim->diag_code = $diag_string;
            } else {
                $claim->diag_code = '';
            }

            //get totals
            $total_invoiced_amt += $claim->invoiced_amount;
            $total_deducted_amt += $claim->deduction_amount;
            $total_amount_payable += $claim->amount_payable;
            $total_admin_fee += $claim->admin_fee;
        }

        return [
            'claims' => $claims,
            'claims_count' => count($claims),
            'provider_member' => $st_prov_mem,
            'totals' => [
                'total_invoiced_amt' => $total_invoiced_amt,
                'total_deduction_amount' => $total_deducted_amt,
                'total_amount_payable' => $total_amount_payable,
                'total_admin_fee' => $total_admin_fee
            ],
        ];
    }

    //save unvoucher claims
    public function saveUnvoucherClaims(Request $request)
    {
        // return $request;
        $un_voucher_date = date("Y-m-d");

        try {
            //return $request;
            //set inserted, updated, and deleted items
            $inserted_item = null;
            $updated_item = "voucher_no : $request->voucher_no, unvoucher_date : $un_voucher_date";
            $deleted_item = null;

            for ($i = 0; $i < count($request->id); $i++) {

                $claim_data = Bills::where('id',  $request->id[$i])->get();
                $claim_data =  $claim_data[0];
                if ($request->paid[$i] == '1') {
                    //only unvoucher claims that have not gone to finance with integ and integ date as null
                    if (is_null($claim_data->integ) && is_null($claim_data->integ_date)) {
                        DB::transaction(function () use ($request, $un_voucher_date, $claim_data, $i, $inserted_item, $updated_item, $deleted_item) {
                            //create entry in unvoucher claim 
                            unvoucheredClaims::create([
                                'claim_no' => $claim_data->CLAIM_NO,
                                'provider' => $claim_data->PROVIDER,
                                'batch_no' => $claim_data->BATCH_NO,
                                'voucher_no' => $claim_data->VOUCHER_NO,
                                'voucher_user' => $claim_data->voucher_user,
                                'voucher_date' => $claim_data->voucher_date,
                                'unvoucher_user' => $request->user_id,
                                'unvoucher_date' => $un_voucher_date
                            ]);

                            //update bills table
                            Bills::where([
                                'id' => intval($request->id[$i])
                            ])->update([
                                'paid' => null,
                                'voucher_user' =>  null,
                                'voucher_no' =>  null,
                                'voucher_date' =>  null
                            ]);
                            //save access log
                            app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                        });
                    }
                }
            }


            return ['message' => 'Save Ok. Claims Unvouchered Successfully'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
