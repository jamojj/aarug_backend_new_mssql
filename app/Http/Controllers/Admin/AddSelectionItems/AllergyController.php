<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Allergy;
use Illuminate\Http\Request;

class AllergyController extends Controller
{
    public function fetchAllergies()
    {
        $allergy = Allergy::select('code', 'allergy')
            ->get();
        return $allergy;
    }

    public function saveAllergies(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Allergy::max('code');

        //update allergy
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_allergy = Allergy::where('code', $request->code[$i])->update([
                    'allergy' => $request->allergy[$i]
                ]);

                if ($update_allergy == 0) {
                    $errors_update++;
                }
            }
        }
        //save allergy
        if (isset($request->allergy_added)) {
            $count = count($request->allergy_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_allergy = Allergy::create([
                    'code' => $max_code,
                    'allergy' => $request->allergy_added[$i]
                ]);

                if ($save_allergy->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Allergy saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Allergy");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Allergy");
            }
        }


        return $response;
    }
}
