<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\BenefitSharing;
use App\Models\Category;
use App\Models\ProductName;
use App\Models\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    public function getAllProducts()
    {
        ini_set('max_execution_time', '0');
        //allproducts
        $data = Products::select(
            't_products.id',
            't_products.product_name as product_name_code',
            't_product_name.product_name',
            't_products.benefit as benefit_code',
            'benefit.benefit',
            'bene2.benefit as sub_limit_of',
            't_products.family_limit',
            't_products.limit',
            't_products.sharing as sharing_code',
            'benefit_sharing.sharing',
            't_products.fund',
            't_products.capitated',
            't_products.quantity',
            't_products.health_plan',
            't_products.sub_limit_of as sub_limit_of_code'
        )
            ->leftJoin('t_product_name', 't_products.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 't_products.benefit', '=', 'benefit.code')
            ->leftJoin('benefit as bene2', 't_products.sub_limit_of', '=', 'bene2.code')
            ->leftJoin('benefit_sharing', 't_products.sharing', '=', 'benefit_sharing.code')
            ->orderBy('t_products.id', 'ASC')
            ->get();

     
        return $data;
    }

    public function updateProducts(Request $request)
    {
        ini_set('max_execution_time', '0');
        try {
            $num = count($request->id);
            for ($i = 0; $i < $num; $i++) {
                Products::where('id', $request->id[$i])->update([
                    'health_plan' => $request->health_plan[$i],
                    'product_name' => $request->product_name[$i],
                    'benefit' => $request->benefit[$i],
                    'family_limit' => $request->family_limit[$i],
                    'limit' => $request->limit[$i],
                    'sharing' => $request->sharing[$i],
                    'sub_limit_of' => $request->sub_limit_of[$i],
                    'fund' => (int)$request->fund[$i],
                    'capitated' => (int)$request->capitated[$i],
                    'quantity' => $request->quantity[$i]
                ]);
            }
            return ['response' => 'Updated successfully,'];
        } catch (Exception $e) {
            return ['response' => 'Error updating'];
        }
    }
    public function updateProductsNew(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                Products::where('id', $request->id)->update([
                    'health_plan' => $request->health_plan,
                    'product_name' => $request->product_name,
                    'benefit' => $request->benefit,
                    'family_limit' => $request->family_limit,
                    'limit' => $request->limit,
                    'sharing' => $request->sharing,
                    'sub_limit_of' => $request->sub_limit_of,
                    'fund' => (int)$request->fund,
                    'capitated' => (int)$request->capitated,
                    'quantity' => $request->quantity
                ]);
            });
            return ['Product updated successfully,'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function saveProducts(Request $request)
    {
        ini_set('max_execution_time', '0');
        try {
            $max_id = Products::max('id');
            $num = count($request->health_plan_added);
            for ($i = 0; $i < $num; $i++) {
                $max_id++;
                Products::create([
                    'id' => $max_id,
                    'health_plan' => $request->health_plan_added[$i],
                    'product_name' => $request->product_name_added[$i],
                    'benefit' => $request->benefit_added[$i],
                    'family_limit' => $request->family_limit_added[$i],
                    'limit' => $request->limit_added[$i],
                    'sharing' => $request->sharing_added[$i],
                    'sub_limit_of' => $request->sub_limit_of_added[$i],
                    'fund' => (int)$request->fund_added[$i],
                    'capitated' => (int)$request->capitated_added[$i],
                    'quantity' => $request->quantity_added[$i]
                ]);
            }
            return ['response' => 'saved successfully'];
        } catch (Exception $e) {
            return ['response' => 'error saving'];
        }
    }
    public function saveNewProduct(Request $request)
    {
        // return $request;
        try {
            $max_id = (Products::max('id') ?? 0) + 1;
            DB::transaction(function () use ($request, $max_id) {
                Products::create([
                    'id' => $max_id,
                    'health_plan' => $request->health_plan_new,
                    'product_name' => $request->product_name_new,
                    'benefit' => $request->benefit_new,
                    'family_limit' => $request->family_limit_new,
                    'limit' => $request->limit_new,
                    'sharing' => $request->sharing_new,
                    'sub_limit_of' => $request->sub_limit_of_new,
                    'fund' => (int)$request->fund_new,
                    'capitated' => (int)$request->capitated_new,
                    'quantity' => $request->quantity_new
                ]);
            });
            return ['Product saved successfully'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
