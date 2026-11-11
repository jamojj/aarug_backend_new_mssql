<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use Illuminate\Http\Request;

class BenefitController extends Controller
{
    //save added benefit
    public function saveBenefit(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Benefit::max('code');

        //update benefits
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_benefits = Benefit::where(
                    'code', $request->code[$i]
                )->update(
                    [
                        'benefit' => $request->benefit[$i]
                    ]);

                if ($update_benefits == 0) {
                    $errors_update++;
                }
            }
        }
        //save benefits
        if (isset($request->benefit_added)) {
            $count = count($request->benefit_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_benefit = Benefit::create([
                    'code' => $max_code,
                    'benefit' => $request->benefit_added[$i]
                ]);

                if ($save_benefit->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Benefit Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Benefit");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Benefit");
            }
        }
        return $response;
    }
}
