<?php

namespace App\Http\Controllers;

use App\Models\Bills;
use App\Models\Corporate\Corporate;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimsAverageAmountController extends Controller
{
    public function claimsAverageAmount(Request $request)
    {
        $from = date('Ymd', strtotime($request->from));
        $to = date('Ymd', strtotime($request->to));
        $ip_amount = $ip_visit = $ip_average = 0;
        $op_amount = $op_visit = $op_average = 0;
        $provider_ip_amount = $provider_ip_visits = $provider_ip_average = $provider_op_amount =  $provider_op_visit = $provider_op_average = $provider_ttl = $provider_visits = 0;
        $corp_ip_amount = $corp_ip_visits = $corp_ip_average = $corp_op_amount =  $corp_op_visit = $corp_op_average = $corp_ttl = $corp_visits = 0;


        $inpatient_cat = Bills::select(
            'benefit.benefit as benefits',
            DB::raw("CONVERT(NUMERIC(20, 0), (sum(isnull(bills.invoiced_amount, 0) ))) as amount"),
            DB::raw("COUNT( DISTINCT CASE WHEN isnull(bills.claim_no, '') != '' THEN bills.claim_no END) as visits"),
            DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN amount != 0 and visits != 0 THEN amount / visits ELSE 0 END)) as average_cost"),
        )
            ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
            ->where('benefit.bene_class', 1)
            ->whereBetween(DB::raw("FORMAT(bills.invoice_date,'yyyyMMdd')"), [$from, $to])
            ->groupBy('benefits')
            ->orderBy('amount', 'desc')
            ->get();
        foreach ($inpatient_cat as $cat) {
            $ip_amount +=  $cat->amount;
            $ip_visit +=  $cat->visits;
        }
        $outpatient_cat = Bills::select(
            'benefit.benefit as benefits',
            DB::raw("CONVERT(NUMERIC(20, 0), (sum(isnull(bills.invoiced_amount, 0) ))) as amount"),
            DB::raw("COUNT( DISTINCT CASE WHEN isnull(bills.claim_no, '') != '' THEN bills.claim_no END) as visits"),
            DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN amount != 0 and visits != 0 THEN amount / visits ELSE 0 END)) as average_cost"),
        )
            ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
            ->where('benefit.bene_class', 2)
            ->whereBetween(DB::raw("FORMAT(bills.invoice_date,'yyyyMMdd')"), [$from, $to])
            ->groupBy('benefits')
            ->orderBy('amount', 'desc')
            ->get();

        foreach ($outpatient_cat as $cat) {
            $op_amount +=  $cat->amount;
            $op_visit +=  $cat->visits;
        }



        $provider_cat = Provider::leftJoin(DB::raw("(SELECT 
                                bills.provider,
                                COUNT(DISTINCT bills.claim_no) as ip_visits
                            FROM bills
                            JOIN benefit ON benefit.code = bills.claim_nature
                            where isnull(benefit.bene_class, 0) = 1
                             AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
                            and benefit.bene_class is not null
                            GROUP BY bills.provider) as ip_count"), 'ip_count.provider', '=', 'provider.code')
            ->leftJoin(DB::raw("(SELECT 
                                bills.provider,
                                COUNT(DISTINCT bills.claim_no) as op_visits
                            FROM bills
                            JOIN benefit ON benefit.code = bills.claim_nature
                            where isnull(benefit.bene_class, 0) = 2
                             AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
                            and benefit.bene_class is not null
                            GROUP BY bills.provider) as op_count"), 'op_count.provider', '=', 'provider.code')
            ->leftJoin(DB::raw("(SELECT 
                                bills.provider,
                                SUM(ISNULL(bills.invoiced_amount, 0)) as ip
                            FROM bills
                            JOIN benefit ON benefit.code = bills.claim_nature
                            where isnull(benefit.bene_class, 0) = 1
                             AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
                            and benefit.bene_class is not null
                            GROUP BY bills.provider) as ip_sum"), 'ip_sum.provider', '=', 'provider.code')
            ->leftJoin(DB::raw("(SELECT 
                                bills.provider,
                                SUM(ISNULL(bills.invoiced_amount, 0)) as op
                            FROM bills
                            JOIN benefit ON benefit.code = bills.claim_nature
                            where isnull(benefit.bene_class, 0) = 2
                             AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
                            and benefit.bene_class is not null
                            GROUP BY bills.provider) as op_sum"), 'op_sum.provider', '=', 'provider.code')

            ->select(
                'provider.provider as provider',
                'provider.code as provider_code',
                DB::raw("COALESCE(ip_count.ip_visits, 0) as ip_visits"),
                DB::raw("COALESCE(op_count.op_visits, 0) as op_visits"),
                DB::raw("ip_sum.ip as ip"),
                DB::raw("op_sum.op as op"),

                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN ip_visits != 0 and ip != 0 THEN ip / ip_visits ELSE 0 END)) as ip_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN op_visits != 0 and op != 0 THEN op / op_visits ELSE 0 END)) as op_average_cost"),
                DB::raw("ip + op as total_amount"),
                DB::raw("ip_visits + op_visits as total_visits")
            )


            ->orderBy('ip', 'desc')
            ->orderBy('op', 'desc')
            ->get();

        foreach ($provider_cat as $cat) {
            $provider_ip_amount +=  $cat->ip;
            $provider_ip_visits +=  $cat->ip_visits;
            $provider_op_amount +=  $cat->op;
            $provider_op_visit +=  $cat->op_visits;
            $provider_ttl +=  $cat->total_amount;
            $provider_visits +=  $cat->total_visits;
        }


        $corp_cat = Corporate::leftJoin(DB::raw("(SELECT 
                                principal_applicant.corp_id,
                                COUNT(DISTINCT bills.claim_no) as ip_visits
                            FROM bills
                            JOIN member_info ON member_info.member_no = bills.member_no
                            JOIN principal_applicant ON principal_applicant.family_no = member_info.family_no
                            join corporate on corporate.corp_id = principal_applicant.corp_id
                            JOIN benefit ON benefit.code = bills.claim_nature
                            where isnull(benefit.bene_class, 0) = 1
                             AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
                            and benefit.bene_class is not null
                            GROUP BY  principal_applicant.corp_id) as ip_count"), 'ip_count.corp_id', '=', 'corporate.corp_id')
            ->leftJoin(DB::raw("(SELECT 
                                principal_applicant.corp_id,
                                COUNT(DISTINCT bills.claim_no) as op_visits
                            FROM bills
                            JOIN member_info ON member_info.member_no = bills.member_no
                            JOIN principal_applicant ON principal_applicant.family_no = member_info.family_no
                            join corporate on corporate.corp_id = principal_applicant.corp_id
                            JOIN benefit ON benefit.code = bills.claim_nature
                            where isnull(benefit.bene_class, 0) = 2
                             AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
                            and benefit.bene_class is not null
                            GROUP BY principal_applicant.corp_id) as op_count"), 'op_count.corp_id', '=', 'corporate.corp_id')
            ->leftJoin(DB::raw("(SELECT 
                                principal_applicant.corp_id,
                                SUM(ISNULL(bills.invoiced_amount, 0)) as ip
                            FROM bills
                            JOIN member_info ON member_info.member_no = bills.member_no
                            JOIN principal_applicant ON principal_applicant.family_no = member_info.family_no
                            join corporate on corporate.corp_id = principal_applicant.corp_id
                            JOIN benefit ON benefit.code = bills.claim_nature
                            where isnull(benefit.bene_class, 0) = 1
                             AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
                            and benefit.bene_class is not null
                            GROUP BY  principal_applicant.corp_id) as ip_sum"), 'ip_sum.corp_id', '=', 'corporate.corp_id')
            ->leftJoin(DB::raw("(SELECT 
                                 principal_applicant.corp_id,
                                SUM(ISNULL(bills.invoiced_amount, 0)) as op
                            FROM bills
                             JOIN member_info ON member_info.member_no = bills.member_no
                            JOIN principal_applicant ON principal_applicant.family_no = member_info.family_no
                            join corporate on corporate.corp_id = principal_applicant.corp_id
                            JOIN benefit ON benefit.code = bills.claim_nature
                            where isnull(benefit.bene_class, 0) = 2
                             AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
                            and benefit.bene_class is not null
                            GROUP BY principal_applicant.corp_id) as op_sum"), 'op_sum.corp_id', '=', 'corporate.corp_id')

            ->select(
                'corporate.corporate as corporate',
                'corporate.corp_id as corp_id',
                DB::raw("COALESCE(ip_count.ip_visits, 0) as ip_visits"),
                DB::raw("COALESCE(op_count.op_visits, 0) as op_visits"),
                DB::raw("ip_sum.ip as ip"),
                DB::raw("op_sum.op as op"),

                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN ip_visits != 0 and ip != 0 THEN ip / ip_visits ELSE 0 END)) as ip_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN op_visits != 0 and op != 0 THEN op / op_visits ELSE 0 END)) as op_average_cost"),
                DB::raw("ip + op as total_amount"),
                DB::raw("ip_visits + op_visits as total_visits")
            )

            ->orderBy('ip', 'desc')
            ->orderBy('op', 'desc')
            ->get();

        foreach ($corp_cat as $cat) {
            $corp_ip_amount +=  $cat->ip;
            $corp_ip_visits +=  $cat->ip_visits;
            $corp_op_amount +=  $cat->op;
            $corp_op_visit +=  $cat->op_visits;
            $corp_ttl +=  $cat->total_amount;
            $corp_visits +=  $cat->total_visits;
        }

       //find averages
       $ip_average = $ip_amount == 0 || $ip_visit == 0 ? 0 : $ip_amount / $ip_visit;
       $op_average = $op_amount == 0 || $op_visit == 0 ? 0 : $op_amount / $op_visit;
       $provider_ip_average = $provider_ip_amount == 0 || $provider_ip_visits == 0 ? 0 : $provider_ip_amount / $provider_ip_visits;
       $provider_op_average = $provider_op_amount == 0 || $provider_op_visit == 0 ? 0 : $provider_op_amount / $provider_op_visit;
       $corp_ip_average = $corp_ip_amount == 0 || $corp_ip_visits == 0 ? 0 : $corp_ip_amount / $corp_ip_visits;
       $corp_op_average = $corp_op_amount == 0 || $corp_op_visit == 0 ? 0 : $corp_op_amount / $corp_op_visit;
   


        $totals = [
            "ip_amount" => $ip_amount,
            "ip_visit" => $ip_visit,
            "ip_average" => round($ip_average),
            "op_amount" => $op_amount,
            "op_visit" => $op_visit,
            "op_average" => round($op_average),
            "provider_ip_amount" => $provider_ip_amount,
            "provider_ip_visits" => $provider_ip_visits,
            "provider_ip_average" => round($provider_ip_average),
            "provider_op_amount" => $provider_op_amount,
            "provider_op_visit" => $provider_op_visit,
            "provider_op_average" => round($provider_op_average),
            "provider_ttl" => $provider_ttl,
            "provider_visits" => $provider_visits,
            "corp_ip_amount" => $corp_ip_amount,
            "corp_ip_visits" => $corp_ip_visits,
            "corp_ip_average" => round($corp_ip_average),
            "corp_op_amount" => $corp_op_amount,
            "corp_op_visit" => $corp_op_visit,
            "corp_op_average" => round($corp_op_average),
            "corp_ttl" => $corp_ttl,
            "corp_visits" => $corp_visits         
        ];



        return ['inpatient' => $inpatient_cat, 'outpatient' => $outpatient_cat, 'provider_cat' => $provider_cat, 'corp_cat' => $corp_cat, 'totals'=>$totals];
    }
}
