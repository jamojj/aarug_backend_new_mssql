<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class RejectionRateController extends Controller
{
    public function fetchRejectionRate(Request $request){
        // return $request;
        // ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', 0);
        $response = [];
        $lc_inv_amount= $lc_rej_amt= $lc_ded_amt = $lc_non_payable = $lc_rej_rate = 0;

        $provider = Bills::select(
            'bills.provider as code', 
            'provider.provider as provider',
            DB::raw("sum(isnull(invoiced_amount,0)) as invoiced_amount"),
            DB::raw("sum(case when isnull(bills.vet_status, 0) = 2 then isnull(bills.invoiced_amount,0) else 0 end) as rejected_amount"),
            DB::raw("sum(case when isnull(bills.vet_status, 0) = 1 then isnull(bills.deduction_amount,0) else 0 end) as deduction_amount"),
            DB::raw("(rejected_amount + deduction_amount) as non_payable"),
            DB::raw("CASE WHEN non_payable = 0 or invoiced_amount = 0 THEN 0 ELSE (non_payable / invoiced_amount) * 100 END as rejection_rate")
            // DB::raw("CASE WHEN non_payable = 0 or invoiced_amount = 0 THEN 0 ELSE ROUND(CAST((non_payable / invoiced_amount) * 100 AS numeric(10, 2)), 2) END as rejection_rate")

            )
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->where('bills.invoice_date', '>=', $request->from)
            ->where('bills.invoice_date', '<=', $request->to)
            ->groupBy('code', 'provider')
            ->orderBy('provider', 'asc')
            ->get();


        // $provider = Bills::select('bills.provider as code', 'provider.provider')
        //     ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
        //     ->where('bills.invoice_date', '>=', $request->from)
        //     ->where('bills.invoice_date', '<=', $request->to)
        //     ->distinct()
        //     ->get();
            // return $provider;
        // foreach($provider as $prov){
        //     $lc_inv_amount = Bills::select(
        //         DB::raw("sum(isnull(invoiced_amount,0)) as invoiced_amount")
        //     )   
        //         ->where('provider', $prov->code)
        //         ->where('bills.invoice_date', '>=', $request->from)
        //         ->where('bills.invoice_date', '<=', $request->to)
        //         ->pluck('invoiced_amount');

        //     $lc_rej_amt = Bills::select(
        //         DB::raw("isnull(sum(isnull(invoiced_amount,0)),0) as rejected_amount")
        //     )   
        //         ->where(DB::raw('isnull(bills.vet_status, 0)'), 2)
        //         ->where('provider', $prov->code)
        //         ->where('bills.invoice_date', '>=', $request->from)
        //         ->where('bills.invoice_date', '<=', $request->to)
        //         ->pluck('rejected_amount');

        //     $lc_ded_amt = Bills::select(
        //         DB::raw("sum(isnull(deduction_amount,0)) as deduction_amount")
        //     )   
        //         ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
        //         ->where('provider', $prov->code)
        //         ->where('bills.invoice_date', '>=', $request->from)
        //         ->where('bills.invoice_date', '<=', $request->to)
        //         ->pluck('deduction_amount');

        //         $lc_non_payable = $lc_rej_amt[0] + $lc_ded_amt[0];
        //         $lc_rej_rate = ($lc_non_payable)/($lc_inv_amount[0])*100;
        //         $lc_rej_rate = number_format($lc_rej_rate, 2);

        //         $dt = [
        //             'provider' => $prov->provider,
        //             'invoiced_amount' => $lc_inv_amount[0],
        //             'rejected_amount' => $lc_rej_amt[0],
        //             'non_payable' => $lc_non_payable,
        //             'rejection_rate' => $lc_rej_rate,
        //         ];
        //         array_push($response, $dt);
        // }
        return json_encode($provider, JSON_INVALID_UTF8_IGNORE);
       
    }
}
