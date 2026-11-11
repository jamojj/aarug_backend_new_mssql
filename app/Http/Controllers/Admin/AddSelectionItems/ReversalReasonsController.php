<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Finance\ReversalReason;
use Illuminate\Http\Request;

class ReversalReasonsController extends Controller
{
    public function fetchReversalReasons()
    {
        $reversal_reasons = ReversalReason::select('*')
            ->get();
        return $reversal_reasons;
    }

    public function saveReversalReasons(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = ReversalReason::max('code');

        //update reversal reason
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_reversal_reason = ReversalReason::where('code', $request->code[$i])->update([
                    'reason' => $request->reason[$i]
                ]);

                if ($update_reversal_reason == 0) {
                    $errors_update++;
                }
            }
        }
        //save reversal reason
        if (isset($request->reason_added)) {
            $count = count($request->reason_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_reversal_reason = ReversalReason::create([
                    'code' => $max_code,
                    'reason' => $request->reason_added[$i]
                ]);

                if ($save_reversal_reason->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Reversal Reason saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Reversal Reason");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Reversal Reason");
            }
        }


        return $response;
    }
}
