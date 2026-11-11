<?php

namespace App\Http\Controllers\Bills\BillPayment;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillsPayment;
use App\Models\LastNumber;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayProviderController extends Controller
{
    //fetch all providers with claims
    public function fetchProviderFund()
    {
        $providers = Bills::select('bills.provider as code', 'provider.provider')
            ->distinct()
            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
            ->where(DB::raw('isnull(bills.refund, 0)'), 0)
            ->where(DB::raw('isnull(bills.paid, 0)'), 0)
            ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
            ->where(DB::raw('isnull(bills.fund, 0)'), 0)
            ->orderBy('provider.provider')
            ->get();

        return ['providers' => $providers];
    }

    //fetch all claims for pay provider
    public function fetchPayProviderClaims($prov_code)
    {
        $total_invoiced_amt = $total_amount_payable = $total_deducted_amt = 0;
        $claims = Bills::select(
            'bills.id',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.service as service_code',
            'service.service',
            'bills.paid',
            'bills.invoiced_amount',
            'bills.deduction_amount',
            'bills.deduction_reason as deduction_reason_code',
            'deduction_reason.deduct_reason',
            'bills.deduction_notes',
            'bills.amount_payable',
            'bills.invoice_date',
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
            'bills.foreigns',
            'bills.currency as currency_code',
            'conversion_rates.currency',
            'bills.conversion_rate as rate',
            't_exchange_rate.rate as conversion_rate',
            'bills.foreign_amt',
            'bills.member_no'
        )
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->leftJoin('deduction_reason', 'deduction_reason.code', '=', 'bills.deduction_reason')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('conversion_rates', 'conversion_rates.idx', '=', 'bills.currency')
            ->leftJoin('t_exchange_rate', 't_exchange_rate.rate', '=', 'bills.conversion_rate')
            // ->where('bills.refund', 0)
            ->where(DB::raw("isnull(bills.refund, 0)"), 0)
            ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
            ->where(DB::raw("isnull(bills.paid, 0)"), 0)
            ->where(DB::raw("isnull(bills.payment_no, 0)"), 0)
            ->where('bills.provider', $prov_code)
            ->get();

        foreach ($claims as $claim) {
            $total_invoiced_amt += $claim->invoiced_amount;
            $total_deducted_amt += $claim->deduction_amount;
            $total_amount_payable += $claim->amount_payable;
        }

        return [
            'claims' => $claims,
            'claims_count' => count($claims),
            'totals' => [
                'total_invoiced_amt' => $total_invoiced_amt,
                'total_deduction_amount' => $total_deducted_amt,
                'total_amount_payable' => $total_amount_payable
            ],
        ];
    }

    //save pay provider
    public function savePayProvider(Request $request)
    {
        try {
            //increment latest voucher number in last number
            $last_pv = LastNumber::where('task', 2)->get('last_no');
            $last_pv = $last_pv[0]['last_no'] + 1;
            $voucher_no = 'PV' . $last_pv;
            $today = date_format(today(), 'Y-m-d');

            DB::transaction(function () use ($request, $last_pv, $voucher_no, $today) {
                //set inserted, updated, and deleted items
                $inserted_item = null;
                $updated_item = "voucher_no : $voucher_no, voucher_date : $today";
                $deleted_item = null;
                
                for ($i = 0; $i < count($request->id); $i++) {
                    //pay only selected claims
                    if ($request->paid[$i] == '1') {
                        //save in bills
                        Bills::where([
                            'id' => intval($request->id[$i])
                        ])->update([
                            'paid' => 1,
                            'voucher_no' => $voucher_no,
                            'voucher_date' => $today,
                            'voucher_user' => $request->user_id
                        ]);
                    }
                }
                //update last number table first
                LastNumber::where('task', 2)
                    ->update([
                        'last_no' => $last_pv
                    ]);

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });

            return ['message' => 'Save OK. The Voucher No is :' . $voucher_no];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
