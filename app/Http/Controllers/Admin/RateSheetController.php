<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientRates;
use App\Models\Corporate\Corporate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RateSheetController extends Controller
{
    public function fetchRateSheetData($corp_id)
    {
        $client_rates = ClientRates::where('corp_id', $corp_id)
        ->orderBy(DB::raw("case when family_no is null then corp_id else family_no END"), 'ASC')
        ->get();
        $corporate = Corporate::select('corp_id', 'corporate', 'individual')->where('corp_id', $corp_id)->get();
        return ['client_rates' => $client_rates, 'corporate' => $corporate];
    }

    public function saveRateSheet(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_idx = ClientRates::max('idx');

        //update rate sheet
        if (isset($request->idx)) {
            $count = count($request->idx);
            for ($i = 0; $i < $count; $i++) {
                $update_rate_sheet = ClientRates::where('idx', $request->idx[$i])->update([
                    'product_name' => $request->product_name[$i],
                    'family_size' => $request->family_size[$i],
                    'family_title' => $request->family_title[$i],
                    'premium' => $request->premium[$i],
                    'min_age' => $request->min_age[$i],
                    'max_age' => $request->max_age[$i],
                    'individual' => $request->client_type[$i],
                    'corp_id' => $request->corporate[$i],
                    'health_plan' => $request->health_plan[$i],
                    'family_no' => $request->family_no[$i]
                ]);

                if ($update_rate_sheet == 0) {
                    $errors_update++;
                }
            }
        }
        //save rate sheet
        if (isset($request->corp_appended)) {
            $count = count($request->corp_appended);
            for ($i = 0; $i < $count; $i++) {
                $max_idx++;
                $save_rate_sheet = ClientRates::create([
                    'idx' => $max_idx,
                    'product_name' => $request->product_name_appended[$i],
                    'family_size' => $request->family_size_appended[$i],
                    'family_title' => $request->family_title_appended[$i],
                    'premium' => $request->premium_appended[$i],
                    'min_age' => $request->min_age_appended[$i],
                    'max_age' => $request->max_age_appended[$i],
                    'individual' => $request->client_type_appended[$i],
                    'corp_id' => $request->corp_appended[$i],
                    'health_plan' => $request->health_plan_appended[$i],
                    'family_no' => $request->family_no_appended[$i]
                ]);

                if ($save_rate_sheet->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Rate sheet saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving rate sheet");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating rate sheet");
            }
        }


        return $response;
    }
}
