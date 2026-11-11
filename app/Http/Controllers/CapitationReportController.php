<?php

namespace App\Http\Controllers;

use App\Models\Bills;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CapitationReportController extends Controller
{
    public function fetchCapitationReport(Request $request)
    {
        ini_set('max_execution_time', '0');

        try {

            $rows = $totals = [];
            $ttl_total_cost =  $ttl_contr = $ttl_total_visits = $ttl_acv_op = $ttl_capitated_acv_op = $ttl_savings = $ttl_savings_annual = 0;

            $from = Carbon::parse($request->from)->format('Y-m-d');

            $toCarbon = Carbon::parse($request->to);
            if (!$toCarbon->isLastOfMonth()) {
                $toCarbon->subMonth()->endOfMonth();
            }
            $to = $toCarbon->format('Y-m-d');

            $to_start_year = $toCarbon->copy()->startOfYear()->format('Y-m-d');
            $to_end_year = $toCarbon->copy()->endOfYear()->format('Y-m-d');


            $ttl_total_op_amount = Bills::join('benefit', 'bills.claim_nature', '=', 'benefit.code')
                ->where('benefit.bene_class', 2)
                ->whereBetween('bills.invoice_date', [$from, $to])
                ->sum(DB::raw('ISNULL(bills.invoiced_amount, 0)'));

            $rows = DB::select("
                    WITH base AS (
                                SELECT
                                    provider_capitation.provider,
                                    provider_capitation.start_date AS capitation_start_date,
                                    provider_capitation.capitation AS capitated_acv_op,
                                    $ttl_total_op_amount AS total_op_amount,
                                    '$to_start_year' AS to_start_year,
                                    '$to_end_year'   AS to_end_year,
                                    '$from'          AS from_date,
                                    '$to'            AS to_date,
                                    CAST(
                                    CASE
                                        WHEN provider_capitation.start_date < CAST('$to_start_year' AS DATE)
                                        THEN CAST('$to_start_year' AS DATE)
                                        ELSE provider_capitation.start_date
                                    END AS DATE
                                    ) AS actual_start_date,
                                    CASE
                                    WHEN provider_capitation.start_date < '$to_start_year' THEN 12
                                    ELSE ISNULL(DATEDIFF(month, provider_capitation.start_date, '$to_end_year'), 0) + 1
                                    END AS capitation_months_by_end_year
                                FROM provider_capitation
                            ),
                            agg AS (
                                SELECT
                                    provider.provider,
                                    b.capitation_start_date,
                                    b.capitated_acv_op,
                                    b.total_op_amount,
                                    b.to_start_year,
                                    b.to_end_year,
                                    b.from_date,
                                    b.to_date,
                                    b.actual_start_date,
                                    b.capitation_months_by_end_year,
                                    SUM(
                                        CASE
                                            WHEN bills.invoice_date BETWEEN b.actual_start_date AND '$to'
                                                THEN ISNULL(bills.invoiced_amount, 0)
                                            ELSE 0
                                        END
                                    ) AS total_cost,
                                    CASE
                                        WHEN ISNULL(DATEDIFF(month, b.actual_start_date, '$to'), 0) + 1 <= 0
                                            THEN 1
                                        ELSE ISNULL(DATEDIFF(month, b.actual_start_date, '$to'), 0) + 1
                                    END AS active_days,
                                    (
                                        SELECT COUNT(DISTINCT b2.claim_no)
                                        FROM bills b2
                                        JOIN benefit bene2 ON b2.claim_nature = bene2.code
                                        WHERE b2.provider = b.provider
                                        AND bene2.bene_class = 2
                                        AND b2.invoice_date BETWEEN b.actual_start_date AND '$to'
                                        AND b2.invoice_date BETWEEN '$from' AND '$to'
                                    ) AS total_visits
                                FROM base b
                                JOIN provider ON provider.code = b.provider
                                JOIN bills    ON bills.provider = b.provider
                                JOIN benefit  ON bills.claim_nature = benefit.code
                                WHERE benefit.bene_class = 2
                                AND bills.invoice_date BETWEEN '$from' AND '$to'
                                GROUP BY
                                    provider.provider,
                                    b.capitation_start_date,
                                    b.capitated_acv_op,
                                    b.total_op_amount,
                                    b.to_start_year,
                                    b.to_end_year,
                                    b.from_date,
                                    b.to_date,
                                    b.actual_start_date,
                                    b.capitation_months_by_end_year,
                                    b.provider
                            ),
                            final_calc AS (
                                SELECT
                                    provider,
                                    capitation_start_date,
                                    capitated_acv_op,
                                    total_op_amount,
                                    to_start_year,
                                    to_end_year,
                                    from_date,
                                    to_date,
                                    actual_start_date,
                                    capitation_months_by_end_year,
                                    total_cost,
                                    active_days,
                                    total_visits,
                                    CASE
                                        WHEN total_cost > 0 OR total_op_amount > 0
                                            THEN CAST(ROUND((total_cost / total_op_amount) * 100, 2) AS DECIMAL(20,2))
                                        ELSE 0
                                    END AS contr,
                                    CASE
                                        WHEN total_cost > 0 OR total_visits > 0
                                            THEN CAST(ROUND(total_cost / total_visits, 2) AS DECIMAL(20,2))
                                        ELSE 0
                                    END AS acv_op
                                FROM agg
                            ),
                            savings_calc AS (
                                SELECT
                                    provider,
                                    capitation_start_date,
                                    capitated_acv_op,
                                    total_op_amount,
                                    to_start_year,
                                    to_end_year,
                                    from_date,
                                    to_date,
                                    actual_start_date,
                                    capitation_months_by_end_year,
                                    total_cost,
                                    active_days,
                                    total_visits,
                                    contr,
                                    acv_op,
                                    CAST(ROUND(((acv_op - capitated_acv_op) * total_visits), 2) AS DECIMAL(20,2)) AS savings
                                FROM final_calc
                            )
                            SELECT
                                provider,
                                capitation_start_date,
                                capitated_acv_op,
                                total_op_amount,
                                to_start_year,
                                to_end_year,
                                from_date,
                                to_date,
                                actual_start_date,
                                capitation_months_by_end_year,
                                total_cost,
                                active_days,
                                total_visits,
                                contr,
                                acv_op,
                                savings,
                                CASE
                                    WHEN active_days = 0 OR savings = 0 THEN 0
                                    ELSE CAST(ROUND(((savings / active_days) * capitation_months_by_end_year), 2) AS DECIMAL(20,2))
                                END AS savings_annual
                            FROM savings_calc

                    ");


            foreach ($rows as $row) {
                $ttl_total_cost += $row["total_cost"];
                $ttl_contr += $row["contr"];
                $ttl_total_visits += $row["total_visits"];
                $ttl_acv_op += $row["acv_op"];
                $ttl_capitated_acv_op += $row["capitated_acv_op"];
                $ttl_savings += $row["savings"];
                $ttl_savings_annual += $row["savings_annual"];
            }

            $totals = [
                "ttl_total_cost" => $ttl_total_cost,
                "ttl_contr" => $ttl_contr,
                "ttl_total_visits" => $ttl_total_visits,
                "ttl_acv_op" => $ttl_acv_op,
                "ttl_capitated_acv_op" => $ttl_capitated_acv_op,
                "ttl_savings" => $ttl_savings,
                "ttl_savings_annual" => $ttl_savings_annual,
                "ttl_total_op_amount" => $ttl_total_op_amount
            ];

            return ["report" => $rows, "totals" => $totals];
        } catch (\Throwable $th) {
            return ["Error, $th->getMessage()"];
        }
    }
}
