<?php

namespace App\Http\Controllers\Bills\BillPayment;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueryPaidVoucherController extends Controller
{
    //fetch paid vouchers
    public function fetchPaidVouchers()
    {
        $vouchers = Bills::select('voucher_no')
            ->distinct()
            ->where('paid', 1)
            ->where('vet_status', 1)
            ->orderBy('voucher_no', 'ASC')
            ->get();
        return $vouchers;
    }

    public function fetchClaimsPerVoucherNo($voucher_no)
    {
        $voucher_no = 'PV' . $voucher_no;
        $invoiced_total = 0;
        $payable_total = 0;
        $deduction_total = 0;

        $voucher_exists = Bills::where('bills.voucher_no', $voucher_no)->get();
        $voucher_already_paid = Bills::where(DB::raw("isnull(bills.payment_no, 0)"), '!=', 0)
            ->where('bills.voucher_no', $voucher_no)
            ->get();

        if (count($voucher_exists) !== 0) {
            if (count($voucher_already_paid) > 0) {
                $claims = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                    DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
                    DB::raw("FORMAT(bills_payment.cheque_date, 'yyyy-mm-dd') as cheque_date"),
                    DB::raw("FORMAT(bills_payment.payrun_date, 'yyyy-mm-dd') as payrun_date"),
                    'bills.id',
                    'bills.member_no',
                    'bills.claim_no',
                    'bills.invoice_no',
                    'service.code as service_code',
                    'service.service',
                    'bills.paid',
                    'bills.fund',
                    'bills.invoiced_amount',
                    'bills.deduction_amount',
                    'bills.deduction_reason',
                    'bills.deduction_notes',
                    'bills.currency',
                    'bills.foreigns',
                    'bills.conversion_rate',
                    'bills.foreign_amt',
                    'bills.amount_payable',
                    'bills.admin_fee',
                    'bills.pre_auth_no',
                    'bills.payment_no',
                    'provider.code as provider_code',
                    'provider.provider',
                    'bills_payment.cheque_no',
                    'bills_payment.cheque_amount',
                    'bills_payment.discounts',
                    'bills_payment.wtax',
                    't_payment_mode.code as payment_code',
                    't_payment_mode.payment_mode',
                    'corporate.corp_id',
                    'corporate.corporate',
                    'bills_payment.bank_account',
                    'banks.bank'

                )
                    ->leftJoin('service', 'bills.service', '=', 'service.code')
                    ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
                    ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
                    ->leftJoin('t_payment_mode', 'bills_payment.payment_mode', '=', 't_payment_mode.code')
                    ->leftJoin('corporate', 'bills.corp_id', '=', 'corporate.corp_id')
                    ->leftJoin('bank_account', 'bills_payment.bank_account', '=', 'bank_account.code')
                    ->leftJoin('banks', 'bank_account.bank_id', '=', 'banks.code')
                    ->where('paid', 1)
                    ->where('vet_status', 1)
                    ->where('bills.voucher_no', $voucher_no)
                    ->get();


                foreach ($claims as $claim) {
                    $invoiced_total += $claim->invoiced_amount;
                    $payable_total += $claim->amount_payable;
                    $deduction_total += $claim->deduction_amount;
                }


                $data = [
                    'claims' => $claims,
                    'invoiced_total' => number_format($invoiced_total, 2),
                    'payable_total' => number_format($payable_total, 2),
                    'deduction_total' => number_format($deduction_total, 2)
                ];
                return $data;
            } else {
                return ['Voucher has not been paid!'];
            }
        } else {
            return ['Voucher does not exist or does not contain claims!'];
        }
    }
}
