<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\ProviderCapitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CapitationCalculationController extends Controller
{
    public function fetchCapitationProviders()
    {
        $providers = ProviderCapitation::select(
            'provider.provider',
            'provider.code'
        )
            ->join('provider', 'provider.code', '=', 'provider_capitation.provider')
            ->get();
            return $providers;
    }


    public function fetchCapitationCalculation(Request $request)
    {
        if (isset($request->provider) && isset($request->month)) {
            $to_pay = $to_pay_op_amounts = $to_pay_average = $op_avg = 0;
            $explode = explode('/', $request->month);
            $month = $explode[1] . $explode[0];

            $op_prov_data = Bills::select(
                DB::raw('isnull(sum(isnull(bills.invoiced_amount, 0)), 0) as op_inv_amt'),
                DB::raw("count(distinct bills.claim_no) as op_visits")
            )
                ->join('benefit', 'bills.claim_nature', '=', 'benefit.code')
                ->where('benefit.bene_class', 2)
                ->where(DB::raw("FORMAT(bills.invoice_date, 'YYYYmm')"), $month)
                ->where('bills.provider', $request->provider)
                ->get();
            $ip_prov_data = Bills::select(
                DB::raw('isnull(sum(isnull(bills.invoiced_amount, 0)), 0) as ip_inv_amt'),
                DB::raw("count(distinct bills.claim_no) as ip_visits")
            )
                ->join('benefit', 'bills.claim_nature', '=', 'benefit.code')
                ->where('benefit.bene_class', 1)
                ->where(DB::raw("FORMAT(bills.invoice_date, 'YYYYmm')"), $month)
                ->where('bills.provider', $request->provider)
                ->get();

            $op_inv_amt = count($op_prov_data) != 0 ?  $op_prov_data[0]->op_inv_amt : 0;
            $ip_inv_amt = count($ip_prov_data) != 0 ?  $ip_prov_data[0]->ip_inv_amt : 0;
            $op_visits = count($op_prov_data) != 0 ?  $op_prov_data[0]->op_visits : 0;
            $ip_visits = count($ip_prov_data) != 0 ?  $ip_prov_data[0]->ip_visits : 0;

            $prov_capitation = ProviderCapitation::select('capitation')->where('provider', $request->provider)->get();
            $prov_capitation = count($prov_capitation) != 0 ? $prov_capitation[0]->capitation : 0;

            //average op
            if ($op_inv_amt != 0 && $op_visits != 0) {
                $op_avg = $op_inv_amt /  $op_visits;
            }

            //ttl claimed
            $ttl_claimed = $op_inv_amt + $ip_inv_amt;
            //calculate to pay
            if ($op_avg <= $prov_capitation) {
                $to_pay = $ttl_claimed;
                $to_pay_op_amounts = $op_inv_amt;
                $to_pay_average = $op_avg;
            } else {
                $to_pay = ($prov_capitation * $op_visits) + $ip_inv_amt;
                $to_pay_op_amounts = $prov_capitation * $op_visits;
                $to_pay_average = $prov_capitation;
            }



            $tbl = "
          
                <tr><td>TOTAL CLAIMED</td><td>" . number_format($ttl_claimed, 2) . "</td><td>" . number_format($to_pay, 2) . "</td></tr>
                <tr><td>OP VISITS</td><td>$op_visits</td><td>$op_visits</td></tr>
                <tr><td>OP AMOUNTS</td><td>" . number_format($ttl_claimed, 2) . "</td><td>" . number_format($to_pay_op_amounts, 2) . "</td></tr>
                <tr><td>AVERAGE</td><td>" . number_format($op_avg, 2) . "</td><td>" . number_format($prov_capitation, 2) . "</td></tr>
                <tr><td>IP VISITS</td><td>$ip_visits</td><td>$ip_visits</td></tr>
                <tr><td>IP AMOUNTS</td><td>" . number_format($ip_inv_amt, 2) . "</td><td>" . number_format($ip_inv_amt, 2) . "</td></tr>
                <tr><td>OVERALL PAYABLE</td><td></td><td>" . number_format($to_pay, 2) . "</td></tr>
            
            ";
            $dt = [
                'ttl_claimed' => number_format($ttl_claimed, 2),
                'to_pay' => number_format($to_pay, 2),
                'op_visits' => $op_visits,
                'to_pay_op_amounts' => number_format($to_pay_op_amounts, 2),
                'op_avg' => number_format($op_avg, 2),
                'to_pay_average' => number_format($prov_capitation, 2),
                'ip_visits' => $ip_visits,
                'ip_inv_amt' => number_format($ip_inv_amt, 2),
            ];

            return $dt;
        }
    }
}
