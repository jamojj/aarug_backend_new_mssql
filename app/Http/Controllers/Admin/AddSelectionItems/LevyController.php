<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Levy;
use Illuminate\Http\Request;

class LevyController extends Controller
{
    //save added levy
    public function saveLevy(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Levy::max('code');

        //update levies
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_levies = Levy::where(
                    'code', $request->code[$i]
                )->update(
                    [
                        'rate' => $request->rate[$i],
                        'levy' => $request->levy[$i]
                    ]);

                if ($update_levies == 0) {
                    $errors_update++;
                }
            }
        }
        //save levies
        if (isset($request->levy_added)) {
            $count = count($request->levy_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_levy = Levy::create([
                    'code' => $max_code,
                    'rate' => $request->rate_added[$i],
                    'levy' => $request->levy_added[$i]
                ]);

                if ($save_levy->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Levy Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Levy");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Levy");
            }
        }
        return $response;
    }
}
