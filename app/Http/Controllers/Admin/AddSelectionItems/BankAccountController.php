<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function fetchBankAccounts()
    {
        $bank_accounts = BankAccount::select('*')
            ->get();
        return $bank_accounts;
    }

    public function saveBankAccounts(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = BankAccount::max('code');
        $today = date('Y-m-d');
        // return $today;

        //update bank accounts
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_bank_accounts = BankAccount::where('code', $request->code[$i])->update([
                    'bank_code' => $request->bank_code[$i],
                    'bank_id' => $request->bank_id[$i],
                    'bank_account' => $request->bank_account[$i],
                    'currencyid' => $request->currency[$i],
                ]);

                if ($update_bank_accounts == 0) {
                    $errors_update++;
                }
            }
        }
        //save bank accounts
        if (isset($request->bank_id_added)) {
            $count = count($request->bank_id_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_bank_accounts = BankAccount::create([
                    'code' => $max_code,
                    'bank_code' => $request->bank_code_added[$i],
                    'bank_id' => $request->bank_id_added[$i],
                    'bank_account' => $request->bank_account_added[$i],
                    'currencyid' => $request->currency_added[$i],
                    'user_id' => $request->user_id_added[$i],
                    'date_entered' => $today,
                ]);

                if ($save_bank_accounts->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Bank Account saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Bank Account");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Bank Account");
            }
        }


        return $response;
    }
}
