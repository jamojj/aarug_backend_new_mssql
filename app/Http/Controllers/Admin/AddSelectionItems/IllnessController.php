<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Illness;
use Illuminate\Http\Request;

class IllnessController extends Controller
{
    public function fetchIllness()
    {
        $illness = Illness::select('code', 'illnes')
            ->get();
        return $illness;
    }

    public function saveIllness(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Illness::max('code');

        //update illness
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_illness = Illness::where('code', $request->code[$i])->update([
                    'illnes' => $request->illness[$i]
                ]);

                if ($update_illness == 0) {
                    $errors_update++;
                }
            }
        }
        //save illness
        if (isset($request->illness_added)) {
            $count = count($request->illness_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_illness = Illness::create([
                    'code' => $max_code,
                    'illnes' => $request->illness_added[$i]
                ]);

                if ($save_illness->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Illness saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Illness");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Illness");
            }
        }


        return $response;
    }
}
