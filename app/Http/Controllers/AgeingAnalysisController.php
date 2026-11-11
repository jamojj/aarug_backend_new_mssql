<?php

namespace App\Http\Controllers;

use App\Models\Bills;
use App\Models\ClaimsBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgeingAnalysisController extends Controller
{
    public  function fetchAgeingAnalysisReport(Request $request)
    {

        ini_set('max_execution_time', '0');

        $ld_tod = $request->as_at;
        $from = date("Y", strtotime($ld_tod)) . '01';
        $ls_str_curr = date("Ym", strtotime($ld_tod));
        $ls_30_str = date("Ym", strtotime("$ld_tod -31 days"));
        $ls_60_str = date("Ym", strtotime("$ld_tod  -62 days"));
        $ls_90_str = date("Ym", strtotime("$ld_tod -93 days"));
        $ls_120_str = date("Ym", strtotime("$ld_tod -124 days"));
        $ls_120_a_str = date("Ym", strtotime("$ld_tod -155 days"));
        $report = [];

        switch ($request->opts) {
            case '1':
                $report = ClaimsBatch::select(
                    'provider.provider',
                    'claims_batch.batch_no',
                    DB::raw("isnull(subquery_current.invoiced_amount,0) as curr"),
                    DB::raw("isnull(subquery_thirty.invoiced_amount,0) as curr1"),
                    DB::raw("isnull(subquery_sixty.invoiced_amount,0) as curr2"),
                    DB::raw("isnull(subquery_ninety.invoiced_amount,0) as curr3"),
                    DB::raw("isnull(subquery_one_twenty.invoiced_amount,0) as curr4"),
                    DB::raw("isnull(subquery_onwards.invoiced_amount,0) as curr5"),
                    DB::raw("(curr + curr1 + curr2 + curr3 + curr4 + curr5) as total"),

                )
                    ->join('bills', 'bills.batch_no', '=', 'claims_batch.batch_no')
                    ->join('provider', 'provider.code', '=', 'claims_batch.provider')
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_str_curr
                        GROUP BY bills.batch_no
                        HAVING                            
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_current"),
                        'subquery_current.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_30_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_thirty"),
                        'subquery_thirty.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_60_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_sixty"),
                        'subquery_sixty.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_90_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_ninety"),
                        'subquery_ninety.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_120_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_one_twenty"),
                        'subquery_one_twenty.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') <= $ls_120_a_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_onwards"),
                        'subquery_onwards.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->where(function ($query) {
                        $query->whereNotNull('subquery_current.invoiced_amount')
                            ->orWhereNotNull('subquery_thirty.invoiced_amount')
                            ->orWhereNotNull('subquery_sixty.invoiced_amount')
                            ->orWhereNotNull('subquery_ninety.invoiced_amount')
                            ->orWhereNotNull('subquery_one_twenty.invoiced_amount')
                            ->orWhereNotNull('subquery_onwards.invoiced_amount');
                    })
                    ->where('claims_batch.provider', $request->provider)
                    ->orderBy('provider.provider', 'asc')
                    ->groupBy('provider.provider', 'claims_batch.batch_no', 'subquery_current.invoiced_amount', 'subquery_thirty.invoiced_amount', 'subquery_sixty.invoiced_amount', 'subquery_ninety.invoiced_amount', 'subquery_one_twenty.invoiced_amount', 'subquery_onwards.invoiced_amount')
                    ->get();
                break;
            case '2':
                $report = ClaimsBatch::select(
                    'provider.provider',
                    'claims_batch.batch_no',
                    DB::raw("isnull(subquery_current.invoiced_amount,0) as curr"),
                    DB::raw("isnull(subquery_thirty.invoiced_amount,0) as curr1"),
                    DB::raw("isnull(subquery_sixty.invoiced_amount,0) as curr2"),
                    DB::raw("isnull(subquery_ninety.invoiced_amount,0) as curr3"),
                    DB::raw("isnull(subquery_one_twenty.invoiced_amount,0) as curr4"),
                    DB::raw("isnull(subquery_onwards.invoiced_amount,0) as curr5"),
                    DB::raw("(curr + curr1 + curr2 + curr3 + curr4 + curr5) as total"),

                )
                    ->join('bills', 'bills.batch_no', '=', 'claims_batch.batch_no')
                    ->join('provider', 'provider.code', '=', 'claims_batch.provider')
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_str_curr
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_current"),
                        'subquery_current.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_30_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_thirty"),
                        'subquery_thirty.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_60_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_sixty"),
                        'subquery_sixty.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_90_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_ninety"),
                        'subquery_ninety.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') = $ls_120_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_one_twenty"),
                        'subquery_one_twenty.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->leftJoin(
                        DB::raw("( 
                        SELECT bills.batch_no, SUM(isnull(bills.invoiced_amount, 0)) AS invoiced_amount
                        FROM bills
                        JOIN claims_batch ON bills.batch_no = claims_batch.batch_no
                        WHERE FORMAT(claims_batch.date_submitted, 'YYYYmm') <= $ls_120_a_str
                        GROUP BY bills.batch_no
                        HAVING 
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END) AND
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) in (0,1,2,3) AND COALESCE(bills.paid, 0) = 0 THEN 1 ELSE 0 END) >                  
                            SUM(CASE WHEN COALESCE(bills.vet_status, 0) != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                    ) AS subquery_onwards"),
                        'subquery_onwards.batch_no',
                        '=',
                        'claims_batch.batch_no'
                    )
                    ->where(function ($query) {
                        $query->whereNotNull('subquery_current.invoiced_amount')
                            ->orWhereNotNull('subquery_thirty.invoiced_amount')
                            ->orWhereNotNull('subquery_sixty.invoiced_amount')
                            ->orWhereNotNull('subquery_ninety.invoiced_amount')
                            ->orWhereNotNull('subquery_one_twenty.invoiced_amount')
                            ->orWhereNotNull('subquery_onwards.invoiced_amount');
                    })

                    ->orderBy('provider.provider', 'asc')
                    ->groupBy('provider.provider', 'claims_batch.batch_no', 'subquery_current.invoiced_amount', 'subquery_thirty.invoiced_amount', 'subquery_sixty.invoiced_amount', 'subquery_ninety.invoiced_amount', 'subquery_one_twenty.invoiced_amount', 'subquery_onwards.invoiced_amount')
                    ->get();
                break;
        }
        return $report;
    }
}
