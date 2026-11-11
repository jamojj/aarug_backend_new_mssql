<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Doctors;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isNull;

class DoctorsController extends Controller
{
    public function fetchDoctors()
    {
        $doctors = Doctors::select('*')
            ->get();
        $doctors = json_encode($doctors, JSON_INVALID_UTF8_IGNORE);
        return $doctors;
    }

    public function saveDoctors(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Doctors::count('code');

        //update doctor
        if (isset($request->code)) {
            if (!isNull($request->doctor)) {
                $count = count($request->code);
                for ($i = 0; $i < $count; $i++) {
                    $update_doctor = Doctors::where('code', $request->code[$i])->update([
                        'doctor' => $request->doctor[$i],
                        'tel_no' => $request->tel_no[$i],
                        'mobile_no' => $request->mobile_no[$i],
                        'fax_no' => $request->fax_no[$i]
                    ]);

                    if ($update_doctor == 0) {
                        $errors_update++;
                    }
                }
            }
        }
        //save doctor
        if (isset($request->doctor_added)) {
            $count = count($request->doctor_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_doctor = Doctors::create([
                    'code' => $max_code,
                    'doctor' => $request->doctor_added[$i],
                    'tel_no' => $request->tel_no_added[$i],
                    'mobile_no' => $request->mobile_no_added[$i],
                    'fax_no' => $request->fax_no_added[$i]
                ]);

                if ($save_doctor->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Doctors saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Doctors");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Doctors");
            }
        }


        return $response;
    }
}
