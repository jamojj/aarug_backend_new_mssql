<?php

namespace App\Http\Controllers\Bills\BillPayment;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillsPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueryChequeController extends Controller
{
    //fetch cheques
    public function fetchCheques()
    {
        $cheques = BillsPayment::select('cheque_no')
            ->distinct()
            ->orderBy('cheque_no', 'ASC')
            ->get();
        $cheques = json_encode($cheques, JSON_INVALID_UTF8_IGNORE);
        return $cheques;
    }

    public function fetchClaimsPerChequeNo($cheque_no)
    {
        $invoiced_total = 0;
        $payable_total = 0;
        $deduction_total = 0;

        $claims = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_names"),
            DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            DB::raw("FORMAT(bills_payment.cheque_date, 'yyyy-mm-dd') as cheque_date"),
            'bills.id',
            'bills.member_no',
            'bills.claim_no',
            'bills.invoice_no',
            'benefit.code',
            'benefit.benefit',
            'bills.paid',
            'bills.invoiced_amount',
            'bills.deduction_amount',
            'bills.deduction_reason',
            'bills.deduction_notes',
            'bills.amount_payable',
            'bills.payment_no',
            'bills.voucher_no',
            'bills_payment.cheque_no',
            'bills_payment.cheque_amount',
            'provider.code as provider_code',
            'provider.provider'

        )
            ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->where('bills_payment.cheque_no', $cheque_no)
            ->where('paid', 1)
            ->where('vet_status', 1)
            ->get();

        foreach ($claims as $claim) {
            $invoiced_total += $claim->invoiced_amount;
        }
        foreach ($claims as $claim) {
            $payable_total += $claim->amount_payable;
        }
        foreach ($claims as $claim) {
            $deduction_total += $claim->deduction_amount;
        }

        $data = [
            'claims' => $claims,
            'invoiced_total' => number_format($invoiced_total, 2),
            'payable_total' => number_format($payable_total, 2),
            'deduction_total' => number_format($deduction_total, 2)
        ];

        return $data;
    }
}
