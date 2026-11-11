<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReinsurerExcessOfLoss;
use Illuminate\Http\Request;

class ReinsurerExcessOfLossController extends Controller
{
    public function fetchExcessOfLoss()
    {
        $excess_of_loss = ReinsurerExcessOfLoss::all();
        return $excess_of_loss;
    }

    public function saveExcessOfLoss(Request $request)
    {
        $response = [];
        $error1 = $error2 = 0;
        //update existing quotashare treaties
        if (isset($request->code)) {
            for ($i = 0; $i < count($request->code); $i++) {
                $update_quota_share = ReinsurerExcessOfLoss::where('code', $request->code[$i])->update([
                    're_insurer' => $request->re_insurer[$i],
                    'upper_limit' => (int)$request->upper_limit[$i],
                    'rate_period' => $request->rate_period[$i],
                ]);

                if ($update_quota_share != 1) {
                    $error1++;
                    $reinsurance_name = $request->re_insurer[$i];
                    array_push($response, "Error while updating $reinsurance_name");
                }
            }
        }


        //save new quotashare treaties
        if (isset($request->upper_limit_appended)) {
            $max_code = ReinsurerExcessOfLoss::max('code');
            for ($i = 0; $i < count($request->upper_limit_appended); $i++) {
                $max_code++;
                $update_quota_share = ReinsurerExcessOfLoss::create([
                    'code' => $max_code,
                    're_insurer' => $request->re_insurer_appended[$i],
                    'upper_limit' => (int)$request->upper_limit_appended[$i],
                    'rate_period' => $request->rate_period_appended[$i],
                ]);

                if ($update_quota_share->wasRecentlyCreated === false) {
                    $error2++;
                    $reinsurance_name = $request->re_insurer_appended[$i];
                    array_push($response, "Error while saving $reinsurance_name");
                }
            }
        }

        if ($error1 == 0 || $error2 == 0) {
            array_push($response, "Reinsurance saved successfully.");
        }

        return $response;
    }
}
