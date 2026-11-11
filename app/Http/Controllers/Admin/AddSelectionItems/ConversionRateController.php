<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\ConversionRates;
use Illuminate\Http\Request;

class ConversionRateController extends Controller
{
    //save added conversion rates
    public function saveConversionRates(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = ConversionRates::max('idx');

        //update conversion rates
        if (isset($request->idx)) {
            $count = count($request->idx);
            for ($i = 0; $i < $count; $i++) {
                $update_rates = ConversionRates::where(
                    'idx', intval($request->idx[$i])
                )->update([
                    'currency' => $request->currency[$i],
                    'value' => intval($request->value[$i]),
                    'operation' => intval($request->operation[$i]),
                ]);

                if ($update_rates == 0) {
                    $errors_update++;
                }
            }
        }
        //save rates
        if (isset($request->currency_added)) {
            $count = count($request->currency_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_rate = ConversionRates::create([
                    'idx' => $max_code,
                    'currency' => $request->currency_added[$i],
                    'value' => $request->value_added[$i],
                    'operation' => $request->operation_added[$i]
                ]);

                if ($save_rate->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Conversion Rate Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Conversion Rate");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Conversion Rate");
            }
        }
        return $response;
    }
}
