<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Tariffs;
use App\Models\TariffYear;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UploadPriceListController extends Controller
{
    public function importPriceList(Request $request)
    {
        ini_set('max_execution_time', 0);
        $response = [];
        $updated_at = date('Y-m-d');
        try {
            if (isset($request->provider)) {
                for ($i = 0; $i < count($request->provider); $i++) {
                    //check if all inputs are present
                    if (
                        isset($request->item_code[$i])
                        && isset($request->category[$i])
                        && isset($request->service_items[$i])
                        && isset($request->price[$i])
                        && isset($request->provider[$i])
                        && isset($request->year[$i])
                    ) {
                        //check if year exists
                        $year_code = TariffYear::select('code')->where('year', $request->year[$i])->get();
                        if (count($year_code) !== 0) {
                            //check if provider exists
                            $provider_exists = Provider::where('code', $request->provider[$i])->get();
                            if (count($provider_exists) !== 0) {
                                //check if service exists
                                // $service_exists = Service::where('code', $request->category[$i])->get();
                                $service_exists = Service::where('SERVICE', $request->category[$i])->get();
                                if (count($service_exists)) {
                                    $service_code =  $service_exists[0]->CODE;                                  
                                    //check dublication
                                    $dublication = Tariffs::where('item_code', $request->item_code[$i])
                                        // ->where('category', $request->category[$i])
                                        // ->where('service_items', $request->service_items[$i])
                                        ->where('provider', $request->provider[$i])
                                        ->where('year', $year_code[0]->code)
                                        ->get();
                                    if (count($dublication) === 0) {
                                        $max = Tariffs::max('idx') + 1;
                                        DB::transaction(function () use ($request, $i, $updated_at, $year_code, $max, $service_code) {
                                            Tariffs::create([
                                                'idx' => $max,
                                                'item_code' => $request->item_code[$i],
                                                'category' => $service_code,
                                                'service_items' => $request->service_items[$i],
                                                'unit_of_measure' => $request->unit_of_measure[$i],
                                                'price' => $request->price[$i],
                                                'updated' => $updated_at,
                                                'provider' => $request->provider[$i],
                                                'user_id' => $request->user_id,
                                                'year' => $year_code[0]->code
                                            ]);
                                        });
                                        $dt = [
                                            'index' => $request->index[$i],
                                            'res' => "Price list uploaded successfully."
                                        ];
                                        array_push($response, $dt);
                                    } else {
                                        $dt = [
                                            'index' => $request->index[$i],
                                            'res' => "Notice, similar price list item exists"
                                        ];
                                        array_push($response, $dt);
                                    }
                                } else {
                                    $dt = [
                                        'index' => $request->index[$i],
                                        'res' => "Notice,  category does not exist"
                                    ];
                                    array_push($response, $dt);
                                }
                            } else {
                                $dt = [
                                    'index' => $request->index[$i],
                                    'res' => "Notice,  provider does not exist"
                                ];
                                array_push($response, $dt);
                            }
                        } else {
                            $dt = [
                                'index' => $request->index[$i],
                                'res' => "Notice, year does not exist"
                            ];
                            array_push($response, $dt);
                        }
                    } else {
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => "Notice, Please fill in all the details!"
                        ];
                        array_push($response, $dt);
                    }
                }
            }
        } catch (Exception $e) {
            $dt = [
                'index' => $request->index[$i],
                'res' => 'crash ' . $e->getMessage()
            ];
            array_push($response, $dt);
        }
        return $response;
    }
}
