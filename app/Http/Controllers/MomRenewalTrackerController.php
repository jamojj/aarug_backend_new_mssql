<?php

namespace App\Http\Controllers;

use App\Models\Corporate\CorpAnniversary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MomRenewalTrackerController extends Controller
{
    public function fetchMomRenewalTrackerReport(Request $request)
    {
        $from = date('Ymd', strtotime($request->date_from));
        $to = date('Ymd', strtotime($request->date_to));
        $ttl_grp = $ttl_loss_ratio = $ttl_ideal_loading = $avg_loss_ratio = $avg_ideal_loading = 0;
        $totals = [];

        // Step 1: Get max anniv per corp
        $subMaxAnniv = DB::table('corp_anniversary')
            ->select('corp_id', DB::raw('MAX(anniv) as max_anniv'))
            ->groupBy('corp_id');

        // Step 2: Join with premium_invoice only for max anniv
        $subPremium = DB::table('premium_invoice as pi')
            ->joinSub($subMaxAnniv, 'ma', function ($join) {
                $join->on('pi.corp_id', '=', 'ma.corp_id')
                    ->on('pi.anniv', '=', 'ma.max_anniv');
            })
            ->select('pi.corp_id', 'pi.anniv', DB::raw('SUM(isnull(pi.premium_net, 0)) as total_premium_net'))
            ->groupBy('pi.corp_id', 'pi.anniv');

        // Step 3: Join with bills only for max anniv
        $subClaims = DB::table('bills as b')
            ->join('member_info as mi', 'b.member_no', '=', 'mi.member_no')
            ->join('principal_applicant as pa', 'mi.family_no', '=', 'pa.family_no')
            ->joinSub($subMaxAnniv, 'ma', function ($join) {
                $join->on('pa.corp_id', '=', 'ma.corp_id')
                    ->on('b.anniv', '=', 'ma.max_anniv');
            })
            ->select('pa.corp_id', 'b.anniv', DB::raw('SUM(isnull(b.amount_payable, 0)) as total_amount_payable'))
            ->groupBy('pa.corp_id', 'b.anniv');

        //step 4: join with memberinfo to get lives  for max anniv of corp
        $subLives = DB::table('member_anniversary as man')
            ->join('member_info as mi', 'mi.member_no', '=', 'man.member_no')
            ->join('principal_applicant as pa', 'mi.family_no', '=', 'pa.family_no')
            ->joinSub($subMaxAnniv, 'ma', function ($join) {
                $join->on('pa.corp_id', '=', 'ma.corp_id')
                    ->on('man.anniv', '=', 'ma.max_anniv');
            })
            ->where(DB::raw("isnull(mi.cancelled, 0)"), 0)
            ->select('pa.corp_id', 'man.anniv', DB::raw('COUNT(man.member_no) as lives'))
            ->groupBy('pa.corp_id', 'man.anniv');

        // Step 5: Final query
        $report = DB::table('corp_anniversary as ca')
            ->join('corporate as c', 'ca.corp_id', '=', 'c.corp_id')
            ->joinSub($subMaxAnniv, 'ma', function ($join) {
                $join->on('ca.corp_id', '=', 'ma.corp_id')
                    ->on('ca.anniv', '=', 'ma.max_anniv');
            })
            ->leftJoinSub($subPremium, 'p', function ($join) {
                $join->on('ca.corp_id', '=', 'p.corp_id')
                    ->on('ca.anniv', '=', 'p.anniv');
            })
            ->leftJoinSub($subClaims, 'cl', function ($join) {
                $join->on('ca.corp_id', '=', 'cl.corp_id')
                    ->on('ca.anniv', '=', 'cl.anniv');
            })
            ->leftJoinSub($subLives, 'li', function ($join) {
                $join->on('ca.corp_id', '=', 'li.corp_id')
                    ->on('ca.anniv', '=', 'li.anniv');
            })
            ->whereBetween('ca.end_date', [$from, $to])
            ->orderBy('c.corporate', 'asc')
            ->select(
                'c.corp_id',
                'c.corporate',
                'ca.start_date',
                'ca.end_date',
                'ca.anniv as max_anniv',
                'ca.start_date',
                'ca.end_date',
                'p.total_premium_net as grp',
                'cl.total_amount_payable as ttl_claims',
                DB::raw("CASE WHEN  isnull(grp,0) != 0 and isnull(ttl_claims, 0) != 0 THEN CAST(ROUND(((grp / ttl_claims) * 100),2) AS DECIMAL(20,0)) ELSE 0 END as loss_ratio"),
                DB::raw("CASE WHEN (loss_ratio - 70) > 0 THEN (loss_ratio - 70) ELSE 0 END as ideal_loading"),
                'li.lives',
                DB::raw("CAST(ROUND((grp / li.lives), 2) AS DECIMAL(20,2)) as risk_premium_per_life"),
                DB::raw("CAST(ROUND((((100 + ideal_loading) / 100) *  risk_premium_per_life), 2)AS DECIMAL(20,2)) as ideal_risk_premium_per_life"),


            )
            ->get();

        foreach ($report as $row) {
            $ttl_grp += $row["grp"];
            $ttl_loss_ratio += $row["loss_ratio"];
            $ttl_ideal_loading += $row["ideal_loading"];
        }

        //count the no of loss ratio and ideal loading  that are not null to use to calculate average
        $count_loss_ratio = $report->filter(function ($row) {
            return !is_null($row["loss_ratio"]) && $row["loss_ratio"] !== '';
        })->count();
        $count_ideal_loading = $report->filter(function ($row) {
            return !is_null($row["ideal_loading"]) && $row["ideal_loading"] !== '';
        })->count();

        //calculate averages
        $avg_loss_ratio = $ttl_loss_ratio / $count_loss_ratio;
        $avg_ideal_loading = $ttl_ideal_loading / $count_ideal_loading;
        $totals = ['ttl_grp'=>$ttl_grp, 'avg_loss_ratio'=> $avg_loss_ratio, 'avg_ideal_loading'=>$avg_ideal_loading];

        return ['report'=>$report, 'totals'=>$totals];
    }
}
