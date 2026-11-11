<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CignaPremiumReceipt;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CignaPremiumReceiptController extends Controller
{
    public function savePremiumReceiptsCigna(Request $request)
    {
        $max_idx = CignaPremiumReceipt::max('idx');
        $max_idx = is_null($max_idx) ? 0 : $max_idx;
        $max_idx += 1;
        $year = date('Y');
        $receipt_no = 'ALA' . '-' . $max_idx . '-' . $year;
        $date_entered = date('Y-m-d');
        try {
            DB::transaction(function () use($request, $max_idx, $date_entered, $receipt_no) {
                //set inserted, updated, and deleted items
                $inserted_item = "idx : $max_idx, receipt_no : $receipt_no";
                $updated_item = null;
                $deleted_item = null;

                CignaPremiumReceipt::create([
                    'idx'=>$max_idx,
                    'receipt_no'=> $receipt_no,
                    'family_no'=> $request->family_no,
                    'receipt_amount' => $request->receipt_amount,
                    'payment_mode'=> $request->payment_mode,
                    'cheque_no'=> $request->cheque_no,
                    'bank'=> $request->bank,
                    'bank_account'=> $request->bank_account,
                    'user_id'=> $request->user_id,
                    'date_entered'=> $date_entered,
                    'receipt_date'=> $request->receipt_date
                ]);

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    
            });
            return ['Receipt saved successfully.'];
        } catch (Exception $e) {
            return ['Error' . $e->getMessage()];
        }
    }
}
