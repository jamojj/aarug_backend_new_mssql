<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\ClaimsBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BatchReportController extends Controller
{
    public function fetchBatchReport(Request $request)
    {
        // return $request;
        ini_set('max_execution_time', '0');
        $status = '';
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));
        $batch_array = [];
        switch ($request->option) {
                //provider
            case '1':
                $data = ClaimsBatch::select(
                    'batch_no',
                    'claims_batch.date_entered as batch_date',
                    'serial_no',
                    'batch_count',
                    'original_cnt',
                    'batch_amount',
                    'provider.provider'
                )
                    ->leftJoin('provider', 'claims_batch.provider', 'provider.code')
                    // ->where('claims_batch.date_entered', '>=', $from)
                    // ->where('claims_batch.date_entered', '<=', $to)
                    ->orderBy('batch_date', 'ASC')
                    ->distinct('batch_no')
                    ->whereBetween(DB::raw("FORMAT(claims_batch.date_entered, 'yyyyMMdd')"), [$from, $to])
                    ->where('claims_batch.provider', $request->provider)
                    ->get();
                //  return $data;
                foreach ($data as $dt) {
                    $vetter = Bills::select('bill_vet.user_id as vetter')
                        ->join('bill_vet', 'bills.claim_no', '=', 'bill_vet.claim_no')
                        ->where('bills.batch_no', $dt->batch_no)
                        ->distinct()
                        ->orderBy('vetter')
                        ->pluck('vetter');

                    $vetter = count($vetter) !== 0 ? $vetter[0] : '';

                    $amount_entered = Bills::select(
                        DB::raw('sum(isnull(invoiced_amount,0)) as invoiced_amount')
                    )
                        ->where('batch_no', $dt->batch_no)
                        ->get();

                    $amount_entered = count($amount_entered) !== 0 ? $amount_entered[0]->invoiced_amount : '';


                    $amount_payable = Bills::select(
                        DB::raw('sum(isnull(amount_payable,0)) as amount_payable')
                    )
                        ->where(DB::raw('isnull(vet_status, 0)'), [1, 3])
                        ->where('batch_no', $dt->batch_no)
                        ->get();

                    $amount_payable = count($amount_payable) !== 0 ? $amount_payable[0]->amount_payable : '';

                    $date_entered = Bills::select(
                        DB::raw("FORMAT(date_entered, 'yyyy-mm-dd') as date_entered")
                    )
                        ->where('batch_no', $dt->batch_no)
                        ->distinct()
                        ->pluck('date_entered');

                    $date_entered = count($date_entered) !== 0 ? $date_entered[0] : '';

                    $user_id = Bills::select('user_id as entrant')
                        ->where('batch_no', $dt->batch_no)
                        ->distinct()
                        ->pluck('entrant');

                    $user_id = count($user_id) !== 0 ? $user_id[0] : '';

                    $ll_stat = Bills::select(
                        DB::raw('count(distinct claim_no) as count')
                    )
                        // ->whereNotNull('vet_status')
                        ->where('batch_no', $dt->batch_no)
                        ->get();


                    $ll_stat = count($ll_stat) !== 0 ?  $ll_stat[0]->count : 0;



                    // if ($vetter !== '') {
                    //     //check if vouchered
                    //     $vouchered = Bills::where('batch_no', $dt->batch_no)->whereNotNull('voucher_no')->get();
                    //     if (count($vouchered)) {
                    //         //check if paid
                    //         $paid = Bills::where('batch_no', $dt->batch_no)->whereNotNull('payment_no')->get();
                    //         if (count($paid)) {
                    //             $status = 'PAID';
                    //         }
                    //     } else {
                    //         $status = 'VETTED';
                    //     }
                    // } else {
                    //     $status = 'ENTERED';
                    // }

                    //get batch statuses
                    $entered = Bills::where(DB::raw("isnull(vet_status, 0)"), 0)
                        ->where('batch_no', $dt->batch_no)
                        ->get();
                    $entered = count($entered);
                    $vetted = Bills::where(DB::raw("isnull(vet_status, 0)"), '!=', 0)
                        ->where(DB::raw("isnull(paid, 0)"), 0)
                        ->where('batch_no', $dt->batch_no)
                        ->get();
                    $vetted = count($vetted);
                    $vouchered = Bills::where(DB::raw("isnull(vet_status, 0)"), '!=', 0)
                        ->where(DB::raw("isnull(paid, 0)"), 1)
                        ->whereNull('payment_no')
                        ->where('batch_no', $dt->batch_no)
                        ->get();
                    $vouchered = count($vouchered);
                    $paid = Bills::where(DB::raw("isnull(vet_status, 0)"), '!=', 0)
                        ->where(DB::raw("isnull(paid, 0)"), 1)
                        ->whereNotNull('payment_no')
                        ->where('batch_no', $dt->batch_no)
                        ->get();
                    $paid = count($paid);


                    switch (max($entered, $vetted, $vouchered, $paid)) {
                        case $paid:
                            $status = 'Chequed/Paid';
                            break;
                        case $entered:
                            $status = 'Entered';
                            break;
                        case $vetted:
                            $status = 'Vetted';
                            break;
                        case $vouchered:
                            $status = 'Vouchered';
                            break;
                    }


                    $datas = [
                        'batch_date' => $dt->batch_date,
                        'batch_no' => $dt->batch_no,
                        'provider' => $dt->provider,
                        'invoice_no' => $dt->serial_no,
                        'original_cnt' => $dt->original_cnt,
                        // 'fin_count' => $dt->batch_count,
                        'fin_count' => $ll_stat,
                        'batch_amount' => $dt->batch_amount,
                        'vetter' => $vetter,
                        'amount_entered' => $amount_entered,
                        'amount_payable' => $amount_payable,
                        'date_entered' => $date_entered,
                        'user_id' => $user_id,
                        'status' => $status,
                    ];
                    array_push($batch_array, $datas);
                }
                break;
                //all providers
            case '2':
                $data = ClaimsBatch::select(
                    'batch_no',
                    'claims_batch.date_entered as batch_date',
                    'serial_no',
                    'batch_count',
                    'original_cnt',
                    'batch_amount',
                    'provider.provider'
                )
                    ->leftJoin('provider', 'claims_batch.provider', 'provider.code')
                    // ->where('claims_batch.date_entered', '>=', $from)
                    // ->where('claims_batch.date_entered', '<=', $to)
                    ->orderBy('batch_date', 'ASC')
                    ->distinct('batch_no')
                    ->whereBetween(DB::raw("FORMAT(claims_batch.date_entered, 'yyyyMMdd')"), [$from, $to])
                    ->get();
                //  return $data;
                foreach ($data as $dt) {
                    $vetter = Bills::select('bill_vet.user_id as vetter')
                        ->leftJoin('bill_vet', 'bills.claim_no', '=', 'bill_vet.claim_no')
                        ->where('bills.batch_no', $dt->batch_no)
                        ->distinct()
                        ->orderBy('vetter')
                        ->pluck('vetter');

                    $vetter = count($vetter) !== 0 ? $vetter[0] : '';

                    $amount_entered = Bills::select(
                        DB::raw('sum(isnull(invoiced_amount, 0)) as invoiced_amount')
                    )
                        ->where('batch_no', $dt->batch_no)
                        ->pluck('invoiced_amount');

                    $amount_entered = count($amount_entered) !== 0 ? $amount_entered[0] : '';

                    $amount_payable = Bills::select(
                        DB::raw('sum(isnull(amount_payable, 0)) as amount_payable')
                    )
                        ->where(DB::raw('isnull(vet_status, 0)'), [1, 3])
                        ->where('batch_no', $dt->batch_no)
                        ->pluck('amount_payable');
                    $amount_payable = count($amount_payable) !== 0 ? $amount_payable[0] : '';

                    $date_entered = Bills::select(
                        DB::raw("FORMAT(date_entered, 'yyyy-mm-dd') as date_entered")
                    )
                        ->distinct()
                        ->where('batch_no', $dt->batch_no)
                        ->pluck('date_entered');
                    $date_entered = count($date_entered) !== 0 ? $date_entered[0] : '';

                    $user_id = Bills::select('user_id as entrant')
                        ->distinct()
                        ->where('batch_no', $dt->batch_no)
                        ->pluck('entrant');

                    $user_id = count($user_id) !== 0 ? $user_id[0] : '';

                    $ll_stat = Bills::select(
                        DB::raw('count(distinct claim_no) as count')
                    )
                        ->whereNotNull('vet_status')
                        ->where('batch_no', $dt->batch_no)
                        ->get();

                    $ll_stat = count($ll_stat) !== 0 ?  $ll_stat[0]->count : 0;



                    //get batch statuses
                    $entered = Bills::where(DB::raw("isnull(vet_status, 0)"), 0)
                    ->where('batch_no', $dt->batch_no)
                    ->get();
                    $entered = count($entered);
                    $vetted = Bills::where(DB::raw("isnull(vet_status, 0)"), '!=', 0)
                    ->where(DB::raw("isnull(paid, 0)"), 0)
                        ->where('batch_no', $dt->batch_no)
                        ->get();
                    $vetted = count($vetted);
                    $vouchered = Bills::where(DB::raw("isnull(vet_status, 0)"), '!=', 0)
                    ->where(DB::raw("isnull(paid, 0)"), 1)
                        ->whereNull('payment_no')
                        ->where('batch_no', $dt->batch_no)
                        ->get();
                    $vouchered = count($vouchered);
                    $paid = Bills::where(DB::raw("isnull(vet_status, 0)"), '!=', 0)
                    ->where(DB::raw("isnull(paid, 0)"), 1)
                        ->whereNotNull('payment_no')
                        ->where('batch_no', $dt->batch_no)
                        ->get();
                    $paid = count($paid);


                    switch (max($entered, $vetted, $vouchered, $paid)) {
                        case $paid:
                            $status = 'Chequed/Paid';
                            break;
                        case $entered:
                            $status = 'Entered';
                            break;
                        case $vetted:
                            $status = 'Vetted';
                            break;
                        case $vouchered:
                            $status = 'Vouchered';
                            break;
                    }


                    $datas = [
                        'batch_date' => $dt->batch_date,
                        'batch_no' => $dt->batch_no,
                        'provider' => $dt->provider,
                        'invoice_no' => $dt->serial_no,
                        'original_cnt' => $dt->original_cnt,
                        'fin_count' => $dt->batch_count,
                        'batch_amount' => $dt->batch_amount,
                        'vetter' => $vetter,
                        'amount_entered' => $amount_entered,
                        'amount_payable' => $amount_payable,
                        'date_entered' => $date_entered,
                        'user_id' => $user_id,
                        'status' => $status,
                    ];
                    array_push($batch_array, $datas);
                }
                break;
        }
        return $batch_array;
    }
}
