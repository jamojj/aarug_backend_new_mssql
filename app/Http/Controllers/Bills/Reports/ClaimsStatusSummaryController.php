<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimsStatusSummaryController extends Controller
{
    public function fetchClaimsStatusSummary(Request $request)
    {
        ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', '0');
        $response  = [];
        $ttl_entered = $ttl_vetted = $ttl_vouchered = $ttl_paid = $ttl_pending_vetting = $ttl_pending_voucher = $ttl_pending_paid = 0;
        switch ($request->option) {
                //year
            case '1':
                $period = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
                foreach ($period as $mth) {
                    $entered = Bills::where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $request->year . $mth)
                        ->sum(DB::raw("isnull(invoiced_amount, 0)"));
                    $vetted = Bills::where(DB::raw("isnull(vet_status, 0)"),'!=', 0)
                        ->where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $request->year . $mth)
                        ->sum(DB::raw("isnull(invoiced_amount, 0)"));    
                    $vetted_approved = Bills::where(DB::raw("isnull(vet_status, 0)"), 1)
                        ->where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $request->year . $mth)
                        ->sum(DB::raw("isnull(invoiced_amount, 0)"));    
                                  
                    $vouchered = Bills::where(DB::raw("isnull(vet_status, 0)"), 1)
                        ->where(DB::raw("isnull(bills.paid, 0)"),  1)
                        ->where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $request->year . $mth)
                        ->sum(DB::raw("isnull(invoiced_amount, 0)"));                   
                    $paid = Bills::where(DB::raw("isnull(paid, 0)"), 1)
                        ->whereNotNull('payment_no')
                        ->where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $request->year . $mth)
                        ->sum(DB::raw("isnull(invoiced_amount, 0)"));                   


                    $pending_vetting = $entered - $vetted;
                    $pending_voucher = $vetted_approved - $vouchered;
                    $pending_paid = $vouchered - $paid;

                    //calculate totals
                    $ttl_entered += $entered;
                    $ttl_vetted += $vetted;
                    $ttl_vouchered += $vouchered;
                    $ttl_paid += $paid;
                    $ttl_pending_vetting += $pending_vetting;
                    $ttl_pending_voucher += $pending_voucher;
                    $ttl_pending_paid += $pending_paid;

                    $dt = [
                        'period' => $mth . '/' . $request->year,
                        'entered' => number_format($entered, 2),
                        'vetted' => number_format($vetted, 2),
                        'pending_vetting' => number_format($pending_vetting, 2),
                        'vouchered' => number_format($vouchered, 2),
                        'pending_voucher' => number_format($pending_voucher, 2),
                        'paid' => number_format($paid, 2),
                        'pending_paid' => number_format($pending_paid, 2),
                    ];
                    array_push($response, $dt);
                }

                break;
                //months
            case '2':
                $month = explode("/", $request->month);
                $month = $month[1] . $month[0];

                $entered = Bills::where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $month)
                    ->sum(DB::raw("isnull(invoiced_amount, 0)"));
                $vetted = Bills::where(DB::raw("isnull(vet_status, 0)"), '!=', 0)
                    ->where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $month)
                    ->sum(DB::raw("isnull(invoiced_amount, 0)"));
                $vetted_approved = Bills::where(DB::raw("isnull(vet_status, 0)"), 1)
                    ->where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $month)
                    ->sum(DB::raw("isnull(invoiced_amount, 0)"));
               
                $vouchered = Bills::where(DB::raw("isnull(vet_status, 0)"), 1)
                    ->where(DB::raw("isnull(bills.paid, 0)"),  1)
                    ->where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $month)
                    ->sum(DB::raw("isnull(invoiced_amount, 0)"));
               
                $paid = Bills::where(DB::raw("isnull(paid, 0)"), 1)
                    ->whereNotNull('payment_no')
                    ->where(DB::raw("FORMAT(invoice_date, 'yyyymm')"), $month)
                    ->sum(DB::raw("isnull(invoiced_amount, 0)"));               

                $pending_vetting = $entered - $vetted;
                $pending_voucher = $vetted_approved - $vouchered;
                $pending_paid = $vouchered - $paid;

                //calculate totals
                $ttl_entered += $entered;
                $ttl_vetted += $vetted;
                $ttl_vouchered += $vouchered;
                $ttl_paid += $paid;
                $ttl_pending_vetting += $pending_vetting;
                $ttl_pending_voucher += $pending_voucher;
                $ttl_pending_paid += $pending_paid;

                $dt = [
                    'period' => $request->month,
                    'entered' => number_format($entered, 2),
                    'vetted' => number_format($vetted, 2),
                    'pending_vetting' => number_format($pending_vetting, 2),
                    'vouchered' => number_format($vouchered, 2),
                    'pending_voucher' => number_format($pending_voucher, 2),
                    'paid' => number_format($paid, 2),
                    'pending_paid' => number_format($pending_paid, 2),
                ];

                array_push($response, $dt);
                break;
        }

        $dt_totals = [
            "ttl_entered" => number_format($ttl_entered, 2),
            "ttl_vetted" => number_format($ttl_vetted, 2),
            "ttl_vouchered" => number_format($ttl_vouchered, 2),
            "ttl_paid" => number_format($ttl_paid, 2),
            "ttl_pending_vetting" => number_format($ttl_pending_vetting, 2),
            "ttl_pending_voucher" => number_format($ttl_pending_voucher, 2),
            "ttl_pending_paid" => number_format($ttl_pending_paid, 2),
        ];


        return ["response" => $response, "totals" => $dt_totals];
    }
}
