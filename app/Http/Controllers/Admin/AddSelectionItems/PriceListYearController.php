<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\TariffYear;
use Illuminate\Http\Request;

class PriceListYearController extends Controller
{
    public function fetchPriceListYear()
    {
        $tariff_year = TariffYear::select('*')
            ->orderBy('code','DESC')
            ->get();
        return $tariff_year;
    }

    public function savePriceListYear(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = TariffYear::max('code');

        //update price list year
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_price_list_year = TariffYear::where('code', $request->code[$i])->update([
                    'year' => $request->year[$i]
                ]);

                if ($update_price_list_year == 0) {
                    $errors_update++;
                }
            }
        }
        //save bdm price list year
        if (isset($request->year_added)) {
            $count = count($request->year_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_price_list_year = TariffYear::create([
                    'code' => $max_code,
                    'year' => $request->year_added[$i]
                ]);

                if ($save_price_list_year->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Price List Year saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Price List Year");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Price List Year");
            }
        }


        return $response;
    }
}
