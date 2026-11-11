<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\DiagnosisClass;
use Illuminate\Http\Request;

class DiagnosisClassController extends Controller
{
    public function fetchDiagnosisClass()
    {
        $diagnosis_class = DiagnosisClass::select('*')
            ->get();
        return $diagnosis_class;
    }

    public function saveDiagnosisClass(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_idx = DiagnosisClass::max('idx');

        //update diagnosis class
        if (isset($request->diagnosis_class)) {
            $count = count($request->diagnosis_class);
            for ($i = 0; $i < $count; $i++) {
                $update_diagnosis = DiagnosisClass::where('diagnosis_class', $request->diagnosis_class[$i])->update([
                    'diagnosis_class' => $request->diagnosis_class[$i],
                    'class_description' => $request->class_description[$i]
                ]);

                if ($update_diagnosis == 0) {
                    $errors_update++;
                }
            }
        }
        //save diagnosis class
        if (isset($request->diagnosis_class_added)) {
            $count = count($request->diagnosis_class_added);
            for ($i = 0; $i < $count; $i++) {
                $max_idx++;
                $save_diagnosis = DiagnosisClass::create([
                    'idx' => $max_idx,
                    'diagnosis_class' => $request->diagnosis_class_added[$i],
                    'class_description' => $request->class_description_added[$i]
                ]);

                if ($save_diagnosis->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Diagnosis Class saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Diagnosis Class");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Diagnosis Class");
            }
        }


        return $response;
    }
}
