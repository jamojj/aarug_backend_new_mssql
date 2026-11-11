<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\CignaUsdExchangeRate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CignaUsdExchangeRateController extends Controller
{
    public function fetchCignaRates()
    {
        $data = CignaUsdExchangeRate::select(
            DB::raw("FORMAT(rate_from, 'dd/mm/YYYY') as rate_from"),
            DB::raw("FORMAT(rate_to, 'dd/mm/YYYY') as rate_to"),
            DB::raw("FORMAT(date_entered, 'dd/mm/YYYY') as date_entered"),
            'idx',
            'ugx_to_usd_rate',
            'user_id'
        )
            ->get();

        return $data;
    }

    public function saveCignaRates(Request $request)
    {
        $today = date("Y-m-d H:i:s");
        try {
            if (isset($request->ugx_to_usd_rate)) {
                $max_idx = CignaUsdExchangeRate::max('idx');
                DB::transaction(function () use ($request, $max_idx, $today) {
                    for ($i = 0; $i < count($request->ugx_to_usd_rate); $i++) {
                        $max_idx++;
                        CignaUsdExchangeRate::create([
                            'idx' => $max_idx,
                            'rate_from' => $request->from[$i],
                            'rate_to' => $request->to[$i],
                            'ugx_to_usd_rate' => $request->ugx_to_usd_rate[$i],
                            'user_id' => $request->user_id,
                            'date_entered' => $today,
                        ]);
                    }
                });
                return ['Rate saved successfully'];
            }
        } catch (Exception $e) {
            return ['Error' . $e->getMessage()];
        }
    }
}
