<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\ClaimsBatch;
use App\Models\ResendBillReason;
use App\Models\ResendBills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchProcessReportController extends Controller
{
    //fetch batch process report
    public function fetchBatchProcessReport(Request $request){

         ini_set('memory_limit', '1524M');
         ini_set('max_execution_time', 800);

        //return $request;

        $batch_data = ClaimsBatch::select('batch_no', 'original_cnt', 'user_id')
            ->distinct()
            ->where('date_entered', '>=', $request->date_from)
            ->where('date_entered', '<=', $request->date_to)
            ->get();

        foreach ($batch_data as $data){
            $ls_batch_no = $data->batch_no;

            $ll_entered = Bills::select(DB::raw("count(claim_no) as count"))
                ->where('batch_no', $ls_batch_no)
                ->where(function ($vet_status) {
                    $vet_status->whereNotNull('bills.vet_status')
                        ->orWhere('bills.vet_status',NULL);
                })
                ->get();
            $ll_entered = (int)$ll_entered[0]['count'];
            $data->entered = $ll_entered;

            $ll_vetted = Bills::select(DB::raw("count(claim_no) as count"))
                ->where('batch_no', $ls_batch_no)
                ->whereNotNull('bills.vet_status')
                ->get();
            $ll_vetted = (int)$ll_vetted[0]['count'];
            $data->vetted = $ll_vetted;

            $ll_resend = ResendBills::select(DB::raw("count(claim_no) as count"))
                ->where('batch_no', $ls_batch_no)
                ->get();
            $ll_resend = (int)$ll_resend[0]['count'];
            $data->resend = $ll_resend;

            $ll_unprocessed = ((int)$data->original_cnt - $ll_entered);

            $data->unprocessed = $ll_unprocessed;
        }
        return $batch_data;
    }
}
