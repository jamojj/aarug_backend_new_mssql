<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\RejectReason;
use Illuminate\Http\Request;

class RejectReasonController extends Controller
{
    //save added reject reason
    public function saveRejectionReason(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = RejectReason::max('code');

        //update product names
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_rejection_reasons = RejectReason::where(
                    'code', $request->code[$i]
                )->update(
                    [
                        'reject_reason' => $request->rejection_reason[$i]
                    ]);

                if ($update_rejection_reasons == 0) {
                    $errors_update++;
                }
            }
        }
        //save rejection reasons
        if (isset($request->rejection_reason_added)) {
            $count = count($request->rejection_reason_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_rejection_reason = RejectReason::create([
                    'code' => $max_code,
                    'reject_reason' => $request->rejection_reason_added[$i]
                ]);

                if ($save_rejection_reason->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Rejection Reason Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Rejection Reason");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Rejection Reason");
            }
        }
        return $response;
    }
}
