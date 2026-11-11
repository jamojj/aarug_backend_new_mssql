<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\CardReprintReason;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmartCardReprintReasonController extends Controller
{

    public function fetchSmartCardReprintReasons()
    {
        $data = CardReprintReason::all();
        return $data;
    }



    public function SaveSmartCardReprintReasons(Request $request)
    {
        // return $request;
        $max_code = CardReprintReason::max('code');
        try {
            DB::transaction(function () use ($request, $max_code) {
                //save new reasons
                if (isset($request->reprint_reason_added)) {
                    for ($i = 0; $i < count($request->reprint_reason_added); $i++) {
                        $max_code++;
                        CardReprintReason::create([
                            'code' => $max_code,
                            'reprint_reason' => $request->reprint_reason_added[$i]
                        ]);
                    }
                }
                //update existing reasons
                if (isset($request->code)) {
                    for ($i = 0; $i < count($request->code); $i++) {
                        CardReprintReason::where('code', $request->code[$i])->update([
                            'reprint_reason' => $request->reprint_reason[$i]
                        ]);
                    }
                }
            });
            return ['Smart card reprint reason saved successfully'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
