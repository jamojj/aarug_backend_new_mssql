<?php

namespace App\Http\Controllers;

use App\Models\Bills;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderAverageCostController extends Controller
{
    public function fetchProviderAverageCost(Request $request)
    {
        // return $request;
        $provider = $request->provider;
        $inpatient_data = $outpatient_data = $columns = [];

        $year = DateTime::createFromFormat('Y', $request->year);
        $year = $year ? $year->format("Y") : null;

        $month_raw = DateTime::createFromFormat("m/Y", $request->month);
        $month = $month_raw ? $month_raw->format("Ym") : null;
        $month_no = $month_raw ? $month_raw->format("n") : null;


        // Get current year and month
        $currentYear = date('Y');
        $currentMonth = date('n'); // 1 to 12


        // Determine which numeric months to include
        $monthIndexes = ($year < $currentYear)
            ? range(1, 12)
            : range(1, $currentMonth);

        $monthList = implode(',', $monthIndexes);



        switch ($request->options) {
            case '1':  //per provider
                switch ($request->date_options) {
                    case '1': //year 

                        if (count($monthIndexes) == 12) {
                            $sql = "SELECT 
                                    p.provider,
                                    ym.month_abbrv,
                                    ym.month_no,
                                    ISNULL(bills_summary.claim_amt, 0) AS claim_amt,
                                    ISNULL(bills_summary.visits, 0) AS visits,
                                    ISNULL(bills_summary.patients, 0) AS patients,
                                    ISNULL(bills_summary.average_cost_per_visit, 0) AS average_cost_per_visit,
                                    ISNULL(bills_summary.average_cost_per_patient, 0) AS average_cost_per_patient
                                FROM 
                                    provider p
                                CROSS JOIN 
                                    year_month ym                                
                                
                                LEFT JOIN 
                                    (
                                        SELECT 
                                            b.provider,
                                            SUM(isnull(b.invoiced_amount, 0)) AS claim_amt,
                                            DATEPART(month, b.invoice_date) AS month_no,
                                            COUNT(DISTINCT b.claim_no) AS visits,
                                            COUNT(DISTINCT b.member_no) AS patients,
                                            claim_amt / visits AS average_cost_per_visit,
                                            claim_amt / patients AS average_cost_per_patient
                                        FROM bills b
                                        JOIN
                                            benefit bene 
                                                ON 
                                                    bene.code = b.claim_nature
                                        WHERE 
                                            DATEPART(year, b.invoice_date) = ?
                                            AND b.member_no IS NOT NULL
                                            AND bene.bene_class = ? //outpatient or inpatient
                                        GROUP BY 
                                            b.provider,
                                            DATEPART(month, b.invoice_date)
                                    ) bills_summary
                                    ON bills_summary.provider = p.code
                                    AND bills_summary.month_no = ym.month_no
                                WHERE 
                                    ISNULL(p.cancel, 0) = 0
                                    AND bills_summary.provider = ?
                                    AND ym.month_no IN ($monthList)
                                ORDER BY 
                                    p.provider, ym.month_no
                            ";
                        } else {

                            $sql = "SELECT 
                                        p.provider,
                                        ym.month_abbrv,
                                        ym.month_no,
                                        ISNULL(bills_summary.claim_amt, 0) AS claim_amt,
                                        ISNULL(bills_summary.visits, 0) AS visits,
                                        ISNULL(bills_summary.patients, 0) AS patients,
                                        ISNULL(bills_summary.average_cost_per_visit, 0) AS average_cost_per_visit,
                                        ISNULL(bills_summary.average_cost_per_patient, 0) AS average_cost_per_patient
                                    FROM 
                                        provider p
                                    LEFT JOIN (
                                        SELECT 
                                            b.provider,
                                            SUM(ISNULL(b.invoiced_amount, 0)) AS claim_amt,
                                            DATEPART(month, b.invoice_date) AS month_no,
                                            COUNT(DISTINCT b.claim_no) AS visits,
                                            COUNT(DISTINCT b.member_no) AS patients,
                                            SUM(ISNULL(b.invoiced_amount, 0)) / NULLIF(COUNT(DISTINCT b.claim_no), 0) AS average_cost_per_visit,
                                            SUM(ISNULL(b.invoiced_amount, 0)) / NULLIF(COUNT(DISTINCT b.member_no), 0) AS average_cost_per_patient
                                        FROM bills b
                                        JOIN benefit bene ON bene.code = b.claim_nature
                                        WHERE DATEPART(year, b.invoice_date) = ?
                                        AND b.member_no IS NOT NULL
                                        AND bene.bene_class = ?
                                        GROUP BY b.provider, DATEPART(month, b.invoice_date)
                                    ) bills_summary ON bills_summary.provider = p.code
                                    LEFT JOIN 
                                        year_month ym ON ym.month_no = bills_summary.month_no
                                    WHERE 
                                        ISNULL(p.cancel, 0) = 0
                                        AND bills_summary.provider = ?
                                    AND 
                                        ym.month_no IN ($monthList)
                                    ORDER BY 
                                        p.provider, ym.month_no
                            ";
                        }


                        $inpatient_data = DB::select($sql, [$year,  1,  $provider, $monthList]);
                        $outpatient_data = DB::select($sql, [$year,  2,  $provider, $monthList]);
                        break;
                    case '2': //month            

                        $sql = "SELECT 
                                    p.provider,
                                    ym.month_abbrv,
                                    ym.month_no,
                                    SUM(ISNULL(b.invoiced_amount, 0)) AS claim_amt,
                                    COUNT(DISTINCT b.claim_no) AS visits,
                                    COUNT(DISTINCT b.member_no) AS patients,
                                    SUM(ISNULL(b.invoiced_amount, 0)) / NULLIF(COUNT(DISTINCT b.claim_no), 0) AS average_cost_per_visit,
                                    SUM(ISNULL(b.invoiced_amount, 0)) / NULLIF(COUNT(DISTINCT b.member_no), 0) AS average_cost_per_patient

                                FROM bills b
                                JOIN provider p ON p.code = b.provider
                                JOIN benefit bene ON bene.code = b.claim_nature
                                LEFT JOIN year_month ym ON ym.month_no = DATEPART(month, b.invoice_date) 
                                WHERE 
                                    ISNULL(p.cancel, 0) = 0
                                    AND FORMAT(b.invoice_date, 'YYYYmm') = ?
                                    AND bene.bene_class = ?
                                    AND b.provider = ?
                                GROUP BY
                                    p.provider, ym.month_abbrv, ym.month_no
                                ORDER BY 
                                    p.provider, ym.month_no
                                ";

                        $inpatient_data = DB::select($sql, [$month,  1, $provider]);  //inpatient
                        $outpatient_data = DB::select($sql, [$month,  2, $provider]);  //outpatient
                        $monthIndexes = [$month_no];  // reasigned because we only need one month not a range of months

                        break;
                }

                break;
            case '2': //all providers
                switch ($request->date_options) {
                    case '1': //year

                        if (count($monthIndexes) == 12) {
                            $sql = "SELECT 
                                    p.provider,
                                    ym.month_abbrv,
                                    ym.month_no,
                                    ISNULL(bills_summary.claim_amt, 0) AS claim_amt,
                                    ISNULL(bills_summary.visits, 0) AS visits,
                                    ISNULL(bills_summary.patients, 0) AS patients,
                                    ISNULL(bills_summary.average_cost_per_visit, 0) AS average_cost_per_visit,
                                    ISNULL(bills_summary.average_cost_per_patient, 0) AS average_cost_per_patient
                                FROM 
                                    provider p
                                CROSS JOIN 
                                    year_month ym                                
                                
                                LEFT JOIN 
                                    (
                                        SELECT 
                                            b.provider,
                                            SUM(isnull(b.invoiced_amount, 0)) AS claim_amt,
                                            DATEPART(month, b.invoice_date) AS month_no,
                                            COUNT(DISTINCT b.claim_no) AS visits,
                                            COUNT(DISTINCT b.member_no) AS patients,
                                            claim_amt / visits AS average_cost_per_visit,
                                            claim_amt / patients AS average_cost_per_patient
                                        FROM bills b
                                        JOIN
                                            benefit bene 
                                                ON 
                                                    bene.code = b.claim_nature
                                        WHERE 
                                            DATEPART(year, b.invoice_date) = ?
                                            AND b.member_no IS NOT NULL
                                            AND bene.bene_class = ? //outpatient or inpatient
                                        GROUP BY 
                                            b.provider,
                                            DATEPART(month, b.invoice_date)
                                    ) bills_summary
                                    ON bills_summary.provider = p.code
                                    AND bills_summary.month_no = ym.month_no
                                WHERE 
                                    ISNULL(p.cancel, 0) = 0
                                    AND ym.month_no IN ($monthList)
                                ORDER BY 
                                    p.provider, ym.month_no
                            ";
                        } else {

                            $sql = "SELECT 
                                        p.provider,
                                        ym.month_abbrv,
                                        ym.month_no,
                                        ISNULL(bills_summary.claim_amt, 0) AS claim_amt,
                                        ISNULL(bills_summary.visits, 0) AS visits,
                                        ISNULL(bills_summary.patients, 0) AS patients,
                                        ISNULL(bills_summary.average_cost_per_visit, 0) AS average_cost_per_visit,
                                        ISNULL(bills_summary.average_cost_per_patient, 0) AS average_cost_per_patient
                                    FROM 
                                        provider p
                                    LEFT JOIN (
                                        SELECT 
                                            b.provider,
                                            SUM(ISNULL(b.invoiced_amount, 0)) AS claim_amt,
                                            DATEPART(month, b.invoice_date) AS month_no,
                                            COUNT(DISTINCT b.claim_no) AS visits,
                                            COUNT(DISTINCT b.member_no) AS patients,
                                            SUM(ISNULL(b.invoiced_amount, 0)) / NULLIF(COUNT(DISTINCT b.claim_no), 0) AS average_cost_per_visit,
                                            SUM(ISNULL(b.invoiced_amount, 0)) / NULLIF(COUNT(DISTINCT b.member_no), 0) AS average_cost_per_patient
                                        FROM bills b
                                        JOIN benefit bene ON bene.code = b.claim_nature
                                        WHERE DATEPART(year, b.invoice_date) = ?
                                        AND b.member_no IS NOT NULL
                                        AND bene.bene_class = ?
                                        GROUP BY b.provider, DATEPART(month, b.invoice_date)
                                    ) bills_summary ON bills_summary.provider = p.code
                                    LEFT JOIN year_month ym ON ym.month_no = bills_summary.month_no
                                    WHERE ISNULL(p.cancel, 0) = 0
                                    AND ym.month_no IN ($monthList)
                                    ORDER BY p.provider, ym.month_no
                            ";
                        }


                        $inpatient_data = DB::select($sql, [$year,  1, $monthList]);
                        $outpatient_data = DB::select($sql, [$year,  2, $monthList]);

                        break;
                    case '2': //month
                        $sql = "SELECT 
                                    p.provider,
                                    ym.month_abbrv,
                                    ym.month_no,
                                    SUM(ISNULL(b.invoiced_amount, 0)) AS claim_amt,
                                    COUNT(DISTINCT b.claim_no) AS visits,
                                    COUNT(DISTINCT b.member_no) AS patients,
                                    SUM(ISNULL(b.invoiced_amount, 0)) / NULLIF(COUNT(DISTINCT b.claim_no), 0) AS average_cost_per_visit,
                                    SUM(ISNULL(b.invoiced_amount, 0)) / NULLIF(COUNT(DISTINCT b.member_no), 0) AS average_cost_per_patient

                                FROM bills b
                                JOIN provider p ON p.code = b.provider
                                JOIN benefit bene ON bene.code = b.claim_nature
                                LEFT JOIN year_month ym ON ym.month_no = DATEPART(month, b.invoice_date) 
                                WHERE ISNULL(p.cancel, 0) = 0
                                AND FORMAT(b.invoice_date, 'YYYYmm') = ?
                                AND bene.bene_class = ?
                                GROUP BY p.provider, ym.month_abbrv, ym.month_no
                                ORDER BY p.provider, ym.month_no
                                ";

                        $inpatient_data = DB::select($sql, [$month,  1]);  //inpatient
                        $outpatient_data = DB::select($sql, [$month,  2]);  //outpatient
                        $monthIndexes = [$month_no];  // reasigned because we only need one month not a range of months

                        break;
                }
                break;
        }


        $months = [
            1 => 'jan',
            2 => 'feb',
            3 => 'mar',
            4 => 'apr',
            5 => 'may',
            6 => 'jun',
            7 => 'jul',
            8 => 'aug',
            9 => 'sep',
            10 => 'oct',
            11 => 'nov',
            12 => 'dec'
        ];
        $months = array_intersect_key($months, array_flip($monthIndexes));


        // Reusable function to group and format data
        $processGrouped = function ($data) use ($months) {
            return collect($data)->groupBy('provider')->map(function ($items, $provider) use ($months) {
                $result = ['provider' => $provider];

                // Initialize totals
                $total_claim_amt = 0;
                $total_visits = 0;
                $total_patients = 0;
                $total_avg_cost_per_visit = 0;
                $total_avg_cost_per_patient = 0;

                foreach ($months as $num => $abbr) {
                    $monthData = collect($items)->firstWhere('month_no', $num);

                    $claim_amt = $monthData ? (float) $monthData['claim_amt'] : 0;
                    $visits = $monthData ? (float) $monthData['visits'] : 0;
                    $patients = $monthData ? (int) $monthData['patients'] : 0;
                    $avg_cost_visit = $monthData ? (float) $monthData['average_cost_per_visit'] : 0;
                    $avg_cost_patient = $monthData ? (float) $monthData['average_cost_per_patient'] : 0;


                    $result["{$abbr}_claim_amt"] = number_format($claim_amt, 0, '.', ',');
                    $result["{$abbr}_visits"] = number_format($visits, 0, '.', ',');
                    $result["{$abbr}_patients"] = number_format($patients, 0, '.', ',');
                    $result["{$abbr}_average_cost_per_visit"] = number_format($avg_cost_visit, 0, '.', ',');
                    $result["{$abbr}_average_cost_per_patient"] = number_format($avg_cost_patient, 0, '.', ',');

                    $total_claim_amt += $claim_amt;
                    $total_visits += $visits;
                    $total_patients += $patients;
                    $total_avg_cost_per_visit += $avg_cost_visit;
                    $total_avg_cost_per_patient += $avg_cost_patient;


                    // $result["{$abbr}_claim_amt"] = $claim_amt;
                    // $result["{$abbr}_visits"] = $visits;
                    // $result["{$abbr}_patients"] = $patients;
                    // $result["{$abbr}_average_cost_per_visit"] = $avg_cost_visit;
                    // $result["{$abbr}_average_cost_per_patient"] = $avg_cost_patient;
                }
                $result["total_claim_amt"] = number_format($total_claim_amt, 0, '.', ',');
                $result["total_visits"] = number_format($total_visits, 0, '.', ',');
                $result["total_patients"] = number_format($total_patients, 0, '.', ',');
                $result["total_average_cost_per_visit"] = number_format($total_avg_cost_per_visit, 0, '.', ',');
                $result["total_average_cost_per_patient"] = number_format($total_avg_cost_per_patient, 0, '.', ',');

                // $result["total_claim_amt"] = $total_claim_amt;
                // $result["total_visits"] = $total_visits;
                // $result["total_patients"] = $total_patients;
                // $result["total_average_cost_per_visit"] = $total_avg_cost_per_visit;
                // $result["total_average_cost_per_patient"] = $total_avg_cost_per_patient;


                return $result;
            })->values();
        };

        // Process both datasets
        $outpatientGrouped = $processGrouped($outpatient_data);
        $inpatientGrouped = $processGrouped($inpatient_data);

        // ✅ FIXED COLUMNS ORDER: metric → month (so React loops naturally)
        $columns = ['provider'];
        $metrics = ['claim_amt', 'visits', 'patients', 'average_cost_per_visit', 'average_cost_per_patient'];
        foreach ($metrics as $metric) {
            foreach ($months as $abbr) {
                $columns[] = "{$abbr}_{$metric}";
            }
        }
        $columns[] = "total_claim_amt";
        $columns[] = "total_visits";
        $columns[] = "total_patients";
        $columns[] = "total_average_cost_per_visit";
        $columns[] = "total_average_cost_per_patient";

        // Final JSON response
        return response()->json([
            'columns' => $columns,
            'outpatient' => $outpatientGrouped,
            'inpatient' => $inpatientGrouped
        ]);
    }
}
