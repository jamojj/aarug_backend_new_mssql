<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    //save added agency
    public function saveAgency(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Agency::max('code');

        //update agencies
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_agencies = Agency::where(
                    'code', $request->code[$i]
                )->update([
                    'name' => $request->agency[$i]
                ]);

                if ($update_agencies == 0) {
                    $errors_update++;
                }
            }
        }
        //save agencies
        if (isset($request->agency_added)) {
            $count = count($request->agency_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_agency = Agency::create([
                    'code' => $max_code,
                    'name' => $request->agency_added[$i]
                ]);

                if ($save_agency->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Agency Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Agency");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Agency");
            }
        }
        return $response;
    }
}
