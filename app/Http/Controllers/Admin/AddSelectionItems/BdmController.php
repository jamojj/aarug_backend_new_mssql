<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Bdm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BdmController extends Controller
{
    public function fetchBdm()
    {
        $bdm = Bdm::select('*')
            ->orderBy('code', 'ASC')
            ->get();
        return $bdm;
    }
    public function fetchActiveBdm()
    {
        $bdm = Bdm::select('*')
            ->orderBy('code', 'ASC')
            ->where(DB::raw("isnull(status, 0)"), 1)
            ->get();
        return $bdm;
    }

    public function saveBdm(Request $request)
    {
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = Bdm::max('code');

        //update bdm
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_bdm = Bdm::where('code', $request->code[$i])->update([
                    'name' => $request->name[$i],
                    'status' => $request->bdm_status[$i],
                ]);

                if ($update_bdm == 0) {
                    $errors_update++;
                }
            }
        }
        //save bdm
        if (isset($request->name_added)) {
            $count = count($request->name_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_bdm = Bdm::create([
                    'code' => $max_code,
                    'name' => $request->name_added[$i],
                    'status' => $request->bdm_status_added[$i]
                ]);

                if ($save_bdm->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Bdm saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving Bdm");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating Bdm");
            }
        }


        return $response;
    }
}
