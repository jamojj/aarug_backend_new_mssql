<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReInsurer;
use Illuminate\Http\Request;

class ReinsurerQuotaShareController extends Controller
{
    public function fetchQuotaShare()
    {
        $quota_share = ReInsurer::all();
        return $quota_share;
    }

    public function saveQuotaShare(Request $request)
    {
        $response = [];
        $error1 = $error2 = 0;
        //update existing quotashare treaties
        if (isset($request->code)) {
            for ($i = 0; $i < count($request->code); $i++) {
                $update_quota_share = ReInsurer::where('code',$request->code[$i])->update([
                    're_insurer'=>$request->re_insurer[$i],
                    'address'=>$request->address[$i],
                    'physical_location'=>$request->physical_location[$i],
                    'ip_rate'=> (int)$request->ip_rate[$i],
                    'commis_rate'=> (int)$request->commis_rate[$i],
                    'rate_period'=> (int)$request->rate_period[$i],
                    're_broker'=> (int)$request->re_broker[$i],
                    'tax_rate'=> (int)$request->tax_rate[$i],
                    'retain_rate'=> (int)$request->retain_rate[$i],
                ]);

                if ($update_quota_share != 1) {
                    $error1++;
                    $reinsurance_name = $request->re_insurer[$i];
                   array_push($response, "Error while updating $reinsurance_name");
                }
            }
        }


        //save new quotashare treaties
        if (isset($request->ip_rate_appended)) {
            $max_code = ReInsurer::max('code');
            for ($i = 0; $i < count($request->ip_rate_appended); $i++) {
                $max_code++;
                $update_quota_share = ReInsurer::create([
                    'code' => $max_code,
                    're_insurer' => $request->re_insurer_appended[$i],
                    'address' => $request->address_appended[$i],
                    'physical_location' => $request->physical_location_appended[$i],
                    'ip_rate' => (int)$request->ip_rate_appended[$i],
                    'commis_rate' => (int)$request->commis_rate_appended[$i],
                    'rate_period' => (int)$request->rate_period_appended[$i],
                    're_broker' => (int)$request->re_broker_appended[$i],
                    'tax_rate' => (int)$request->tax_rate_appended[$i],
                    'retain_rate' => (int)$request->retain_rate_appended[$i],
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
