<?php

namespace App\Http\Controllers;

use App\Models\Bills;
use App\Models\YearMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IpopReportController extends Controller
{
    public function ipopReport(Request $request)
    {
        $from = date('Ymd', strtotime($request->from));
        $to = date('Ymd', strtotime($request->to));


        $ttl_dental = $ttl_dental_visits = $ttl_optical = $ttl_optical_visits = $ttl_op = $ttl_op_visits = $ttl_op_average = $ttl_op_combined = 0;
        $ttl_op_combined_visits = $ttl_op_combined_average = $ttl_maternity = $ttl_maternity_visits = $ttl_ip = $ttl_ip_visits = $ttl_ip_average = $ttl_ip_combined = $ttl_ip_combined_visits = $ttl_ip_combined_average = $ttl_aar_hc = 0;
        $ttl_aar_hc_visits = $ttl_aar_hc_average = $ttl_ppos = $ttl_ppos_visits = $ttl_ppos_average = $ttl_overall = $ttl_overall_visits  = 0;

        $report = YearMonth::leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               COUNT(DISTINCT bills.claim_no) as dental_visits
           FROM bills
           WHERE bills.claim_nature IN (11, 12)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as dental_count
       "), 'year_month.date_format', '=', 'dental_count.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               COUNT(DISTINCT bills.claim_no) as optical_visits
           FROM bills
           WHERE bills.claim_nature IN (83, 36, 57)
              AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as optical_count
       "), 'year_month.date_format', '=', 'optical_count.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               COUNT(DISTINCT bills.claim_no) as op_visits
           FROM bills
            JOIN benefit on benefit.code = bills.claim_nature
           WHERE benefit.bene_class = 2 AND  bills.claim_nature NOT IN (11, 12, 83, 36, 57)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as op_count
       "), 'year_month.date_format', '=', 'op_count.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               COUNT(DISTINCT bills.claim_no) as maternity_visits
           FROM bills
           WHERE bills.claim_nature IN (59, 33, 32, 34, 35, 24, 68)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as maternity_count
       "), 'year_month.date_format', '=', 'maternity_count.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               COUNT(DISTINCT bills.claim_no) as ip_visits
           FROM bills
           JOIN benefit on benefit.code = bills.claim_nature
           WHERE benefit.bene_class = 1 and bills.claim_nature NOT IN (59, 33, 32, 34, 35, 24, 68)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as ip_count
       "), 'year_month.date_format', '=', 'ip_count.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               COUNT(DISTINCT bills.claim_no) as aar_hc_visits
           FROM bills
           WHERE bills.provider in (737,51,53,54,49,809,775,761,56,60,55,238,762,330,52)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as aar_hc_count
       "), 'year_month.date_format', '=', 'aar_hc_count.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               COUNT(DISTINCT bills.claim_no) as ppos_visits
           FROM bills
           WHERE bills.provider not in (737,51,53,54,49,809,775,761,56,60,55,238,762,330,52)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as ppos_count
       "), 'year_month.date_format', '=', 'ppos_count.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               SUM(isnull(bills.invoiced_amount, 0)) as dental
           FROM bills
           WHERE bills.claim_nature in ( 11, 12)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as dental_sum
       "), 'year_month.date_format', '=', 'dental_sum.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               SUM(isnull(bills.invoiced_amount, 0)) as optical
           FROM bills
           WHERE bills.claim_nature in (83, 36, 57)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as optical_sum
       "), 'year_month.date_format', '=', 'optical_sum.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               SUM(isnull(bills.invoiced_amount, 0)) as op
           FROM bills
            join benefit on benefit.code = bills.claim_nature
           WHERE benefit.bene_class = 2 and bills.claim_nature not in (11, 12, 83, 36, 57)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as op_sum
       "), 'year_month.date_format', '=', 'op_sum.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               SUM(isnull(bills.invoiced_amount, 0)) as maternity
           FROM bills
           WHERE bills.claim_nature in (59, 33, 32, 34, 35, 24, 68 )
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as maternity_sum
       "), 'year_month.date_format', '=', 'maternity_sum.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               SUM(isnull(bills.invoiced_amount, 0)) as ip
           FROM bills
           join benefit on benefit.code = bills.claim_nature
           WHERE benefit.bene_class = 1 and bills.claim_nature not in (59, 33, 32, 34, 35, 24, 68)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as ip_sum
       "), 'year_month.date_format', '=', 'ip_sum.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               SUM(isnull(bills.invoiced_amount, 0)) as aar_hc
           FROM bills
           WHERE bills.provider in (737,51,53,54,49,809,775,761,56,60,55,238,762,330,52)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as aar_hc_sum
       "), 'year_month.date_format', '=', 'aar_hc_sum.month_format')
            ->leftJoin(DB::raw("
           (SELECT 
               FORMAT(bills.invoice_date,'mm') as month_format,
               SUM(isnull(bills.invoiced_amount, 0)) as ppos
           FROM bills
           WHERE bills.provider not in (737,51,53,54,49,809,775,761,56,60,55,238,762,330,52)
               AND FORMAT(bills.invoice_date,'yyyyMMdd') between  '$from' and '$to'
               AND bills.claim_no IS NOT NULL
           GROUP BY FORMAT(bills.invoice_date,'mm')
           ) as ppos_sum
       "), 'year_month.date_format', '=', 'ppos_sum.month_format')

            ->select(
                'year_month.date_format as month_code',
                'year_month.month_name as month',
                DB::raw("COALESCE(dental_count.dental_visits, 0) as dental_visits"),
                DB::raw("COALESCE(optical_count.optical_visits, 0) as optical_visits"),
                DB::raw("COALESCE(op_count.op_visits, 0) as op_visits"),
                DB::raw("COALESCE(maternity_count.maternity_visits, 0) as maternity_visits"),
                DB::raw("COALESCE(ip_count.ip_visits, 0) as ip_visits"),
                DB::raw("COALESCE(aar_hc_count.aar_hc_visits, 0) as aar_hc_visits"),
                DB::raw("COALESCE(ppos_count.ppos_visits, 0) as ppos_visits"),
                DB::raw("dental_visits + optical_visits + op_visits as op_combined_visits"),
                DB::raw("maternity_visits + ip_visits  as ip_combined_visits"),
                DB::raw("op_combined_visits + ip_combined_visits  as total_visits"),
                DB::raw("dental_sum.dental"),
                DB::raw("optical_sum.optical"),
                DB::raw("op_sum.op"),
                DB::raw("maternity_sum.maternity"),
                DB::raw("ip_sum.ip"),
                DB::raw("dental + optical + op as op_combined"),
                DB::raw("maternity + ip as ip_combined"),
                DB::raw("op_combined + ip_combined as total_cost"),
                DB::raw("aar_hc_sum.aar_hc"),
                DB::raw("ppos_sum.ppos"),

                // average cost per benefit        
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN op != 0 and op_visits != 0 THEN op / op_visits ELSE 0 END)) as op_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN op_combined != 0 and op_combined_visits != 0 THEN op_combined / op_combined_visits ELSE 0 END)) as op_combined_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN ip != 0 and ip_visits != 0 THEN ip / ip_visits ELSE 0 END)) as ip_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN ip_combined != 0 and ip_combined_visits != 0 THEN ip_combined / ip_combined_visits ELSE 0 END)) as ip_combined_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN total_cost != 0 and total_visits != 0 THEN total_cost / total_visits ELSE 0 END)) as total_average_cost"),

                // average cost for healthcare
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN aar_hc != 0 and aar_hc_visits != 0 THEN aar_hc / aar_hc_visits ELSE 0 END)) as aar_hc_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN ppos != 0 and ppos_visits != 0 THEN ppos / ppos_visits ELSE 0 END)) as ppos_average_cost"),

            )
            ->get();


        // $report = Bills::select(
        //     'year_month.date_format as month_code',
        //     'year_month.month_name as month',

        //     //visits per benefit category    
        //     DB::raw("COALESCE(COUNT(DISTINCT CASE WHEN bills.claim_nature IN (11, 12) and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN bills.claim_no END), 0) as dental_visits"),
        //     // DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (83, 36, 57)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as optical_visits"),
        //     // DB::raw("COUNT(DISTINCT CASE WHEN benefit.bene_class = 2 and bills.claim_nature not in (11, 12, 83, 36, 57)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as op_visits"),
        //     // DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (59, 33, 32, 34, 35, 24, 68)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as maternity_visits"),
        //     // DB::raw("COUNT(DISTINCT CASE WHEN benefit.bene_class = 1 and bills.claim_nature not in (59, 33, 32, 34, 35, 24, 68)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as ip_visits"),

        //     // DB::raw("SUM(CASE WHEN bills.claim_nature IN (11, 12) and FORMAT(bills.invoice_date,'YYYY') = '$dt'  THEN 1 ELSE 0 END) as dental_visits"),
        //     // DB::raw("SUM(DISTINCT CASE WHEN bills.claim_nature IN (83, 36, 57)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as optical_visits"),
        //     // DB::raw("SUM(DISTINCT CASE WHEN benefit.bene_class = 2 and bills.claim_nature not in (11, 12, 83, 36, 57)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as op_visits"),
        //     // DB::raw("SUM(DISTINCT CASE WHEN bills.claim_nature IN (59, 33, 32, 34, 35, 24, 68)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as maternity_visits"),
        //     // DB::raw("SUM(DISTINCT CASE WHEN benefit.bene_class = 1 and bills.claim_nature not in (59, 33, 32, 34, 35, 24, 68)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as ip_visits"),
        //     //visits per healthcare
        //     // DB::raw("SUM(DISTINCT CASE WHEN bills.claim_nature in (737,51,53,54,49,809,775,761,56,60,55,238,762,330,52) and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as aar_hc_visits"),
        //     // DB::raw("SUM(DISTINCT CASE WHEN bills.claim_nature not in (737,51,53,54,49,809,775,761,56,60,55,238,762,330,52) and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN 1 ELSE 0 END) as ppos_visits"),
        //     // DB::raw("dental_visits + optical_visits + op_visits as op_combined_visits"),
        //     // DB::raw("maternity_visits + ip_visits  as ip_combined_visits"),
        //     // DB::raw("op_combined_visits + ip_combined_visits  as total_visits"),

        //     //total cost per benefit
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in ( 11, 12)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as dental"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (83, 36, 57)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as optical"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN benefit.bene_class = 2 and bills.claim_nature not in (11, 12, 83, 36, 57)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as op"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (59, 33, 32, 34, 35, 24, 68 )  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as maternity"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN benefit.bene_class = 1 and bills.claim_nature not in (59, 33, 32, 34, 35, 24, 68)  and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as ip"),
        //     // DB::raw("dental + optical + op as op_combined"),
        //     // DB::raw("maternity + ip as ip_combined"),
        //     // DB::raw("op_combined + ip_combined as total_cost"),

        //     //total cost for healthcare 
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (737,51,53,54,49,809,775,761,56,60,55,238,762,330,52) and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as aar_hc"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature not in (737,51,53,54,49,809,775,761,56,60,55,238,762,330,52) and FORMAT(bills.invoice_date,'YYYY') = '$dt' THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as ppos"),


        //     //average cost per benefit        
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN op != 0 and op_visits != 0 THEN op / op_visits ELSE 0 END)) as op_average_cost"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN op_combined != 0 and op_combined_visits != 0 THEN op_combined / op_combined_visits ELSE 0 END)) as op_combined_average_cost"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN ip != 0 and ip_visits != 0 THEN ip / ip_visits ELSE 0 END)) as ip_average_cost"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN ip_combined != 0 and ip_combined_visits != 0 THEN ip_combined / ip_combined_visits ELSE 0 END)) as ip_combined_average_cost"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN total_cost != 0 and total_visits != 0 THEN total_cost / total_visits ELSE 0 END)) as total_average_cost"),

        //     //average cost for healthcare
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN aar_hc != 0 and aar_hc_visits != 0 THEN aar_hc / aar_hc_visits ELSE 0 END)) as aar_hc_average_cost"),
        //     // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN ppos != 0 and ppos_visits != 0 THEN ppos / ppos_visits ELSE 0 END)) as ppos_average_cost"),


        // )
        //     ->join('benefit', 'bills.claim_nature', '=', 'benefit.code')
        //     ->leftJoin('year_month', 'year_month.date_format', '=', DB::raw("FORMAT(bills.invoice_date,'mm')"))
        //     // ->where(DB::raw("FORMAT(bills.invoice_date,'YYYY')"), $dt)
        //     ->where(DB::raw("isnull(bills.claim_no, '')"), '!=', '')
        //     ->groupBy('month', 'month_code')
        //     ->orderBy('month_code', 'asc')
        //     ->get();

        foreach ($report as $rt) {
            $ttl_dental += $rt->dental;
            $ttl_dental_visits += $rt->dental_visits;
            $ttl_optical += $rt->optical;
            $ttl_optical_visits += $rt->optical_visits;
            $ttl_op += $rt->op;
            $ttl_op_visits += $rt->op_visits;
            $ttl_op_combined += $rt->op_combined;
            $ttl_op_combined_visits += $rt->op_combined_visits;
            $ttl_maternity += $rt->maternity;
            $ttl_maternity_visits += $rt->maternity_visits;
            $ttl_ip += $rt->ip;
            $ttl_ip_visits += $rt->ip_visits;
            $ttl_ip_combined += $rt->ip_combined;
            $ttl_ip_combined_visits += $rt->ip_combined_visits;
            $ttl_aar_hc += $rt->aar_hc;
            $ttl_aar_hc_visits += $rt->aar_hc_visits;
            $ttl_ppos += $rt->ppos;
            $ttl_ppos_visits += $rt->ppos_visits;
            $ttl_overall += $rt->total_cost;
            $ttl_overall_visits += $rt->total_visits;
        }

        //find averages
        $ttl_op_average = $ttl_op == 0 || $ttl_op_visits == 0 ? 0 : $ttl_op / $ttl_op_visits;
        $ttl_op_combined_average = $ttl_op_combined == 0 || $ttl_op_combined_visits == 0 ? 0 : $ttl_op_combined / $ttl_op_combined_visits;
        $ttl_ip_average = $ttl_ip == 0 || $ttl_ip_visits == 0 ? 0 :  $ttl_ip / $ttl_ip_visits;
        $ttl_ip_combined_average = $ttl_ip_combined == 0 ||  $ttl_ip_combined_visits == 0 ? 0 : $ttl_ip_combined / $ttl_ip_combined_visits;
        $ttl_aar_hc_average = $ttl_aar_hc == 0 ||  $ttl_aar_hc_visits == 0 ? 0 : $ttl_aar_hc / $ttl_aar_hc_visits;
        $ttl_ppos_average = $ttl_ppos == 0 || $ttl_ppos_visits == 0 ? 0 : $ttl_ppos / $ttl_ppos_visits;


        $ttls = [
            "ttl_dental" => $ttl_dental,
            "ttl_dental_visits" => $ttl_dental_visits,
            "ttl_optical" => $ttl_optical,
            "ttl_optical_visits" => $ttl_optical_visits,
            "ttl_op" => $ttl_op,
            "ttl_op_visits" => $ttl_op_visits,
            "ttl_op_average" => round($ttl_op_average),
            "ttl_op_combined" => $ttl_op_combined,
            "ttl_op_combined_visits" => $ttl_op_combined_visits,
            "ttl_op_combined_average" => round($ttl_op_combined_average),
            "ttl_maternity" => $ttl_maternity,
            "ttl_maternity_visits" => $ttl_maternity_visits,
            "ttl_ip" => $ttl_ip,
            "ttl_ip_visits" => $ttl_ip_visits,
            "ttl_ip_average" => round($ttl_ip_average),
            "ttl_ip_combined" => $ttl_ip_combined,
            "ttl_ip_combined_visits" => $ttl_ip_combined_visits,
            "ttl_ip_combined_average" => round($ttl_ip_combined_average),
            "ttl_aar_hc" => $ttl_aar_hc,
            "ttl_aar_hc_visits" => $ttl_aar_hc_visits,
            "ttl_aar_hc_average" => round($ttl_aar_hc_average),
            "ttl_ppos" => $ttl_ppos,
            "ttl_ppos_visits" => $ttl_ppos_visits,
            "ttl_ppos_average" => round($ttl_ppos_average),
            "ttl_overall" => $ttl_overall,
            "ttl_overall_visits" => round($ttl_overall_visits)
        ];

        return ["report" => $report, "totals" => $ttls];
    }
}
