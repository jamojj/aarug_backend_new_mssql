<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherBatchingController extends Controller
{
    public function fetchVoucherBatchBatches($provider)
    {
        $batches = Bills::select('batch_no')
            ->distinct('batch_no')
            ->whereNotNull('voucher_no')
            ->whereNull('payment_no')
            ->whereNotNull('batch_no')
            ->where('provider', $provider)
            ->get();

        return $batches;
    }

    public function fetchVoucherBatchData($batch_no)
    {

        $ttl_invd = $ttl_deduct = $ttl_amt_pybl = 0;
        $data = Bills::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(bills.date_received, 'yyyy-mm-dd') as date_received"),
            DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            DB::raw("case when bills.paid = 1 then 'PAID' else 'NOT PAID' END as paid"),
            'member_info.member_no',
            'bills.claim_no',
            'bills.invoice_no',
            'provider.provider',
            'service.service',
            'benefit.benefit',
            'bills.ip_doctor',
            'bills.invoiced_amount',
            'bills.deduction_amount',
            'deduction_reason.deduct_reason',
            'bills.amount_payable',
            'bills.voucher_no',
            'bills.payment_no',
            'bills.batch_no',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
            ->leftJoin('deduction_reason', 'deduction_reason.code', '=', 'bills.deduction_reason')
            ->orderBy('principal_name', 'ASC')
            ->where('bills.batch_no', $batch_no)
            ->get();

        foreach ($data as $dt) {
            $ttl_invd += $dt->invoiced_amount;
            $ttl_deduct += $dt->deduction_amount;
            $ttl_amt_pybl += $dt->amount_payable;
        }

        return [
            'report_data' => json_encode($data, JSON_INVALID_UTF8_IGNORE),
            'ttl_invd' => $ttl_invd,
            'ttl_deduct' => $ttl_deduct,
            'ttl_amt_pybl' => $ttl_amt_pybl,
        ];
    }
}
