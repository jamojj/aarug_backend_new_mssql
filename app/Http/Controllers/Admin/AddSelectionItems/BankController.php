<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Banks;
use Illuminate\Http\Request;

class BankController extends Controller
{
    //save added bank
    public function saveBank(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Banks::max('code');

        //update banks
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_banks = Banks::where(
                    'code', $request->code[$i]
                )->update([
                    'bank' => $request->bank[$i]
                ]);

                if ($update_banks == 0) {
                    $errors_update++;
                }
            }
        }
        //save banks
        if (isset($request->bank_added)) {
            $count = count($request->bank_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_bank = Banks::create([
                    'code' => $max_code,
                    'bank' => $request->bank_added[$i]
                ]);

                if ($save_bank->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Bank Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Bank");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Bank");
            }
        }
        return $response;
    }

}
