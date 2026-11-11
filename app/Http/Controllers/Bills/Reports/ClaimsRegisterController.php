<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;

class ClaimsRegisterController extends Controller
{
    public function fetchClaimsRegister(Request $request)
    {
        $response = [];
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));

        switch ($request->options) {
            case '1': //provider
                $provider = $request->provider;
                $response = Provider::select(
                    'claims_batch.batch_no',
                    'claims_batch.date_submitted',
                    'provider.provider',
                    'claims_batch.actual_amt',
                    'claims_batch.serial_no'
                )
                    ->leftjoin('claims_batch', 'provider.code', '=', 'claims_batch.provider')
                    ->whereBetween('claims_batch.date_submitted', [$from, $to])
                    ->where('claims_batch.provider', $provider)
                    ->orderBy('claims_batch.date_submitted', 'desc')
                    ->orderBy('provider.provider', 'asc')
                    ->get();
             
                break;
            case '2': //all
                // $response = Provider::select(
                //     'claims_batch.batch_no',
                //     'claims_batch.date_submitted',
                //     'provider.provider',
                //     'claims_batch.actual_amt',
                //     'claims_batch.serial_no'
                // )
                //     ->leftjoin('claims_batch', 'provider.code', '=', 'claims_batch.provider')
                //     ->whereBetween('claims_batch.date_submitted', [$from, $to])
                //     ->orderBy('claims_batch.date_submitted', 'desc')
                //     ->orderBy('provider.provider', 'asc')
                //     ->get();
                $response = Provider::select(
                    'claims_batch.batch_no',
                    'claims_batch.date_submitted',
                    'provider.provider',
                    'claims_batch.actual_amt',
                    'claims_batch.serial_no'
                )
                    ->leftJoin('claims_batch', function ($join) use ($from, $to) {
                        $join->on('provider.code', '=', 'claims_batch.provider')
                            ->whereBetween('claims_batch.date_submitted', [$from, $to]);
                    })
                    ->orderBy('claims_batch.date_submitted', 'desc')
                    ->orderBy('provider.provider', 'asc')
                    ->get();
                break;
        }

        return $response;
    }
}
