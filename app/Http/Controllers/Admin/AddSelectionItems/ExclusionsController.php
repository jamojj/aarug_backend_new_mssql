<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Exclusion;
use Illuminate\Http\Request;

class ExclusionsController extends Controller
{
    public function fetchExclusions()
    {
        $exclusion = Exclusion::select('code', 'exclusion')
            ->get();
        return $exclusion;
    }

    public function saveExclusions(Request $request)
    {
        // return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Exclusion::max('code');

        //update exclusions
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_exclusions = Exclusion::where('code', $request->code[$i])->update([
                    'exclusion' => $request->exclusions[$i]
                ]);

                if ($update_exclusions == 0) {
                    $errors_update++;
                }
            }
        }
        //save exclusions
        if (isset($request->exclusions_added)) {
            $count = count($request->exclusions_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_exclusions = Exclusion::create([
                    'code' => $max_code,
                    'exclusion' => $request->exclusions_added[$i]
                ]);

                if ($save_exclusions->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Exclusion saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Exclusion");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Exclusion");
            }
        }


        return $response;
    }
}
