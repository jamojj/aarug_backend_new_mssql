<?php

namespace App\Http\Controllers\Finance\Reports;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Corporate\CorpAdminFee;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Finance\CommissionRate;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetails;
use App\Models\PremiumInvoiceDetailsSammary;
use App\Models\Registration\MemberAnniversary;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PremiumRegisterController extends Controller
{
    public function fetchPremiumRegister(Request $request)
    {
        $dt = [];
        $response = [];
        $lc_commis_rate = 0;
        $lc_commis = 0;
        $override = 0;
        $total_commission = 0;
        $ls_agent_na = "";
        $il_agent_type = "";
        $is_agent_id = "";
        $ttl_premium = $ttl_commission = $ttl_override = $ttl_ttl_commission = 0;
        $ll_anniv =  $ls_corpid = $ll_new_ren = $ls_mem = $ll_premtype = $lc_pre_commrate = $ld_invdt = $lc_premium_amt = 0;
        $from = $request->from;
        $from = date('Ymd', strtotime($from));
        $to = $request->to;
        $to = date('Ymd', strtotime($to));
        $start_of_premium = date('Y', strtotime($to));
        $start_of_premium = $start_of_premium . '0101';

        $rows = DB::select("select case 
			    when len(member_no) > 2 then 'Individual'
			        else 'Corporate'
		        END as Plan,
                cover_start_date start_date, end_date,
                case
                    when len(member_no) > 2 then (select surname||' '||first_name||' '||other_names from member_info where member_no = premium_invoice.member_no)
                    else (select corporate from corporate where corp_id = premium_invoice.corp_id)
                END as Corporate,
                case
                    when len(member_no) > 2 then ''
                    else (select policy_no from corporate where corp_id = premium_invoice.corp_id)
                END as policy_no,
                invoice_no debit_no, 
                (select premium_net from premium_invoice where dateformat (invoice_date,'yyyyMMdd') between $start_of_premium and $to and invoice_no = debit_no) as premium,  
                discount_amt comm_rate, discount_amt commission, discount_amt override, discount_amt total_commission,
                case
                    when new_renewal = 1 then 'New'
                    when new_renewal = 2 then 'Renewal'
                    when new_renewal = 4 then 'Credit notes'
                    when new_renewal = 7 then 'Additions'
                END as cover_type, 
                case
                    when premium_type = 1 then 'New'
                    when premium_type = 2 then 'Renewal'
                    when premium_type = 3 then 'Addition New'
                    when premium_type = 4 then 'Addition Renewal'
                    when premium_type = 5 then 'Organic Growth'
                    else ''
                END as Premium_Type,
                (select agent_name from agent where agent_id = premium_invoice.agent_id) agent_broker, agent_broker channel,
                case 
                    when getdate() <= end_date then (premium / (DATEDIFF(day, start_date, end_date) + 1)) * (DATEDIFF(day, getdate(), end_date) + 1)
                END as UPR,
                case 
                    when (select individual from corporate where corp_id = premium_invoice.corp_id) = 1 then 'CORPORATE'
                    when (select individual from corporate where corp_id = premium_invoice.corp_id) = 2 then 'RETAIL'
                    when (select individual from corporate where corp_id = premium_invoice.corp_id) = 3 then 'SME'
                END as corp_type
                
                from premium_invoice
            where dateformat (invoice_date,'yyyyMMdd') between $from and $to;");

        foreach ($rows as $row) {
            $ll_srate = $lc_override_rate =  $lc_commis_rate = $override = $total_commission = 0;
            $ls_inv_no = $row['debit_no'];
            $ld_strdt = $row['start_date'];
            $ld_sdt = $row['start_date'];
            $ld_edt = $row['end_date'];
            if ($row['cover_type'] != 'Credit notes') {

                $agent_id = PremiumInvoice::where('invoice_no', $ls_inv_no)->pluck('agent_id');
                $agent_id =  count($agent_id) == 0 ? 0 : $agent_id[0];

                $agent_data = Agent::select('agent_name', 'agent_type', 'agent_id')
                    ->where('agent_id', $agent_id)
                    ->get();
                if (count($agent_data) != 0) {
                    $ls_agent_na = $agent_data[0]->agent_name;
                    $il_agent_type = $agent_data[0]->agent_type;
                    $is_agent_id = $agent_data[0]->agent_id;
                }
                $premium_invoice_data = PremiumInvoice::select(
                    DB::raw("isnull(premium_type, 0) as premium_type"),
                    DB::raw("isnull(agent_rate, 0) as agent_rate"),
                    'anniv',
                    'corp_id',
                    'new_renewal',
                    'member_no',
                    'invoice_date',
                    'premium_net'
                )
                    ->where('invoice_no', $ls_inv_no)
                    ->get();

                $ll_anniv = count($premium_invoice_data) != 0 ? $premium_invoice_data[0]->anniv : 0;
                $ls_corpid = count($premium_invoice_data) != 0 ? $premium_invoice_data[0]->corp_id : 0;
                $ll_new_ren = count($premium_invoice_data) != 0 ? $premium_invoice_data[0]->new_renewal : 0;
                $ls_mem = count($premium_invoice_data) != 0 ? $premium_invoice_data[0]->member_no : 0;
                $ll_premtype = count($premium_invoice_data) != 0 ? $premium_invoice_data[0]->premium_type : 0;
                $lc_pre_commrate = count($premium_invoice_data) != 0 ? $premium_invoice_data[0]->agent_rate : 0;
                $ld_invdt = count($premium_invoice_data) != 0 ? $premium_invoice_data[0]->invoice_date : 0;
                $lc_premium_amt = count($premium_invoice_data) != 0 ? $premium_invoice_data[0]->premium_net : 0;

                if ($lc_pre_commrate > 0) {
                    $lc_commis_rate = $lc_pre_commrate;
                } else {
                    $ll_srate = CorpAdminFee::select(
                        DB::raw("isnull(sum(isnull(agent_commis_rate, 0)), 0) as agent_commis_rate"),
                    )
                        ->where('anniv', $ll_anniv)
                        ->where('corp_id', $ls_corpid)
                        ->get();

                    $ll_srate = count($ll_srate) == 0 ? 0 : $ll_srate[0]->agent_commis_rate;

                    if ($ll_srate > 0) {
                        $lc_commis_rate = CorpAdminFee::where('anniv', $ll_anniv)
                            ->where('corp_id', $ls_corpid)
                            ->pluck('agent_commis_rate');
                        $lc_commis_rate = count($lc_commis_rate) == 0 ? 0 : $lc_commis_rate[0];
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

                                        $ll_new_ren = count($ll_new_ren) == 0 ? 0 : $ll_new_ren[0];
                                    } else {
                                        $ll_new_ren = PremiumInvoice::whereIn('new_renewal', [1, 2])
                                            ->where('anniv', $ll_anniv)
                                            ->where('member_no', $ls_mem)
                                            ->pluck('new_renewal');

                                        $ll_new_ren = count($ll_new_ren) == 0 ? 0 : $ll_new_ren[0];
                                    }
                                }
                            }
                        }


                        //new business
                        if ($ll_new_ren == 1) {
                            if ($il_agent_type == 1) {
                                $lc_premium_amt = PremiumInvoice::where('invoice_no', $ls_inv_no)->pluck('premium_net');
                                $lc_premium_amt = count($lc_premium_amt) == 0 ? 0 : $lc_premium_amt[0];

                                $effective_from = CommissionRate::where('effective_from', '<=', $ld_invdt)
                                    ->where('agent_type', $il_agent_type)
                                    ->max(DB::raw("isnull(effective_from, 0)"));


                                $lc_max_prem = CommissionRate::where('effective_from', $effective_from)
                                    ->where('agent_type', $il_agent_type)
                                    ->max(DB::raw("isnull(premium_from, 0)"));

                                $effective_from_two = CommissionRate::where('effective_from', '<=', $ld_invdt)
                                    ->max(DB::raw("isnull(effective_from, 0)"));

                                $lc_prem_rate = CommissionRate::where('effective_from', $effective_from_two)
                                    ->where('agent_type', $il_agent_type)
                                    ->where('premium_from', $lc_max_prem)
                                    ->pluck('new_business');

                                $lc_prem_rate = count($lc_prem_rate) == 0 ? 0 : $lc_prem_rate[0];

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
                                    $ll_inv_mems = PremiumInvoiceDetails::where('invoice_no', $ls_inv_no)
                                        ->count('member_no');

                                    if ($ll_inv_mems >= $ll_lives) {
                                        $lc_commis_rate = $lc_ren_rate;
                                    } else {
                                        $lc_commis_rate = CommissionRate::where('effective_from', $effective_from)
                                            ->where(DB::raw("isnull(premium_from, 0)"), 0)
                                            ->where('agent_type', $il_agent_type)
                                            ->pluck('new_business');

                                        $lc_commis_rate = count($lc_commis_rate) == 0 ? 0 : $lc_commis_rate[0];
                                    }
                                } else {
                                    $lc_commis_rate = CommissionRate::where('effective_from', $effective_from)
                                        ->where(DB::raw("isnull(premium_from, 0)"), 0)
                                        ->where('agent_type', $il_agent_type)
                                        ->pluck('new_business');

                                    $lc_commis_rate = count($lc_commis_rate) == 0 ? 0 : $lc_commis_rate[0];
                                }
                            } else {
                                $effective_from = CommissionRate::where('effective_from', '<=', $ld_invdt)
                                    ->where('agent_type', $il_agent_type)
                                    ->max(DB::raw("isnull(effective_from, 0)"));


                                $lc_commis_rate = CommissionRate::where('effective_from', $effective_from)
                                    ->where('agent_type', $il_agent_type)
                                    ->pluck('new_business');

                                $lc_commis_rate = count($lc_commis_rate) == 0 ? 0 : $lc_commis_rate[0];
                            }
                            //renewal business
                        } else if ($ll_new_ren == 2) {

                            $effective_from = CommissionRate::where('effective_from', '<=', $ld_invdt)
                                ->where('agent_type', $il_agent_type)
                                ->max(DB::raw("isnull(effective_from, 0)"));

                            if (!is_null($effective_from)) {

                                $lc_commis_rate = CommissionRate::where('effective_from', $effective_from)
                                    ->where('agent_type', $il_agent_type)
                                    ->pluck('renewal');

                                $lc_commis_rate = count($lc_commis_rate) == 0 ? 0 : $lc_commis_rate[0];
                            } else {
                                $lc_commis_rate = 0;
                            }

                            //addition or organic growth
                        } else if ($ll_new_ren == 3 || $ll_new_ren == 7) {
                            if ($il_agent_type == 1) {
                                $lc_premium_amt = PremiumInvoice::where('invoice_no', $ls_inv_no)->pluck('premium_net');
                                $lc_premium_amt = count($lc_premium_amt) == 0 ? 0 : $lc_premium_amt[0];

                                $lc_max_prem = CommissionRate::where('agent_type', $il_agent_type)
                                    ->max(DB::raw("isnull(premium_from, 0)"));
                                $lc_prem_rate = CommissionRate::where('agent_type', $il_agent_type)
                                    ->where('premium_from', $lc_max_prem)
                                    ->pluck('new_business');

                                $lc_prem_rate = count($lc_prem_rate) == 0 ? 0 : $lc_prem_rate[0];

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
                                    $ll_inv_mems = PremiumInvoiceDetails::where('invoice_no', $ls_inv_no)
                                        ->count('member_no');

                                    if ($ll_inv_mems >= $ll_lives) {
                                        $lc_commis_rate = $lc_mid_prem;
                                    } else {
                                        $lc_commis_rate = CommissionRate::where(DB::raw("isnull(premium_from, 0)"), 0)
                                            ->where('agent_type', $il_agent_type)
                                            ->pluck('new_business');
                                        $lc_commis_rate = count($lc_commis_rate) == 0 ? 0 : $lc_commis_rate[0];
                                    }
                                } else {
                                    $lc_commis_rate = CommissionRate::where(DB::raw("isnull(premium_from, 0)"), 0)
                                        ->where('agent_type', $il_agent_type)
                                        ->pluck('new_business');
                                    $lc_commis_rate = count($lc_commis_rate) == 0 ? 0 : $lc_commis_rate[0];
                                }
                            } else {
                                $lc_commis_rate = CommissionRate::where('agent_type', $il_agent_type)->pluck('new_business');
                                $lc_commis_rate = count($lc_commis_rate) == 0 ? 0 : $lc_commis_rate[0];
                            }
                        }
                    }
                }
                if ($is_agent_id == "7" | $is_agent_id == "11" || $is_agent_id == "15" || $is_agent_id == "16" || $is_agent_id == "44" || $is_agent_id == "59") {
                    $lc_commis_rate = 0;
                    $lc_taxrate = 0;
                } else {
                    $lc_taxrate = 10;
                }
                //calculate the commission for this benefit n agent
                $lc_commis = $lc_premium_amt * ($lc_commis_rate / 100);

                if (is_null($ld_strdt)) {
                    $pi_data = PremiumInvoice::select('corp_id', 'member_no')
                        ->where('invoice_no', $ls_inv_no)
                        ->get();

                    $ls_corp = count($pi_data) != 0 ? $pi_data[0]->corp_id : '';
                    $ls_mem = count($pi_data) != 0 ? $pi_data[0]->member_no : '';

                    if ($ls_mem != '') {
                        $dates = MemberAnniversary::select('start_date', 'end_date')
                            ->where('anniv', $ll_anniv)
                            ->where('member_no', $ls_mem)
                            ->get();

                        $ld_sdt = count($dates) != 0 ? $dates[0]->start_date : '';
                        $ld_edt = count($dates) != 0 ? $dates[0]->end_date : '';
                    } else {
                        $dates = CorpAnniversary::select('start_date', 'end_date')
                            ->where('anniv', $ll_anniv)
                            ->where('corp_id', $ls_corp)
                            ->get();

                        $ld_sdt = count($dates) != 0 ? $dates[0]->start_date : '';
                        $ld_edt = count($dates) != 0 ? $dates[0]->end_date : '';
                    }
                }

                //unit manager
                $lc_overrideamt = PremiumInvoiceDetailsSammary::select(
                    DB::raw(" isnull(sum(isnull(override_amt, 0)),0) as override_amt")
                )
                    ->where('invoice_no', $ls_inv_no)
                    ->get();
                $lc_overrideamt = count($lc_overrideamt) == 0 ? 0 : $lc_overrideamt[0]->override_amt;

                if (is_null($lc_overrideamt)) {
                    $lc_overrideamt = 0;
                }

                //bdm
                if ($is_agent_id == 15) {
                    $lc_override_rate = 0;
                } else {
                    $ll_srate = CorpAdminFee::select(
                        DB::raw("sum(isnull(bdm_rate, 0))as bdm_rate")
                    )
                        ->where('anniv', $ll_anniv)
                        ->where('corp_id', $ls_corpid)
                        ->get();

                    $ll_srate = count($ll_srate) == 0 ? 0 : $ll_srate[0]->bdm_rate;


                    //special rates
                    if ($ll_srate > 0) {
                        $lc_override_rate = CorpAdminFee::where('anniv', $ll_anniv)
                            ->where('corp_id', $ls_corpid)
                            ->pluck('bdm_rate');

                        $lc_override_rate = count($lc_override_rate) == 0 ? 0 : $lc_override_rate[0];
                    } else {
                        if ($ll_new_ren == 7) {
                            if ($ll_premtype == 3 || $ll_premtype == 5) {
                                $ll_new_ren = 1;
                            } else if ($ll_premtype == 4) {
                                $ll_new_ren = 2;
                            }
                        }

                        //organic growth
                        $effective_from = CommissionRate::where('effective_from', '<=', $ld_invdt)
                            ->where('agent_type', 4)
                            ->max(DB::raw("isnull(effective_from, 0)"));
                        if ($ll_premtype == 5) {
                            $lc_override_rate = CommissionRate::where('effective_from', $effective_from)
                                ->where('agent_type', 4)
                                ->pluck('organic_growth');
                            $lc_override_rate = count($lc_override_rate) == 0 ? 0 : $lc_override_rate[0];
                            //new
                        } else if ($ll_new_ren == 1) {
                            $lc_override_rate = CommissionRate::where('effective_from', $effective_from)
                                ->where('agent_type', 4)
                                ->pluck('new_business');
                            $lc_override_rate = count($lc_override_rate) == 0 ? 0 : $lc_override_rate[0];
                        } else if ($ll_new_ren == 2) {
                            $lc_override_rate = CommissionRate::where('effective_from', $effective_from)
                                ->where('agent_type', 4)
                                ->pluck('renewal');
                            $lc_override_rate = count($lc_override_rate) == 0 ? 0 : $lc_override_rate[0];
                        }
                    }
                }

                if (is_null($lc_override_rate)) {
                    $lc_override_rate = 0;
                }
                $lc_bdm_commis = $lc_premium_amt * ($lc_override_rate / 100);

                //ass hos
                $lc_asst_rate = 0;

                if ($ld_invdt > "2020-12-31") {
                    if ($is_agent_id == '15') {
                        $lc_asst_rate = 0;
                    } else {
                        if ($ll_new_ren == 7) {
                            if ($ll_premtype == 3 || $ll_premtype == 5) {
                                $ll_new_ren = 1;
                            } else if ($ll_premtype == 4) {
                                $ll_new_ren = 2;
                            }
                        }

                        //organic growth
                        $effective_from = CommissionRate::where('effective_from', '<=', $ld_invdt)
                            ->where('agent_type', 6)
                            ->max(DB::raw("isnull(effective_from, 0)"));

                        if ($ll_premtype == 5) {
                            $lc_asst_rate = CommissionRate::where('effective_from', $effective_from)
                                ->where('agent_type', 6)
                                ->pluck('organic_growth');
                            $lc_asst_rate = count($lc_asst_rate) == 0 ? 0 : $lc_asst_rate[0];
                            //new
                        } else if ($ll_new_ren == 1) {
                            $lc_asst_rate = CommissionRate::where('effective_from', $effective_from)
                                ->where('agent_type', 6)
                                ->pluck('new_business');
                            $lc_asst_rate = count($lc_asst_rate) == 0 ? 0 : $lc_asst_rate[0];
                            //renewal buz
                        } else if ($ll_new_ren == 2) {
                            $lc_asst_rate = CommissionRate::where('effective_from', $effective_from)
                                ->where('agent_type', 6)
                                ->pluck('renewal');
                            $lc_asst_rate = count($lc_asst_rate) == 0 ? 0 : $lc_asst_rate[0];
                        }
                    }
                }

                $lc_assthos_commis = $lc_premium_amt * ($lc_asst_rate / 100);
                //hos
                $lc_hos_rate = 0;

                if ($ll_new_ren == 7) {
                    if ($ll_premtype == 3 || $ll_premtype == 5) {
                        $ll_new_ren = 1;
                    } else if ($ll_premtype == 4) {
                        $ll_new_ren = 2;
                    }
                }

                //organic growth
                $effective_from = CommissionRate::where('effective_from', '<=', $ld_invdt)
                    ->where('agent_type', 5)
                    ->max(DB::raw("isnull(effective_from, 0)"));
                if ($ll_premtype == 5) {
                    $lc_hos_rate = CommissionRate::where('effective_from', $effective_from)
                        ->where('agent_type', 5)
                        ->pluck('organic_growth');
                    $lc_hos_rate = count($lc_hos_rate) == 0 ? 0 : $lc_hos_rate[0];
                    //new
                } else if ($ll_new_ren == 1) {
                    $lc_hos_rate = CommissionRate::where('effective_from', $effective_from)
                        ->where('agent_type', 5)
                        ->pluck('new_business');
                    $lc_hos_rate = count($lc_hos_rate) == 0 ? 0 : $lc_hos_rate[0];
                    //renewal buz
                } else if ($ll_new_ren == 2) {
                    $lc_hos_rate = CommissionRate::where('effective_from', $effective_from)
                        ->where('agent_type', 5)
                        ->pluck('renewal');
                    $lc_hos_rate = count($lc_hos_rate) == 0 ? 0 : $lc_hos_rate[0];
                }

                $lc_hos_commis = $lc_premium_amt * ($lc_hos_rate / 100);

                $override = $lc_hos_commis + $lc_assthos_commis + $lc_bdm_commis + $lc_overrideamt;
                $total_commission = $lc_commis + $lc_hos_commis + $lc_assthos_commis + $lc_bdm_commis + $lc_overrideamt;
            }
            //totals
            $ttl_premium += $row['premium'];
            $ttl_commission += $lc_commis;
            $ttl_override += $override;
            $ttl_ttl_commission += $total_commission;

            $dt = [
                'plan' => $row['Plan'],
                'corporate' => $row['Corporate'],
                'corp_type' => $row['corp_type'],
                'policy_no' => $row['policy_no'],
                'debit_no' => $row['debit_no'],
                'premium' => number_format($row['premium'], 2),
                'cover_type' => $row['cover_type'],
                'premium_type' => $row['Premium_Type'],
                'agent_broker' => $row['agent_broker'],
                'channel' => $row['channel'],
                'upr' => number_format($row['UPR'], 2),
                'comm_rate' => $lc_commis_rate,
                'commission' => number_format($lc_commis, 2),
                'start_date' =>  $ld_sdt !== '' ? date("jS M Y", strtotime($ld_sdt)) : '',
                'end_date' =>  $ld_edt !== '' ? date("jS M Y", strtotime($ld_edt)) : '',     
                'override' => number_format($override, 2),
                'total_commission' => number_format($total_commission, 2)
            ];

            array_push($response, $dt);
        }
        $ttls = [
            'ttl_premium' =>$ttl_premium,
            'ttl_commission' => $ttl_commission,
            'ttl_override' => $ttl_override,
            'ttl_ttl_commission' => $ttl_ttl_commission
        ];
        $data = [
            'body' => json_encode($response, JSON_INVALID_UTF8_IGNORE),
            'totals' => $ttls
        ];

        return $data;
    }
}
