<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use Illuminate\Http\Request;

class PlanClassGroupController extends Controller
{
    //save added class group plan
    public function saveClassGroupPlan(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = ClassGroup::max('class_group');

        //update banks
        if (isset($request->class_group)) {
            $count = count($request->class_group);
            for ($i = 0; $i < $count; $i++) {
                $update_plans = ClassGroup::where(
                    'class_group', $request->class_group[$i]
                )->update([
                    'class_group_desc' => $request->class_group_desc[$i]
                ]);

                if ($update_plans == 0) {
                    $errors_update++;
                }
            }
        }
        //save added class group plans
        if (isset($request->class_group_desc_added)) {
            $count = count($request->class_group_desc_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_plans = ClassGroup::create([
                    'class_group' => $max_code,
                    'class_group_desc' => $request->class_group_desc_added[$i]
                ]);

                if ($save_plans->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Class Group Plan Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Class Group Plan");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Class Group Plan");
            }
        }
        return $response;
    }
}
