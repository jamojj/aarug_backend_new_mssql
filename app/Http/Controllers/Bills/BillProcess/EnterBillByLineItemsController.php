<?php

namespace App\Http\Controllers\Bills\BillProcess;

use App\Http\Controllers\Controller;
use App\Models\LineItems;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Tariffs;
use App\Models\TariffYear;
use App\Models\TLineItems;
use Illuminate\Http\Request;

class EnterBillByLineItemsController extends Controller
{
    public function fetchPriceList(Request $request)
    {
        // return $request;
        $year = date('Y', strtotime($request->invoice_date));
        $data = Tariffs::select(
            'item_code',
            'service.service',
            'service_items',
            'unit_of_measure',
            'price',
            'updated',
            'provider',
            'user_id',
            't_tariff_year.year',
        )
            ->leftJoin('t_tariff_year', 't_tariff_year.code', '=', 't_tariffs.year')
            ->leftJoin('service', 't_tariffs.category', '=', 'service.code')
            ->where('t_tariff_year.year', $year)
            ->where('t_tariffs.category', $request->service)
            ->where('provider', $request->provider)
            ->get();

        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }

    public function fetchOneLineItem(Request $request)
    {
        $dt = [];
        $rows = Tariffs::where('item_code', $request->item_code)->get();
        foreach ($rows as $row) {
            $provider = Provider::where('code', $request->provider_code)->pluck('provider');
            $provider = is_null($provider[0]) ? '' : $provider[0];
            $service = Service::where('code', $request->service_code)->pluck('service');
            $service = is_null($service[0]) ? '' : $service[0];
            $year = TariffYear::where('code', $row->year)->pluck('year');
            $year = is_null($year[0]) ? '' : $year[0];

            $dt = [
                'idx' => $row->idx,
                'item_code' => $row->item_code,
                'category' => $service,
                'service_items' => $row->service_items,
                'unit_of_measure' => $row->unit_of_measure,
                'price' => $row->price,
                'updated' => $row->updated,
                'year' => $year,
                'user_id' => $request->user_id,
                'invoice_no' => $request->invoice_no,
                'service_code' => $request->service_code,
                'provider_code' => $request->provider_code,
                'provider' => $provider,
                'date_entered' => $request->invoice_date,
            ];
        }
        return $dt;
    }
}
