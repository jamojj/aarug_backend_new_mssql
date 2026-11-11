<?php

namespace App\Http\Controllers\Commission\override;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Bdm;
use App\Models\ChequePayment;
use App\Models\Corporate\CorpAdminFee;
use App\Models\Finance\CommissionRate;
use App\Models\LastNumber;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetailsSammary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayOverrideBDMController extends Controller
{
    //fetch bdm invoices
    public function fetchBDMInvoices($bdm_code)
    {
        $data = PremiumInvoiceDetailsSammary::select(
            'premium_invoice_details_sammary.id_key',
            'premium_invoice_details_sammary.invoice_no',
            'premium_invoice_details_sammary.class',
            'premium_invoice.new_renewal',
            'premium_invoice_details_sammary.net_premium',
            'premium_invoice_details_sammary.allocated',
            'premium_invoice_details_sammary.allocated_amt',
            'premium_invoice_details_sammary.bdm_paid',
            'premium_invoice_details_sammary.bdm_amt',
            'premium_invoice_details_sammary.bdm_vno',
            'premium_invoice_details_sammary.bdm_payno',
            'premium_invoice_details_sammary.bdm_rate',
            'premium_invoice_details_sammary.bdm_whtax_rate',
            'premium_invoice_details_sammary.bdm_whtax',
            'premium_invoice.corp_id',
            'premium_invoice.member_no',
            'premium_invoice_details_sammary.bdm_paid',
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
            'premium_invoice.agent_id',
            'corporate.corporate'
        )
            ->leftJoin('premium_invoice', 'premium_invoice.invoice_no', '=', 'premium_invoice_details_sammary.invoice_no')
            ->leftJoin('agent', 'agent.agent_id', '=', 'premium_invoice.agent_id')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
            ->orderBy('premium_invoice_details_sammary.id_key', 'ASC')
            ->where('premium_invoice.credit_note', 1)
            ->where(DB::raw('isnull(premium_invoice_details_sammary.net_premium, 0)'), 0)
            ->where(DB::raw('isnull(premium_invoice_details_sammary.bdm_paid, 0)'), 0)
            ->where('agent.bdm', $bdm_code)
            ->get();

        return $data;
    }

    //pay bdm invoices per row to fetch bdm whtax rate
    public function fetchBDMRate(Request $request)
    {
        //return $request;
        $new_ren = $request[1];
        $invoice_no = $request[2];
        $premium = $request[7];
        $bdm_whtax_rate = 10;

        $dt = PremiumInvoice::select('anniv', 'corp_id', 'member_no', 'invoice_date', 'bdm_rate', 'agent_id')
            ->where('invoice_no', $invoice_no)
            ->get();

        $agent_id = $dt[0]->agent_id;
        $anniv = $dt[0]->anniv;
        $corpid = $dt[0]->corp_id;
        $mem_no = $dt[0]->member_no;
        $invdt = $dt[0]->invoice_date;
        $bdm_override_rate = $dt[0]->bdm_rate;

        //get agent dbm
        $bdm = Agent::select('bdm')->where('agent_id', $agent_id)->get();
        $bdm = count($bdm) !== 0  ? $bdm[0]->bdm : null;
        
        //get bdm name
        $bdm_name = Bdm::select('name')->where('code', $bdm)->get();
        $bdm_name = count($bdm_name) !== 0  ? $bdm_name[0]->name : null;

        //get agent type of bdm
        $agent_type_bdm = Agent::select('agent_type')->where('agent_name', $bdm_name)->get();
        $agent_type_bdm = count($agent_type_bdm) !== 0 ? $agent_type_bdm[0]->agent_type : null;


        if ($agent_type_bdm == 7 || $agent_type_bdm == 8 || $agent_type_bdm == 9) {
             $bdm_whtax_rate = 0;
            $bdm_override_rate = 0;
            $bdm_commis = 0;
            $bdm_whtax = 0;
            $bdm_amt = 0;
        } else {
            if (is_null($bdm_override_rate)) {
                $corp_admin_fee_data = CorpAdminFee::select(DB::raw('sum(isnull(bdm_rate, 0)) as bdm_rate'))
                    ->where('anniv', $anniv)
                    ->where('corp_id', $corpid)
                    ->get();
                $bdm_rate = $corp_admin_fee_data[0]->bdm_rate;
                //special rates
                if ($bdm_rate > 0) {
                    $corp_admin_fee_override_rate = CorpAdminFee::select('bdm_rate')
                        ->where('anniv', $anniv)
                        ->where('corp_id', $corpid)
                        ->get();

                    $bdm_override_rate = $corp_admin_fee_override_rate[0]->bdm_rate;
                } else {
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
                                ->where('member_no', $mem_no)
                                ->pluck('new_renewal');
                            $new_ren = $new_ren[0];
                        }
                    }
                    //new business
                    if ($new_ren == 1) {
                        $effective_from = CommissionRate::where('effective_from', '<=', $invdt)
                            ->where('agent_type', 4)
                            ->max('effective_from');

                        $bdm_override_rate = CommissionRate::where('effective_from', $effective_from)
                            ->where('agent_type', 4)
                            ->pluck('new_business');

                        $bdm_override_rate = $bdm_override_rate[0];
                    } //renewal business
                    else if ($new_ren == 2) {
                        $effective_from = CommissionRate::where('effective_from', '<=', $invdt)
                            ->where('agent_type', 4)
                            ->max('effective_from');

                        $bdm_override_rate = CommissionRate::where('effective_from', $effective_from)
                            ->where('agent_type', 4)
                            ->pluck('renewal');

                        $bdm_override_rate = $bdm_override_rate[0];
                    } //addition or organic growth
                    else if ($new_ren == 3) {
                        $effective_from = CommissionRate::where('effective_from', '<=', $invdt)
                            ->where('agent_type', 4)
                            ->max('effective_from');

                        $bdm_override_rate = CommissionRate::where('effective_from', $effective_from)
                            ->where('agent_type', 4)
                            ->pluck('organic_growth ');

                        $bdm_override_rate = $bdm_override_rate[0];
                    }
                }
            }

            //calculate the override commission
            $bdm_commis = $premium * ($bdm_override_rate / 100);
            $bdm_whtax = $bdm_commis * ($bdm_whtax_rate / 100);
            $bdm_amt = $bdm_commis - $bdm_whtax;
        }

        $data = [
            'bdm_amt' => number_format($bdm_amt, 2, '.', ''),
            'bdm_whtax' => number_format($bdm_whtax, 2, '.', ''),
            'bdm_rate' => number_format($bdm_override_rate, 2, '.', ''),
            'bdm_whtax_rate' => number_format($bdm_whtax_rate, 2, '.', ''),
        ];
        return $data;
    }

    //save pay bdm invoices
    public function savePayBDMInvoices(Request $request)
    {
        //get today's date
        $today = date_format(today(), 'Y-m-d');
        switch ($request->task) {
                //save pay override bdm
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

                    //set inserted, updated, and deleted items
                    $inserted_item = null;
                    $updated_item = "voucher_no : $voucher_no";
                    $deleted_item = null;

                    for ($i = 0; $i < count($request->invoice_no); $i++) {
                        if ($request->pay_bdm[$i] == '1') {
                            //update with new OV number
                            PremiumInvoiceDetailsSammary::where([
                                'id_key' => $request->id_key[$i]
                            ])->update([
                                'bdm_date' => $today,
                                'bdm_vno' => $voucher_no,
                                'bdm_paid' => 1,
                                'bdm_rate' => $request->bdm_rate[$i],
                                'bdm_whtax_rate' => $request->bdm_whtax_rate[$i],
                                'bdm_whtax' => $request->bdm_whtax[$i],
                                'bdm_amt' => $request->bdm_amt[$i],
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
                //save voucher override bdm
                //
            case '2':
                //return $request;
                try {
                    //fetch agent
                    $agent = PremiumInvoice::join('premium_invoice_details_sammary', 'premium_invoice.invoice_no', '=', 'premium_invoice_details_sammary.invoice_no')
                        ->where('premium_invoice_details_sammary.bdm_vno', $request->voucher_no)
                        ->distinct()
                        ->pluck('premium_invoice.agent_id');
                    $agent = $agent[0];
                    //get latest payment no
                    $last_pay_no = LastNumber::select('last_no')->where('task', 3)->get();

                    $last_pay_no = $last_pay_no[0]['last_no'] + 1;

                    //set inserted, updated, and deleted items
                    $inserted_item = null;
                    $updated_item = "last_pay_no : $last_pay_no";
                    $deleted_item = null;

                    //update last number table first
                    LastNumber::where('task', 3)
                        ->update([
                            'last_no' => $last_pay_no
                        ]);
                    ChequePayment::create([
                        'payment_no' => $last_pay_no,
                        'cheque_no' => $request->cheque_no,
                        'cheque_date' => $request->cheque_date,
                        'cheque_amount' => $request->cheque_amount,
                        'payee' => $agent,
                        'payee_type' => 1,
                        'dispatched' => 1,
                        'date_dispatched' => $today,
                        'user_id' => $request->user,
                        'date_entered' => $today,
                        'total_commission' => $request->total_commission,
                        'vend' => 20,
                    ]);
                    for ($i = 0; $i < count($request->id_key_t2); $i++) {
                        PremiumInvoiceDetailsSammary::where([
                            'id_key' => $request->id_key_t2[$i]
                        ])->update([
                            'bdm_payno' => $last_pay_no,
                        ]);
                    }
                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);

                    return ['message' => 'Save OK. The Payment No is :' . $last_pay_no];
                } catch (\Exception $e) {
                    return ['error' => $e->getMessage()];
                }
                break;
            default:
                break;
        }
    }

    //voucher - fetch invoices by ov number
    public function fetchVoucherInvoicesByOVNumber($ov_number)
    {
        $response = array();
        $total_bdm_amt = $total_commiss = $total_whtax_amt = 0;
        $vouchers = PremiumInvoiceDetailsSammary::select('*')
            ->where('bdm_vno', $ov_number)
            ->count();
        if ($vouchers > 0) {
            $already_paid = PremiumInvoiceDetailsSammary::select(DB::raw("isnull(bdm_payno, 0)as bdm_payno"))->where('bdm_vno', $ov_number)->get();
            if ($already_paid[0]->bdm_payno == 0) {
                $data = PremiumInvoiceDetailsSammary::select(
                    'premium_invoice.agent_id',
                    'premium_invoice.corp_id',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                    'corporate.corporate',
                    'agent.agent_name',
                    'premium_invoice_details_sammary.allocated_amt',
                    'premium_invoice_details_sammary.bdm_rate',
                    'premium_invoice_details_sammary.bdm_whtax_rate',
                    'premium_invoice_details_sammary.bdm_whtax',
                    'premium_invoice_details_sammary.bdm_amt',
                    'premium_invoice_details_sammary.bdm_paid',
                    'premium_invoice_details_sammary.id_key',
                    DB::raw("premium_invoice_details_sammary.allocated_amt * (premium_invoice_details_sammary.bdm_rate / 100) as commis")
                )
                    ->leftJoin('premium_invoice', 'premium_invoice.invoice_no', '=', 'premium_invoice_details_sammary.invoice_no')
                    ->leftJoin('agent', 'agent.agent_id', '=', 'premium_invoice.agent_id')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                    ->where('premium_invoice_details_sammary.bdm_vno', $ov_number)
                    ->get();
                foreach ($data as $dt) {
                    $total_bdm_amt += $dt->bdm_amt;
                    $total_whtax_amt += $dt->bdm_whtax;
                    $total_commiss += ($dt->allocated_amt * ($dt->bdm_rate) / 100);
                }
                return [
                    'data' => $data,
                    'total_bdm_amt' => number_format($total_bdm_amt, 2, '.', ''),
                    'total_whtax_amt' => number_format($total_whtax_amt, 2, '.', ''),
                    'total_commiss' => number_format($total_commiss, 2, '.', '')
                ];
            } else {
                array_push($response, 'Notice, Voucher Is Already Paid!');
                return ['message' => $response[0]];
            }
        } else {
            array_push($response, 'Notice, Invalid Voucher No!');
            return ['message' => $response[0]];
        }
    }
}
