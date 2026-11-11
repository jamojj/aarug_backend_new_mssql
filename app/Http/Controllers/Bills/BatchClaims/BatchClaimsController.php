<?php

namespace App\Http\Controllers\Bills\BatchClaims;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\ClaimsBatch;
use App\Models\LastNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BatchClaimsController extends Controller
{
    //save batch claims
    public function saveClaimsBatch(Request $request)
    {
        //return $request;
        //smart batch
        $response = [];
        if ($request->user_id == 'INTEG') {
            $batch_type = 'E';
        } else {
            $batch_type = 'M';
        }
        switch ($request->task) {
                //create
            case '1':
                if (isset($request->provider)) {
                    try {
                        $max_id = ClaimsBatch::select('id')->max('id');
                        $max_id = $max_id + 1;
                        $batch_no = 'BAT-' . $max_id;

                        DB::transaction(function () use ($request, $max_id, $batch_no, $batch_type) {
                            //set inserted, updated, and deleted items
                            $inserted_item = "id : $max_id, batch_no : $batch_no";
                            $updated_item = null;
                            $deleted_item = null;

                            ClaimsBatch::create([
                                'id' => $max_id,
                                'batch_no' => $batch_no,
                                'batch_count' => $request->batch_count,
                                'batch_amount' => $request->batch_amount,
                                'user_id' => $request->user_id,
                                'date_entered' => $request->date_created,
                                'provider' => $request->provider,
                                'serial_no' => $request->serial_no,
                                'date_received' => $request->date_received,
                                'original_amt' => (int)$request->batch_amount,
                                'original_cnt' => (int)$request->batch_count,
                                'date_submitted' => $request->date_submitted,
                                'actual_amt' => $request->actual_amount,
                                'batch_type' => $batch_type,
                            ]);

                            //save access log
                            app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                        });
                        array_push($response, 'Claims Batch created successfully. Batch No: ' . $batch_no);
                    } catch (\Exception $e) {
                        array_push($response, "Error " . $e->getMessage());
                    }
                }
                break;
                //update
            case '2':
                if (isset($request->batch_no)) {

                    try {
                        DB::transaction(function () use ($request) {
                            //set inserted, updated, and deleted items
                            $inserted_item = null;
                            $updated_item = "batch_no : $request->batch_no";
                            $deleted_item = null;

                            //user id and batch type, and provider will not be updated to prevent dublication of batches
                            ClaimsBatch::where('batch_no', $request->batch_no)
                                ->update([
                                    // 'batch_count' => $request->batch_count,
                                    // 'batch_amount' => $request->amount_entered,
                                    // 'user_id' => $request->user_id,
                                    // 'date_entered' => $request->date_entered,
                                    // 'provider' => $request->provider,
                                    // 'serial_no' => $request->serial_no,
                                    // 'date_received' => $request->date_received,
                                    // 'original_amt' => $request->batch_amount,
                                    // 'original_cnt' => $request->batch_count,
                                    // 'batch_type' => $batch_type,
                                    'date_submitted' => $request->date_submitted,
                                    'actual_amt' => $request->actual_amount,
                                ]);

                            //save access log
                            app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                        });

                        array_push($response, 'Claims Batch Updated successfully. Batch No: ' . $request->batch_no);
                    } catch (\Exception $e) {
                        array_push($response, "Error " . $e->getMessage());
                    }
                }
        }
        return $response;
    }
    //query claims batch
    public function queryClaimsBatch($batch_no)
    {
        $batch_no = 'BAT-' . $batch_no;
        $data = ClaimsBatch::select(
            DB::raw("SUM(isnull(bills.invoiced_amount, 0)) as batch_amt"),
            'claims_batch.*'
            )
            ->leftJoin('bills', 'bills.batch_no', '=', 'claims_batch.batch_no')
            ->groupBy( 'claims_batch.id','claims_batch.batch_no','claims_batch.batch_count','claims_batch.batch_amount','claims_batch.user_id','claims_batch.date_entered','claims_batch.provider','claims_batch.serial_no','claims_batch.date_received','claims_batch.original_amt','claims_batch.original_cnt','claims_batch.actual_amt','claims_batch.batch_type','claims_batch.date_submitted')
            ->where('claims_batch.batch_no', $batch_no)
            ->get();
       

        return ['response' => $data[0]];
    }
}
