<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\ClinicalDiagnosis;
use Illuminate\Http\Request;

class DiagnosisController extends Controller
{
    public function fetchDiagnosis()
    {
        $diagnosis = ClinicalDiagnosis::select('*')
            ->get();
        $diagnosis = json_encode($diagnosis, JSON_INVALID_UTF8_IGNORE);
        return $diagnosis;
    }

    public function saveDiagnosis(Request $request)
    {
        // return $request;

        //return $request;
        // ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', '0');

        $response = [];
        $errors_save = $errors_update = 0;
        $max_idx = ClinicalDiagnosis::max('idx');

        //update diagnosis
        if (isset($request->diag_code)) {
            $count = count($request->diag_code);
            for ($i = 0; $i < $count; $i++) {
                $update_diagnosis = ClinicalDiagnosis::where('diag_code', $request->diag_code[$i])->update([
                    'clinical_diagnosis' => $request->clinical_diagnosis[$i]
                ]);

                if ($update_diagnosis == 0) {
                    $errors_update++;
                }
            }
        }
        //save diagnosis
        if (isset($request->diag_code_added)) {
            $count = count($request->diag_code_added);
            for ($i = 0; $i < $count; $i++) {
                $max_idx++;
                $type = 'ICD10';
                $save_diagnosis = ClinicalDiagnosis::create([
                    'idx' => $max_idx,
                    'diag_code' => $request->diag_code_added[$i],
                    'diagnosis_class' => $request->diagnosis_class_added[$i],
                    'clinical_diagnosis' => $request->clinical_diagnosis_added[$i],
                    'type' => $type
                ]);

                if ($save_diagnosis->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Diagnosis saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Diagnosis");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Diagnosis");
            }
        }


        return $response;
    }
}
