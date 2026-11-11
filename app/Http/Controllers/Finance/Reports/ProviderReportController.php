<?php

namespace App\Http\Controllers\Finance\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillsPayment;
use App\Models\ClaimsBatch;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderReportController extends Controller
{
    public function fetchProviderReport(Request $request)
    {
        ini_set('max_execution_time', '0');

        $response = [];
        $batches = [];
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));
        $ttl_paid = $ttl_claimed = $ttl_not_paid = 0;
        switch ($request->task) {
                //particular provider
            case '1':
                $batches = Bills::select(

                    DB::raw("            
                            CASE
                                WHEN MAX(isnull(bills.payment_no, 0)) != 0  THEN 'PAID'
                                WHEN MAX(isnull(bills.paid, 0)) = 1 AND MAX(isnull(bills.payment_no, 0)) = 0 THEN 'VOUCHERED'
                                WHEN MAX(isnull(bills.vet_status, 0)) != 0 AND MAX(isnull(bills.paid, 0)) = 0 AND MAX(isnull(bills.payment_no, 0)) = 0 THEN 'VETTED'
                                WHEN MAX(isnull(bills.vet_status, 0)) = 0 THEN 'ENTRY'
                            END AS batch_status
                "),
                    DB::raw("FORMAT(claims_batch.date_received, 'mm/yyyy') as service_month"),
                    DB::raw("sum(isnull(bills.amount_payable, 0)) as amount_payable"),
                    DB::raw("sum(isnull(bills.invoiced_amount, 0)) as amount_claimed"),
                    DB::raw("amount_claimed - amount_payable as amount_not_payable"),
                    DB::raw("MAX(isnull(bills.payment_no, '')) as payment_no"),
                    DB::raw("MAX(isnull(bills_payment.cheque_amount, '')) as cheque_amount"),
                    DB::raw("MAX(ISNULL(CONVERT(VARCHAR, FORMAT(bills_payment.cheque_date,'YYYY-MM-DD'), 120), '')) AS date_paid"),
                    'bills.batch_no',
                    'provider.provider',
                )
                    ->leftJoin('claims_batch', 'bills.batch_no', 'claims_batch.batch_no')
                    ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
                    ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                    ->groupBy('bills.batch_no', 'provider.provider', 'claims_batch.date_received')
                    ->orderBy('bills.batch_no', 'asc')
                    ->distinct('bills.provider')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                    ->where('bills.provider', $request->provider)
                    ->get();
                break;
                break;
                //all providers
            case '2':
                $batches = Bills::select(

                    DB::raw("            
                            CASE
                                WHEN MAX(isnull(bills.payment_no, 0)) != 0  THEN 'PAID'
                                WHEN MAX(isnull(bills.paid, 0)) = 1 AND MAX(isnull(bills.payment_no, 0)) = 0 THEN 'VOUCHERED'
                                WHEN MAX(isnull(bills.vet_status, 0)) != 0 AND MAX(isnull(bills.paid, 0)) = 0 AND MAX(isnull(bills.payment_no, 0)) = 0 THEN 'VETTED'
                                WHEN MAX(isnull(bills.vet_status, 0)) = 0 THEN 'ENTRY'
                            END AS batch_status
                "),
                    DB::raw("FORMAT(claims_batch.date_received, 'mm/yyyy') as service_month"),
                    DB::raw("sum(isnull(bills.amount_payable, 0)) as amount_payable"),
                    DB::raw("sum(isnull(bills.invoiced_amount, 0)) as amount_claimed"),
                    DB::raw("amount_claimed - amount_payable as amount_not_payable"),                    
                    DB::raw("MAX(isnull(bills.payment_no, '')) as payment_no"),                  
                    DB::raw("MAX(isnull(bills_payment.cheque_amount, '')) as cheque_amount"),
                    DB::raw("MAX(ISNULL(CONVERT(VARCHAR, FORMAT(bills_payment.cheque_date,'YYYY-MM-DD'), 120), '')) AS date_paid"),
                    'bills.batch_no',
                    'provider.provider',
                )
                    ->leftJoin('claims_batch', 'bills.batch_no', 'claims_batch.batch_no')
                    ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')                  
                    ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                    ->groupBy('bills.batch_no', 'provider.provider','claims_batch.date_received')
                    ->orderBy('bills.batch_no', 'asc')
                    ->distinct('bills.provider')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                    ->get();
                break;
        }


        foreach ($batches as $batch) {

            //batch_month
            // $service_month = ClaimsBatch::select(
            //     DB::raw("FORMAT(date_received, 'mm/yyyy') as date_received")
            // )
            //     ->where('batch_no', $batch->batch_no)
            //     ->get();

            // $service_month = count($service_month) == 0 ? "" : $service_month[0]->date_received;

            //GET BATCH STATUS
            // $batch_status = '';
            // $paid = Bills::whereNotNull('payment_no')
            //     ->where('batch_no', $batch->batch_no)
            //     ->get();
            // $vouchered = Bills::whereNull('payment_no')
            //     ->where(DB::raw("isnull(paid, 0)"), 1)
            //     ->where('batch_no', $batch->batch_no)
            //     ->get();
            // $vetted = Bills::where(DB::raw("isnull(paid, 0)"), 0)
            //     ->where(DB::raw("isnull(vet_status, 0)"), "!=", 0)
            //     ->where('batch_no', $batch->batch_no)
            //     ->get();
            // $entry = Bills::where(DB::raw("isnull(vet_status, 0)"), 0)
            //     ->where('batch_no', $batch->batch_no)
            //     ->get();

            // if (count($paid) > 0) {
            //     $batch->batch_status = 'PAID';
            // } else if (count($vouchered) > 0) {
            //     $batch->batch_status = 'VOUCHERED';
            // } else if (count($vetted) > 0) {
            //     $batch->batch_status = 'VETTED';
            // } else if (count($entry) > 0) {
            //     $batch->batch_status = 'ENTRY';
            // }

            //get payment date
            // $payment_no = Bills::select(
            //     'payment_no'
            // )
            //     ->distinct('payment_no')
            //     ->where('batch_no', $batch->batch_no)
            //     ->get();

            // $payment_no = count($payment_no) == 0 ? '' : $payment_no[0]->payment_no;

            // $date_paid = BillsPayment::select(
            //     DB::raw("FORMAT(cheque_date, 'dd/mm/yyyy') as date_paid")
            // )
            //     ->where('payment_no', $batch->payment_no)
            //     ->get();

            // $date_paid = count($date_paid) == 0 ? '' : $date_paid[0]->date_paid;

            //GET TOTAL PAYMENT
            // $payment_info = Bills::select(
            //     // DB::raw("case when payment_no is not null then sum(isnull(amount_payable, 0)) else 0 END as amount_paid"),
            //     DB::raw("sum(isnull(amount_payable, 0)) as amount_paid"),
            //     DB::raw("sum(isnull(invoiced_amount, 0)) as amount_claimed"),
            // )
            //     // ->groupBy('payment_no')
            //     ->where('batch_no', $batch->batch_no)
            //     ->get();

            // $amount_not_paid = $payment_info[0]->amount_claimed -  $payment_info[0]->amount_paid;

            // // calculating totals
            $ttl_paid += $batch->amount_payable ;
            $ttl_claimed += $batch->amount_claimed;
            $ttl_not_paid += $batch->amount_not_payable;

            // $dt = [
            //     'provider' => $batch->provider,
            //     'service_month' => $batch->service_month,
            //     'batch_no' => $batch->batch_no,
            //     'batch_status' => $batch->batch_status,
            //     'amount_paid' => number_format($batch->amount_paid, 2),
            //     'amount_claimed' => number_format($batch->amount_claimed, 2),
            //     'amount_not_paid' => number_format($amount_not_paid, 2),
            //     'date_paid' => $batch->date_paid
            // ];

            // array_push($response, $dt);
        }
        $totals = [
            'ttl_payable' => number_format($ttl_paid, 2),
            'ttl_claimed' =>  number_format($ttl_claimed, 2),
            'ttl_not_payable' => number_format($ttl_not_paid, 2)
        ];

        return ['batch_data' => $batches, 'totals' => $totals];
    }
}
