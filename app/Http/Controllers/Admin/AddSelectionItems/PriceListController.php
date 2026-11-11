<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Tariffs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceListController extends Controller
{
    public function fetchServiceItems(Request $request)
    {
        // return $request;
        $data = Tariffs::select(
            't_tariffs.idx',
            't_tariffs.item_code',
            't_tariffs.category',
            't_tariffs.service_items',
            't_tariffs.unit_of_measure',
            't_tariffs.price',
            't_tariffs.updated',
            'provider.code as provider_code',
            'provider.provider',
            't_tariffs.user_id',
            't_tariff_year.code as year_code',
            't_tariff_year.year',
            't_tariffs.date_entered'
        )
            ->leftJoin('provider', 'provider.code', '=', 't_tariffs.provider')
            ->leftJoin('t_tariff_year', 't_tariff_year.code', '=', 't_tariffs.year')
            ->where('t_tariffs.provider', $request->provider)
            ->where('t_tariffs.category', $request->category)
            //this is a conditional clause and only runs when $request->service_item has a value
            ->when($request->service_item, function ($query, $service_item) {
                return $query->where('t_tariffs.service_items', 'like', '%' . $service_item . '%');
            })
            ->get();

        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }

    public function fetchServiceItemsByItemCode($item_code)
    {
        $items_array = [];
        $items = Tariffs::select('*')
            ->where('item_code', $item_code)
            ->get();

        $providers = Provider::select('code', 'provider')
            ->whereNotExists(function ($query) use ($items) {
                $query->select(DB::raw(1))
                    ->from('t_tariffs')
                    ->whereColumn('t_tariffs.provider', 'provider.code')
                    ->where([
                        't_tariffs.item_code'     => $items[0]->item_code,
                        't_tariffs.category'      => $items[0]->category,
                        't_tariffs.service_items' => $items[0]->service_items,
                        't_tariffs.year'          => $items[0]->year
                    ]);
            })
            ->get();


        foreach ($providers as $provider) {
            $dt = [
                'item_code' => $items[0]->item_code,
                'category' => $items[0]->category,
                'service_items' => $items[0]->service_items,
                'unit_of_measure' => $items[0]->unit_of_measure,
                'price' => $items[0]->price,
                'updated' => $items[0]->updated,
                'code' => $items[0]->provider,
                'provider_code' => $provider->code,
                'provider' => $provider->provider,
                'user_id' => $items[0]->user_id,
                'year' => $items[0]->year

            ];

            array_push($items_array, $dt);
        }

        return $items_array;
    }

    public function saveServiceItems(Request $request)
    {
        // return $request;
        try {
            $max_code = Tariffs::max('idx');

            //update price list
            if (isset($request->item_code)) {
                $count = count($request->item_code);
                for ($i = 0; $i < $count; $i++) {
                    DB::transaction(function () use ($request, $i) {
                        Tariffs::where([
                            'item_code' => $request->item_code[$i],
                            'provider' => $request->provider[$i],
                            'year' => $request->year[$i]
                        ])->update([
                            'category' => $request->category[$i],
                            'service_items' => $request->service_items[$i],
                            'unit_of_measure' => $request->unit_of_measure[$i],
                            'price' => $request->price[$i],
                            'updated' => $request->updated[$i],
                            'provider' => $request->provider[$i],
                            'user_id' => $request->user_id[$i],
                            'year' => $request->year[$i]
                        ]);
                    });
                }
            }
            //save price list
            if (isset($request->service_items_added)) {
                $count = count($request->service_items_added);
                for ($i = 0; $i < $count; $i++) {
                    $max_code++;
                    DB::transaction(function () use ($request, $max_code, $i) {
                        Tariffs::create([
                            'idx' => $max_code,
                            'item_code' => $request->item_code_added[$i],
                            'category' => $request->category_added[$i],
                            'service_items' => $request->service_items_added[$i],
                            'unit_of_measure' => $request->unit_of_measure_added[$i],
                            'price' => $request->price_added[$i],
                            'updated' => $request->updated_added[$i],
                            'provider' => $request->provider_added[$i],
                            'user_id' => $request->user_id_added[$i],
                            'year' => $request->year_added[$i]
                        ]);
                    });
                }
            }

            //save price list to all providers
            if (isset($request->provider_code)) {
                $count = count($request->provider_code);
                for ($i = 0; $i < $count; $i++) {
                    if ($request->verified[$i] == 1) {
                        $max_code++;
                        DB::transaction(function () use ($request, $max_code, $i) {
                            Tariffs::create([
                                'idx' => $max_code,
                                'item_code' => $request->item_code_all[$i],
                                'category' => $request->category_all[$i],
                                'service_items' => $request->service_items_all[$i],
                                'unit_of_measure' => $request->unit_of_measure_all[$i],
                                'price' => $request->price_all[$i],
                                'updated' => $request->updated_all[$i],
                                'provider' => $request->provider_code[$i],
                                'user_id' => $request->user_id_all[$i],
                                'year' => $request->year_all[$i]
                            ]);
                        });
                    }
                }
            }

            return ["Price List saved successfully."];
        } catch (\Throwable $th) {
            return ["Error " . $th->getMessage()];
        }
    }
}
