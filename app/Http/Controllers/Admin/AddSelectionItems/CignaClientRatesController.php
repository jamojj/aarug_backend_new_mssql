<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\CignaClientRates;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CignaClientRatesController extends Controller
{

    public function saveCignaRateSheet(Request $request)
    {
        // return $request;
        try {
            $response = [];
            $date_entered = date("Y-m-d");
            $max_idx = CignaClientRates::max('idx');
            for ($i = 0; $i < count($request->index); $i++) {
                $max_idx++;
                //CHECK DUBLICATE DATA
                // $dublicate_rate = CignaClientRates::where('min_age', $request->min_age[$i])
                // ->where('max_age', $request->max_age[$i])
                // ->where('age', $request->age[$i])
                // ->where('plan', $request->plan[$i])
                // ->where('AOC', $request->AOC[$i])
                // ->where('new_or_renewal', $request->new_or_renewal[$i])
                // ->get();

                // if (count($dublicate_rate) === 0) {

                    DB::transaction(function () use ($request, $i, $date_entered, $max_idx) {
                        CignaClientRates::create([
                            'idx' => $max_idx,
                            'premium' => $request->premium[$i],
                            'min_age' => $request->min_age[$i],
                            'max_age' => $request->max_age[$i],
                            'age' => $request->age[$i],
                            'plan' => $request->plan[$i],
                            'AOC' => $request->AOC[$i],
                            'new_or_renewal' => $request->new_or_renewal[$i],
                            'date_entered' => $date_entered,
                            'user_id' => $request->user_id
                        ]);
                    });
                    $dt = [
                        'index' => $request->index[$i],
                        'res' => "Cigna rates saved successfully"
                    ];

                    array_push($response, $dt);
                // } else {
                //     $dt = [
                //         'index' => $request->index[$i],
                //         'res' => "Error. Rate with same plan, AOC,  and new or renewal exists"
                //     ];

                //     array_push($response, $dt);
                // }
            }
        } catch (Exception $e) {
            array_push($response, $e->getMessage());
        }
        return $response;
    }
}
