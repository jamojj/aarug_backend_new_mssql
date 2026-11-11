<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CignaPremiumInvoiceDetailsSummary;
use App\Models\PremiumInvoiceCigna;
use Illuminate\Http\Request;

class QueryCignaPremiumDebitsController extends Controller
{
    public function fetchCignaDebits($family_no)
    {
        $data = PremiumInvoiceCigna::select('invoice_no')->where('family_no', $family_no)->get();
        return $data;
    }

    public function fetchCignaDebitDetails($invoice_no)
    {
        $individual_premiums = CignaPremiumInvoiceDetailsSummary::where('invoice_no', $invoice_no)->get();
        foreach ($individual_premiums as $premium) {
            $premium->plan_premium = number_format($premium->plan_premium, 2);
            $premium->dental_premium = number_format($premium->dental_premium, 2);
        }
        $debit_totals = PremiumInvoiceCigna::where('invoice_no', $invoice_no)->get();
        foreach ($debit_totals as $total) {
            $total->stamp_duty = number_format($total->stamp_duty, 2);
            $total->iiu = number_format($total->iiu, 2);
            $total->premium_net = number_format($total->premium_net, 2);
            $total->total_premium = number_format($total->total_premium, 2);
        }

        return ['individual_premiums' => $individual_premiums, 'debit_totals' => $debit_totals[0]];
    }
}
