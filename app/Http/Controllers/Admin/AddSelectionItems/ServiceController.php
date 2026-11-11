<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function fetchService()
    {
        $service = Service::select('*')
            ->get();
        $service = json_encode($service, JSON_INVALID_UTF8_IGNORE);
        return $service;
    }

    public function saveService(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Service::max('CODE');

        //update service
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_service = Service::where('CODE', $request->code[$i])->update([
                    'CODE' => $request->code[$i],
                    'SERVICE' => $request->service[$i]
                ]);

                if ($update_service == 0) {
                    $errors_update++;
                }
            }
        }
        //save service
        if (isset($request->service_added)) {
            $count = count($request->service_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_service = Service::create([
                    'CODE' => $max_code,
                    'SERVICE' => $request->service_added[$i],
                ]);

                if ($save_service->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Service saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Service");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Service");
            }
        }


        return $response;
    }
}
