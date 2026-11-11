<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Units;
use Illuminate\Http\Request;

class UnitsController extends Controller
{
    //save added units
    public function saveUnit(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Units::max('code');

        //update units
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_units = Units::where(
                    'code', $request->code[$i]
                )->update([
                    'name' => $request->unit[$i]
                ]);

                if ($update_units == 0) {
                    $errors_update++;
                }
            }
        }
        //save units
        if (isset($request->unit_added)) {
            $count = count($request->unit_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_unit = Units::create([
                    'code' => $max_code,
                    'name' => $request->unit_added[$i]
                ]);

                if ($save_unit->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Unit Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Unit");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Unit");
            }
        }
        return $response;
    }
}
