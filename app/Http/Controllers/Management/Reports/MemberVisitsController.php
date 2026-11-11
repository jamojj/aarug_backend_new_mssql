<?php

namespace App\Http\Controllers\Management\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberVisitsController extends Controller
{
    public function fetchProviderAverageCost(Request $request)
    {
        $report = [];
        $from = date("Y-m-d", strtotime($request->from));
        $to = date("Y-m-d", strtotime($request->to));
        $ttl_claim_amt = $ttl_visits = $ttl_ac_visit = 0;

        switch ($request->options) {
                // outpatient
            case '1':
                $report = Bills::select(
                    DB::raw("isnull(sum(isnull(bills.invoiced_amount, 0)), 0) as claim_amt"),
                    DB::raw("count(distinct(bills.claim_no)) as visits"),
                    DB::raw("claim_amt/visits as ac_visit"),
                    'provider.provider'
                )
                    ->leftJoin('provider', 'provider.code', 'bills.provider')
                    ->orderBy('claim_amt', 'DESC')
                    ->groupBy(['provider.provider'])
                    ->whereBetween('bills.invoice_date', [$from, $to])
                    ->whereIn('bills.claim_nature', [38, 4, 8, 60, 26, 20, 9, 44])
                    ->get();

                foreach ($report as $repo) {
                    $ttl_claim_amt += $repo->claim_amt;
                    $ttl_visits += $repo->visits;
                    $ttl_ac_visit += $repo->ac_visit;
                    $repo->ac_visit = number_format($repo->ac_visit, 2);
                }
                break;
                // inpatient
            case '2':
                $report = Bills::select(
                    DB::raw("isnull(sum(isnull(bills.invoiced_amount, 0)), 0) as claim_amt"),
                    DB::raw("count(distinct(bills.claim_no)) as visits"),
                    DB::raw("claim_amt/visits as ac_visit"),
                    'provider.provider'
                )
                    ->leftJoin('provider', 'provider.code', 'bills.provider')
                    ->orderBy('claim_amt', 'DESC')
                    ->groupBy(['provider.provider'])
                    ->whereBetween('bills.invoice_date', [$from, $to])
                    ->whereIn('bills.claim_nature', [24, 7, 27, 45, 3, 59, 35, 43, 13, 1, 61])
                    ->get();

                foreach ($report as $repo) {
                    $ttl_claim_amt += $repo->claim_amt;
                    $ttl_visits += $repo->visits;
                    $ttl_ac_visit += $repo->ac_visit;
                    $repo->ac_visit = number_format($repo->ac_visit, 2);
                }
                break;
                // dental/optical
            case '3':
                $report = Bills::select(
                    DB::raw("isnull(sum(isnull(bills.invoiced_amount, 0)), 0) as claim_amt"),
                    DB::raw("count(distinct(bills.claim_no)) as visits"),
                    DB::raw("claim_amt/visits as ac_visit"),
                    'provider.provider'
                )
                    ->leftJoin('provider', 'provider.code', 'bills.provider')
                    ->orderBy('claim_amt', 'DESC')
                    ->groupBy(['provider.provider'])
                    ->whereBetween('bills.invoice_date', [$from, $to])
                    ->whereIn('bills.claim_nature', [11, 36])
                    ->get();

                foreach ($report as $repo) {
                    $ttl_claim_amt += $repo->claim_amt;
                    $ttl_visits += $repo->visits;
                    $ttl_ac_visit += $repo->ac_visit;
                    $repo->ac_visit = number_format($repo->ac_visit, 2);
                }
                break;
            case '4':
                $report = Bills::select(
                    DB::raw("isnull(sum(isnull(bills.invoiced_amount, 0)), 0) as claim_amt"),
                    DB::raw("count(distinct(bills.claim_no)) as visits"),
                    DB::raw("claim_amt/visits as ac_visit"),
                    'provider.provider'
                )
                    ->leftJoin('provider', 'provider.code', 'bills.provider')
                    ->orderBy('claim_amt', 'DESC')
                    ->groupBy(['provider.provider'])
                    ->whereBetween('bills.invoice_date', [$from, $to])
                    ->whereIn('bills.claim_nature', [38, 4, 8, 60, 26, 20, 9, 44, 11, 36])
                    ->get();

                foreach ($report as $repo) {
                    $ttl_claim_amt += $repo->claim_amt;
                    $ttl_visits += $repo->visits;
                    $ttl_ac_visit += $repo->ac_visit;
                    $repo->ac_visit = number_format($repo->ac_visit, 2);
                }
                break;
        }

        return [
            'report' => $report,
            'totals' => [
                'ttl_claim_amt' => number_format($ttl_claim_amt, 2),
                'ttl_visits' => $ttl_visits,
                'ttl_ac_visit' => number_format($ttl_ac_visit, 2)
            ]
        ];
    }
}
