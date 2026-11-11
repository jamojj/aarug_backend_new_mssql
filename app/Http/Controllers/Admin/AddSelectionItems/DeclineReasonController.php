<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeclineReason;

class DeclineReasonController extends Controller
{
    //save added decline reason
    public function saveDeclineReason(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = DeclineReason::max('code');

        //update product names
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_decline_reasons = DeclineReason::where(
                    'code', $request->code[$i]
                )->update(
                    [
                        'decline_reason' => $request->decline_reason[$i]
                    ]);

                if ($update_decline_reasons == 0) {
                    $errors_update++;
                }
            }
        }
        //save decline reasons
        if (isset($request->decline_reason_added)) {
            $count = count($request->decline_reason_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_decline_reason = DeclineReason::create([
                    'code' => $max_code,
                    'decline_reason' => $request->decline_reason_added[$i]
                ]);

                if ($save_decline_reason->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Decline Reason Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Decline Reason");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Decline Reason");
            }
        }
        return $response;
    }
}
