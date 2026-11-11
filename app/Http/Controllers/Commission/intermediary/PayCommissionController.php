<?php

namespace App\Http\Controllers\Commission\intermediary;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentCommisRates;
use App\Models\ChequePayment;
use App\Models\Corporate\CorpAdminFee;
use App\Models\Finance\CommissionRate;
use App\Models\LastNumber;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetails;
use App\Models\PremiumInvoiceDetailsSammary;
use App\Models\PremiumReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayCommissionController extends Controller
{
    public function fetchAgentsToPayCommission()
    {
        $agents = Agent::select('agent.agent_id', 'agent.agent_name', 'agent.agent_type')
            ->leftJoin('premium_invoice', 'agent.agent_id', '=', 'premium_invoice.agent_id')
            ->leftJoin('premium_invoice_details_sammary', 'premium_invoice.invoice_no', '=', 'premium_invoice_details_sammary.invoice_no')
            ->where('premium_invoice_details_sammary.allocated', 1)
            ->where('premium_invoice.credit_note', 1)
            ->where(DB::raw('isnull(premium_invoice_details_sammary.paid, 0)'), 0)
            ->where(DB::raw('isnull(agent.status, 0)'), 1)
            ->orderBy('agent.agent_name', 'ASC')
            ->distinct()
            ->get();

        return $agents;
    }

    public function fetchVoucherNosToAttachCheque()
    {
        $voucher_nos = PremiumInvoiceDetailsSammary::select('commis_vno')
            ->where(DB::raw("isnull(commis_payno, 0)"), 0)
            ->whereNotNull('commis_vno')
            ->distinct()
            ->orderBy('commis_vno')
            ->get();
        return $voucher_nos;
    }

    public function fetchChequesCommission()
    {
        $cheques = ChequePayment::select('cheque_no', 'payment_no')
            ->where('payee_type', 1)
            ->orderBy('cheque_date', 'DESC')
            ->get();
        return $cheques;
    }

    public function fetchDebitsNotPaidCommission($agent_id)
    {
        $debits = PremiumInvoiceDetailsSammary::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            'premium_invoice_details_sammary.invoice_no',
            'corporate.corporate',
            'premium_invoice_details_sammary.class',
            'premium_invoice_details_sammary.net_premium',
            'premium_invoice_details_sammary.allocated',
            'premium_invoice_details_sammary.allocated_amt',
            'premium_invoice_details_sammary.paid',
            'premium_invoice_details_sammary.commis_rate',
            'premium_invoice_details_sammary.wh_tax_rate',
            'premium_invoice_details_sammary.wh_tax',
            'premium_invoice_details_sammary.commission',
            'premium_invoice_details_sammary.id_key',
            'premium_invoice.new_renewal',
            'agent.agent_type',
            'agent.agent_id'
        )
            ->leftJoin('premium_invoice', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
            ->leftJoin('corporate', 'premium_invoice.corp_id', '=', 'corporate.corp_id')
            ->leftJoin('member_info', 'premium_invoice.member_no', '=', 'member_info.member_no')
            ->leftJoin('agent', 'premium_invoice.agent_id', '=', 'agent.agent_id')
            ->where(DB::raw('isnull(premium_invoice_details_sammary.net_premium, 0)'), 0)
            ->where(DB::raw('isnull(premium_invoice_details_sammary.paid, 0)'), 0)
            ->where('premium_invoice.agent_id', $agent_id)
            ->orderBy('premium_invoice_details_sammary.invoice_no', 'ASC')
            ->get();

        return $debits;
    }

    public function fetchDebitsPaidToAttachCheque($voucher_no)
    {
        $debits = PremiumInvoiceDetailsSammary::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("premium_invoice_details_sammary.wh_tax + premium_invoice_details_sammary.commission as ttlCommis"),
            'premium_invoice_details_sammary.invoice_no',
            'corporate.corporate',
            'premium_invoice_details_sammary.class',
            'premium_invoice_details_sammary.net_premium',
            'premium_invoice_details_sammary.allocated',
            'premium_invoice_details_sammary.allocated_amt',
            'premium_invoice_details_sammary.paid',
            'premium_invoice_details_sammary.commis_rate',
            'premium_invoice_details_sammary.wh_tax_rate',
            'premium_invoice_details_sammary.wh_tax',
            'premium_invoice_details_sammary.commission',
            'premium_invoice_details_sammary.id_key',
            'premium_invoice.new_renewal',
            'agent.agent_type'
        )
            ->leftJoin('premium_invoice', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
            ->leftJoin('corporate', 'premium_invoice.corp_id', '=', 'corporate.corp_id')
            ->leftJoin('member_info', 'premium_invoice.member_no', '=', 'member_info.member_no')
            ->leftJoin('agent', 'premium_invoice.agent_id', '=', 'agent.agent_id')
            ->where('premium_invoice_details_sammary.commis_vno', $voucher_no)
            ->orderBy('premium_invoice_details_sammary.invoice_no', 'ASC')
            ->get();

        $agent = PremiumInvoice::join('premium_invoice_details_sammary', 'premium_invoice.invoice_no', '=', 'premium_invoice_details_sammary.invoice_no')
            ->join('agent', 'premium_invoice.agent_id', '=', 'agent.agent_id')
            ->where('premium_invoice_details_sammary.commis_vno', $voucher_no)
            ->distinct()
            ->pluck('agent.agent_name');

        $data = [
            'debits' => $debits,
            'agent' => $agent
        ];

        return $data;
    }

    public function fetchDebitsForCheque($payment_no)
    {
        $debits = PremiumInvoiceDetailsSammary::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            'premium_invoice_details_sammary.invoice_no',
            'corporate.corporate',
            'premium_invoice_details_sammary.class',
            'premium_invoice_details_sammary.net_premium',
            'premium_invoice_details_sammary.allocated',
            'premium_invoice_details_sammary.allocated_amt',
            'premium_invoice_details_sammary.paid',
            'premium_invoice_details_sammary.commis_rate',
            'premium_invoice_details_sammary.wh_tax_rate',
            'premium_invoice_details_sammary.wh_tax',
            'premium_invoice_details_sammary.commission',
            'premium_invoice_details_sammary.id_key',
            'premium_invoice.new_renewal',
            'agent.agent_type'
        )
            ->leftJoin('premium_invoice', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
            ->leftJoin('corporate', 'premium_invoice.corp_id', '=', 'corporate.corp_id')
            ->leftJoin('member_info', 'premium_invoice.member_no', '=', 'member_info.member_no')
            ->leftJoin('agent', 'premium_invoice.agent_id', '=', 'agent.agent_id')
            ->where('premium_invoice_details_sammary.commis_payno', $payment_no)
            ->orderBy('premium_invoice_details_sammary.invoice_no', 'ASC')
            ->get();

            $agent = PremiumInvoice::join('premium_invoice_details_sammary', 'premium_invoice.invoice_no', '=', 'premium_invoice_details_sammary.invoice_no')
            ->join('agent', 'premium_invoice.agent_id', '=', 'agent.agent_id')
            ->where('premium_invoice_details_sammary.commis_payno', $payment_no)
            ->distinct()
            ->pluck('agent.agent_name');

        $data = [
            'debits' => $debits,
            'agent' => $agent
        ];

        return $data;
    }

    public function fetchCommissionRate(Request $request)
    {
        // return $request;
        $response = array();
        $il_agent_type = $request[1];
        $is_agent_id = $request[2];
        $lc_premium = $request[10];
        $ll_benefit = $request[7];
        $ll_new_ren = $request[3];
        $ls_invoice_no = $request[4];
        $ll_premtype = $lc_commis_rate =  $lc_taxrate = 0;

        if (!is_null($il_agent_type)) {
            $ls_receipt_no = PremiumInvoiceDetailsSammary::where('allocated', 1)
                ->where('invoice_no', $ls_invoice_no)
                ->pluck('receipt_no');
            $ls_receipt_no =   count($ls_receipt_no) != 0 ? $ls_receipt_no[0] : "";
            $dt = PremiumInvoice::select(
                DB::raw("isnull(premium_type,0) as premium_type"),
                DB::raw("isnull(agent_rate,0) as agent_rate"),
                'anniv',
                'corp_id',
                'member_no',
                'invoice_date'
            )
                ->where('invoice_no', $ls_invoice_no)
                ->get();

            $ll_anniv = $dt[0]->anniv;
            $ls_corpid = $dt[0]->corp_id;
            $ls_memnu = $dt[0]->member_no;
            $ld_invdt = $dt[0]->invoice_date;
            $ll_premtype = $dt[0]->premium_type;
            $lc_pre_commrate = $dt[0]->agent_rate;

            $ll_srate = CorpAdminFee::select(DB::raw("isnull(sum(isnull(agent_commis_rate, 0)), 0) as agent_commis_rate"))
                ->where('anniv', $ll_anniv)
                ->where('corp_id', $ls_corpid)
                ->get();

            $ll_srate = count($ll_srate) != 0 ? $ll_srate[0]->agent_commis_rate : 0;

            $ll_comm_paid = PremiumReceipt::where('receipt_no', $ls_receipt_no)->pluck('commis_paid');
            $ll_comm_paid = count($ll_comm_paid) != 0 ? $ll_comm_paid[0] : 0;

            if (is_null($ll_comm_paid) ||  $ll_comm_paid == 0) {
                if ($lc_pre_commrate > 0) {
                    $lc_commis_rate = $lc_pre_commrate;
                } else {
                    if ($ll_srate > 0) {
                        $lc_commis_rate = CorpAdminFee::where('anniv', $ll_anniv)->where('corp_id', $ls_corpid)->pluck('agent_commis_rate');
                        $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                    } else {

                        if ($ll_new_ren == 7) {
                            if ($ll_premtype == 3 || $ll_premtype == 5) {
                                $ll_premtype == 1;
                            } else if ($ll_premtype == 4) {
                                $ll_premtype == 2;
                            } else if ($ll_premtype == 0) {
                                if ($ll_new_ren == 7) {
                                    if (!is_null($ls_corpid)) {
                                        $ll_new_ren = PremiumInvoice::whereIn('new_renewal', [1, 2])
                                            ->where('anniv', $ll_anniv)
                                            ->where('corp_id', $ls_corpid)
                                            ->pluck('new_renewal');
                                        $ll_new_ren = count($ll_new_ren) != 0 ? $ll_new_ren[0] : 0;
                                    } else {
                                        $ll_new_ren = PremiumInvoice::whereIn('new_renewal', [1, 2])
                                            ->where('anniv', $ll_anniv)
                                            ->where('member_no', $ls_memnu)
                                            ->pluck('new_renewal');
                                        $ll_new_ren = count($ll_new_ren) != 0 ? $ll_new_ren[0] : 0;
                                    }
                                }
                            }
                        }


                        //new business
                        if ($ll_new_ren == 1) {
                            if ($il_agent_type == 1) {
                                $lc_premium_amt = PremiumInvoice::where('invoice_no', $ls_invoice_no)->pluck('premium_net');
                                $lc_premium_amt = count($lc_premium_amt) != 0 ? $lc_premium_amt[0] : 0;
                                $effective_from = CommissionRate::where('effective_from', '<=', $ld_invdt)
                                    ->where('agent_type', $il_agent_type)
                                    ->max('effective_from');


                                $lc_max_prem = CommissionRate::where('effective_from', $effective_from)
                                    ->where('agent_type', $il_agent_type)
                                    ->max(DB::raw("isnull(premium_from, 0)"));

                                $effective_from_two = CommissionRate::where('effective_from', '<=', $ld_invdt)
                                    ->max('effective_from');

                                $lc_prem_rate = CommissionRate::where('effective_from', $effective_from_two)
                                    ->where('agent_type', $il_agent_type)
                                    ->where('premium_from', $lc_max_prem)
                                    ->pluck('new_business');
                                $lc_prem_rate = count($lc_prem_rate) != 0 ? $lc_prem_rate[0] : 0;

                                $dt = CommissionRate::select('lives', 'premium_from', 'new_business')
                                    ->where('effective_from', $effective_from)
                                    ->where(DB::raw("isnull(lives, 0)"), ">", 0)
                                    ->where('agent_type', $il_agent_type)
                                    ->get();


                                $ll_lives = count($dt) != 0 ? $dt[0]->lives : 0;
                                $lc_mid_prem = count($dt) != 0 ? $dt[0]->premium_from : 0;
                                $lc_ren_rate = count($dt) != 0 ? $dt[0]->new_business : 0;


                                if ($lc_premium_amt >= $lc_max_prem) {
                                    $lc_commis_rate = $lc_prem_rate;
                                } else if ($lc_premium_amt >= $lc_mid_prem) {
                                    $ll_inv_mems = PremiumInvoiceDetails::where('invoice_no', $ls_invoice_no)
                                        ->count('member_no');

                                    if ($ll_inv_mems >= $ll_lives) {
                                        $lc_commis_rate = $lc_ren_rate;
                                    } else {
                                        $lc_commis_rate = CommissionRate::where('effective_from', $effective_from)
                                            ->where(DB::raw("isnull(premium_from, 0)"), 0)
                                            ->where('agent_type', $il_agent_type)
                                            ->pluck('new_business');
                                        $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                                    }
                                } else {
                                    $lc_commis_rate = CommissionRate::where('effective_from', $effective_from)
                                        ->where(DB::raw("isnull(premium_from, 0)"), 0)
                                        ->where('agent_type', $il_agent_type)
                                        ->pluck('new_business');
                                    $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                                }
                            } else {
                                $effective_from = CommissionRate::where('effective_from', '<=', $ld_invdt)
                                    ->where('agent_type', $il_agent_type)
                                    ->max('effective_from');

                                $lc_commis_rate = CommissionRate::where('effective_from', $effective_from)
                                    ->where('agent_type', $il_agent_type)
                                    ->pluck('new_business');

                                $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                            }
                            //renewal business
                        } else if ($ll_new_ren == 2) {

                            $effective_from = CommissionRate::where('effective_from', '<=', $ld_invdt)
                                ->where('agent_type', $il_agent_type)
                                ->max('effective_from');

                            if (!is_null($effective_from)) {

                                $lc_commis_rate = CommissionRate::where('effective_from', $effective_from)
                                    ->where('agent_type', $il_agent_type)
                                    ->pluck('renewal');

                                $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                            } else {
                                $lc_commis_rate = 0;
                            }

                            //addition or organic growth
                        } else if ($ll_new_ren == 3 || $ll_new_ren == 7) {
                            if ($il_agent_type == 1) {
                                $lc_premium_amt = PremiumInvoice::where('invoice_no', $ls_invoice_no)->pluck('premium_net');
                                $lc_premium_amt = count($lc_premium_amt) != 0 ? $lc_premium_amt[0] : 0;
                                $lc_max_prem = CommissionRate::where('agent_type', $il_agent_type)->max(DB::raw("isnull(premium_from, 0)"));
                                $lc_prem_rate = CommissionRate::where('agent_type', $il_agent_type)
                                    ->where('premium_from', $lc_max_prem)
                                    ->pluck('new_business');
                                $lc_prem_rate = count($lc_prem_rate) != 0 ? $lc_prem_rate[0] : 0;

                                $dt = CommissionRate::select('lives', 'premium_from', 'new_business')
                                    ->where(DB::raw("isnull(lives, 0)"), ">", 0)
                                    ->where('agent_type', $il_agent_type)
                                    ->get();

                                $ll_lives = count($dt) != 0 ? $dt[0]->lives : 0;
                                $lc_mid_prem = count($dt) != 0 ? $dt[0]->premium_from : 0;
                                $lc_ren_rate = count($dt) != 0 ? $dt[0]->new_business : 0;


                                if ($lc_premium_amt >= $lc_max_prem) {
                                    $lc_commis_rate = $lc_prem_rate;
                                } else if ($lc_premium_amt >= $lc_mid_prem) {
                                    $ll_inv_mems = PremiumInvoiceDetails::where('invoice_no', $ls_invoice_no)
                                        ->count('member_no');

                                    if ($ll_inv_mems >= $ll_lives) {
                                        $lc_commis_rate = $lc_mid_prem;
                                    } else {
                                        $lc_commis_rate = CommissionRate::where(DB::raw("isnull(premium_from, 0)"), 0)
                                            ->where('agent_type', $il_agent_type)
                                            ->pluck('new_business');
                                        $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                                    }
                                } else {
                                    $lc_commis_rate = CommissionRate::where(DB::raw("isnull(premium_from, 0)"), 0)
                                        ->where('agent_type', $il_agent_type)
                                        ->pluck('new_business');

                                    $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                                }
                            } else {
                                $lc_commis_rate = CommissionRate::where('agent_type', $il_agent_type)->pluck('new_business');
                                $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                            }
                        }
                    }
                }

                if ($is_agent_id == "11" || $is_agent_id == "15" || $is_agent_id == "16" || $is_agent_id == "44" || $is_agent_id == "59") {
                    $lc_commis_rate = 0;
                    $lc_taxrate = 0;
                } else {
                    if ($il_agent_type == 2) {
                        $lc_taxrate = 6;
                    }else{
                        $lc_taxrate = 10;
                    }
                }
                //calculate the commission for this benefit n agent
                $lc_commis = $lc_premium * ($lc_commis_rate / 100);
                $lc_wh_tax = $lc_commis * ($lc_taxrate / 100);
                $commission = $lc_commis - $lc_wh_tax;               

                $data = [
                    'commission' => number_format($commission, 2, '.', ''),
                    'wh_tax' => number_format($lc_wh_tax, 2, '.', ''),
                    'commis_rate' => number_format($lc_commis_rate, 2, '.', ''),
                    'wh_tax_rate' => number_format($lc_taxrate, 2, '.', ''),
                ];

                array_push($response, $data);
            } else if (!is_null($ll_comm_paid)) {
                //new buz or addition
                if ($ll_new_ren == 1 || $ll_new_ren == 3) {
                    $lc_commis_rate = AgentCommisRates::where('benefit', $ll_benefit)
                        ->where('agent_id', $is_agent_id)
                        ->pluck('commission_rate_newbuz');
                    $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                    //renewal buz
                } else if ($ll_new_ren == 2) {
                    $lc_commis_rate = AgentCommisRates::where('benefit', $ll_benefit)
                        ->where('agent_id', $is_agent_id)
                        ->pluck('commission_rate_renewalbuz');
                    $lc_commis_rate = count($lc_commis_rate) != 0 ? $lc_commis_rate[0] : 0;
                }

                $lc_wh_tax_rate = AgentCommisRates::where('benefit', $ll_benefit)
                    ->where('agent_id', $is_agent_id)
                    ->pluck('commission_withholding_tax');
                $lc_wh_tax_rate = count($lc_wh_tax_rate) != 0 ? $lc_wh_tax_rate[0] : 0;

                if ($is_agent_id == "11" || $is_agent_id == "15" || $is_agent_id == "16" || $is_agent_id == "44") {
                    $lc_commis_rate = 0;
                    $lc_taxrate = 0;
                } else {
                    if ($il_agent_type == 2) {
                        $lc_taxrate = 6;
                    } else {
                        $lc_taxrate = 10;
                    }
                }

                //calculate the commission for this benefit n agent
                $lc_commis = $lc_premium * ($lc_commis_rate / 100);
                $lc_wh_tax = $lc_commis * ($lc_taxrate / 100);
                $commission = $lc_commis - $lc_wh_tax;               

                $data = [
                    'commission' => 0.00,
                    'wh_tax' => number_format($lc_wh_tax, 2, '.', ''),
                    'commis_rate' => number_format($lc_commis_rate, 2, '.', ''),
                    'wh_tax_rate' => 6,
                ];

                array_push($response, $data);
            }
        } else {
            $data = [
                'commission' => 0.00,
                'wh_tax' => 0.00,
                'commis_rate' => 0.00,
                'wh_tax_rate' => 0.00,
            ];

            array_push($response, $data);
        }

        return $response;
    }

    public function updateCommissionPay(Request $request)
    {
        // return $request;
        $response = array();
        $commis_date = $today = date("Y-m_d");

        switch ($request->options) {
            //pay
            case '1':
                //fetch last no and increment
                $last_no = LastNumber::where('task', 2)->pluck('last_no');
                $last_no = $last_no[0];
                $last_no += 1;

                //set inserted, updated, and deleted items
                $inserted_item = null;
                $updated_item = "commis_vno : CM$last_no";
                $deleted_item = null;

                //update premium invoice details summary
                $rows = count($request->paid);
                for ($i = 0; $i < $rows; $i++) {
                    if ($request->paid[$i] == 1) {
                        $update_commission = PremiumInvoiceDetailsSammary::where('id_key', $request->id_key[$i])
                            ->update([
                                'commis_vno' => "CM" . $last_no,
                                'commis_rate' => $request->commis_rate[$i],
                                'wh_tax' => $request->wh_tax[$i],
                                'wh_tax_rate' => $request->wh_tax_rate[$i],
                                'commission' => $request->commission[$i],
                                'commis_date' => $commis_date,
                                'paid' => 1
                            ]);
                        array_push($response, $update_commission);
                    }
                }
                if (in_array(1, $response)) {
                    // update last number
                    $update_last_number = LastNumber::where('task', 2)->update(['last_no' => $last_no]);
                    if ($update_last_number == 1) {
                        $response = array();
                        array_push($response, "Commission paid successfully with voucher no : CM" .  $last_no);
                    }
                }

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);

                return $response;
                break;
                //voucher
            case '2':
                //fetch last no and increment
                $last_no = LastNumber::where('task', 3)->pluck('last_no');
                $last_no = $last_no[0];
                $last_no += 1;

                //set inserted, updated, and deleted items
                $inserted_item = "payment_no : $last_no";
                $updated_item = null;
                $deleted_item = null;

                //update premium invoice details summary
                $rows = count($request->id_key);
                for ($i = 0; $i < $rows; $i++) {
                    $update_commission = PremiumInvoiceDetailsSammary::where('id_key', $request->id_key[$i])
                        ->update([
                            'commis_payno' => $last_no,
                        ]);
                    array_push($response, $update_commission);
                }
                if (in_array(1, $response)) {
                    // update last number
                    $update_last_number = LastNumber::where('task', 3)->update(['last_no' => $last_no]);
                    /****save cheque details ***/
                    if ($update_last_number == 1) {

                        //fetch agent
                        $agent = PremiumInvoice::join('premium_invoice_details_sammary', 'premium_invoice.invoice_no', '=', 'premium_invoice_details_sammary.invoice_no')
                            ->where('premium_invoice_details_sammary.commis_vno', $request->voucher_no)
                            ->distinct()
                            ->pluck('premium_invoice.agent_id');

                        $create_cheque =  ChequePayment::create([
                            'payment_no' => $last_no,
                            'cheque_no' => $request->cheque_no,
                            'cheque_date' => $request->cheque_date,
                            'payee' => $agent[0],
                            'payee_type' => 1,
                            'dispatched' => 1,
                            'date_dispatched' => $today,
                            'user_id' => $request->user,
                            'date_entered' => $today,
                            'vend' => 20,
                            'cheque_amount' => number_format($request->cheque_amount_raw, 2, '.', ''),
                            'deduction' => $request->deduction,
                            'total_commission' => number_format($request->total_commission_raw, 2, '.', ''),
                        ]);

                        //reinstantiate response
                        $response = array();

                        if ($create_cheque->wasRecentlyCreated === true) {
                            array_push($response, "Voucher created successfully with payment no: " . $last_no);
                        } else {
                            array_push($response, "Error creating cheque");
                        }
                    }
                }

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);

                break;
        }
        return $response;
    }
}
