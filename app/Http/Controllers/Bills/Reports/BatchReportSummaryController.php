<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchReportSummaryController extends Controller
{
    public function BatchReportSummary($batch_no)
    {
        ini_set('max_execution_time', '0');
        $total_invoiced_amt = $total_amount_payable = $total_deduction_amount = 0;
        $batch_no = 'BAT-' . $batch_no;

        $claims = DB::select("SELECT distinct claim_no, invoice_no,   
         (select provider from provider where code = bills.provider),
         (select benefit from benefit where code = bills.claim_nature),
		bills.member_no,
		(select surname||' '||first_name||' '||other_names from member_info where member_info.member_no = bills.member_no) member_names,
		invoice_date, isnull(sum(isnull(bills.invoiced_amount, 0)), 0) as invoiced_amount, 
        isnull(sum(isnull(bills.deduction_amount, 0)), 0) as deduction_amount,		
		 isnull(sum(isnull(bills.amount_payable, 0)), 0) as amount_payable, 
         voucher_no,
		(select cheque_no from bills_payment where bills_payment.payment_no = bills.payment_no),
		(select cheque_date from bills_payment where bills_payment.payment_no = bills.payment_no)	
		
    FROM bills
   WHERE batch_no = '$batch_no' 
   GROUP BY claim_no, provider, claim_nature, member_no, member_names, invoice_date, voucher_no, payment_no, invoice_no
   ORDER BY claim_no ASC"
   );

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
