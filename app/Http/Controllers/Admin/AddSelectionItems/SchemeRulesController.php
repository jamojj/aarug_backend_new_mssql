<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Rules;
use Illuminate\Http\Request;

class SchemeRulesController extends Controller
{
    public function fetchSchemeRules()
    {
        $rules = Rules::select('*')
            ->get();
        return $rules;
    }

    public function saveSchemeRules(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Rules::max('CODE');

        //update rules
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_rules = Rules::where('CODE', $request->code[$i])->update([
                    'CODE' => $request->code[$i],
                    'RULE' => $request->rule[$i]
                ]);

                if ($update_rules == 0) {
                    $errors_update++;
                }
            }
        }
        //save rules
        if (isset($request->rule_added)) {
            $count = count($request->rule_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_rules = Rules::create([
                    'CODE' => $max_code,
                    'RULE' => $request->rule_added[$i],
                ]);

                if ($save_rules->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Rules saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Rules");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Rules");
            }
        }


        return $response;
    }
}
