<?php

namespace App\Http\Controllers\Admin\AddSelectionItems;

use App\Http\Controllers\Controller;
use App\Models\ProductName;
use Illuminate\Http\Request;

class ProductNameController extends Controller
{
    //save added product names
    public function saveProductName(Request $request)
    {
        //return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_code = ProductName::max('code');

        //update product names
        if (isset($request->code)) {
            $count = count($request->code);
            for ($i = 0; $i < $count; $i++) {
                $update_product_names = ProductName::where(
                    'code', $request->code[$i]
                )->update(
                    [
                        'product_name' => $request->product_name[$i]
                    ]);

                if ($update_product_names == 0) {
                    $errors_update++;
                }
            }
        }
        //save product names
        if (isset($request->product_name_added)) {
            $count = count($request->product_name_added);
            for ($i = 0; $i < $count; $i++) {
                $max_code++;
                $save_product_name = ProductName::create([
                    'code' => $max_code,
                    'product_name' => $request->product_name_added[$i]
                ]);

                if ($save_product_name->wasRecentlyCreated !== true) {
                    $errors_save++;
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "Product Name Saved Successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error Saving Product Names");
            } else if ($errors_update > 0) {
                array_push($response, "Error Updating Product Name");
            }
        }
        return $response;
    }
}
