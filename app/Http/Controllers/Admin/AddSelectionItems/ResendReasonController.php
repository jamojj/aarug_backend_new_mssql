<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ResendBillReason;

class ResendReasonController extends Controller
{
    //fetch resend reasons
    public function fetchResendReason()
    {
        $bill_reason = ResendBillReason::select('code', 'resend_reason')
            ->orderBy('code')
            ->get();
        return $bill_reason;
    }

    //save added resend reason
    public function saveResendReason(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = ResendBillReason::max('code');

        //update product names
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_resend_reasons = ResendBillReason::where(
                    'code', $request->code[$i]
                )->update(
                    [
                        'resend_reason' => $request->resend_reason[$i]
                    ]);

                if ($update_resend_reasons == 0) {
                    $errors_update++;
                }
            }
        }
        //save resend reasons
        if (isset($request->resend_reason_added)) {
            $count = count($request->resend_reason_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_resend_reason = ResendBillReason::create([
                    'code' => $max_code,
                    'resend_reason' => $request->resend_reason_added[$i]
                ]);

                if ($save_resend_reason->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Resend Reason Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Resend Reason");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Resend Reason");
            }
        }
        return $response;
    }
}
