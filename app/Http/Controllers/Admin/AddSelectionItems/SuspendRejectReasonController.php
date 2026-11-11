<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\MemberDifferReject;
use Illuminate\Http\Request;

class SuspendRejectReasonController extends Controller
{
    public function fetchSuspendRejectReasons()
    {
        $suspend_reject = MemberDifferReject::select('*')
            ->get();
        return $suspend_reject;
    }

    public function saveSuspendRejectReasons(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = MemberDifferReject::max('code');

        //update bdm
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_suspend_reject = MemberDifferReject::where('code', $request->code[$i])->update([
                    'reason' => $request->reason[$i]
                ]);

                if ($update_suspend_reject == 0) {
                    $errors_update++;
                }
            }
        }
        //save bdm
        if (isset($request->reason_added)) {
            $count = count($request->reason_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_suspend_reject = MemberDifferReject::create([
                    'code' => $max_code,
                    'reason' => $request->reason_added[$i]
                ]);

                if ($save_suspend_reject->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Suspend/Reject Reason saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Suspend/Reject Reason ");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Suspend/Reject Reason ");
            }
        }


        return $response;
    }
}
