<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\UnitManagers;
use Illuminate\Http\Request;

class UnitManagerController extends Controller
{
    //save added unit managers
    public function saveUnitManager(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = UnitManagers::max('code');

        //update units
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_unit_manager = UnitManagers::where(
                    'code', $request->code[$i]
                )->update([
                    'name' => $request->unit_manager[$i],
                    'unit' => $request->unit[$i]
                ]);

                if ($update_unit_manager == 0) {
                    $errors_update++;
                }
            }
        }
        //save units
        if (isset($request->unit_manager_added)) {
            $count = count($request->unit_manager_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_unit_manager = UnitManagers::create([
                    'code' => $max_code,
                    'name' => $request->unit_manager_added[$i],
                    'unit' => $request->unit_added[$i]
                ]);

                if ($save_unit_manager->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Unit Manager Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Unit Manager");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Unit Manager");
            }
        }
        return $response;
    }
}
