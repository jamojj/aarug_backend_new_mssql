<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Corporate\Corporate;
use App\Models\PremiumInvoicingCorporateInvoices;
use App\Models\PremiumInvoicingCorporates;
use App\Models\PremiumInvoicingMembers;
use App\Models\PremiumInvoicingProducts;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PremiumInvoicingController extends Controller
{
    public function fetchPremiumInvoicingProducts()
    {
        $data = PremiumInvoicingProducts::select('product_name')->get();
        return $data;
    }
    public function fetchPremiumInvoicingCorporates()
    {
        $data = PremiumInvoicingCorporates::select('corporate')->get();
        return $data;
    }
    
    public function fetchCorporatesWithInvoices()
    {
        $data = PremiumInvoicingCorporateInvoices::select('corporate')->distinct('corporate')->get();
        return $data;
    }
    public function fetchPremiumInvoicingInvoices($corporate)
    {
        $data = PremiumInvoicingCorporateInvoices::where('corporate', $corporate)->get();
        return $data;
    }

    public function savePremiumInvoicingProduct(Request $request)
    {
        //check product exists
        $exists = PremiumInvoicingProducts::where('product_name', $request->product_add)->get();
        if (count($exists) == 0) {
            try {
                DB::transaction(function () use ($request) {
                    PremiumInvoicingProducts::create([
                        'product_name' => $request->product_add,
                        'user_id' => $request->user_id,
                    ]);
                });
                return ['Product saved successfully'];
            } catch (Exception $e) {
                return ['Error ' . $e->getMessage()];
            }
        } else {
            return ['Product with same name already exists!'];
        }
    }
    public function savePremiumInvoicingCorporate(Request $request)
    {
        $exists = PremiumInvoicingCorporates::where('corporate', $request->corporate_add)->get();
        if (count($exists) == 0) {
            try {
                DB::transaction(function () use ($request) {
                    //set inserted, updated, and deleted items
                    $inserted_item = "corporate : $request->corporate_add";
                    $updated_item = null;
                    $deleted_item = null;

                    PremiumInvoicingCorporates::create([
                        'corporate' => $request->corporate_add,
                        'user_id' => $request->user_id,
                    ]);

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    
                });
                return ['Corporate saved successfully'];
            } catch (Exception $e) {
                return ['Error ' . $e->getMessage()];
            }
        } else {
            return ['Corporate with same name already exists!'];
        }
    }


    public function savePremiumInvoicingData(Request $request)
    {
        $response = [];
        $max_idx_corp_invoices = PremiumInvoicingCorporateInvoices::max('idx');
        $max_idx_corp_invoices = isset($max_idx_corp_invoices) ? $max_idx_corp_invoices + 1 : 1;

        switch ($request->task) {
            case '1':
                //new 
                if (isset($request->new)) {
                    try {

                        //save premium invoicing corporate invoices
                        DB::transaction(function () use ($request, $max_idx_corp_invoices) {
                            PremiumInvoicingCorporateInvoices::create([
                                'idx' => $max_idx_corp_invoices,
                                'corporate' => $request->new,
                                'inv_no' => $max_idx_corp_invoices,
                                'user_id' => $request->user_id,
                            ]);
                        });
                        //save premium invoicing members
                        if (isset($request->index)) {
                            for ($i = 0; $i < count($request->index); $i++) {
                                $max_idx_members = PremiumInvoicingMembers::max('idx');
                                $max_idx_members = isset($max_idx_members) ? $max_idx_members + 1 : 1;
                                DB::transaction(function () use ($request, $max_idx_corp_invoices, $max_idx_members, $i) {
                                    PremiumInvoicingMembers::create([
                                        'idx' => $max_idx_members,
                                        'inv_no' => $max_idx_corp_invoices,
                                        'member_names' => $request->member_names[$i],
                                        'products' => $request->products[$i],
                                        'total_cost' => $request->total_cost[$i],
                                        'user_id' => $request->user_id,
                                    ]);
                                });
                                $dt = [
                                    'index' => $request->index[$i],
                                    'res' => 'Data saved successfully'
                                ];
                                array_push($response, $dt);
                            }
                        }

                        return $response;
                    } catch (Exception $e) {
                        return ['Error ' . $e->getMessage()];
                    }
                } else {
                    return ['Select new corporate to continue!'];
                }
                break;
            case '2':
                //renewal
                if (isset($request->renewal)) {
                    try {
                        //save premium invoicing corporate invoices
                        DB::transaction(function () use ($request, $max_idx_corp_invoices) {
                            PremiumInvoicingCorporateInvoices::create([
                                'idx' => $max_idx_corp_invoices,
                                'corporate' => $request->renewal,
                                'product' => $request->product,
                                'inv_no' => $max_idx_corp_invoices,
                                'user_id' => $request->user_id,
                            ]);
                        });
                        //save premium invoicing members
                        if (isset($request->index)) {
                            for ($i = 0; $i < count($request->index); $i++) {
                                $max_idx_members = PremiumInvoicingMembers::max('idx');
                                $max_idx_members = isset($max_idx_members) ? $max_idx_members + 1 : 1;
                                DB::transaction(function () use ($request, $max_idx_corp_invoices, $max_idx_members, $i) {
                                    PremiumInvoicingMembers::create([
                                        'idx' => $max_idx_members,
                                        'inv_no' => $max_idx_corp_invoices,
                                        'member_names' => $request->member_names[$i],
                                        'products' => $request->products[$i],
                                        'total_cost' => $request->total_cost[$i],
                                        'user_id' => $request->user_id,
                                    ]);
                                });
                                $dt = [
                                    'index' => $request->index[$i],
                                    'res' => 'Data saved successfully'
                                ];
                                array_push($response, $dt);
                            }
                        }

                        return $response;
                    } catch (Exception $e) {
                        return ['Error ' . $e->getMessage()];
                    }
                } else {
                    return ['Select Renewal corporate to continue!'];
                }
                break;
        }
    }

    public function printPremiumInvoicingInvoice($inv_no)
    {
        $members =  '';
        $grand_total_cost  = $quantity = $training_levy =  $amt_payable = 0;
        $stamp_duty = 35000;
        $corp_data = PremiumInvoicingCorporateInvoices::where('inv_no', $inv_no)->get();
        $mem_data = PremiumInvoicingMembers::where('inv_no', $inv_no)->get();

        foreach ($mem_data as $dt) {
            $quantity++;
            $grand_total_cost += (int)$dt->total_cost;
            $members .= " 
                        <tr>
                            <td  style='width: 10%;'>$quantity</td>
                            <td style='width: 30%;'>$dt->products</td>
                            <td style='width: 30%;'>$dt->member_names</td>
                            <td style='width: 30%;'>UGX " . number_format($dt->total_cost, 2) . "</td>
                        </tr>
                        ";
        }
        $training_levy = ((0.5 / 100) * $grand_total_cost);
        $amt_payable = $grand_total_cost + $training_levy + $stamp_duty;
        $footer = "
        <tr><th colspan='3' style='text-align:right;'>GRAND TOTAL:</th><th>UGX " . number_format($grand_total_cost, 0) . "</th></tr>
        <tr><th colspan='3' style='text-align:right;'>IIU TRAINING LEVY:</th><th>UGX " . number_format($training_levy, 0) . "</th></tr>
        <tr><th colspan='3' style='text-align:right;'>STAMP DUTY: </th><th>UGX " . number_format($stamp_duty, 0) . "</th></tr>
        <tr><th colspan='3' style='text-align:right;'>AMOUNT PAYABLE IN UGANDA SHILLINGS:</th><th>UGX " . number_format($amt_payable, 0) . "</th></tr>
        ";
        return ["members" => $members, "product" => $corp_data[0]->product, "footer" => $footer, "invoice_no"=>$inv_no];
    }
}
