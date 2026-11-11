<?php

namespace App\Http\Controllers;

use App\Models\ClaimsBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgeingAnalysisFinanceController extends Controller
{
    public  function fetchAgeingAnalysisFinanceReport(Request $request)
    {
        $ld_tod = $request->as_at;
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
                    DB::raw("
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_str_curr then  isnull(bills.invoiced_amount, 0) else 0 end) as curr,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_30_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr1,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_60_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr2,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_90_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr3,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_120_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr4,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_120_a_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr5,
                    (curr + curr1 + curr2 + curr3 + curr4 + curr5) as total
                    ")
                )

                    ->join('bills', 'bills.batch_no', '=', 'claims_batch.batch_no')
                    ->join('provider', 'provider.code', '=', 'claims_batch.provider')
                    ->where('claims_batch.provider', $request->provider)
                    ->orderBy('provider.provider', 'asc')
                    ->groupBy('provider.provider')
                    ->havingRaw("
                SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                >
                SUM(CASE WHEN bills.vet_status IS NULL OR bills.vet_status = 0 THEN 1 ELSE 0 END)
                AND
                SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                >
                SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND (bills.paid IS NULL OR bills.paid = 0) THEN 1 ELSE 0 END)
                AND
                SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                >
                SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END)
                ")
                    ->get();
                break;
            case '2':
                $report = ClaimsBatch::select(
                    'provider.provider',
                    DB::raw("
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_str_curr then  isnull(bills.invoiced_amount, 0) else 0 end) as curr,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_30_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr1,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_60_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr2,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_90_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr3,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_120_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr4,
                    sum(case when FORMAT(claims_batch.date_submitted,'YYYYmm') =  $ls_120_a_str then  isnull(bills.invoiced_amount, 0) else 0 end) as curr5,
                     (curr + curr1 + curr2 + curr3 + curr4 + curr5) as total
                    ")
                )

                    ->join('bills', 'bills.batch_no', '=', 'claims_batch.batch_no')
                    ->join('provider', 'provider.code', '=', 'claims_batch.provider')
                    ->orderBy('provider.provider', 'asc')
                    ->groupBy('provider.provider')
                    ->havingRaw("
                  SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                >
                SUM(CASE WHEN bills.vet_status IS NULL OR bills.vet_status = 0 THEN 1 ELSE 0 END)
                AND
                SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                >
                SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND (bills.paid IS NULL OR bills.paid = 0) THEN 1 ELSE 0 END)
                AND
                SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND bills.paid = 1 AND bills.payment_no IS NOT NULL THEN 1 ELSE 0 END)
                >
                SUM(CASE WHEN bills.vet_status IS NOT NULL AND bills.vet_status != 0 AND bills.paid = 1 AND bills.payment_no IS NULL THEN 1 ELSE 0 END)
                ")
                    ->get();
                break;
        }
        return $report;
    }
}
