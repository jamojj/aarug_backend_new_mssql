<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Bdm;
use App\Models\CignaPremiumInvoiceDetailsSummary;
use App\Models\ClientRates;
use App\Models\Corporate\CorpAdminFee;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\Finance\CommissionRate;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetails;
use App\Models\PremiumInvoiceDetailsSammary;
use App\Models\PremiumInvoiceMembers;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\TransactRate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isNull;

class PremiumDebitingController extends Controller
{

    //fetch corporates to debit
    public function fetchCreditNote(Request $request)
    {
        $data = [];
        $invoice_no = "CRT-$request->credit_no";
        //CHECK IF CREDIT NOTE IS A REVERSAL OF A DEBIT
        $p_summary = PremiumInvoiceDetailsSammary::where('invoice_no', $invoice_no)->get();
        $p_details = PremiumInvoiceDetails::where('invoice_no', $invoice_no)->get();
        $p_members = PremiumInvoiceMembers::where('invoice_no', $invoice_no)->get();
        if (count($p_summary) == 0 && count($p_details) == 0 && count($p_members) == 0) {
            $credit_data = PremiumInvoice::select(
                DB::raw("case when premium_invoice.member_no is not null then member_info.surname ||' '|| member_info.first_name ||' '|| member_info.other_names else corporate.corporate end as client"),
                'invoice_no',
                'invoice_date',
                'invoice_total',
                'anniv',
                'premium_invoice.date_entered',
                'premium_type',
                'invoice_net',
                'premium_net',
                'cover_start_date',
                'stamp_duty',
                'phcf',
                'tl',
                'credit_note',
                'premium_invoice.idx',
                'notes',
                'end_date',
                'contigency',
                'smart_cost',
                'access_fee',
                'gift_items',
                'first_aid',
                'days',
              
            )
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                ->where('invoice_no', $invoice_no)
                ->get();
            $data = $credit_data;
        } else {
            $data = ['notice'=>'Credit is a reversal of a debit!'];
        }
        return $data;
    }
    //fetch corporates to debit
    public function fetchCorporatesToDebit()
    {
        $corporates = Corporate::select('corporate.corp_id', 'corporate.corporate')
            ->join('principal_applicant', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->whereIn('principal_applicant.individual', [1, 3])
            ->distinct()
            ->orderBy('corporate')
            ->get();
        return $corporates;
    }

    //fetch agent rates
    public function getAgentRates(Request $request)
    {
        // return $request;
        $lc_commis_rate =  $lc_override_rate =  $lc_asst_rate = $ll_srate = $lc_hos_rate = 0;
        $max_effective_from = '';
        $il_buz_type = $request->biz_type;
        $invoice_date = date("Y-m-d");
        $is_agent_id = $request->agent_id;
        $agent_type = Agent::select('agent.agent_type')
            ->where('agent.agent_id', $is_agent_id)->get();
        $agent_type = $agent_type[0]->agent_type;

        $il_anniv = $request->max_anniv;

        //get dbm from agent
        $bdm = Agent::select('bdm')->where('agent_id', $is_agent_id)->get();
        $bdm = count($bdm) !== 0  ? $bdm[0]->bdm : null;
        //get bdm name
        $bdm_name = Bdm::select('name')->where('code', $bdm)->get();
        $bdm_name = count($bdm_name) !== 0  ? $bdm_name[0]->name : null;

        //get agent type of bdm
        $agent_type_bdm = Agent::select('agent_type')->where('agent_name', $bdm_name)->get();
        $agent_type_bdm = count($agent_type_bdm) !== 0 ? $agent_type_bdm[0]->agent_type : null;

        //commission rate
        if ($is_agent_id == '11' || $is_agent_id == '15' || $is_agent_id == '16' || $is_agent_id == '44' || $is_agent_id == '59' || $is_agent_id == '7') {
            $lc_commis_rate = 0;
        } else {
            if ($request->choosen_option == 1) {
                $ll_srate = CorpAdminFee::select(DB::raw("isnull(sum(isnull(agent_commis_rate, 0)), 0) as ls_rate"))
                    ->where('anniv', $il_anniv)
                    ->where('corp_id', $request->corp_id)
                    ->get();

                $ll_srate = $ll_srate[0]->ls_rate;
            }
            if ($ll_srate > 0) {
                $lc_commis_rate = CorpAdminFee::where('anniv', $il_anniv)
                    ->where('corp_id', $request->corp_id)
                    ->pluck('agent_commis_rate');
                $lc_commis_rate = $lc_commis_rate[0];
            } else {
                if ($il_buz_type == 7) {
                    if ($request->invoice_type == 3 || $request->invoice_type == 5) {
                        $il_buz_type = 1;
                    } else if ($request->invoice_type == 4) {
                        $il_buz_type = 2;
                    }
                }

                //variables to be used for new and renewal
                $lc_premium_amt = $request->loaded_premium;
                $max_effective_from = CommissionRate::where('agent_type', $agent_type)
                    ->where('effective_from', "<=", $invoice_date)
                    ->max('effective_from');

                $lc_max_prem = CommissionRate::where('agent_type', $agent_type)
                    ->where('effective_from', $max_effective_from)
                    ->max(DB::raw("isnull(premium_from, 0)"));

                $lc_prem_rate = CommissionRate::where('effective_from', "<=", $invoice_date)
                    ->where('agent_type', $agent_type)
                    ->where(DB::raw("isnull(premium_from, 0)"), $lc_max_prem)
                    ->where('effective_from', $max_effective_from)
                    ->max(DB::raw("isnull(new_business, 0)"));

                // $lc_prem_rate = count($lc_prem_rate) !== 0 ? $lc_prem_rate[0] : 0;

                switch ($il_buz_type) {
                        //new buz
                    case '1':
                        if ($agent_type == 1) {
                            if ($lc_premium_amt >= $lc_max_prem) {
                                $lc_commis_rate = $lc_prem_rate;
                            } else {
                                $commission_rate_data = CommissionRate::select(
                                    'lives',
                                    'premium_from',
                                    'new_business',
                                    'renewal'
                                )
                                    ->where('effective_from', $max_effective_from)
                                    ->where(DB::raw("isnull(lives, 0)"), '>', 0)
                                    ->where('agent_type', $agent_type)
                                    ->get();
                                /***************** check if commission rate data is null, added this to avoid crashing */
                                if (count($commission_rate_data) !== 0) {
                                    if ($lc_premium_amt >= $commission_rate_data[0]->premium_from) {
                                        /**
                                         **** number of lives count to be discussed further *
                                         ***/
                                        $ll_dbt_lives = count($request->member_no);
                                        if ($ll_dbt_lives >= $commission_rate_data[0]->lives) {
                                            $lc_commis_rate = $commission_rate_data[0]->new_business;
                                        } else {
                                            $lc_commis_rate = $commission_rate_data[0]->renewal;
                                        }
                                    } else {
                                        $lc_commis_rate = CommissionRate::where('effective_from', $max_effective_from)
                                            ->where('agent_type', $agent_type)
                                            ->where(DB::raw("isnull(premium_from, 0)"), 0)
                                            ->pluck('renewal');
                                        $lc_commis_rate = count($lc_commis_rate) !== 0 ? $lc_commis_rate[0] : 0;
                                    }
                                } else {
                                    $lc_commis_rate = 0;
                                }
                            }
                        } else {
                            $lc_commis_rate = CommissionRate::where('effective_from', $max_effective_from)
                                ->where('agent_type', $agent_type)
                                ->pluck('new_business');
                            $lc_commis_rate = count($lc_commis_rate) !== 0 ? $lc_commis_rate[0] : 0;
                        }
                        break;

                        //renewal buz
                    case '2':
                        $lc_commis_rate = CommissionRate::where('effective_from', $max_effective_from)
                            ->where('agent_type', $agent_type)
                            ->pluck('renewal');
                        $lc_commis_rate = count($lc_commis_rate) !== 0 ? $lc_commis_rate[0] : 0;
                        break;
                }
            }
        }

        //global business type conditional statement
        if ($il_buz_type == 7) {
            if ($request->invoice_type == 3 || $request->invoice_type == 5) {
                $il_buz_type = 1;
            } else if ($request->invoice_type == 4) {
                $il_buz_type = 2;
            }
        }
        /*****************bdm rate*******************/

        //check whether it's aarophan or customer service direct(agent id 7 and 15) of if dbm has agent type 7,8, and 9
        if ($is_agent_id == '7' || $is_agent_id == '15' || $agent_type_bdm == 7 || $agent_type_bdm == 8 || $agent_type_bdm == 9) {
            $lc_override_rate = 0;
        } else {
            $ll_srate = CorpAdminFee::select(DB::raw("isnull(sum(isnull(bdm_rate, 0)),0) as bdm_rate"))
                ->where('anniv', $il_anniv)
                ->where('corp_id', $request->corp_id)
                ->pluck('bdm_rate');
            $ll_srate = $ll_srate[0];

            //special rates
            if ($ll_srate > 0) {
                $lc_override_rate = CorpAdminFee::where('anniv', $il_anniv)
                    ->where('corp_id', $request->corp_id)
                    ->pluck('bdm_rate');
                $lc_override_rate = $lc_override_rate[0];
            } else {
                //variables to be use globally for bdm
                $max_effective_from = CommissionRate::where('agent_type', 4)
                    ->where('effective_from', "<=", $invoice_date)
                    ->max('effective_from');

                /***
                 * Discuss difference btw invoice type and business class **
                 */
                //organic growth
                if ($request->invoice_type == 5) {
                    $lc_override_rate = CommissionRate::where('agent_type', 4)
                        ->where('effective_from', $max_effective_from)
                        ->pluck('organic_growth');
                } //new
                elseif ($il_buz_type == 1) {
                    $lc_override_rate = CommissionRate::where('agent_type', 4)
                        ->where('effective_from', $max_effective_from)
                        ->pluck('new_business');
                } //renewal
                elseif ($il_buz_type == 2) {
                    $lc_override_rate = CommissionRate::where('agent_type', 4)
                        ->where('effective_from', $max_effective_from)
                        ->pluck('renewal');
                }
                $lc_override_rate = $lc_override_rate[0];
            }
        }

        //assistant hos
        //check whether it's aarophan or customer service direct(agent id 7 and 15)
        if ($is_agent_id == '7' || $is_agent_id == '15' || $agent_type_bdm == 7 || $agent_type_bdm == 8 || $agent_type_bdm == 9) {
            $lc_asst_rate = 0;
        } else {
            //variables to be use globally for asst hos rate
            $max_effective_from = CommissionRate::where('agent_type', 6)
                ->where('effective_from', "<=", $invoice_date)
                ->max('effective_from');

            /***
             * Discuss difference btw invoice type and business class **
             */
            //organic growth
            if ($request->invoice_type == 5) {
                $lc_asst_rate = CommissionRate::where('agent_type', 6)
                    ->where('effective_from', $max_effective_from)
                    ->pluck('organic_growth');
            } //new
            elseif ($il_buz_type == 1) {
                $lc_asst_rate = CommissionRate::where('agent_type', 6)
                    ->where('effective_from', $max_effective_from)
                    ->pluck('new_business');
            } //renewal
            elseif ($il_buz_type == 2) {
                $lc_asst_rate = CommissionRate::where('agent_type', 6)
                    ->where('effective_from', $max_effective_from)
                    ->pluck('renewal');
            }
            $lc_asst_rate = $lc_asst_rate[0];
        }

        //hos rate
        //check whether it's AAR ORPHAN agent id 7
        if ($is_agent_id == '7' || $agent_type_bdm == 7 || $agent_type_bdm == 8 || $agent_type_bdm == 9) {
            $lc_hos_rate = 0;
        } else {
            if ($is_agent_id == '15') {
                if ($request->invoice_type == 5 || $request->invoice_type == 1) {
                    $lc_hos_rate = 1.5;
                } else {
                    $lc_hos_rate = 0.5;
                }
            } else {
                //organic growth
                if ($request->invoice_type == 5) {
                    $lc_hos_rate = CommissionRate::where('agent_type', 5)
                        ->where('effective_from', $max_effective_from)
                        ->pluck('organic_growth');
                } //new
                elseif ($il_buz_type == 1) {
                    $lc_hos_rate = CommissionRate::where('agent_type', 5)
                        ->where('effective_from', $max_effective_from)
                        ->pluck('new_business');
                } //renewal
                elseif ($il_buz_type == 2) {
                    $lc_hos_rate = CommissionRate::where('agent_type', 5)
                        ->where('effective_from', $max_effective_from)
                        ->pluck('renewal');
                }
                $lc_hos_rate = $lc_hos_rate[0];
            }
        }

        return [
            'commission_rate' => $lc_commis_rate,
            'bdm' => $lc_override_rate,
            'asst_hos' => $lc_asst_rate,
            'hos_rate' => $lc_hos_rate,
            'buz_type' => $il_buz_type,
            'max_effective_from' => $max_effective_from,
            'agent_type' => $agent_type,
            'invoice_type' => $request->invoice_type,
        ];
    }

    //fetch member population to premium debiting modal
    public function fetchCorpPopulation(Request $request)
    {
        //return $request;
        ini_set('max_execution_time', '0');

        $population = array();
        $today = date("Y-m-d");
        $max_anniv = CorpAnniversary::where('corp_id', $request->corp)->max('anniv');
        $fam_max_anniv = MemberAnniversary::where('member_no', $request->individual_member_no)->max('anniv');

        switch ($request->task) {
                //debit corporate
            case "1":
                $prem_invoice_entry = PremiumInvoice::select('premium_invoice.member_no')
                    ->where('premium_invoice.corp_id', $request->corp)
                    ->where('premium_invoice.anniv', $max_anniv)
                    ->count();
                switch ($request->business_class) {
                        //#1: new debit for corporate
                    case '1':
                        //for new buz check for only anniv 1
                        // if ($prem_invoice_entry == 0 && $max_anniv == 1) {

                        $mem_count = MemberAnniversary::select(DB::raw("count(distinct member_anniversary.member_no) count_mem"))
                            ->join('member_info', 'member_anniversary.member_no', '=', 'member_info.member_no')
                            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                            ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'principal_applicant.corp_id')
                            ->where('member_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->pluck('count_mem');

                        $members = MemberAnniversary::select('member_anniversary.member_no', 'member_anniversary.anniv')
                            ->distinct('member_anniversary.member_no')
                            ->join('member_info', 'member_anniversary.member_no', '=', 'member_info.member_no')
                            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                            ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'principal_applicant.corp_id')
                            ->orderBy('member_anniversary.member_no', 'ASC')
                            ->where('member_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                            ->get();

                        if ($mem_count[0] > 0) {
                            foreach ($members as $member) {
                                $corp_end_date =  CorpAnniversary::where('corp_id', $request->corp)->where('anniv', $max_anniv)->pluck('end_date');
                                $mem_end_date =  MemberAnniversary::where('member_no', $member->member_no)->where('anniv', $member->anniv)->pluck('end_date');
                                $corp_end_date = $corp_end_date[0];
                                $mem_end_date = $mem_end_date[0];

                                if ($mem_end_date >= $today) {

                                    // $premium_inv_members = PremiumInvoiceMembers::select(DB::raw("count(member_no) as count"))
                                    //     ->where('member_no', $member->member_no)
                                    //     ->where('anniv', $max_anniv)
                                    //     ->pluck('count');
                                    // $premium_inv_members = (int)$premium_inv_members[0];

                                    // if ($premium_inv_members == 0) {
                                    $member_population = PrincipalApplicant::select(
                                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                                        DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                                        'member_info.member_no',
                                        'member_info.dob',
                                        'member_info.family_title',
                                        'member_anniversary.start_date',
                                        'member_anniversary.end_date',
                                        'member_anniversary.health_plan',
                                        'member_anniversary.h_option',
                                        't_product_name.product_name',
                                        'family_relation.relation',
                                        'corp_anniversary.smart_sync as smart_sync',
                                        'corp_anniversary.smart_sync as access_fee'
                                    )
                                        ->distinct('member_info.member_no')
                                        ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                                        ->leftJoin("member_benefits", 'member_benefits.member_no', '=', 'member_info.member_no')
                                        ->leftJoin('member_anniversary', function ($join) use ($max_anniv) {
                                            $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                                ->where('member_anniversary.anniv', '=', $max_anniv);
                                        })
                                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                                        ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                                        ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                                        ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                                        ->orderBy('member_info.member_no', 'ASC')
                                        ->whereNotNull('member_anniversary.health_plan')
                                        ->where(DB::raw("isnull(member_benefits.fund, 0)"), 0)
                                        ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                                        ->where('corp_anniversary.anniv', $max_anniv)
                                        ->where('member_info.member_no', $member->member_no)
                                        ->where('principal_applicant.corp_id', $request->corp)
                                        ->get();

                                    array_push($population, $member_population[0]);
                                    // }

                                }
                            }
                        }

                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        // } else {
                        //     return ['note' => 'Notice ! You cannot debit corporate twice in the same anniversary.'];
                        // }
                        break;
                        //#2: renewal debit for corporate
                    case '2':
                        // if ($prem_invoice_entry == 0 && $max_anniv > 1) {

                        //$max_anniv = CorpAnniversary::where('corp_id', $request->corp)->max('anniv');
                        $mem_count = MemberAnniversary::select(DB::raw("count(distinct member_anniversary.member_no) count_mem"))
                            ->join('member_info', 'member_anniversary.member_no', '=', 'member_info.member_no')
                            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                            ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'principal_applicant.corp_id')
                            ->where('member_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->pluck('count_mem');

                        $members = MemberAnniversary::select('member_anniversary.member_no', 'member_anniversary.anniv')
                            ->distinct('member_anniversary.member_no')
                            ->join('member_info', 'member_anniversary.member_no', '=', 'member_info.member_no')
                            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                            ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'principal_applicant.corp_id')
                            ->orderBy('member_anniversary.member_no', 'ASC')
                            ->where('member_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                            ->get();

                        if ($mem_count[0] > 0) {
                            foreach ($members as $member) {
                                $corp_end_date =  CorpAnniversary::where('corp_id', $request->corp)->where('anniv', $max_anniv)->pluck('end_date');
                                $mem_end_date =  MemberAnniversary::where('member_no', $member->member_no)->where('anniv', $member->anniv)->pluck('end_date');
                                $corp_end_date = $corp_end_date[0];
                                $mem_end_date = $mem_end_date[0];

                                if ($mem_end_date >= $today) {

                                    // $premium_inv_members = PremiumInvoiceMembers::select(DB::raw("count(member_no) as count"))
                                    //     ->where('member_no', $member->member_no)
                                    //     ->where('anniv', $max_anniv)
                                    //     ->pluck('count');
                                    // $premium_inv_members = (int)$premium_inv_members[0];

                                    // if ($premium_inv_members == 0) {
                                    $member_population = PrincipalApplicant::select(
                                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                                        DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                                        'member_info.member_no',
                                        'member_info.dob',
                                        'member_info.family_title',
                                        'member_anniversary.start_date',
                                        'member_anniversary.end_date',
                                        'member_anniversary.health_plan',
                                        'member_anniversary.h_option',
                                        't_product_name.product_name',
                                        'family_relation.relation',
                                        'corp_anniversary.smart_sync as smart_sync',
                                        'corp_anniversary.smart_sync as access_fee'
                                    )
                                        ->distinct('member_info.member_no')
                                        ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                                        ->leftJoin("member_benefits", 'member_benefits.member_no', '=', 'member_info.member_no')
                                        ->leftJoin('member_anniversary', function ($join) use ($max_anniv) {
                                            $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                                ->where('member_anniversary.anniv', '=', $max_anniv);
                                        })
                                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                                        ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                                        ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                                        ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                                        ->orderBy('member_info.member_no', 'ASC')
                                        ->whereNotNull('member_anniversary.health_plan')
                                        ->where(DB::raw("isnull(member_benefits.fund, 0)"), 0)
                                        ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                                        ->where('corp_anniversary.anniv', $max_anniv)
                                        ->where('member_info.member_no', $member->member_no)
                                        ->where('principal_applicant.corp_id', $request->corp)
                                        ->get();

                                    array_push($population, $member_population[0]);
                                    // }
                                }
                            }
                        }
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        // } else {
                        //     return ['note' => 'Notice ! You cannot do renewal debit on a new corporate or on an already debited corporate.'];
                        // }
                        break;
                        //#3: addition debit for corporate
                    case '3':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_anniversary.start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            'corp_anniversary.smart_sync as smart_sync',
                            'corp_anniversary.smart_sync as access_fee'
                        )
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNull('premium_invoice.invoice_no')
                            ->whereNull('premium_invoice_details.invoice_no')
                            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                            ->where('corp_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->get();

                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#4: deletion debit for corporate
                    case '4':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_cancellation.date_can as start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as smart_sync"),
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as access_fee")
                        )
                            ->distinct('member_info.member_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->leftJoin('member_cancellation', 'member_cancellation.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNotNull('premium_invoice_details.invoice_no')
                            ->where('member_info.cancelled', 1)
                            ->where('corp_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#5: cover upgrade debit for corporate
                    case '5':
                        break;
                        //#6: cover downgrade for corporate
                    case '6':
                        break;
                        //#7: addition enmass
                    case '7':
                        //for new buz check for all anniv greater than 1
                        // if ($prem_invoice_entry != 0) {

                        //$max_anniv = CorpAnniversary::where('corp_id', $request->corp)->max('anniv');
                        $mem_count = MemberAnniversary::select(DB::raw("count(distinct member_anniversary.member_no) count_mem"))
                            ->join('member_info', 'member_anniversary.member_no', '=', 'member_info.member_no')
                            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                            ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'principal_applicant.corp_id')
                            ->where('member_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->pluck('count_mem');

                        $members = MemberAnniversary::select('member_anniversary.member_no', 'member_anniversary.anniv')
                            ->distinct('member_anniversary.member_no')
                            ->join('member_info', 'member_anniversary.member_no', '=', 'member_info.member_no')
                            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                            ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'principal_applicant.corp_id')
                            ->orderBy('member_anniversary.member_no', 'ASC')
                            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                            ->where('member_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->get();

                        if ($mem_count[0] > 0) {
                            foreach ($members as $member) {
                                $corp_end_date =  CorpAnniversary::where('corp_id', $request->corp)->where('anniv', $max_anniv)->pluck('end_date');
                                $mem_end_date =  MemberAnniversary::where('member_no', $member->member_no)->where('anniv', $member->anniv)->pluck('end_date');
                                $corp_end_date = $corp_end_date[0];
                                $mem_end_date = $mem_end_date[0];

                                if ($mem_end_date >= $today) {

                                    $premium_inv_members = PremiumInvoiceMembers::select(DB::raw("count(member_no) as count"))
                                        ->where('member_no', $member->member_no)
                                        ->where('anniv', $max_anniv)
                                        ->pluck('count');
                                    $premium_inv_members = (int)$premium_inv_members[0];

                                    if ($premium_inv_members == 0) {
                                        $member_population = PrincipalApplicant::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                                            'member_info.member_no',
                                            'member_info.dob',
                                            'member_info.family_title',
                                            'member_anniversary.start_date',
                                            'member_anniversary.end_date',
                                            'member_anniversary.health_plan',
                                            'member_anniversary.h_option',
                                            't_product_name.product_name',
                                            'family_relation.relation',
                                            'corp_anniversary.smart_sync as smart_sync',
                                            'corp_anniversary.smart_sync as access_fee'
                                        )
                                            ->distinct('member_info.member_no')
                                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                                            ->leftJoin("member_benefits", 'member_benefits.member_no', '=', 'member_info.member_no')
                                            ->leftJoin('member_anniversary', function ($join) use ($max_anniv) {
                                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                                    ->where('member_anniversary.anniv', '=', $max_anniv);
                                            })
                                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                                            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                                            ->orderBy('member_info.member_no', 'ASC')
                                            ->whereNotNull('member_anniversary.health_plan')
                                            ->where(DB::raw("isnull(member_benefits.fund, 0)"), 0)
                                            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                                            ->where('corp_anniversary.anniv', $max_anniv)
                                            ->where('member_info.member_no', $member->member_no)
                                            ->where('principal_applicant.corp_id', $request->corp)
                                            ->get();

                                        array_push($population, $member_population[0]);
                                    }
                                }
                            }
                        }

                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        // } else {
                        //     return ['note' => 'Notice ! You cannot debit addition enmass on a corporate that has not yet been debited'];
                        // }
                        break;
                        //#8: cover extension for corporate
                    case '8':
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                }
                break;
                //#5 manual invoice - corporate
            case "5":
                switch ($request->business_class) {
                        //#1: manual new debit for corporate
                    case '1':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_cancellation.date_can as start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as smart_sync"),
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as access_fee"),
                        )
                            ->distinct('member_info.member_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->leftJoin('member_cancellation', 'member_cancellation.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNotNull('premium_invoice_details.invoice_no')
                            ->where('member_info.cancelled', 1)
                            ->where('corp_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#2: manual renewal debit for corporate
                    case '2':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_cancellation.date_can as start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            DB::raw('(CASE WHEN member_cancellation.smart_sync = 1 THEN 0 END) AS smart_sync'),
                            'member_cancellation.smart_sync as access_fee',
                        )
                            ->distinct('member_info.member_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->leftJoin('member_cancellation', 'member_cancellation.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNotNull('premium_invoice_details.invoice_no')
                            ->where('corp_anniversary.anniv', $max_anniv)
                            ->where('member_info.cancelled', 1)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#3: manual additional enmass debit for corporate
                    case '3':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_anniversary.start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            'corp_anniversary.smart_sync as smart_sync',
                            'corp_anniversary.smart_sync as access_fee'
                        )
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNull('premium_invoice.invoice_no')
                            ->whereNull('premium_invoice_details.invoice_no')
                            ->where(function ($cancelled) {
                                $cancelled->where('member_info.cancelled', null)
                                    ->orWhere('member_info.cancelled', 0);
                            })
                            ->where('corp_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->get();

                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#4: manual deletion debit for corporate
                    case '4':
                        break;
                        //#5: manual cover upgrade debit for corporate
                    case '5':
                        break;
                        //#6: manual cover downgrade for corporate
                    case '6':
                        break;
                        //#7: manual addition for corporate - has no functonality
                    case '7':
                        break;
                        //#8: manual cover extension for corporate
                    case '8':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_cancellation.date_can as start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as smart_sync"),
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as access_fee"),
                        )
                            ->distinct('member_info.member_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->leftJoin('member_cancellation', 'member_cancellation.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNotNull('premium_invoice_details.invoice_no')
                            ->where('member_info.cancelled', 1)
                            ->where('corp_anniversary.anniv', $max_anniv)
                            ->where('principal_applicant.corp_id', $request->corp)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                }
                break;
                //#3 debit - family
            case "3":
                $prem_invoice_entry = PremiumInvoice::select('premium_invoice.member_no')
                    ->where('premium_invoice.member_no', $request->individual_member_no)
                    ->where('premium_invoice.anniv', $fam_max_anniv)
                    ->count();

                switch ($request->business_class) {
                        //#1: new debit for family
                    case '1':
                        //for new buz check if already debited
                        // if ($prem_invoice_entry == 0 && $fam_max_anniv == 1) {
                        $member_dates = MemberAnniversary::select('start_date', 'end_date')
                            ->where('anniv', $fam_max_anniv)
                            ->where('member_no', $request->individual_member_no)
                            ->get();

                        $ld_str_date = $member_dates[0]->start_date;
                        $ld_end_date = $member_dates[0]->end_date;

                        if ($ld_str_date < $request->effective_date_main) {
                            $ll_pro_unit = $ld_str_date->diffInDays($ld_end_date, false) + 1;
                            $ll_pro_class = 1;
                        }
                        if (!($request->effective_date_main > $ld_end_date) || !($request->effective_date_main < $ld_str_date)) {

                            $family_details = PrincipalApplicant::select('family_size', 'family_no')
                                ->where('member_no', $request->individual_member_no)
                                ->get();
                            $ls_famno = $family_details[0]['family_no'];
                            $ll_size = $family_details[0]['family_size'];

                            //pick family members
                            $members = MemberInfo::select('member_info.member_no', 'member_info.family_title', 'member_info.dob')
                                ->distinct('member_info.member_no')
                                ->leftJoin('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                                ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                                ->orderBy('member_info.member_no', 'ASC')
                                ->where(DB::raw("isnull(member_benefits.fund, 0)"), 0)
                                ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                                ->where('member_anniversary.anniv', $fam_max_anniv)
                                ->where('member_info.family_no', $ls_famno)
                                ->get();

                            $member_count = count($members);
                            if ($member_count > 0) {
                                foreach ($members as $member) {
                                    // $premium_inv_members = PremiumInvoiceMembers::select(DB::raw("count(member_no) as count"))
                                    //     ->where('member_no', $member->member_no)
                                    //     ->where('anniv', $fam_max_anniv)
                                    //     ->pluck('count');
                                    // $premium_inv_members = (int)$premium_inv_members[0];

                                    // if ($premium_inv_members == 0) {
                                    $member_population = PrincipalApplicant::select(
                                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                                        DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                                        'member_info.member_no',
                                        'member_info.dob',
                                        'member_info.family_title',
                                        'member_anniversary.start_date',
                                        'member_anniversary.end_date',
                                        'member_anniversary.health_plan',
                                        'member_anniversary.h_option',
                                        't_product_name.product_name',
                                        'family_relation.relation',
                                        DB::raw("isnull(member_anniversary.smart_sync, 0) as smart_sync"),
                                        DB::raw("isnull(member_anniversary.smart_sync, 0) as access_fee")
                                    )
                                        ->distinct('member_info.member_no')
                                        ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                                        ->leftJoin("member_anniversary", 'member_anniversary.member_no', '=', 'member_info.member_no')
                                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                                        ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                                        ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                                        ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                                        ->orderBy('member_info.member_no', 'ASC')
                                        ->whereNotNull('member_anniversary.health_plan')
                                        ->where('member_anniversary.anniv', $fam_max_anniv)
                                        ->where('principal_applicant.family_no', $ls_famno)
                                        ->where('member_info.member_no', $member->member_no)
                                        ->get();
                                    array_push($population, $member_population[0]);
                                    // }
                                }
                            }
                            return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        } else {
                            return ['message' => 'Notice ! cover start date is outside current client anniversary'];
                        }
                        // } else {
                        //     return ['note' => 'Notice ! You cannot debit family twice in the same anniversary.'];
                        // }
                        break;
                        //#2: renewal debit for family
                    case '2':
                        // if ($prem_invoice_entry == 0 && $fam_max_anniv > 1) {
                        $member_dates = MemberAnniversary::select('start_date', 'end_date')
                            ->where('anniv', $fam_max_anniv)
                            ->where('member_no', $request->individual_member_no)
                            ->get();

                        $ld_str_date = $member_dates[0]->start_date;
                        $ld_end_date = $member_dates[0]->end_date;

                        if ($ld_str_date < $request->effective_date_main) {
                            $ll_pro_unit = $ld_str_date->diffInDays($ld_end_date, false) + 1;
                            $ll_pro_class = 1;
                        }
                        if (!($request->effective_date_main > $ld_end_date) || !($request->effective_date_main < $ld_str_date)) {

                            $family_details = PrincipalApplicant::select('family_size', 'family_no')
                                ->where('member_no', $request->individual_member_no)
                                ->get();
                            $ls_famno = $family_details[0]['family_no'];
                            $ll_size = $family_details[0]['family_size'];

                            //pick family members
                            $members = MemberInfo::select('member_info.member_no', 'member_info.family_title', 'member_info.dob')
                                ->distinct('member_info.member_no')
                                ->leftJoin('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                                ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                                ->orderBy('member_info.member_no', 'ASC')
                                ->where(DB::raw("isnull(member_benefits.fund, 0)"), 0)
                                ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                                ->where('member_anniversary.anniv', $fam_max_anniv)
                                ->where('member_info.family_no', $ls_famno)
                                ->get();

                            $member_count = count($members);
                            if ($member_count > 0) {
                                foreach ($members as $member) {
                                    // $premium_inv_members = PremiumInvoiceMembers::select(DB::raw("count(member_no) as count"))
                                    //     ->where('member_no', $member->member_no)
                                    //     ->where('anniv', $fam_max_anniv)
                                    //     ->pluck('count');
                                    // $premium_inv_members = (int)$premium_inv_members[0];

                                    // if ($premium_inv_members == 0) {
                                    $member_population = PrincipalApplicant::select(
                                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                                        DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                                        'member_info.member_no',
                                        'member_info.dob',
                                        'member_info.family_title',
                                        'member_anniversary.start_date',
                                        'member_anniversary.end_date',
                                        'member_anniversary.health_plan',
                                        'member_anniversary.h_option',
                                        't_product_name.product_name',
                                        'family_relation.relation',
                                        DB::raw("isnull(member_anniversary.smart_sync, 0) as smart_sync"),
                                        DB::raw("isnull(member_anniversary.smart_sync, 0) as access_fee")
                                    )
                                        ->distinct('member_info.member_no')
                                        ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                                        ->leftJoin("member_anniversary", 'member_anniversary.member_no', '=', 'member_info.member_no')
                                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                                        ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                                        ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                                        ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                                        ->orderBy('member_info.member_no', 'ASC')
                                        ->whereNotNull('member_anniversary.health_plan')
                                        ->where('member_anniversary.anniv', $fam_max_anniv)
                                        ->where('principal_applicant.family_no', $ls_famno)
                                        ->where('member_info.member_no', $member->member_no)
                                        ->get();
                                    array_push($population, $member_population[0]);
                                    // }
                                }
                            }
                            return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        } else {
                            return ['message' => 'Notice ! cover start date is outside current client anniversary'];
                        }
                        // } else {
                        //     return ['note' => 'Notice ! You cannot do renewal debit on a new family or on an already debited family.'];
                        // }
                        break;
                        //#3: addition debit for family
                    case '3':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_anniversary.start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            'member_anniversary.smart_sync as smart_sync',
                            'member_anniversary.smart_sync as access_fee'
                        )
                            //->distinct('principal_applicant.family_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($fam_max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $fam_max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            //->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNull('premium_invoice.invoice_no')
                            ->whereNull('premium_invoice_details.invoice_no')
                            ->where(function ($cancelled) {
                                $cancelled->where('member_info.cancelled', null)
                                    ->orWhere('member_info.cancelled', 0);
                            })
                            ->where('member_anniversary.anniv', $fam_max_anniv)
                            ->where('principal_applicant.member_no', $request->family)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#4: deletion debit for family
                    case '4':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_cancellation.date_can as start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as smart_sync"),
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as access_fee")
                        )
                            ->distinct('member_info.member_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($fam_max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $fam_max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            //->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->leftJoin('member_cancellation', 'member_cancellation.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNotNull('premium_invoice_details.invoice_no')
                            ->where('member_info.cancelled', 1)
                            ->where('member_anniversary.anniv', $fam_max_anniv)
                            ->where('principal_applicant.member_no', $request->family)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#5: cover upgrade debit for family
                    case '5':
                        break;
                        //#6: cover downgrade for family
                    case '6':
                        break;
                        //#7: addition enmass for family
                    case '7':
                        //for new buz check for  anniv greater than 1
                        // if ($prem_invoice_entry != 0) {
                        $member_dates = MemberAnniversary::select('start_date', 'end_date')
                            ->where('anniv', $fam_max_anniv)
                            ->where('member_no', $request->individual_member_no)
                            ->get();
                        $ld_str_date = $member_dates[0]->start_date;
                        $ld_end_date = $member_dates[0]->end_date;

                        if ($ld_str_date < $request->effective_date) {
                            $ll_pro_unit = $ld_str_date->diffInDays($ld_end_date, false) + 1;
                            $ll_pro_class = 1;
                        }
                        if (!($request->effective_date_main > $ld_end_date) || !($request->effective_date_main < $ld_str_date)) {

                            $family_details = PrincipalApplicant::select('family_size', 'family_no')
                                ->where('member_no', $request->individual_member_no)
                                ->get();
                            $ls_famno = $family_details[0]['family_no'];
                            $ll_size = $family_details[0]['family_size'];

                            //pick family members
                            $members = MemberInfo::select('member_info.member_no', 'member_info.family_title', 'member_info.dob')
                                ->distinct('member_info.member_no')
                                ->leftJoin('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                                ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                                ->orderBy('member_info.member_no', 'ASC')
                                ->where(DB::raw("isnull(member_benefits.fund, 0)"), 0)
                                ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                                ->where('member_benefits.anniv', $fam_max_anniv)
                                ->where('member_info.family_no', $ls_famno)
                                ->get();
                            $member_count = count($members);
                            if ($member_count > 0) {
                                foreach ($members as $member) {
                                    $premium_inv_members = PremiumInvoiceMembers::select(DB::raw("count(member_no) as count"))
                                        ->where('member_no', $member->member_no)
                                        ->where('anniv', $fam_max_anniv)
                                        ->pluck('count');
                                    $premium_inv_members = (int)$premium_inv_members[0];

                                    if ($premium_inv_members == 0) {
                                        $member_population = PrincipalApplicant::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                                            'member_info.member_no',
                                            'member_info.dob',
                                            'member_info.family_title',
                                            'member_anniversary.start_date',
                                            'member_anniversary.end_date',
                                            'member_anniversary.health_plan',
                                            'member_anniversary.h_option',
                                            't_product_name.product_name',
                                            'family_relation.relation',
                                            DB::raw("isnull(member_anniversary.smart_sync, 0) as smart_sync"),
                                            DB::raw("isnull(member_anniversary.smart_sync, 0) as access_fee")
                                        )
                                            ->distinct('member_info.member_no')
                                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                                            ->leftJoin("member_anniversary", 'member_anniversary.member_no', '=', 'member_info.member_no')
                                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                                            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                                            ->orderBy('member_info.member_no', 'ASC')
                                            ->whereNotNull('member_anniversary.health_plan')
                                            ->where('member_anniversary.anniv', $fam_max_anniv)
                                            ->where('principal_applicant.family_no', $ls_famno)
                                            ->where('member_info.member_no', $member->member_no)
                                            ->get();
                                        array_push($population, $member_population[0]);
                                    }
                                }
                            }
                            return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        } else {
                            return ['message' => 'Notice ! cover start date is outside current client anniversary'];
                        }
                        // } else {
                        //     return ['note' => 'Notice ! You cannot debit additional enmass on a new family that has not yet been debited'];
                        // }
                        break;
                        //#8: cover extension for family
                    case '8':
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                }
                break;
                //#6 manual invoice - family
            case "6":
                switch ($request->business_class) {
                        //#1: manual new debit for family
                    case '1':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_anniversary.start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            'member_anniversary.smart_sync as smart_sync',
                            'member_anniversary.smart_sync as access_fee'
                        )
                            //->distinct('principal_applicant.family_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($fam_max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $fam_max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            //->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->where(function ($cancelled) {
                                $cancelled->where('member_info.cancelled', null)
                                    ->orWhere('member_info.cancelled', 0);
                            })
                            ->where('principal_applicant.member_no', $request->family)
                            ->where('member_anniversary.anniv', $fam_max_anniv)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#2: manual renewal debit for family
                    case '2':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_anniversary.start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            DB::raw('(CASE WHEN member_anniversary.smart_sync = 1 THEN 0 END) AS smart_sync'),
                            'member_anniversary.smart_sync as access_fee',
                        )
                            //->distinct('principal_applicant.family_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($fam_max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $fam_max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            //->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->where(function ($cancelled) {
                                $cancelled->where('member_info.cancelled', null)
                                    ->orWhere('member_info.cancelled', 0);
                            })
                            ->where('member_anniversary.anniv', $fam_max_anniv)
                            ->where('principal_applicant.member_no', $request->family)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#3: manual additional enmass debit for family
                    case '3':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_anniversary.start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            'member_anniversary.smart_sync as smart_sync',
                            'member_anniversary.smart_sync as access_fee'
                        )
                            //->distinct('principal_applicant.family_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($fam_max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $fam_max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            //->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNull('premium_invoice.invoice_no')
                            ->whereNull('premium_invoice_details.invoice_no')
                            ->where(function ($cancelled) {
                                $cancelled->where('member_info.cancelled', null)
                                    ->orWhere('member_info.cancelled', 0);
                            })
                            ->where('member_anniversary.anniv', $fam_max_anniv)
                            ->where('principal_applicant.member_no', $request->family)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#4: manual deletion debit for family
                    case '4':
                        $population = PrincipalApplicant::select(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            DB::raw("isnull(principal_applicant.family_size, 1) as family_size"),
                            'member_info.member_no',
                            'member_info.dob',
                            'member_info.family_title',
                            'member_cancellation.date_can as start_date',
                            'member_anniversary.end_date',
                            'member_anniversary.health_plan',
                            'member_anniversary.h_option',
                            't_product_name.product_name',
                            'family_relation.relation',
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as smart_sync"),
                            DB::raw("isnull(member_cancellation.smart_sync, 0) as access_fee")
                        )
                            ->distinct('member_info.member_no')
                            ->leftJoin("member_info", 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', function ($join) use ($fam_max_anniv) {
                                $join->on('member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.anniv', '=', $fam_max_anniv);
                            })
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->leftJoin('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                            //->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
                            ->leftJoin('premium_invoice', 'premium_invoice.member_no', '=', 'member_info.member_no')
                            ->leftJoin('premium_invoice_details', 'premium_invoice_details.member_no', '=', 'member_info.member_no')
                            ->leftJoin('member_cancellation', 'member_cancellation.member_no', '=', 'member_info.member_no')
                            ->orderBy('member_info.member_no', 'ASC')
                            ->whereNotNull('member_anniversary.health_plan')
                            ->whereNotNull('premium_invoice_details.invoice_no')
                            ->where('member_info.cancelled', 1)
                            ->where('principal_applicant.member_no', $request->family)
                            ->where('member_anniversary.anniv', $fam_max_anniv)
                            ->get();
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                        //#5: manual cover upgrade debit for family
                    case '5':
                        break;
                        //#6: manual cover downgrade for family
                    case '6':
                        break;
                        //#7: manual addition for family - has no functonality
                    case '7':
                        break;
                        //#8: manual cover extension for family
                    case '8':
                        return json_encode($population, JSON_INVALID_UTF8_IGNORE);
                        break;
                }
                break;
        }
    }

    //onclick debit, calculate premiums for each member
    public function calculateMemberPremiums(Request $request)
    {
        // return $request;
        // ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', '0');
        $response = [];
        $lc_prem = $prorata_prem = $ll_pro_unit = 0;
        $total_premium = $iiu = $ira = $invoice_ttl = $total_smart_card_fee = $total_smart_access_fee = 0;
        $member_premiums = [];
        $smart_card_array = [];
        $smart_access_array = [];
        $loaded_prem = 0;

        $smart_card_fee = TransactRate::where('code', 1)->pluck('rate');
        $smart_card_fee = count($smart_card_fee) === 0 ? 0 : $smart_card_fee[0];
        $smart_access_fee = TransactRate::where('code', 2)->pluck('rate');
        $smart_access_fee = count($smart_access_fee) === 0 ? 0 : $smart_access_fee[0];

        //task select dropdown option
        switch ($request->choosen_option) {
                //debit corporate
            case 1:
                switch ($request->business_class) {
                        //new debit and addition enamss debit for corporate
                    case 1:
                    case 7:
                        //try {
                        for ($i = 0; $i < count($request->member_no); $i++) {
                            $smart_card_fee = TransactRate::where('code', 1)->pluck('rate');
                            $smart_card_fee = count($smart_card_fee) === 0 ? 0 : $smart_card_fee[0];
                            $smart_access_fee = TransactRate::where('code', 2)->pluck('rate');
                            $smart_access_fee = count($smart_access_fee) === 0 ? 0 : $smart_access_fee[0];

                            if ($request->debit[$i] == '1') {
                                $ll_cnt_rate = ClientRates::select(DB::raw("count(isnull(corp_id, 0)) as rates"))
                                    ->where('product_name', $request->product_name[$i])
                                    ->where('health_plan', $request->health_plan[$i])
                                    ->where('corp_id', $request->corp_id)
                                    ->get();
                                $ll_cnt_rate = count($ll_cnt_rate) === 0 ? 0 : $ll_cnt_rate[0]->rates;

                                if ($ll_cnt_rate > 0) {
                                    $lc_age = ClientRates::where('product_name', $request->product_name[$i])
                                        ->where('health_plan', $request->health_plan[$i])
                                        ->where('corp_id', $request->corp_id)
                                        ->sum(DB::raw("isnull(min_age, 0)"));
                                    if ($lc_age > 0) {
                                        $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                            ->where('min_age', '<=', $request->age[$i])
                                            ->where(DB::raw("isnull(max_age, 100)"), '>=', $request->age[$i])
                                            ->where('health_plan', $request->health_plan[$i])
                                            ->where('product_name', $request->product_name[$i])
                                            ->where('corp_id', $request->corp_id)
                                            ->get();
                                        $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                    } else {
                                        $lc_fam_tt = ClientRates::select(DB::raw("sum(isnull(family_title, 0)) as fam_title"))
                                            ->where('health_plan', $request->health_plan[$i])
                                            ->where('product_name', $request->product_name[$i])
                                            ->where('corp_id', $request->corp_id)
                                            ->get();

                                        $lc_fam_tt = count($lc_fam_tt) === 0 ? 0 : $lc_fam_tt[0]->fam_title;

                                        if ($lc_fam_tt > 0) {
                                            $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                ->where('family_title', $request->relation[$i])
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('corp_id', $request->corp_id)
                                                ->get();
                                            $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                        } else {

                                            $lc_prems = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                ->where('family_size', $request->family_size[$i])
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('corp_id', $request->corp_id)
                                                ->get();

                                            if (count($lc_prems) > 0) {
                                                $lc_prems = $lc_prems[0]->premium;
                                                if ($lc_prems > 0) {
                                                    if ($lc_prems != 3) {
                                                        $lc_prems = 0;
                                                    }
                                                }
                                                $lc_prem = $lc_prems;
                                            } else {
                                                //if (sizeof($lc_prem) == 0) {
                                                $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('corp_id', $request->corp_id)
                                                    ->get();

                                                $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                            }
                                        }
                                    }
                                }
                                //check prorata days
                                //prorata_prem represents discounted_prem, loaded_prem, prorated_prem
                                if ($lc_prem > 0) {
                                    $prorata_prem = $lc_prem;

                                    //member anniversary date difference
                                    $start_date_f = \Carbon\Carbon::parse($request->effective_date[$i]);
                                    $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);

                                    $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;

                                    /*if ($ll_pro_unit < 365) {
                                        if ($ll_pro_unit < 90) {
                                            $ll_pro_unit = 90;
                                        }
                                        switch ($request->transaction) {
                                            //if debit note raised
                                            case '1':
                                                $prorata_prem = ($lc_prem / 365) * $ll_pro_unit;
                                                break;
                                            //if credit note raised
                                            case '2':
                                                $prorata_prem = 0 - (($lc_prem / 365) * $ll_pro_unit);
                                                break;
                                        }
                                    } else {
                                        if ($request->transaction == 2) {
                                            $prorata_prem = 0 - $lc_prem;
                                        }
                                    }*/

                                    //without proration
                                    switch ($request->transaction) {
                                            //if debit note raised
                                        case '1':
                                            $prorata_prem = $lc_prem;
                                            break;
                                            //if credit note raised
                                        case '2':
                                            $prorata_prem = 0 - ($lc_prem);
                                            break;
                                    }
                                } else {
                                    $prorata_prem = 0;
                                }


                                if ($request->smart_card[$i] == 1) {
                                    array_push($smart_card_array, $smart_card_fee);
                                    $total_smart_card_fee += $smart_card_fee;
                                } else {
                                    $smart_card_fee = 0;
                                    array_push($smart_card_array, 0);
                                    $total_smart_card_fee += 0;
                                }
                                if ($request->access_fee[$i] == 1) {
                                    array_push($smart_access_array, $smart_access_fee);
                                    $total_smart_access_fee += $smart_access_fee;
                                } else {
                                    $smart_access_fee = 0;
                                    array_push($smart_access_array, 0);
                                    $total_smart_access_fee += 0;
                                }


                                $dt = [
                                    'member_premium' => $lc_prem,
                                    'prorata_premium' => $prorata_prem,
                                    'member_no' => $request->member_no[$i],
                                    'health_plan' => $request->health_plan[$i],
                                    'principal_name' => $request->principal_name[$i],
                                    'member_name' => $request->member_name[$i],
                                    'product_name' => $request->product_name[$i],
                                    'family_size' => $request->family_size[$i],
                                    'relation' => $request->relation[$i],
                                    'age' => $request->age[$i],
                                    'effective_date' => $request->effective_date[$i],
                                    'end_date' => $request->end_date[$i],
                                    'prorata_period' => $ll_pro_unit,
                                    'smart_cost' => $smart_card_fee,
                                    'access_cost' => $smart_access_fee

                                ];

                                $loaded_prem += $prorata_prem;
                                $total_premium += $lc_prem;
                                array_push($member_premiums, $dt);
                            }
                        }

                        if (!is_null($loaded_prem)) {
                            $ira = 0.015 * $loaded_prem;
                            $iiu = 0.005 * $loaded_prem;
                            $retained = 0.75 * $loaded_prem;
                            $ceded = 0.25 * $loaded_prem;
                            $invoice_ttl = $ira + $iiu + $loaded_prem + $total_smart_card_fee + $total_smart_access_fee + $request->membership_contigency + $request->membership_gift_items + $request->membership_first_aid + $request->membership_stamp_duty + $request->membership_others;
                        }
                        $data = [
                            'ira' => $ira,
                            'iiu' => $iiu,
                            'total_premium' => $total_premium,
                            'invoice_total' => $invoice_ttl,
                            'loaded_prem' => $loaded_prem,
                            'member_premiums' => $member_premiums,
                            'smart_card_array' => $smart_card_array,
                            'access_fee_array' => $smart_access_array,
                            'total_smart_card_fee' => $total_smart_card_fee,
                            'total_smart_access_fee' => $total_smart_access_fee,
                            'retained' => $retained,
                            'ceded' => $ceded,
                            'details' => $request->member_no

                        ];
                        array_push($response, $data);
                        //                        } catch (Exception $e) {
                        //                            array_push($response, $e->getMessage());
                        //                        }
                        return $response;
                        break;

                        //#2: renewal debit for corporate not done well new cards should be zero
                    case 2:
                        try {
                            for ($i = 0; $i < count($request->member_no); $i++) {
                                $smart_card_fee = TransactRate::where('code', 1)->pluck('rate');
                                $smart_card_fee = count($smart_card_fee) === 0 ? 0 : $smart_card_fee[0];
                                $smart_access_fee = TransactRate::where('code', 2)->pluck('rate');
                                $smart_access_fee = count($smart_access_fee) === 0 ? 0 : $smart_access_fee[0];

                                if ($request->debit[$i] == '1') {
                                    $ll_cnt_rate = ClientRates::where('product_name', $request->product_name[$i])
                                        ->where('health_plan', $request->health_plan[$i])
                                        ->where('corp_id', $request->corp_id)
                                        ->count('corp_id');
                                    if ($ll_cnt_rate > 0) {
                                        $lc_age = ClientRates::where('product_name', $request->product_name[$i])
                                            ->where('health_plan', $request->health_plan[$i])
                                            ->where('corp_id', $request->corp_id)
                                            ->sum(DB::raw("isnull(min_age, 0)"));
                                        if ($lc_age > 0) {
                                            $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                ->where('min_age', '<=', $request->age[$i])
                                                ->where(DB::raw("isnull(max_age, 100)"), '>=', $request->age[$i])
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('corp_id', $request->corp_id)
                                                ->get();
                                            $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                        } else {
                                            $lc_fam_tt = ClientRates::select(DB::raw("sum(isnull(family_title, 0)) as fam_title"))
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('corp_id', $request->corp_id)
                                                ->get();
                                            $lc_fam_tt = count($lc_fam_tt) === 0 ? 0 : $lc_fam_tt[0]->fam_title;

                                            if ($lc_fam_tt > 0) {
                                                $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_title', $request->relation[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('corp_id', $request->corp_id)
                                                    ->get();
                                                $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                            } else {

                                                $lc_prems = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_size', $request->family_size[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('corp_id', $request->corp_id)
                                                    ->get();


                                                if (count($lc_prems) > 0) {
                                                    $lc_prems = $lc_prems[0]->premium;
                                                    if ($lc_prems > 0) {
                                                        if ($lc_prems != 3) {
                                                            $lc_prems = 0;
                                                        }
                                                    }
                                                    $lc_prem = $lc_prems;
                                                } else {
                                                    $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                        ->where('health_plan', $request->health_plan[$i])
                                                        ->where('product_name', $request->product_name[$i])
                                                        ->where('corp_id', $request->corp_id)
                                                        ->get();

                                                    $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                                }
                                            }
                                        }
                                    }
                                    //check prorata days
                                    //prorata_prem represents discounted_prem, loaded_prem, prorated_prem
                                    if ($lc_prem > 0) {
                                        $prorata_prem = $lc_prem;

                                        //member anniversary date difference
                                        $start_date_f = \Carbon\Carbon::parse($request->effective_date[$i]);
                                        $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);

                                        $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;


                                        /*if ($ll_pro_unit < 365) {
                                        if ($ll_pro_unit < 90) {
                                            $ll_pro_unit = 90;
                                        }
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = ($lc_prem / 365) * $ll_pro_unit;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - (($lc_prem / 365) * $ll_pro_unit);
                                                break;
                                        }
                                    } else {
                                        if ($request->transaction == 2) {
                                            $prorata_prem = 0 - $lc_prem;
                                        }
                                    }*/

                                        //with no proration
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = $lc_prem;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - ($lc_prem);
                                                break;
                                        }
                                    } else {
                                        $prorata_prem = 0;
                                    }


                                    if ($request->smart_card[$i] == 1) {
                                        array_push($smart_card_array, $smart_card_fee);
                                        $total_smart_card_fee += $smart_card_fee;
                                    } else {
                                        $smart_card_fee = 0;
                                        array_push($smart_card_array, 0);
                                        $total_smart_card_fee += 0;
                                    }
                                    if ($request->access_fee[$i] == 1) {
                                        array_push($smart_access_array, $smart_access_fee);
                                        $total_smart_access_fee += $smart_access_fee;
                                    } else {
                                        $smart_access_fee = 0;
                                        array_push($smart_access_array, 0);
                                        $total_smart_access_fee += 0;
                                    }

                                    $dt = [
                                        'member_premium' => $lc_prem,
                                        'prorata_premium' => $prorata_prem,
                                        'member_no' => $request->member_no[$i],
                                        'health_plan' => $request->health_plan[$i],
                                        'principal_name' => $request->principal_name[$i],
                                        'member_name' => $request->member_name[$i],
                                        'product_name' => $request->product_name[$i],
                                        'family_size' => $request->family_size[$i],
                                        'relation' => $request->relation[$i],
                                        'age' => $request->age[$i],
                                        'effective_date' => $request->effective_date[$i],
                                        'end_date' => $request->end_date[$i],
                                        'prorata_period' => $ll_pro_unit,
                                        'smart_cost' => $smart_card_fee,
                                        'access_cost' => $smart_access_fee

                                    ];

                                    $loaded_prem += $prorata_prem;
                                    $total_premium += $lc_prem;
                                    array_push($member_premiums, $dt);
                                }
                            }

                            if (!is_null($loaded_prem)) {
                                $ira = 0.015 * $loaded_prem;
                                $iiu = 0.005 * $loaded_prem;
                                $retained = 0.75 * $loaded_prem;
                                $ceded = 0.25 * $loaded_prem;
                                $invoice_ttl = $ira + $iiu + $loaded_prem + $total_smart_card_fee + $total_smart_access_fee + $request->membership_contigency + $request->membership_gift_items + $request->membership_first_aid + $request->membership_stamp_duty + $request->membership_others;
                            }
                            $data = [
                                'ira' => $ira,
                                'iiu' => $iiu,
                                'total_premium' => $total_premium,
                                'invoice_total' => $invoice_ttl,
                                'loaded_prem' => $loaded_prem,
                                'member_premiums' => $member_premiums,
                                'smart_card_array' => $smart_card_array,
                                'access_fee_array' => $smart_access_array,
                                'total_smart_card_fee' => $total_smart_card_fee,
                                'total_smart_access_fee' => $total_smart_access_fee,
                                'retained' => $retained,
                                'ceded' => $ceded,
                                'details' => $request->member_no,
                            ];
                            array_push($response, $data);
                        } catch (Exception $e) {
                            array_push($response, $e->getMessage());
                        }
                        return $response;
                        break;
                        //#3: addition enmass debit for corporate
                    case 3:
                        try {
                            for ($i = 0; $i < count($request->member_no); $i++) {
                                $smart_card_fee = TransactRate::where('code', 1)->pluck('rate');
                                $smart_card_fee = count($smart_card_fee) === 0 ? 0 : $smart_card_fee[0];
                                $smart_access_fee = TransactRate::where('code', 2)->pluck('rate');
                                $smart_access_fee = count($smart_access_fee) === 0 ? 0 : $smart_access_fee[0];

                                if ($request->debit[$i] == '1') {
                                    $ll_cnt_rate = ClientRates::where('product_name', $request->product_name[$i])
                                        ->where('health_plan', $request->health_plan[$i])
                                        ->where('corp_id', $request->corp_id)
                                        ->count('corp_id');

                                    if ($ll_cnt_rate > 0) {
                                        $lc_age = ClientRates::where('product_name', $request->product_name[$i])
                                            ->where('health_plan', $request->health_plan[$i])
                                            ->where('corp_id', $request->corp_id)
                                            ->sum(DB::raw("isnull(min_age, 0)"));
                                        if ($lc_age > 0) {
                                            $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                ->where('min_age', '<=', $request->age[$i])
                                                ->where(DB::raw("isnull(max_age, 100)"), '>=', $request->age[$i])
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('corp_id', $request->corp_id)
                                                ->get();
                                            $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                        } else {
                                            $lc_fam_tt = ClientRates::select(DB::raw("sum(isnull(family_title, 0)) as fam_title"))
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('corp_id', $request->corp_id)
                                                ->get();
                                            $lc_fam_tt = count($lc_fam_tt) === 0 ? 0 : $lc_fam_tt[0]->fam_title;

                                            if ($lc_fam_tt > 0) {
                                                $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_title', $request->relation[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('corp_id', $request->corp_id)
                                                    ->get();
                                                $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                            } else {
                                                $lc_prems = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_size', $request->family_size[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('corp_id', $request->corp_id)
                                                    ->get();

                                                if (count($lc_prems) > 0) {
                                                    $lc_prems = $lc_prems[0]->premium;
                                                    if ($lc_prems > 0) {
                                                        if ($lc_prems != 3) {
                                                            $lc_prems = 0;
                                                        }
                                                    }
                                                    $lc_prem = $lc_prems;
                                                } else {
                                                    $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                        ->where('health_plan', $request->health_plan[$i])
                                                        ->where('product_name', $request->product_name[$i])
                                                        ->where('corp_id', $request->corp_id)
                                                        ->get();

                                                    $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                                }
                                            }
                                        }
                                    }
                                    //check prorata days
                                    //prorata_prem represents discounted_prem, loaded_prem, prorated_prem
                                    if ($lc_prem > 0) {
                                        $prorata_prem = (float)$lc_prem;

                                        //member anniversary date difference
                                        $start_date_f = \Carbon\Carbon::parse($request->effective_date[$i]);
                                        $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);

                                        $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;

                                        /*if ($ll_pro_unit < 365) {
                                        if ($ll_pro_unit < 90) {
                                            $ll_pro_unit = 90;
                                        }
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = ($lc_prem / 365) * $ll_pro_unit;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - (($lc_prem / 365) * $ll_pro_unit);
                                                break;
                                        }
                                    } else {
                                        if ($request->transaction == 2) {
                                            $prorata_prem = 0 - $lc_prem;
                                        }
                                    }*/

                                        //with no proration
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = $lc_prem;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - ($lc_prem);
                                                break;
                                        }
                                    } else {
                                        $prorata_prem = 0;
                                    }

                                    if ($request->smart_card[$i] == 1) {
                                        array_push($smart_card_array, $smart_card_fee);
                                        $total_smart_card_fee += $smart_card_fee;
                                    } else {
                                        $smart_card_fee = 0;
                                        array_push($smart_card_array, 0);
                                        $total_smart_card_fee += 0;
                                    }
                                    if ($request->access_fee[$i] == 1) {
                                        array_push($smart_access_array, $smart_access_fee);
                                        $total_smart_access_fee += $smart_access_fee;
                                    } else {
                                        $smart_access_fee = 0;
                                        array_push($smart_access_array, 0);
                                        $total_smart_access_fee += 0;
                                    }


                                    $dt = [
                                        'member_premium' => $lc_prem,
                                        'prorata_premium' => $prorata_prem,
                                        'member_no' => $request->member_no[$i],
                                        'health_plan' => $request->health_plan[$i],
                                        'principal_name' => $request->principal_name[$i],
                                        'member_name' => $request->member_name[$i],
                                        'product_name' => $request->product_name[$i],
                                        'family_size' => $request->family_size[$i],
                                        'relation' => $request->relation[$i],
                                        'age' => $request->age[$i],
                                        'effective_date' => $request->effective_date[$i],
                                        'end_date' => $request->end_date[$i],
                                        'prorata_period' => $ll_pro_unit,
                                        'smart_cost' => $smart_card_fee,
                                        'access_cost' => $smart_access_fee,
                                    ];

                                    $loaded_prem += $prorata_prem;
                                    $total_premium += $lc_prem;
                                    array_push($member_premiums, $dt);
                                }
                            }

                            if (!is_null($loaded_prem)) {
                                $ira = 0.015 * $loaded_prem;
                                $iiu = 0.005 * $loaded_prem;
                                $retained = 0.75 * $loaded_prem;
                                $ceded = 0.25 * $loaded_prem;
                                $invoice_ttl = $ira + $iiu + $loaded_prem + $total_smart_card_fee + $total_smart_access_fee + $request->membership_contigency + $request->membership_gift_items + $request->membership_first_aid + $request->membership_stamp_duty + $request->membership_others;
                            }
                            $data = [
                                'ira' => $ira,
                                'iiu' => $iiu,
                                'total_premium' => $total_premium,
                                'invoice_total' => $invoice_ttl,
                                'loaded_prem' => $loaded_prem,
                                'member_premiums' => $member_premiums,
                                'smart_card_array' => $smart_card_array,
                                'access_fee_array' => $smart_access_array,
                                'total_smart_card_fee' => $total_smart_card_fee,
                                'total_smart_access_fee' => $total_smart_access_fee,
                                'retained' => $retained,
                                'ceded' => $ceded,
                                'details' => $request->member_no,

                            ];
                            array_push($response, $data);
                        } catch (Exception $e) {
                            array_push($response, $e->getMessage());
                        }
                        return $response;
                        break;
                        //#4: deletion debit for corporate

                    case 4:
                        try {
                            for ($i = 0; $i < count($request->member_no); $i++) {
                                $smart_card_fee = TransactRate::where('code', 1)->pluck('rate');
                                $smart_card_fee = count($smart_card_fee) === 0 ? 0 : $smart_card_fee[0];
                                $smart_access_fee = TransactRate::where('code', 2)->pluck('rate');
                                $smart_access_fee = count($smart_access_fee) === 0 ? 0 : $smart_access_fee[0];

                                if ($request->debit[$i] == '1') {
                                    $ll_cnt_rate = ClientRates::where('product_name', $request->product_name[$i])
                                        ->where('health_plan', $request->health_plan[$i])
                                        ->where('corp_id', $request->corp_id)
                                        ->count('corp_id');

                                    if ($ll_cnt_rate > 0) {
                                        $lc_age = ClientRates::where('product_name', $request->product_name[$i])
                                            ->where('health_plan', $request->health_plan[$i])
                                            ->where('corp_id', $request->corp_id)
                                            ->sum(DB::raw("isnull(min_age, 0)"));
                                        if ($lc_age > 0) {
                                            $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                ->where('min_age', '<=', $request->age[$i])
                                                ->where(DB::raw("isnull(max_age, 100)"), '>=', $request->age[$i])
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('corp_id', $request->corp_id)
                                                ->get();
                                            $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                        } else {
                                            $lc_fam_tt = ClientRates::select(DB::raw("sum(isnull(family_title, 0)) as fam_title"))
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('corp_id', $request->corp_id)
                                                ->get();
                                            $lc_fam_tt = count($lc_fam_tt) === 0 ? 0 : $lc_fam_tt[0]->fam_title;
                                            if ($lc_fam_tt > 0) {
                                                $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_title', $request->relation[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('corp_id', $request->corp_id)
                                                    ->get();
                                                $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                            } else {
                                                $lc_prems = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_size', $request->family_size[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('corp_id', $request->corp_id)
                                                    ->get();

                                                if (count($lc_prems) > 0) {
                                                    $lc_prems = $lc_prems[0]->premium;
                                                    if ($lc_prems > 0) {
                                                        if ($lc_prems != 3) {
                                                            $lc_prems = 0;
                                                        }
                                                    }
                                                    $lc_prem = $lc_prems;
                                                } else {
                                                    $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                        ->where('health_plan', $request->health_plan[$i])
                                                        ->where('product_name', $request->product_name[$i])
                                                        ->where('corp_id', $request->corp_id)
                                                        ->get();

                                                    $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                                }
                                            }
                                        }
                                    }
                                    //check prorata days
                                    //prorata_prem represents discounted_prem, loaded_prem, prorated_prem
                                    if ($lc_prem > 0) {
                                        $prorata_prem = (float)$lc_prem;

                                        //member anniversary date difference
                                        $start_date_f = \Carbon\Carbon::parse($request->effective_date[$i]);
                                        $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);

                                        $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;

                                        /*if ($ll_pro_unit < 365) {
                                        if ($ll_pro_unit < 90) {
                                            $ll_pro_unit = 90;
                                        }
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = ($lc_prem / 365) * $ll_pro_unit;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - (($lc_prem / 365) * $ll_pro_unit);
                                                break;
                                        }
                                    } else {
                                        if ($request->transaction == 2) {
                                            $prorata_prem = 0 - $lc_prem;
                                        }
                                    }*/

                                        //with no proration
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = $lc_prem;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - ($lc_prem);
                                                break;
                                        }
                                    } else {
                                        $prorata_prem = 0;
                                    }


                                    if ($request->smart_card[$i] == 1) {
                                        array_push($smart_card_array, $smart_card_fee);
                                        $total_smart_card_fee += $smart_card_fee;
                                    } else {
                                        $smart_card_fee = 0;
                                        array_push($smart_card_array, 0);
                                        $total_smart_card_fee += 0;
                                    }
                                    if ($request->access_fee[$i] == 1) {
                                        array_push($smart_access_array, $smart_access_fee);
                                        $total_smart_access_fee += $smart_access_fee;
                                    } else {
                                        $smart_access_fee = 0;
                                        array_push($smart_access_array, 0);
                                        $total_smart_access_fee += 0;
                                    }


                                    $dt = [
                                        'member_premium' => $lc_prem,
                                        'prorata_premium' => $prorata_prem,
                                        'member_no' => $request->member_no[$i],
                                        'health_plan' => $request->health_plan[$i],
                                        'principal_name' => $request->principal_name[$i],
                                        'member_name' => $request->member_name[$i],
                                        'product_name' => $request->product_name[$i],
                                        'family_size' => $request->family_size[$i],
                                        'relation' => $request->relation[$i],
                                        'age' => $request->age[$i],
                                        'effective_date' => $request->effective_date[$i],
                                        'end_date' => $request->end_date[$i],
                                        'prorata_period' => $ll_pro_unit,
                                        'smart_cost' => $smart_card_fee,
                                        'access_cost' => $smart_access_fee,
                                    ];

                                    $loaded_prem += $prorata_prem;
                                    $total_premium += $lc_prem;
                                    array_push($member_premiums, $dt);
                                }
                            }

                            if (!is_null($loaded_prem)) {
                                $ira = 0.015 * $loaded_prem;
                                $iiu = 0.005 * $loaded_prem;
                                $retained = 0.75 * $loaded_prem;
                                $ceded = 0.25 * $loaded_prem;
                                $invoice_ttl = $ira + $iiu + $loaded_prem + $total_smart_card_fee + $total_smart_access_fee + $request->membership_contigency + $request->membership_gift_items + $request->membership_first_aid + $request->membership_stamp_duty + $request->membership_others;
                            }
                            $data = [
                                'ira' => $ira,
                                'iiu' => $iiu,
                                'total_premium' => $total_premium,
                                'invoice_total' => $invoice_ttl,
                                'loaded_prem' => $loaded_prem,
                                'member_premiums' => $member_premiums,
                                'smart_card_array' => $smart_card_array,
                                'access_fee_array' => $smart_access_array,
                                'total_smart_card_fee' => $total_smart_card_fee,
                                'total_smart_access_fee' => $total_smart_access_fee,
                                'retained' => $retained,
                                'ceded' => $ceded,
                                'details' => $request->member_no,

                            ];
                            array_push($response, $data);
                        } catch (Exception $e) {
                            array_push($response, $e->getMessage());
                        }
                        return $response;
                        break;
                        //#5: cover upgrade debit for corporate
                    case 5:
                        return $response;
                        break;
                        //#6: cover downgrade debit for corporate
                    case 6:
                        return $response;
                        break;
                        //#8: cover extension debit for corporate
                    case 8:
                        return $response;
                        break;
                }
                break;
                //query corporate debit
            case 2:
                break;
                //debit family
            case 3:
                switch ($request->business_class) {
                        //new debit and addition enmass for family
                    case 1:
                    case 7:
                        try {
                            // $family_no = explode('-', $request->principal_member_no);
                            // $family_no = $family_no[0];
                            $family_no = $request->family_no;
                            for ($i = 0; $i < count($request->member_no); $i++) {
                                $smart_card_fee = TransactRate::where('code', 1)->pluck('rate');
                                $smart_card_fee = count($smart_card_fee) === 0 ? 0 : $smart_card_fee[0];
                                $smart_access_fee = TransactRate::where('code', 2)->pluck('rate');
                                $smart_access_fee = count($smart_access_fee) === 0 ? 0 : $smart_access_fee[0];

                                if ($request->debit[$i] == '1') {
                                    $ll_cnt_rate = ClientRates::where('product_name', $request->product_name[$i])
                                        ->where('health_plan', $request->health_plan[$i])
                                        ->where('family_no', $family_no)
                                        ->count('corp_id');
                                    if ($ll_cnt_rate > 0) {
                                        $lc_age = ClientRates::where('product_name', $request->product_name[$i])
                                            ->where('health_plan', $request->health_plan[$i])
                                            ->where('family_no', $family_no)
                                            ->sum(DB::raw("isnull(min_age, 0)"));
                                        if ($lc_age > 0) {
                                            $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                ->where('min_age', '<=', $request->age[$i])
                                                ->where(DB::raw("isnull(max_age, 100)"), '>=', $request->age[$i])
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('family_no', $family_no)
                                                ->get();
                                            $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                        } else {
                                            $lc_fam_tt = ClientRates::select(DB::raw("sum(isnull(family_title, 0)) as fam_title"))
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('family_no', $family_no)
                                                ->get();
                                            $lc_fam_tt = count($lc_fam_tt) === 0 ? 0 : $lc_fam_tt[0]->fam_title;

                                            if ($lc_fam_tt > 0) {
                                                $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_title', $request->relation[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('family_no', $family_no)
                                                    ->get();
                                                $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                            } else {

                                                $lc_prems = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_size', $request->family_size[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('family_no', $family_no)
                                                    ->get();

                                                if (count($lc_prems) > 0) {
                                                    $lc_prems = $lc_prems[0]->premium;
                                                    if ($lc_prems > 0) {
                                                        if ($lc_prems !== 3) {
                                                            $lc_prems = 0;
                                                        }
                                                    }
                                                    $lc_prem = $lc_prems;
                                                } else {
                                                    $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                        ->where('health_plan', $request->health_plan[$i])
                                                        ->where('product_name', $request->product_name[$i])
                                                        ->where('family_no', $family_no)
                                                        ->get();
                                                    $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                                }
                                            }
                                        }
                                    }
                                    //check prorata days
                                    //prorata_prem represents discounted_prem, loaded_prem, prorated_prem
                                    $prorata_prem = $lc_prem;
                                    //each member anniversary date difference
                                    $start_date_f = \Carbon\Carbon::parse($request->effective_date[$i]);
                                    $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);
                                    if ($lc_prem > 0) {
                                        $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;

                                        /*if ($ll_pro_unit < 365) {
                                            if ($ll_pro_unit < 90) {
                                                $ll_pro_unit = 90;
                                            }
                                            switch ($request->transaction) {
                                                case '1':
                                                    $prorata_prem = ($lc_prem / 365) * $ll_pro_unit;
                                                    break;
                                                case '2':
                                                    $prorata_prem = 0 - (($lc_prem / 365) * $ll_pro_unit);
                                                    break;
                                            }
                                        } else {
                                            if ($request->transaction == 2) {
                                                $prorata_prem = 0 - $lc_prem;
                                            }
                                        }*/

                                        //with no proration
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = $lc_prem;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - ($lc_prem);
                                                break;
                                        }
                                    } else {
                                        $prorata_prem = 0;
                                    }


                                    if ($request->smart_card[$i] == 1) {
                                        array_push($smart_card_array, $smart_card_fee);
                                        $total_smart_card_fee += $smart_card_fee;
                                    } else {
                                        $smart_card_fee = 0;
                                        array_push($smart_card_array, 0);
                                        $total_smart_card_fee += 0;
                                    }
                                    if ($request->access_fee[$i] == 1) {
                                        array_push($smart_access_array, $smart_access_fee);
                                        $total_smart_access_fee += $smart_access_fee;
                                    } else {
                                        $smart_access_fee = 0;
                                        array_push($smart_access_array, 0);
                                        $total_smart_access_fee += 0;
                                    }


                                    $dt = [
                                        'member_premium' => $lc_prem,
                                        'prorata_premium' => $prorata_prem,
                                        'member_no' => $request->member_no[$i],
                                        'health_plan' => $request->health_plan[$i],
                                        'principal_name' => $request->principal_name[$i],
                                        'member_name' => $request->member_name[$i],
                                        'product_name' => $request->product_name[$i],
                                        'family_size' => $request->family_size[$i],
                                        'relation' => $request->relation[$i],
                                        'age' => $request->age[$i],
                                        'effective_date' => $request->effective_date[$i],
                                        'end_date' => $request->end_date[$i],
                                        'prorata_period' => $ll_pro_unit,
                                        'smart_cost' => $smart_card_fee,
                                        'access_cost' => $smart_access_fee

                                    ];

                                    $loaded_prem += $prorata_prem;
                                    $total_premium += $lc_prem;
                                    array_push($member_premiums, $dt);
                                }

                                if (!is_null($loaded_prem)) {
                                    $ira = 0.015 * $loaded_prem;
                                    $iiu = 0.005 * $loaded_prem;
                                    $retained = 0.75 * $loaded_prem;
                                    $ceded = 0.25 * $loaded_prem;
                                    $invoice_ttl = $ira + $iiu + $loaded_prem + $total_smart_card_fee + $total_smart_access_fee + $request->membership_contigency + $request->membership_gift_items + $request->membership_first_aid + $request->membership_stamp_duty + $request->membership_others;
                                }
                            }
                            $data = [
                                'ira' => $ira,
                                'iiu' => $iiu,
                                'total_premium' => $total_premium,
                                'invoice_total' => $invoice_ttl,
                                'loaded_prem' => $loaded_prem,
                                'member_premiums' => $member_premiums,
                                'smart_card_array' => $smart_card_array,
                                'access_fee_array' => $smart_access_array,
                                'total_smart_card_fee' => $total_smart_card_fee,
                                'total_smart_access_fee' => $total_smart_access_fee,
                                'retained' => $retained,
                                'ceded' => $ceded,
                                'details' => $request->member_no

                            ];
                            array_push($response, $data);
                        } catch (Exception $e) {
                            array_push($response, $e->getMessage());
                        }
                        return $response;
                        //#2: renewal debit for family not done well new cards should be zero
                    case 2:
                        try {
                            // $family_no = explode('-', $request->principal_member_no);
                            // $family_no = $family_no[0];
                            $family_no = $request->family_no;
                            for ($i = 0; $i < count($request->member_no); $i++) {
                                $smart_card_fee = TransactRate::where('code', 1)->pluck('rate');
                                $smart_card_fee = count($smart_card_fee) === 0 ? 0 : $smart_card_fee[0];
                                $smart_access_fee = TransactRate::where('code', 2)->pluck('rate');
                                $smart_access_fee = count($smart_access_fee) === 0 ? 0 : $smart_access_fee[0];

                                if ($request->debit[$i] == '1') {
                                    $ll_cnt_rate = ClientRates::where('product_name', $request->product_name[$i])
                                        ->where('health_plan', $request->health_plan[$i])
                                        ->where('family_no', $family_no)
                                        ->count('corp_id');
                                    if ($ll_cnt_rate > 0) {
                                        $lc_age = ClientRates::where('product_name', $request->product_name[$i])
                                            ->where('health_plan', $request->health_plan[$i])
                                            ->where('family_no', $family_no)
                                            ->sum(DB::raw("isnull(min_age, 0)"));
                                        if ($lc_age > 0) {
                                            $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                ->where('min_age', '<=', $request->age[$i])
                                                ->where(DB::raw("isnull(max_age, 100)"), '>=', $request->age[$i])
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('family_no', $family_no)
                                                ->get();
                                            $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                        } else {
                                            $lc_fam_tt = ClientRates::select(DB::raw("sum(isnull(family_title, 0)) as fam_title"))
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('family_no', $family_no)
                                                ->get();
                                            $lc_fam_tt = count($lc_fam_tt) === 0 ? 0 : $lc_fam_tt[0]->fam_title;
                                            if ($lc_fam_tt > 0) {
                                                $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_title', $request->relation[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('family_no', $family_no)
                                                    ->get();
                                                $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                            } else {

                                                $lc_prems = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_size', $request->family_size[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('family_no', $family_no)
                                                    ->get();

                                                if (count($lc_prems) > 0) {
                                                    $lc_prems = $lc_prems[0]->premium;
                                                    if ($lc_prems > 0) {
                                                        if ($lc_prems !== 3) {
                                                            $lc_prems = 0;
                                                        }
                                                    }
                                                    $lc_prem = $lc_prems;
                                                } else {
                                                    $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                        ->where('health_plan', $request->health_plan[$i])
                                                        ->where('product_name', $request->product_name[$i])
                                                        ->where('family_no', $family_no)
                                                        ->get();
                                                    $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                                }
                                            }
                                        }
                                    }
                                    //check prorata days
                                    //prorata_prem represents discounted_prem, loaded_prem, prorated_prem
                                    $prorata_prem = $lc_prem;
                                    //each member anniversary date difference
                                    $start_date_f = \Carbon\Carbon::parse($request->effective_date[$i]);
                                    $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);
                                    if ($lc_prem > 0) {
                                        $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;

                                        /*if ($ll_pro_unit < 365) {
                                        if ($ll_pro_unit < 90) {
                                            $ll_pro_unit = 90;
                                        }
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = ($lc_prem / 365) * $ll_pro_unit;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - (($lc_prem / 365) * $ll_pro_unit);
                                                break;
                                        }
                                    } else {
                                        if ($request->transaction == 2) {
                                            $prorata_prem = 0 - $lc_prem;
                                        }
                                    }*/

                                        //with no proration
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = $lc_prem;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - ($lc_prem);
                                                break;
                                        }
                                    } else {
                                        $prorata_prem = 0;
                                    }


                                    if ($request->smart_card[$i] == 1) {
                                        array_push($smart_card_array, $smart_card_fee);
                                        $total_smart_card_fee += $smart_card_fee;
                                    } else {
                                        $smart_card_fee = 0;
                                        array_push($smart_card_array, 0);
                                        $total_smart_card_fee += 0;
                                    }
                                    if ($request->access_fee[$i] == 1) {
                                        array_push($smart_access_array, $smart_access_fee);
                                        $total_smart_access_fee += $smart_access_fee;
                                    } else {
                                        $smart_access_fee = 0;
                                        array_push($smart_access_array, 0);
                                        $total_smart_access_fee += 0;
                                    }


                                    $dt = [
                                        'member_premium' => $lc_prem,
                                        'prorata_premium' => $prorata_prem,
                                        'member_no' => $request->member_no[$i],
                                        'health_plan' => $request->health_plan[$i],
                                        'principal_name' => $request->principal_name[$i],
                                        'member_name' => $request->member_name[$i],
                                        'product_name' => $request->product_name[$i],
                                        'family_size' => $request->family_size[$i],
                                        'relation' => $request->relation[$i],
                                        'age' => $request->age[$i],
                                        'effective_date' => $request->effective_date[$i],
                                        'end_date' => $request->end_date[$i],
                                        'prorata_period' => $ll_pro_unit,
                                        'smart_cost' => $smart_card_fee,
                                        'access_cost' => $smart_access_fee

                                    ];

                                    $loaded_prem += $prorata_prem;
                                    $total_premium += $lc_prem;
                                    array_push($member_premiums, $dt);
                                }
                            }

                            if (!is_null($loaded_prem)) {
                                $ira = 0.015 * $loaded_prem;
                                $iiu = 0.005 * $loaded_prem;
                                $retained = 0.75 * $loaded_prem;
                                $ceded = 0.25 * $loaded_prem;
                                $invoice_ttl = $ira + $iiu + $loaded_prem + $total_smart_card_fee + $total_smart_access_fee + $request->membership_contigency + $request->membership_gift_items + $request->membership_first_aid + $request->membership_stamp_duty + $request->membership_others;
                            }
                            $data = [
                                'ira' => $ira,
                                'iiu' => $iiu,
                                'total_premium' => $total_premium,
                                'invoice_total' => $invoice_ttl,
                                'loaded_prem' => $loaded_prem,
                                'member_premiums' => $member_premiums,
                                'smart_card_array' => $smart_card_array,
                                'access_fee_array' => $smart_access_array,
                                'total_smart_card_fee' => $total_smart_card_fee,
                                'total_smart_access_fee' => $total_smart_access_fee,
                                'retained' => $retained,
                                'ceded' => $ceded,
                                'details' => $request->member_no

                            ];
                            array_push($response, $data);
                        } catch (Exception $e) {
                            array_push($response, $e->getMessage());
                        }
                        return $response;
                        //#3: addition enmass debit for corporate
                    case 3:
                        try {
                            // $family_no = explode('-', $request->principal_member_no);
                            // $family_no = $family_no[0];
                            $family_no = $request->family_no;
                            for ($i = 0; $i < count($request->member_no); $i++) {
                                $smart_card_fee = TransactRate::where('code', 1)->pluck('rate');
                                $smart_card_fee = count($smart_card_fee) === 0 ? 0 : $smart_card_fee[0];
                                $smart_access_fee = TransactRate::where('code', 2)->pluck('rate');
                                $smart_access_fee = count($smart_access_fee) === 0 ? 0 : $smart_access_fee[0];

                                if ($request->debit[$i] == '1') {
                                    $ll_cnt_rate = ClientRates::where('product_name', $request->product_name[$i])
                                        ->where('health_plan', $request->health_plan[$i])
                                        ->where('family_no', $family_no)
                                        ->count('corp_id');
                                    if ($ll_cnt_rate > 0) {
                                        $lc_age = ClientRates::where('product_name', $request->product_name[$i])
                                            ->where('health_plan', $request->health_plan[$i])
                                            ->where('family_no', $family_no)
                                            ->sum(DB::raw("isnull(min_age, 0)"));
                                        if ($lc_age > 0) {
                                            $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                ->where('min_age', '<=', $request->age[$i])
                                                ->where(DB::raw("isnull(max_age, 100)"), '>=', $request->age[$i])
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('family_no', $family_no)
                                                ->get();
                                            $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                        } else {
                                            $lc_fam_tt = ClientRates::select(DB::raw("sum(isnull(family_title, 0)) as fam_title"))
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('family_no', $family_no)
                                                ->get();
                                            $lc_fam_tt = count($lc_fam_tt) === 0 ? 0 : $lc_fam_tt[0]->fam_title;
                                            if ($lc_fam_tt > 0) {
                                                $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_title', $request->relation[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('family_no', $family_no)
                                                    ->get();
                                                $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                            } else {

                                                $lc_prems = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_size', $request->family_size[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('family_no', $family_no)
                                                    ->get();

                                                if (count($lc_prems) > 0) {
                                                    $lc_prems = $lc_prems[0]->premium;
                                                    if ($lc_prems > 0) {
                                                        if ($lc_prems !== 3) {
                                                            $lc_prems = 0;
                                                        }
                                                    }
                                                    $lc_prem = $lc_prems;
                                                } else {
                                                    $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                        ->where('health_plan', $request->health_plan[$i])
                                                        ->where('product_name', $request->product_name[$i])
                                                        ->where('family_no', $family_no)
                                                        ->get();
                                                    $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                                }
                                            }
                                        }
                                    }
                                    //check prorata days
                                    //prorata_prem represents discounted_prem, loaded_prem, prorated_prem
                                    $prorata_prem = $lc_prem;
                                    //each member anniversary date difference
                                    $start_date_f = \Carbon\Carbon::parse($request->effective_date[$i]);
                                    $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);
                                    if ($lc_prem > 0) {
                                        $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;

                                        /*if ($ll_pro_unit < 365) {
                                        if ($ll_pro_unit < 90) {
                                            $ll_pro_unit = 90;
                                        }
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = ($lc_prem / 365) * $ll_pro_unit;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - (($lc_prem / 365) * $ll_pro_unit);
                                                break;
                                        }
                                    } else {
                                        if ($request->transaction == 2) {
                                            $prorata_prem = 0 - $lc_prem;
                                        }
                                    }*/
                                        //with no proration
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = $lc_prem;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - ($lc_prem);
                                                break;
                                        }
                                    } else {
                                        $prorata_prem = 0;
                                    }

                                    if ($request->smart_card[$i] == 1) {
                                        array_push($smart_card_array, $smart_card_fee);
                                        $total_smart_card_fee += $smart_card_fee;
                                    } else {
                                        $smart_card_fee = 0;
                                        array_push($smart_card_array, 0);
                                        $total_smart_card_fee += 0;
                                    }
                                    if ($request->access_fee[$i] == 1) {
                                        array_push($smart_access_array, $smart_access_fee);
                                        $total_smart_access_fee += $smart_access_fee;
                                    } else {
                                        $smart_access_fee = 0;
                                        array_push($smart_access_array, 0);
                                        $total_smart_access_fee += 0;
                                    }


                                    $dt = [
                                        'member_premium' => $lc_prem,
                                        'prorata_premium' => $prorata_prem,
                                        'member_no' => $request->member_no[$i],
                                        'health_plan' => $request->health_plan[$i],
                                        'principal_name' => $request->principal_name[$i],
                                        'member_name' => $request->member_name[$i],
                                        'product_name' => $request->product_name[$i],
                                        'family_size' => $request->family_size[$i],
                                        'relation' => $request->relation[$i],
                                        'age' => $request->age[$i],
                                        'effective_date' => $request->effective_date[$i],
                                        'end_date' => $request->end_date[$i],
                                        'prorata_period' => $ll_pro_unit,
                                        'smart_cost' => $smart_card_fee,
                                        'access_cost' => $smart_access_fee

                                    ];

                                    $loaded_prem += $prorata_prem;
                                    $total_premium += $lc_prem;
                                    array_push($member_premiums, $dt);
                                }
                            }

                            if (!is_null($loaded_prem)) {
                                $ira = 0.015 * $loaded_prem;
                                $iiu = 0.005 * $loaded_prem;
                                $retained = 0.75 * $loaded_prem;
                                $ceded = 0.25 * $loaded_prem;
                                $invoice_ttl = $ira + $iiu + $loaded_prem + $total_smart_card_fee + $total_smart_access_fee + $request->membership_contigency + $request->membership_gift_items + $request->membership_first_aid + $request->membership_stamp_duty + $request->membership_others;
                            }
                            $data = [
                                'ira' => $ira,
                                'iiu' => $iiu,
                                'total_premium' => $total_premium,
                                'invoice_total' => $invoice_ttl,
                                'loaded_prem' => $loaded_prem,
                                'member_premiums' => $member_premiums,
                                'smart_card_array' => $smart_card_array,
                                'access_fee_array' => $smart_access_array,
                                'total_smart_card_fee' => $total_smart_card_fee,
                                'total_smart_access_fee' => $total_smart_access_fee,
                                'retained' => $retained,
                                'ceded' => $ceded,
                                'details' => $request->member_no

                            ];
                            array_push($response, $data);
                        } catch (Exception $e) {
                            array_push($response, $e->getMessage());
                        }
                        return $response;
                        break;
                        //#4: deletion debit for family
                    case 4:
                        try {
                            // $family_no = explode('-', $request->principal_member_no);
                            // $family_no = $family_no[0];
                            $family_no = $request->family_no;
                            for ($i = 0; $i < count($request->member_no); $i++) {
                                $smart_card_fee = TransactRate::where('code', 1)->pluck('rate');
                                $smart_card_fee = count($smart_card_fee) === 0 ? 0 : $smart_card_fee[0];
                                $smart_access_fee = TransactRate::where('code', 2)->pluck('rate');
                                $smart_access_fee = count($smart_access_fee) === 0 ? 0 : $smart_access_fee[0];

                                if ($request->debit[$i] == '1') {
                                    $ll_cnt_rate = ClientRates::where('product_name', $request->product_name[$i])
                                        ->where('health_plan', $request->health_plan[$i])
                                        ->where('family_no', $family_no)
                                        ->count('corp_id');
                                    if ($ll_cnt_rate > 0) {
                                        $lc_age = ClientRates::where('product_name', $request->product_name[$i])
                                            ->where('health_plan', $request->health_plan[$i])
                                            ->where('family_no', $family_no)
                                            ->sum(DB::raw("isnull(min_age, 0)"));
                                        if ($lc_age > 0) {
                                            $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                ->where('min_age', '<=', $request->age[$i])
                                                ->where(DB::raw("isnull(max_age, 100)"), '>=', $request->age[$i])
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('family_no', $family_no)
                                                ->get();
                                            $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                        } else {
                                            $lc_fam_tt = ClientRates::select(DB::raw("sum(isnull(family_title, 0)) as fam_title"))
                                                ->where('health_plan', $request->health_plan[$i])
                                                ->where('product_name', $request->product_name[$i])
                                                ->where('family_no', $family_no)
                                                ->get();
                                            $lc_fam_tt = count($lc_fam_tt) === 0 ? 0 : $lc_fam_tt[0]->fam_title;
                                            if ($lc_fam_tt > 0) {
                                                $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_title', $request->relation[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('family_no', $family_no)
                                                    ->get();
                                                $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                            } else {

                                                $lc_prems = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                    ->where('family_size', $request->family_size[$i])
                                                    ->where('health_plan', $request->health_plan[$i])
                                                    ->where('product_name', $request->product_name[$i])
                                                    ->where('family_no', $family_no)
                                                    ->get();

                                                if (count($lc_prems) > 0) {
                                                    $lc_prems = $lc_prems[0]->premium;
                                                    if ($lc_prems > 0) {
                                                        if ($lc_prems !== 3) {
                                                            $lc_prems = 0;
                                                        }
                                                    }
                                                    $lc_prem = $lc_prems;
                                                } else {
                                                    $lc_prem = ClientRates::select(DB::raw("isnull(premium, 0) as premium"))
                                                        ->where('health_plan', $request->health_plan[$i])
                                                        ->where('product_name', $request->product_name[$i])
                                                        ->where('family_no', $family_no)
                                                        ->get();
                                                    $lc_prem = count($lc_prem) === 0 ? 0 : $lc_prem[0]->premium;
                                                }
                                            }
                                        }
                                    }
                                    //check prorata days
                                    //prorata_prem represents discounted_prem, loaded_prem, prorated_prem
                                    $prorata_prem = $lc_prem;
                                    //each member anniversary date difference
                                    $start_date_f = \Carbon\Carbon::parse($request->effective_date[$i]);
                                    $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);
                                    if ($lc_prem > 0) {
                                        $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;

                                        /*if ($ll_pro_unit < 365) {
                                        if ($ll_pro_unit < 90) {
                                            $ll_pro_unit = 90;
                                        }
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = ($lc_prem / 365) * $ll_pro_unit;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - (($lc_prem / 365) * $ll_pro_unit);
                                                break;
                                        }
                                    } else {
                                        if ($request->transaction == 2) {
                                            $prorata_prem = 0 - $lc_prem;
                                        }
                                    }*/
                                        //with no proration
                                        switch ($request->transaction) {
                                            case '1':
                                                $prorata_prem = $lc_prem;
                                                break;
                                            case '2':
                                                $prorata_prem = 0 - ($lc_prem);
                                                break;
                                        }
                                    } else {
                                        $prorata_prem = 0;
                                    }


                                    if ($request->smart_card[$i] == 1) {
                                        array_push($smart_card_array, $smart_card_fee);
                                        $total_smart_card_fee += $smart_card_fee;
                                    } else {
                                        $smart_card_fee = 0;
                                        array_push($smart_card_array, 0);
                                        $total_smart_card_fee += 0;
                                    }
                                    if ($request->access_fee[$i] == 1) {
                                        array_push($smart_access_array, $smart_access_fee);
                                        $total_smart_access_fee += $smart_access_fee;
                                    } else {
                                        $smart_access_fee = 0;
                                        array_push($smart_access_array, 0);
                                        $total_smart_access_fee += 0;
                                    }


                                    $dt = [
                                        'member_premium' => $lc_prem,
                                        'prorata_premium' => $prorata_prem,
                                        'member_no' => $request->member_no[$i],
                                        'health_plan' => $request->health_plan[$i],
                                        'principal_name' => $request->principal_name[$i],
                                        'member_name' => $request->member_name[$i],
                                        'product_name' => $request->product_name[$i],
                                        'family_size' => $request->family_size[$i],
                                        'relation' => $request->relation[$i],
                                        'age' => $request->age[$i],
                                        'effective_date' => $request->effective_date[$i],
                                        'end_date' => $request->end_date[$i],
                                        'prorata_period' => $ll_pro_unit,
                                        'smart_cost' => $smart_card_fee,
                                        'access_cost' => $smart_access_fee

                                    ];

                                    $loaded_prem += $prorata_prem;
                                    $total_premium += $lc_prem;
                                    array_push($member_premiums, $dt);
                                }
                            }

                            if (!is_null($loaded_prem)) {
                                $ira = 0.015 * $loaded_prem;
                                $iiu = 0.005 * $loaded_prem;
                                $retained = 0.75 * $loaded_prem;
                                $ceded = 0.25 * $loaded_prem;
                                $invoice_ttl = $ira + $iiu + $loaded_prem + $total_smart_card_fee + $total_smart_access_fee + $request->membership_contigency + $request->membership_gift_items + $request->membership_first_aid + $request->membership_stamp_duty + $request->membership_others;
                            }
                            $data = [
                                'ira' => $ira,
                                'iiu' => $iiu,
                                'total_premium' => $total_premium,
                                'invoice_total' => $invoice_ttl,
                                'loaded_prem' => $loaded_prem,
                                'member_premiums' => $member_premiums,
                                'smart_card_array' => $smart_card_array,
                                'access_fee_array' => $smart_access_array,
                                'total_smart_card_fee' => $total_smart_card_fee,
                                'total_smart_access_fee' => $total_smart_access_fee,
                                'retained' => $retained,
                                'ceded' => $ceded,
                                'details' => $request->member_no

                            ];
                            array_push($response, $data);
                        } catch (Exception $e) {
                            array_push($response, $e->getMessage());
                        }
                        return $response;
                        break;
                        //#5: cover upgrade debit for family
                    case 5:
                        return $response;
                        break;
                        //#6: cover downgrade debit for family
                    case 6:
                        return $response;
                        break;
                        //#8: cover extension debit for family
                    case 7:
                        return $response;
                        break;
                }
                break;
                //query family debit
            case 4:
                break;
                //manual invoice -  corporate
            case 5:
                break;
                //manual invoice -  retail
            case 6:
                break;
        }
    }

    //save premium debiting
    public function savePremiumDebiting(Request $request)
    {
        //return $request;
        ini_set('max_execution_time', '0');
        $response = [];
        $results[] = $request->all();

        if (
            $request->choosen_option == 1 || $request->choosen_option == 3 ||
            $request->choosen_option == 5 || $request->choosen_option == 6
        ) {
            $ll_anniv = $request->anniv;
            $ll_buz_type = $request->business_class;

            $ll_count = PremiumInvoice::select(DB::raw("count(invoice_no) as count"))
                ->distinct('invoice_no')
                ->where('credit_note', 1)
                ->where('corp_id', $request->corp_id)
                ->where('anniv', $ll_anniv)
                ->get();
            $ll_count = $ll_count[0]['count'];
            //new or renewal
            /*if ($ll_count > 0 && $ll_buz_type == '1' || $ll_buz_type == '2') {
                array_push($response, 'Notice ! You Debited a New or a Re-newing Corporate more than
                 once in the same Anniversary.');
            }*/
        }

        if ($request->choosen_option == 5 || $request->choosen_option == 6) {
            $lc_smart_cost = $request->new_cards;
            $lc_access_fee = $request->access_fee;
            $lc_phcf = $request->phcf;
            $lc_tlevy = $request->levy;
            $lc_invoice_net = $request->invoice_net;
            $lc_invoice_total = $request->invoice_total;
            $lc_premium_net = $request->premium_net;
            $lc_contigency = $request->contingency;
            $lc_gift_items = $request->gift_items;
            $lc_first_aid = $request->first_aid;
            if (!is_null($lc_smart_cost) && $lc_smart_cost > 0) {
                $lc_smart_cost = 0 - $lc_smart_cost;
            }
            if (!is_null($lc_access_fee) && $lc_access_fee > 0) {
                $lc_access_fee = 0 - $lc_access_fee;
            }
            if (!is_null($lc_phcf) && $lc_phcf > 0) {
                $lc_phcf = 0 - $lc_phcf;
            }
            if (!is_null($lc_tlevy) && $lc_tlevy > 0) {
                $lc_tlevy = 0 - $lc_tlevy;
            }
            if (!is_null($lc_invoice_net) && $lc_invoice_net > 0) {
                $lc_invoice_net = 0 - $lc_invoice_net;
            }
            if (!is_null($lc_invoice_total) && $lc_invoice_total > 0) {
                $lc_invoice_total = 0 - $lc_invoice_total;
            }
            if (!is_null($lc_premium_net) && $lc_premium_net > 0) {
                $lc_premium_net = 0 - $lc_premium_net;
            }
            if (!is_null($lc_contigency) && $lc_contigency > 0) {
                $lc_contigency = 0 - $lc_contigency;
            }
            if (!is_null($lc_gift_items) && $lc_gift_items > 0) {
                $lc_gift_items = 0 - $lc_gift_items;
            }
            if (!is_null($lc_first_aid) && $lc_first_aid > 0) {
                $lc_first_aid = 0 - $lc_first_aid;
            }
        }

        //saving functionality
        $premium_invoice_max_idx = PremiumInvoice::max('idx');
        if (is_null($premium_invoice_max_idx)) {
            $premium_invoice_max_idx = 0;
        }
        $premium_invoice_max_idx++;
        $premium_invoice_members_max_idx = PremiumInvoiceMembers::max('idx');
        if (is_null($premium_invoice_members_max_idx)) {
            $premium_invoice_members_max_idx = 0;
        }
        $premium_invoice_details_max_idx = PremiumInvoiceDetails::max('idx');
        if (is_null($premium_invoice_details_max_idx)) {
            $premium_invoice_details_max_idx = 0;
        }
        $premium_invoice_details_sammary_max_idx = PremiumInvoiceDetailsSammary::max('id_key');
        if (is_null($premium_invoice_details_sammary_max_idx)) {
            $premium_invoice_details_sammary_max_idx = 0;
        }

        $premium_invoice_details_sammary_max_idx += 1;
        //return $premium_invoice_members_max_idx;
        //other levies
        $first_aid = $request->first_aid;
        $contigency = $request->contingency;
        $gift_items = $request->gift_items;
        if (is_null($first_aid)) {
            $first_aid = 0;
        }
        if (is_null($contigency)) {
            $contigency = 0;
        }
        if (is_null($gift_items)) {
            $gift_items = 0;
        }

        $total_others = $first_aid + $contigency + $gift_items;
        $reference_no = '';

        if ($request->transaction == 1) {
            $reference_no = 'DBT' . '-' . $premium_invoice_max_idx;
        } elseif ($request->transaction == 2) {
            $reference_no = 'CRT' . '-' . $premium_invoice_max_idx;
        }
        /*if ($request -> transaction == 3){
            $reference_no = 'END'.'-'.$premium_invoice_max_idx;
        }*/

        $prorate_days = 0;
        if ($request->days < 365) {
            $prorate_days = $request->days;
        } else if ($request->days >= 365) {
            $prorate_days = null;
        }
        $today = date('Y-m-d');

        //set inserted, updated, and deleted items
        $inserted_item = "invoice_no : $reference_no";
        $updated_item = null;
        $deleted_item = null;
        //save corporate debiting
        if ($request->choosen_option == 1 || $request->choosen_option == 5) {
            //save to table premium invoice
            try {
                PremiumInvoice::create([
                    'INVOICE_NO' => $reference_no,
                    'CORP_ID' => $request->corp_id,
                    'INVOICE_DATE' => $today,
                    'INVOICE_TOTAL' => str_replace(',', '', $request->invoice_total),
                    'ANNIV' => $request->anniv,
                    'USER_ID' => $request->user,
                    'DATE_ENTERED' => $today,
                    'premium_type' => $request->invoice_type_modal,
                    'INVOICE_NET' => str_replace(',', '', $request->invoice_net),
                    //'DISCOUNT_AMT' => $request -> invoice_net,
                    'AGENT_ID' => $request->agent_id,
                    'PREMIUM_NET' => str_replace(',', '', $request->premium_net),
                    //'DISCOUNT_ALLOWED'=> $request->premium_net,
                    //'FUND_INVOICE',
                    //'PRORATED',
                    'insurer' => 1,
                    //'allocated'=> $request->premium_net,
                    //'allocated_amt',
                    'cover_start_date' => $request->effective_date,
                    //'load'
                    'new_renewal' => $request->business_class,
                    'stamp_duty' => $request->stamp_duty,
                    'phcf' => str_replace(',', '', $request->iiu_tax),
                    'tl' => str_replace(',', '', $request->ira_tax),
                    //'unit_manager' => $request->unit_manager,
                    //'nullify' => $request->nullify,
                    'credit_note' => $request->transaction,
                    'prorate_days' => $prorate_days,
                    'idx' => $premium_invoice_max_idx,
                    //'prorate_days_month'=>$request->days,
                    'notes' => $request->notes,
                    'end_date' => $request->end_date,
                    'days' => $request->days,
                    'contigency' => $contigency,
                    //'sync'=>$request -> sync,
                    //'integ_date'=>$request -> integ_date,
                    'smart_cost' => str_replace(',', '', $request->new_cards),
                    'access_fee' => str_replace(',', '', $request->access_fee),
                    'gift_items' => $gift_items,
                    'first_aid' => $first_aid,
                    'retained' => str_replace(',', '', $request->retained),
                    'ceded' => str_replace(',', '', $request->ceded),
                    'other' => $total_others,
                    'agent_rate' => $request->agent_rate,
                    'bdm_rate' => $request->bdm_rate,
                    'ass_hos_rate' => $request->asst_hos_rate,
                    'hos_rate' => $request->hos_rate,
                ]);

                //save to premium invoice details summary
                PremiumInvoiceDetailsSammary::create([
                    'id_key' => $premium_invoice_details_sammary_max_idx,
                    'invoice_no' => $reference_no,
                    'class' => 1,
                    'net_premium' => str_replace(',', '', $request->premium_net),
                    'other_costs' => $total_others
                ]);
                //loop and save into two tables - Premium Invoice Details and Premium Invoice Members
                for ($i = 0; $i < count($request->member_no); $i++) {
                    //increment idx
                    $premium_invoice_details_max_idx++;
                    if ($request->relation[$i] == 3) {
                        $relation = 24;
                    } else {
                        $relation = 25;
                    }
                    //get family number for each member number row from requests
                    $family_no = explode('-', $request->member_no[$i]);
                    $family_no = $family_no[0];
                    PremiumInvoiceDetails::create([
                        'family_no' => $family_no,
                        'member_no' => $request->member_no[$i],
                        //'benefit' => $request -> benefit[$i],
                        //'limit' => $request -> limit[$i],
                        'premium' => str_replace(',', '', $request->member_premium[$i]),
                        'invoice_no' => $reference_no,
                        //'sharing' => $request -> sharing[$i],
                        'family_size' => $request->family_size[$i],
                        //'family_age' => $request -> age[$i],
                        'total' => str_replace(',', '', $request->total_premium[$i]),
                        'prorated_prem' => str_replace(',', '', $request->prorata_premium[$i]),
                        'discounted_prem' => str_replace(',', '', $request->discounted_premium[$i]),
                        'loaded_prem' => str_replace(',', '', $request->loaded_premium[$i]),
                        //'disc_amt'=> $request -> loaded_premium[$i],
                        //'load_amt'=> $request -> loaded_premium[$i]
                        'category' => $request->health_plan[$i],
                        'idx' => $premium_invoice_details_max_idx,
                        'member_age' => $request->age[$i],
                        'product_name' => $request->product_name[$i],
                        'family_title' => $request->relation[$i],
                        //'prorata'=> $request -> relation[$i],
                        //'join_date'=> $request -> relation[$i],
                        'prorata_period' => $request->prorata_period[$i],
                        'effective_date' => $request->member_effective_date[$i],
                        'end_date' => $request->member_end_date[$i],
                        'smart_cost' => str_replace(',', '', $request->smart_cost[$i]),
                        'access_fee' => str_replace(',', '', $request->access_cost[$i])
                    ]);
                    //increment idx
                    $premium_invoice_members_max_idx++;
                    PremiumInvoiceMembers::create([
                        'idx' => $premium_invoice_members_max_idx,
                        'invoice_no' => $reference_no,
                        'member_no' => $request->member_no[$i],
                        'anniv' => $request->anniv,
                        'start_date' => $request->member_effective_date[$i],
                        'relation' => $relation,
                        'premium' => str_replace(',', '', $request->member_premium[$i]),
                        //'allocated_amount' => $request->allocated_amt[$i]
                        //'receipt_no' => $request->receipt_no[$i],
                        //'date_allocated' => $request->date_allocated[$i]
                    ]);
                }

                if ($request->transaction == 1) {
                    array_push($response, 'Corporate Debited Successfully. Debit Note: ' . $reference_no);
                } else {
                    array_push($response, 'Corporate Credited Successfully. Credit Note: ' . $reference_no);
                }

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            } catch (Exception $e) {
                array_push($response, $e->getMessage());
            }
        }
        //save family debiting
        if ($request->choosen_option == 3 || $request->choosen_option == 6) {
            //save to table premium invoice
            try {
                $save_premium_invoice = PremiumInvoice::create([
                    'INVOICE_NO' => $reference_no,
                    'MEMBER_NO' => $request->principal_member_no,
                    'INVOICE_DATE' => $today,
                    'INVOICE_TOTAL' => str_replace(',', '', $request->invoice_total),
                    'ANNIV' => $request->anniv,
                    'USER_ID' => $request->user,
                    'DATE_ENTERED' => $today,
                    'premium_type' => $request->invoice_type_modal,
                    'INVOICE_NET' => str_replace(',', '', $request->invoice_net),
                    //'DISCOUNT_AMT' => $request -> invoice_net,
                    'AGENT_ID' => $request->agent_id,
                    'PREMIUM_NET' => str_replace(',', '', $request->premium_net),
                    //'DISCOUNT_ALLOWED'=> $request->premium_net,
                    //'FUND_INVOICE',
                    //'PRORATED',
                    'insurer' => 1,
                    //'allocated'=> $request->premium_net,
                    //'allocated_amt',
                    'cover_start_date' => $request->effective_date,
                    //'load'
                    'new_renewal' => $request->business_class,
                    'stamp_duty' => $request->stamp_duty,
                    'phcf' => str_replace(',', '', $request->iiu_tax),
                    'tl' => str_replace(',', '', $request->ira_tax),
                    //'unit_manager' => $request->unit_manager,
                    //'nullify' => $request->nullify,
                    'credit_note' => $request->transaction,
                    'prorate_days' => $prorate_days,
                    'idx' => $premium_invoice_max_idx,
                    //'prorate_days_month'=>$request->days,
                    'notes' => $request->notes,
                    'end_date' => $request->end_date,
                    'days' => $request->days,
                    'contigency' => $contigency,
                    //'sync'=>$request -> sync,
                    //'integ_date'=>$request -> integ_date,
                    'smart_cost' => str_replace(',', '', $request->new_cards),
                    'access_fee' => str_replace(',', '', $request->access_fee),
                    'gift_items' => $gift_items,
                    'first_aid' => $first_aid,
                    'retained' => str_replace(',', '', $request->retained),
                    'ceded' => str_replace(',', '', $request->ceded),
                    'other' => $total_others,
                    'agent_rate' => $request->agent_rate,
                    'bdm_rate' => $request->bdm_rate,
                    'ass_hos_rate' => $request->asst_hos_rate,
                    'hos_rate' => $request->hos_rate,
                ]);

                //save to premium invoice details summary
                PremiumInvoiceDetailsSammary::create([
                    'id_key' => $premium_invoice_details_sammary_max_idx,
                    'invoice_no' => $reference_no,
                    'class' => 1,
                    'net_premium' => str_replace(',', '', $request->premium_net),
                    'other_costs' => $total_others
                ]);

                $family_no = explode('-', $request->principal_member_no);
                $family_no = $family_no[0];
                for ($i = 0; $i < count($request->member_no); $i++) {
                    //increment idx
                    $premium_invoice_details_max_idx++;
                    if ($request->relation[$i] == 3) {
                        $relation = 24;
                    } else {
                        $relation = 25;
                    }
                    PremiumInvoiceDetails::create([
                        'family_no' => $family_no,
                        'member_no' => $request->member_no[$i],
                        //'benefit' => $request -> benefit[$i],
                        //'limit' => $request -> limit[$i],
                        'premium' => str_replace(',', '', $request->member_premium[$i]),
                        'invoice_no' => $reference_no,
                        //'sharing' => $request -> sharing[$i],
                        'family_size' => $request->family_size[$i],
                        //'family_age' => $request -> age[$i],
                        'total' => str_replace(',', '', $request->total_premium[$i]),
                        'prorated_prem' => str_replace(',', '', $request->prorata_premium[$i]),
                        'discounted_prem' => str_replace(',', '', $request->discounted_premium[$i]),
                        'loaded_prem' => str_replace(',', '', $request->loaded_premium[$i]),
                        //'disc_amt'=> $request -> loaded_premium[$i],
                        //'load_amt'=> $request -> loaded_premium[$i]
                        'category' => $request->health_plan[$i],
                        'idx' => $premium_invoice_details_max_idx,
                        'member_age' => $request->age[$i],
                        'product_name' => $request->product_name[$i],
                        'family_title' => $request->relation[$i],
                        //'prorata'=> $request -> relation[$i],
                        //'join_date'=> $request -> relation[$i],
                        'prorata_period' => $request->prorata_period[$i],
                        'effective_date' => $request->member_effective_date[$i],
                        'end_date' => $request->member_end_date[$i],
                        'smart_cost' => str_replace(',', '', $request->smart_cost[$i]),
                        'access_fee' => str_replace(',', '', $request->access_cost[$i])
                    ]);
                    //increment idx
                    $premium_invoice_members_max_idx++;
                    PremiumInvoiceMembers::create([
                        'idx' => $premium_invoice_members_max_idx,
                        'invoice_no' => $reference_no,
                        'member_no' => $request->member_no[$i],
                        'anniv' => $request->anniv,
                        'start_date' => $request->member_effective_date[$i],
                        'relation' => $relation,
                        'premium' => str_replace(',', '', $request->member_premium[$i]),
                        //'allocated_amount' => $request->allocated_amt[$i]
                        //'receipt_no' => $request->receipt_no[$i],
                        //'date_allocated' => $request->date_allocated[$i]
                    ]);
                }

                if ($request->transaction == 1) {
                    array_push($response, 'Family Debited Successfully. Debit No ' . $reference_no);
                } else {
                    array_push($response, 'Family Credited Successfully. Credit No ' . $reference_no);
                }
            } catch (Exception $e) {
                array_push($response, "Error " . $e->getMessage());
            }
        }
        return [
            'response' => $response,
            'reference_no' => $reference_no,
            'request' => $results[0]
        ];
    }

    /*********************save manual debiting for corporate and retail */

    public function saveManualDebiting(Request $request)
    {
        // return $request;
        //variables 
        $today = date('Y-m-d');
        $premium_invoice_max_idx = PremiumInvoice::max('idx');
        if (is_null($premium_invoice_max_idx)) {
            $premium_invoice_max_idx = 0;
        }
        $premium_invoice_max_idx++;
        $reference_no = 'CRT' . '-' . $premium_invoice_max_idx;

        try {

            DB::transaction(function () use ($request, $today, $premium_invoice_max_idx, $reference_no) {
                //corporate manual debit
                if ($request->choosen_option == 1) {
                    //set inserted, updated, and deleted items
                    $inserted_item = "reference_no : $reference_no, corp_id : $request->corp_id";
                    $updated_item = null;
                    $deleted_item = null;

                    PremiumInvoice::create([
                        'INVOICE_NO' => $reference_no,
                        'CORP_ID' => $request->corp_id,
                        'INVOICE_DATE' => $today,
                        'INVOICE_TOTAL' =>  $request->invoice_total,
                        'ANNIV' => $request->anniv,
                        'USER_ID' => $request->user_id,
                        'DATE_ENTERED' => $today,
                        'INVOICE_NET' => $request->invoice_net,
                        'AGENT_ID' => $request->agent_id,
                        'PREMIUM_NET' => $request->premium_net,
                        'insurer' => 1,
                        'cover_start_date' => $request->effective_date_main,
                        'phcf' => $request->iiu_tax,
                        'tl' => $request->ira_tax,
                        'credit_note' => (int)$request->transaction,
                        'idx' => $premium_invoice_max_idx,
                        'notes' => $request->notes,
                        'end_date' => $request->end_date,
                        'days' => $request->days,
                        'contigency' => (int)$request->contigency,
                        'smart_cost' => $request->new_cards,
                        'access_fee' => $request->access_fee,
                        'gift_items' => $request->gift_items,
                        'first_aid' => $request->first_aid,
                        'stamp_duty' => $request->stamp_duty,
                        'new_renewal' => 4,
                    ]);

                    //retail manual debit
                } else {
                    //set inserted, updated, and deleted items
                    $inserted_item = "reference_no : $reference_no, member_no : $request->principal_member_no";
                    $updated_item = null;
                    $deleted_item = null;
                    PremiumInvoice::create([
                        'INVOICE_NO' => $reference_no,
                        'MEMBER_NO' => $request->principal_member_no,
                        'INVOICE_DATE' => $today,
                        'INVOICE_TOTAL' =>  $request->invoice_total,
                        'ANNIV' => $request->anniv,
                        'USER_ID' => $request->user_id,
                        'DATE_ENTERED' => $today,
                        'INVOICE_NET' => $request->invoice_net,
                        'AGENT_ID' => $request->agent_id,
                        'PREMIUM_NET' => $request->premium_net,
                        'insurer' => 1,
                        'cover_start_date' => $request->effective_date_main,
                        'phcf' => $request->iiu_tax,
                        'tl' => $request->ira_tax,
                        'credit_note' => (int)$request->transaction,
                        'idx' => $premium_invoice_max_idx,
                        'notes' => $request->notes,
                        'end_date' => $request->end_date,
                        'days' => $request->days,
                        'contigency' => (int)$request->contigency,
                        'smart_cost' => $request->new_cards,
                        'access_fee' => $request->access_fee,
                        'gift_items' => $request->gift_items,
                        'first_aid' => $request->first_aid,
                        'stamp_duty' => $request->stamp_duty,
                        'new_renewal' => 4,
                    ]);
                }

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });

            return ['Manual debit successful with credit no ' . $reference_no];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function updateCreditNote(Request $request)  {
        $invoice_no = "CRT-$request->credit_no";
        // return $invoice_no;
        try {
            DB::transaction(function() use($request, $invoice_no){
                PremiumInvoice::where('invoice_no', $invoice_no)
                ->update([
                    'INVOICE_TOTAL' =>  $request->invoice_total,
                    'INVOICE_NET' => $request->invoice_net,
                    'PREMIUM_NET' => $request->premium_net,
                    'phcf' => $request->iiu_tax,
                    'tl' => $request->ira_tax,
                    'contigency' => (int)$request->contigency,
                    'smart_cost' => $request->new_cards,
                    'access_fee' => $request->access_fee,
                    'gift_items' => $request->gift_items,
                    'first_aid' => $request->first_aid,
                    'stamp_duty' => $request->stamp_duty,
                    'notes' => $request->notes,
                ]);

            });
            return ['Successfully updated'];
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }
    public function reverseCreditNote(Request $request)  {
        $invoice_no = "CRT-$request->credit_no";
        try {
            DB::transaction(function() use($invoice_no){
                PremiumInvoice::where('invoice_no', $invoice_no)
                ->delete();
            });
            return ['Successfully deleted'];
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }
}
