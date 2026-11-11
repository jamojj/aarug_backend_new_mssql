<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillVet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimsStatusDetailedController extends Controller
{
    public function fetchClaimsStatusDetailed(Request $request)
    {
        ini_set('max_execution_time', '0');
        $data = [];
        $ttl_invoiced_amt = $ttl_amt_payable = $ttl_deduction_amt = 0;
        if (isset($request->options) && isset($request->month)) {
            $month = explode('/', $request->month);
            $month = $month[1] . $month[0];
            switch ($request->options) {
                case '1':
                    //pending vetting
                    $data = Bills::select(
                        DB::raw("
                        CASE 
                                WHEN isnull(bills.vet_status, 0) = 0 THEN 'PENDING' 
                                WHEN isnull(bills.vet_status, 0) = 1 THEN 'APPROVED' 
                                WHEN isnull(bills.vet_status, 0) = 2 THEN 'REJECTED'
                                WHEN isnull(bills.vet_status, 0) = 3 THEN 'SUSPENDED'
                        END AS vet_status
                        "),
                        DB::raw("
                        CASE 
                                WHEN isnull(bills.paid, 0) = 1 THEN 'PAID'                                  
                        END AS paid
                        "),
                        'bills.id',
                        'bills.claim_no',
                        'bills.member_no',
                        'provider.provider',
                        'service.code as service_code',
                        'service.service',
                        'benefit.benefit',
                        'bills.invoice_no',
                        'bills.invoice_date',
                        'bills.invoiced_amount',
                        'bills.deduction_amount',
                        'bills.amount_payable',
                        'bills.voucher_no',
                        'bills.voucher_date',
                        'bills.voucher_user',
                        'bills.payment_no',
                        'msa',
                        'source',
                        'bills.batch_no'
                    )
                        ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                        ->leftJoin('service', 'service.code', '=', 'bills.service')
                        ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                        ->orderBy('bills.claim_no', 'ASC')
                        ->where(DB::raw("isnull(bills.vet_status, 0)"), 0)
                        ->where(DB::raw("FORMAT(bills.invoice_date, 'yyyymm')"), $month)
                        ->get();

                    break;
                case '2':
                    //pending voucher
                    $data = Bills::select(
                        DB::raw("
                        CASE 
                                WHEN isnull(bills.vet_status, 0) = 0 THEN 'PENDING' 
                                WHEN isnull(bills.vet_status, 0) = 1 THEN 'APPROVED' 
                                WHEN isnull(bills.vet_status, 0) = 2 THEN 'REJECTED'
                                WHEN isnull(bills.vet_status, 0) = 3 THEN 'SUSPENDED'
                        END AS vet_status
                        "),
                        DB::raw("
                        CASE 
                                WHEN isnull(bills.paid, 0) = 1 THEN 'PAID' ELSE 'NOT PAID'                                
                        END AS paid
                        "),
                        'bills.id',
                        'bills.claim_no',
                        'bills.member_no',
                        'provider.provider',
                        'service.code as service_code',
                        'service.service',
                        'benefit.benefit',
                        'bills.invoice_no',
                        'bills.invoice_date',
                        'bills.invoiced_amount',
                        'bills.deduction_amount',
                        'bills.amount_payable',
                        'bills.voucher_no',
                        'bills.voucher_date',
                        'bills.voucher_user',
                        'bills.payment_no',
                        'msa',
                        'source',
                        'bills.batch_no'
                    )
                        ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                        ->leftJoin('service', 'service.code', '=', 'bills.service')
                        ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                        ->orderBy('bills.claim_no', 'ASC')
                        ->where(DB::raw("isnull(bills.vet_status, 0)"), '!=', 0)
                        ->where(DB::raw("isnull(bills.voucher_no, '')"), '')
                        ->where(DB::raw("isnull(bills.paid, 0)"), 0)
                        ->where(DB::raw("FORMAT(bills.invoice_date, 'yyyymm')"), $month)
                        ->get();
                    break;
                case '3':
                    //pending paid
                    $data = Bills::select(
                        DB::raw("
                        CASE 
                                WHEN isnull(bills.vet_status, 0) = 0 THEN 'PENDING' 
                                WHEN isnull(bills.vet_status, 0) = 1 THEN 'APPROVED' 
                                WHEN isnull(bills.vet_status, 0) = 2 THEN 'REJECTED'
                                WHEN isnull(bills.vet_status, 0) = 3 THEN 'SUSPENDED'
                        END AS vet_status
                        "),
                        DB::raw("
                        CASE 
                                WHEN isnull(bills.paid, 0) = 1 THEN 'PAID' ELSE 'NOT PAID'                                
                        END AS paid
                        "),
                        'bills.id',
                        'bills.claim_no',
                        'bills.member_no',
                        'provider.provider',
                        'service.code as service_code',
                        'service.service',
                        'benefit.benefit',
                        'bills.invoice_no',
                        'bills.invoice_date',
                        'bills.invoiced_amount',
                        'bills.deduction_amount',
                        'bills.amount_payable',
                        'bills.voucher_no',
                        'bills.voucher_date',
                        'bills.voucher_user',
                        'bills.payment_no',
                        'msa',
                        'source',
                        'bills.batch_no'
                    )
                        ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                        ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                        ->leftJoin('service', 'service.code', '=', 'bills.service')
                        ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                        ->orderBy('bills.claim_no', 'ASC')
                        ->where(DB::raw("isnull(bills.voucher_no, '')"), '!=', '')
                        ->where(DB::raw("isnull(bills.paid, 0)"), 1)
                        ->whereNull('bills.payment_no')
                        ->where(DB::raw("FORMAT(bills.invoice_date, 'yyyymm')"), $month)
                        ->get();
                    break;
            }
        }
        foreach ($data as $dt) {
            // if ($dt->vet_status != 'PENDING') {
            //     $vet_bill_data = BillVet::select('user_id', 'date_entered')
            //         ->where('service', $dt->service_code)
            //         ->where('claim_no', $dt->claim_no)
            //         ->get();
            //     $vuser = count($vet_bill_data) != 0 ? $vet_bill_data[0]->user_id : '';
            //     $vdate = count($vet_bill_data) != 0 ? $vet_bill_data[0]->date_entered : '';
            //     $dt->msa = $vuser;
            //     $dt->source = $vdate;
            // } else {
            // }
            $vet_bill_data = BillVet::select('user_id', 'date_entered')
                ->where('service', $dt->service_code)
                ->where('claim_no', $dt->claim_no)
                ->get();
            $vuser = count($vet_bill_data) != 0 ? $vet_bill_data[0]->user_id : '';
            $vdate = count($vet_bill_data) != 0 ? $vet_bill_data[0]->date_entered : '';
            $dt->msa = $vuser;
            $dt->source = $vdate;
            // $dt->msa = '';
            // $dt->source = '';

            $ttl_invoiced_amt += $dt->invoiced_amount;
            $ttl_amt_payable += $dt->amount_payable;
            $ttl_deduction_amt += $dt->deduction_amount;
        }
        $count = count($data);
        return [
            'response' => $data,
            'count' => number_format($count, 0),
            'totals' => [
                'ttl_invoiced_amt' => number_format(
                    $ttl_invoiced_amt,
                    2
                ),
                'ttl_amt_payable' => number_format(
                    $ttl_amt_payable,
                    2
                ),
                'ttl_deduction_amt' => number_format($ttl_deduction_amt, 2)
            ]

        ];
    }
}
