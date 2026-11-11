<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\TransactRate;
use Illuminate\Http\Request;

class TransactionRateController extends Controller
{
    public function fetchTransactionRates()
    {
        $transact_rate = TransactRate::select('*')
            ->get();
        return $transact_rate;
    }

    public function saveTransactionRates(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = TransactRate::max('code');

        //update Transact Rate
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_transact_rate = TransactRate::where('code', $request->code[$i])->update([
                    'description' => $request->description[$i],
                    'rate' => $request->rate[$i],
                ]);

                if ($update_transact_rate == 0) {
                    $errors_update++;
                }
            }
        }
        //save Transact Rate
        if (isset($request->description_added)) {
            $count = count($request->description_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_transact_rate = TransactRate::create([
                    'code' => $max_code,
                    'description' => $request->description_added[$i],
                    'rate' => $request->rate_added[$i],
                ]);

                if ($save_transact_rate->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Transaction Rate saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Transaction Rate");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Transaction Rate");
            }
        }


        return $response;
    }
}
