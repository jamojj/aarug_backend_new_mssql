<?php

namespace App\Http\Controllers\Bills\BillProcess;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\LineItems;
use App\Models\TariffYear;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddAnInvoiceController extends Controller
{
    public function fetchClaimsPerClaimNoAddInvoice($claim_no)
    {
        $total = 0;
        $claim_no = 'CLM ' . $claim_no;


        $claims = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            'service.code',
            'service.service',
            'provider.code',
            'provider.provider',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.member_no',
            'bills.invoiced_amount',
            'bills.amount_payable',
            'bills.invoice_date',
            'bills.payee',
            'bills.voucher_no',
            'bills.id',
            't_product_name.code',
            't_product_name.product_name',
            'bills.batch_no',
            'bills.smart_bill_id',
            'benefit.code as benefit_code',
            'benefit.benefit',
            'bills.anniv',
            'bills.date_entered',
            'bills.integ',

        )
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
            ->where('bills.claim_no', $claim_no)
            ->get();

        foreach ($claims as $claim) {
            $total += $claim->invoiced_amount;
        }

        $data = [
            'claims' => $claims,
            'total' => number_format($total, 2)
        ];

        return $data;
    }

    public function fetchClaimByIdAddInvoice($id)
    {
        $claim = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            DB::raw("FORMAT(bills.date_entered, 'yyyy-mm-dd') as date_entered"),
            DB::raw("FORMAT(claim_form.visit_date, 'yyyy-mm-dd') as visit_date"),
            DB::raw("FORMAT(claim_form.doctor_date, 'yyyy-mm-dd') as doctor_date"),
            'service.code as service_code',
            'service.service',
            'provider.code as provider_code',
            'provider.provider',
            'bills.claim_no',
            'bills.vet_status',
            'bills.invoice_no',
            'bills.member_no',
            'bills.invoiced_amount',
            'bills.amount_payable',
            'bills.payee',
            'bills.voucher_no',
            'bills.id',
            't_product_name.code as product_code',
            't_product_name.product_name',
            'bills.category',
            'bills.batch_no',
            'bills.smart_bill_id',
            'benefit.code as benefit_code',
            'benefit.benefit',
            'bills.anniv',
            'bills.integ',
            'bills.integ_date',
            'bills.user_id',
            'bills.id',
            'bills.claim_no',
            't_gender.gender',
            'family_relation.code as family_relation_code',
            'family_relation.relation',
            'claim_form.doctor_sign',
            'claim_form.claim_form_signed',
            'claim_form.date_admitted',
            'claim_form.date_discharged',


        )
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
            ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
            ->leftJoin('claim_form', 'bills.claim_no', 'claim_form.claim_no')
            ->where('bills.id', $id)
            ->get();


        return $claim;
    }

    public function saveAddInvoice(Request $request)
    {
        // return $request;
        $invoice_date =  date('Y-m-d', strtotime($request->invoice_date));
        $date_received = date('Y-m-d', strtotime($request->date_received));
        $date_entered = date("Y-m-d");

        //check if entry exist
        $service = Bills::select('*')
            ->where('service', $request->service)
            ->where(
                'invoice_no',
                $request->invoice_no
            )
            ->where('invoice_date', $request->invoice_date)
            ->where('member_no', $request->member_no)
            ->get();

        if (count($service) !== 0) {
            return ['Notice! A similar invoice exists..cannot proceed. Please rectify'];
        }
        try {
            DB::transaction(function () use ($request, $date_entered, $date_received, $invoice_date) {
                $max_id = Bills::max('id') + 1;

                //set inserted, updated, and deleted items
                $inserted_item = "id : $max_id, claim_no : $request->claim_no, inv_amt :  $request->invoice_amt, service : $request->service";
                $updated_item = null;
                $deleted_item = null;

                Bills::create([
                    'CLAIM_NO' => $request->claim_no,
                    'INVOICE_NO' => $request->invoice_no,
                    'CLAIM_FORM_NO' => $request->claim_no,
                    'PROVIDER' => $request->provider,
                    'MEMBER_NO' => $request->member_no,
                    'SERVICE' => $request->service,
                    'CLAIM_NATURE' => $request->benefit,
                    'INVOICE_DATE' => $invoice_date,
                    'INVOICED_AMOUNT' => $request->invoice_amt,
                    'BATCH_NO' => $request->batch,
                    'USER_ID' => $request->entrant,
                    'DATE_ENTERED' => $date_entered,
                    'anniv' => $request->anniv,
                    'admin_fee' => 0,
                    'date_received' => $date_received,
                    'smart_bill_id' => $request->smart_bill_id,
                    'pre_auth_no' => $request->pre_auth_no,
                    'pri_dep' => $request->family_title,
                    'category' => $request->category,
                    'product_name' => $request->product_name,
                    'id' => $max_id,
                    // 'integ' => $request->integ,
                    // 'integ_date' => $request->integ_date,
                ]);

                //saving line items 
                if (isset($request->item_code_price_list)) {
                    for ($i = 0; $i < count($request->item_code_price_list); $i++) {

                        $unit_of_measure = is_null($request->unit_of_measure_price_list[$i]) ? '' : $request->unit_of_measure_price_list[$i];
                        //check if line item exists
                        $exists = LineItems::where('item_code', $request->item_code_price_list[$i])
                            ->where('invoice_no', $request->invoice_no_price_list[$i])
                            ->where('service', $request->service)
                            ->where('provider', $request->provider)
                            ->get();
                        if (count($exists) === 0) {
                            $year = TariffYear::where('year', $request->year_price_list[$i])->pluck('code');
                            $year =  count($year) > 0 ? $year[0] : "";
                            LineItems::create([
                                'item_code' => $request->item_code_price_list[$i],
                                'invoice_no' => $request->invoice_no_price_list[$i],
                                'provider' => $request->provider,
                                'category' => $request->category_price_list[$i],
                                'service' => $request->service,
                                'service_items' => $request->service_items_price_list[$i],
                                'unit_of_measure' => $unit_of_measure,
                                'invoiced_amt' => $request->invoiced_amt_price_list[$i],
                                'date_entered' => $request->date_entered_price_list[$i],
                                // 'item_class' => $request->item_class_price_list[$i],
                                'user_id' => $request->user,
                                'year' => $year,
                                'quantity' => $request->quantity_price_list[$i],
                                'adjusted_amt' => 0,
                                'recomm_amt' => 0,
                                'variation' => 0,
                                'claim_no' =>  $request->claim_no,
                                'smart_bill_id' => 0,
                                'auto_vetted' => 0,
                                'parent_id' => 0
                            ]);
                        }
                    }
                }

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });
            return ['Invoice added successfully'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
