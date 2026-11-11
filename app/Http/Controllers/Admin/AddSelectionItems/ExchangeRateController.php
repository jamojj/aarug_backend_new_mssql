<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRates;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{

    public function fetchExchangeRates()
    {
        $exchange_rate = ExchangeRates::select('*')
            ->get();
        return $exchange_rate;
    }

    public function saveExchangeRates(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_id = ExchangeRates::max('id');

        //update exchange rate
        if (isset($request->id)) {
            $count = count($request->id);
            for ($i = 0; $i < $count; $i++) {
                $update_exchange_rate = ExchangeRates::where('id', $request->id[$i])->update([
                    'fromdt' => $request->fromdt[$i],
                    'todt' => $request->todt[$i],
                    'rate' => $request->rate[$i],
                    'currency' => $request->currency[$i],
                ]);

                if ($update_exchange_rate == 0) {
                    $errors_update++;
                }
            }
        }
        //save exchange rate
        if (isset($request->currency_added)) {
            $count = count($request->currency_added);
            for ($i = 0; $i < $count; $i++) {
                $max_id++;
                $save_exchange_rate = ExchangeRates::create([
                    'id' => $max_id,
                    'fromdt' => $request->fromdt_added[$i],
                    'todt' => $request->todt_added[$i],
                    'rate' => $request->rate_added[$i],
                    'currency' => $request->currency_added[$i],
                ]);

                if ($save_exchange_rate->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Exchange Rate saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Exchange Rate");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Exchange Rate");
            }
        }


        return $response;
    }
}
