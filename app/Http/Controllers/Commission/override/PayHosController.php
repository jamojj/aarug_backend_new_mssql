<?php

namespace App\Http\Controllers\Commission\override;

use App\Http\Controllers\Controller;
use App\Models\ChequePayment;
use App\Models\Finance\CommissionRate;
use App\Models\LastNumber;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetailsSammary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayHosController extends Controller
{

    public function fetchHosVoucherNo()
    {
        $vouchers = PremiumInvoiceDetailsSammary::select('hos_vno')->distinct()
            ->whereNotNull('hos_vno')
            ->where('hos_paid', 1)
            ->where(DB::raw('isnull(hos_payno, 0)'), 0)
            ->get();
        return $vouchers;
    }
    public function fetchHosDebitsNotPaidCommission()
    {
        $debits = PremiumInvoiceDetailsSammary::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            'premium_invoice_details_sammary.invoice_no',
            'corporate.corporate',
            'premium_invoice_details_sammary.id_key',
            'premium_invoice_details_sammary.net_premium',
            'premium_invoice_details_sammary.allocated',
            'premium_invoice_details_sammary.allocated_amt',
            'premium_invoice_details_sammary.hos_paid',
            'premium_invoice_details_sammary.hos_amt',
            'premium_invoice_details_sammary.hos_vno',
            'premium_invoice_details_sammary.hos_payno',
            'premium_invoice_details_sammary.hos_rate',
            'premium_invoice_details_sammary.hos_whtax',
            'premium_invoice_details_sammary.hos_whtax_rate',
            'premium_invoice.new_renewal',
            DB::raw("premium_invoice_details_sammary.allocated_amt * (premium_invoice_details_sammary.hos_rate / 100) as commis")

        )
            ->leftJoin('premium_invoice', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
            ->leftJoin('corporate', 'premium_invoice.corp_id', '=', 'corporate.corp_id')
            ->leftJoin('member_info', 'premium_invoice.member_no', '=', 'member_info.member_no')
            ->where('premium_invoice_details_sammary.allocated', 1)
            ->where('premium_invoice.credit_note', 1)
            ->where(DB::raw('isnull(premium_invoice_details_sammary.hos_paid, 0)'), 0)
            ->where(DB::raw('isnull(premium_invoice_details_sammary.net_premium, 0)'), 0)
            ->get();
        return $debits;
    }
    public function fetchHosDebitsPerHosVoucherNo($hos_vno)
    {
        $total_hos_amt = 0;
        $total_commission = 0;
        $debits = PremiumInvoiceDetailsSammary::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            'premium_invoice_details_sammary.invoice_no',
            'corporate.corporate',
            'premium_invoice_details_sammary.id_key',
            'premium_invoice_details_sammary.net_premium',
            'premium_invoice_details_sammary.allocated',
            'premium_invoice_details_sammary.allocated_amt',
            'premium_invoice_details_sammary.hos_paid',
            'premium_invoice_details_sammary.hos_amt',
            'premium_invoice_details_sammary.hos_vno',
            'premium_invoice_details_sammary.hos_payno',
            'premium_invoice_details_sammary.hos_rate',
            'premium_invoice_details_sammary.hos_whtax',
            'premium_invoice_details_sammary.hos_whtax_rate',
            DB::raw("premium_invoice_details_sammary.allocated_amt * (premium_invoice_details_sammary.hos_rate / 100) as commis")
        )
            ->leftJoin('premium_invoice', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
            ->leftJoin('corporate', 'premium_invoice.corp_id', '=', 'corporate.corp_id')
            ->leftJoin('member_info', 'premium_invoice.member_no', '=', 'member_info.member_no')
            ->where('premium_invoice_details_sammary.allocated', 1)
            ->where('premium_invoice.credit_note', 1)
            ->where('premium_invoice_details_sammary.hos_paid', 1)
            ->where(DB::raw('isnull(premium_invoice_details_sammary.hos_payno, 0)'), 0)
            ->where('premium_invoice_details_sammary.hos_vno', $hos_vno)
            ->get();

        foreach ($debits as $dt) {
            $total_hos_amt += $dt->hos_amt;
        }
        foreach ($debits as $dt) {
            $total_commission += ($dt->allocated_amt * ($dt->hos_rate) / 100);
        }
        return [
            'debits' => $debits,
            // 'total_hos_amt' => number_format($total_hos_amt, 2, '.', ''),
            'total_hos_amt' => number_format($total_hos_amt, 2),
            'total_commission' => number_format($total_commission, 2),
        ];
    }
    public function fetchHosRate(Request $request)
    {
        // return $request;
        $new_ren = $request[1];
        $invoice_no = $request[2];
        $premium = str_replace(",", "", $request[7]);
        $hos_whtax_rate = 10;

        $dt = PremiumInvoice::select(
            'anniv',
            'corp_id',
            'member_no',
            'invoice_date',
            'hos_rate'
        )
            ->where('invoice_no', $invoice_no)
            ->get();

        $anniv = $dt[0]->anniv;
        $corpid = $dt[0]->corp_id;
        $memnu = $dt[0]->member_no;
        $invdt = $dt[0]->invoice_date;
        $hos_rate = $dt[0]->hos_rate;      


        if (is_null($hos_rate)) {
            if ($new_ren == 7) {
                if (!is_null($corpid)) {
                    $new_ren = PremiumInvoice::whereIn('new_renewal', [1, 2])
                        ->where('anniv', $anniv)
                        ->where('corp_id', $corpid)
                        ->pluck('new_renewal');
                    $new_ren = $new_ren[0];
                } else {
                    $new_ren = PremiumInvoice::whereIn('new_renewal', [1, 2])
                        ->where('anniv', $anniv)
                        ->where('member_no', $memnu)
                        ->pluck('new_renewal');
                    $new_ren = $new_ren[0];
                }
            }
            //new business
            if ($new_ren == 1) {
                $effective_from = CommissionRate::where('effective_from', '<=', $invdt)
                    ->where('agent_type', 5)
                    ->max('effective_from');
    
                $hos_rate = CommissionRate::where('effective_from', $effective_from)
                    ->where('agent_type', 5)
                    ->pluck('new_business');
    
                $hos_rate = $hos_rate[0];
    
                //renewal business
            } else if ($new_ren == 2) {
                $effective_from = CommissionRate::where('effective_from', '<=', $invdt)
                    ->where('agent_type', 5)
                    ->max('effective_from');
    
                $hos_rate = CommissionRate::where('effective_from', $effective_from)
                    ->where('agent_type', 5)
                    ->pluck('renewal');
    
                $hos_rate = $hos_rate[0];
    
                //addition or organic growth
            } else if ($new_ren == 3 || $new_ren == 7) {
                $effective_from = CommissionRate::where('effective_from', '<=', $invdt)
                    ->where('agent_type', 5)
                    ->max('effective_from');
    
                $hos_rate = CommissionRate::where('effective_from', $effective_from)
                    ->where('agent_type', 5)
                    ->pluck('organic_growth ');
    
                $hos_rate = $hos_rate[0];
            }
            
        }


        //calculate the override commission 
        $hos_commis = $premium * ($hos_rate / 100);
        $hos_whtax = $hos_commis * ($hos_whtax_rate / 100);
        $hos_amt = $hos_commis - $hos_whtax;
        $data = [
            'hos_amt' => number_format($hos_amt, 2, '.', ''),
            'hos_whtax' => number_format($hos_whtax, 2, '.', ''),
            'hos_rate' => number_format($hos_rate, 2, '.', ''),
            'hos_whtax_rate' => number_format($hos_whtax_rate, 2, '.', ''),
            'commis' => number_format($hos_commis, 2, '.', ''),
        ];
        return $data;
    }

    //save pay hos invoices
    public function savePayHosInvoices(Request $request)
    {
        //    return $request;
        ini_set('max_execution_time', '0');

        $today = date_format(today(), 'Y-m-d');

        switch ($request->selected_option) {
            //pay
            case '1':
                try {
                    $response = array();
                    //generate a voucher no
                    $last_ov = LastNumber::select('last_no')->where('task', 2)->get();

                    $last_ov = $last_ov[0]['last_no'] + 1;

                   

                    //update last number table first
                    LastNumber::where('task', 2)
                        ->update([
                            'last_no' => $last_ov
                        ]);
                    //generate new voucher number
                    $voucher_no = 'OV' . $last_ov;

                    //get today's date
                    $today = date_format(today(), 'Y-m-d');

                    //set inserted, updated, and deleted items
                    $inserted_item = null;
                    $updated_item = "voucher_no : $voucher_no";
                    $deleted_item = null;

                    for ($i = 0; $i < count($request->invoice_no); $i++) {
                        if ($request->pay_hos[$i] == 1) {
                            //update with new OV number
                            PremiumInvoiceDetailsSammary::where([
                                'id_key' => $request->id_key[$i]
                            ])->update([
                                'hos_date' => $today,
                                'hos_vno' => $voucher_no,
                                'hos_paid' => 1,
                                'hos_rate' => $request->hos_rate[$i],
                                'hos_whtax_rate' => $request->hos_whtax_rate[$i],
                                'hos_whtax' =>  str_replace(",", "", $request->hos_whtax[$i]),
                                'hos_amt' => str_replace(",", "", $request->hos_amt[$i]),
                            ]);
                            array_push($response, $request->invoice_no[$i]);
                        }
                    }
                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);

                    return ['message' => 'Save OK. The Voucher No is :' . $voucher_no];
                } catch (\Exception $e) {
                    return ['error' => $e->getMessage()];
                }
                break;
//voucher
            case '2':
                try {
                    //get latest payment no
                    $last_pay_no = LastNumber::select('last_no')->where('task', 3)->get();

                    $last_pay_no = $last_pay_no[0]['last_no'] + 1;

                    //set inserted, updated, and deleted items
                    $inserted_item = "last_pay_no : $last_pay_no";
                    $updated_item = null;
                    $deleted_item = null;

                    //update last number table first
                    LastNumber::where('task', 3)
                        ->update([
                            'last_no' => $last_pay_no
                        ]);

                    //fetch agent
                    $agent = PremiumInvoice::join('premium_invoice_details_sammary', 'premium_invoice.invoice_no', '=', 'premium_invoice_details_sammary.invoice_no')
                        ->where('premium_invoice_details_sammary.hos_vno', $request->voucher_no)
                        ->distinct()
                        ->pluck('premium_invoice.agent_id');

                    ChequePayment::create([
                        'payment_no' => $last_pay_no,
                        'cheque_no' => $request->cheque_no,
                        'cheque_date' => $request->cheque_date,
                        'cheque_amount' => str_replace(',', '', $request->cheque_amount),
                        'payee' => $agent[0],
                        'payee_type' => 1,
                        'dispatched' => 1,
                        'vend' => 20,
                        'date_dispatched' => $today,
                        'user_id' => $request->user,
                        'date_entered' => $today,
                        'deduction' => $request->deduction,
                        'total_commission' => str_replace(',', '', $request->total_commission),
                    ]);
                    for ($i = 0; $i < count($request->id_key); $i++) {
                        PremiumInvoiceDetailsSammary::where([
                            'id_key' => $request->id_key[$i]
                        ])->update([
                            'hos_payno' => $last_pay_no,
                        ]);
                    }
                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);

                    return ['message' => 'Save OK. The Payment No is :' . $last_pay_no];
                } catch (\Exception $e) {
                    return ['error' => $e->getMessage()];
                }
                break;
        }
    }
}
