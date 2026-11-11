<?php

namespace App\Http\Controllers;

use App\Models\Bills;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimsPaymentController extends Controller
{
    public function fetchClaimsPayment(Request $request)
    {
        $date = DateTime::createFromFormat("m/Y", $request->month);
        $month = $date ? $date->format("Ym") : null;
        $ttl_inv_amt = $ttl_amt_payable = $ttl_deductions = 0;
        $count = 0;

        $report = Bills::select(
            'provider.provider',
            'claims_batch.batch_no',
            'claims_batch.date_submitted',
            'bills_payment.cheque_date',
            'bills_payment.cheque_no',
            'bills.voucher_no',
            DB::raw("sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount"),
            DB::raw("sum(isnull(bills.amount_payable, 0)) as amount_payable"),
            DB::raw("sum(isnull(bills.deduction_amount, 0)) as deduction_amount"),
        )
            ->join('provider', 'provider.code', '=', 'bills.provider')
            ->join('claims_batch', 'claims_batch.batch_no', '=', 'bills.batch_no')
            ->leftJoin('bills_payment', 'bills_payment.payment_no', '=', 'bills.payment_no')
            ->where(DB::raw("isnull(bills.paid, 0)"), '!=', 0)
            ->where(DB::raw("FORMAT(bills.invoice_date, 'YYYYMM')"), $month)
            ->groupBy(
                'provider.provider',
                'claims_batch.batch_no',
                'claims_batch.date_submitted',
                'bills_payment.cheque_date',
                'bills_payment.cheque_no',
                'bills_payment.cheque_amount',
                'bills.voucher_no'
            )

            ->orderBy('provider.provider', 'asc')
            ->get();

        foreach ($report as $dt) {
            $ttl_inv_amt += $dt->invoiced_amount;
            $ttl_amt_payable += $dt->amount_payable;
            $ttl_deductions += $dt->deduction_amount;
        }

        $count = count($report);

        $totals = ['ttl_inv_amt' => number_format($ttl_inv_amt, 2), 'ttl_amt_payable' => number_format($ttl_amt_payable,0), 'ttl_deductions' => number_format($ttl_deductions, 0)];

        return ['report' => $report, 'totals' => $totals, 'count' => number_format($count, 0)];
    }
}
