<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\DeductionReason;
use Illuminate\Http\Request;

class DeductionReasonsController extends Controller
{
    public function fetchDeductionReasons()
    {
        $reasons = DeductionReason::select('*')
            ->get();
        $reasons = json_encode($reasons, JSON_INVALID_UTF8_IGNORE);
        return $reasons;
    }

    public function saveDeductionReasons(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = DeductionReason::max('code');

        //update deduction reason
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_reason = DeductionReason::where('code', $request->code[$i])->update([
                    'code' => $request->code[$i],
                    'deduct_reason' => $request->deduct_reason[$i],
                    'category' => $request->category[$i]
                ]);

                if ($update_reason == 0) {
                    $errors_update++;
                }
            }
        }
        //save deduction reason
        if (isset($request->deduct_reason_added)) {
            $count = count($request->deduct_reason_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_reason = DeductionReason::create([
                    'code' => $max_code,
                    'deduct_reason' => $request->deduct_reason_added[$i],
                    'category' => 1
                ]);

                if ($save_reason->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Deduct Reason saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Deduct Reason");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Deduct Reason");
            }
        }


        return $response;
    }
}
