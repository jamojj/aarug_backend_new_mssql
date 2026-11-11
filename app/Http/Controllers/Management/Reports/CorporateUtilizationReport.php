<?php

namespace App\Http\Controllers\Management\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\ClientRates;
use App\Models\Corporate\CorpAnniversary;
use App\Models\PremiumInvoice;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberInfo;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CorporateUtilizationReport extends Controller
{

    public function fetchCorpUtilizationMemberAnnivs(Request $request)
    {
        $annivs = MemberAnniversary::select('anniv')
            ->where('member_no', $request->member)
            ->orderBy('anniv', 'ASC')
            ->get();
        return $annivs;
    }




    public function fetchCorporateUtilizationReport(Request $request)
    {
        ini_set('max_execution_time', '0');

        //variables 
        $start_date = $end_date = $start_date_1 = $end_date_1 = $start_date_2 = $end_date_2 = $start_date_3 = $end_date_3 = '';
        $ttl_inv_amt = $ttl_inv_amt_avrg = $ttl_lives_that_used = $claims_current = $claims_previous_1 = $claims_previous_2 = $claims_previous_3 = $consumed_cover_days = $months = 0;


        switch ($request->task) {
            case '1': //corporate
                //total amount payable
                $corp_cover_dates = CorpAnniversary::select(
                    DB::raw("FORMAT(start_date, 'yyyyMMdd') as start_date"),
                    DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date"),
                )->where('anniv', $request->anniv)->where('corp_id', $request->corporate)->get();

                $start_date = $corp_cover_dates[0]->start_date;
                $end_date = $corp_cover_dates[0]->end_date;

                $today = date('Ymd');

                $start_date_calc = new DateTime($start_date);
                $end_date_calc = new DateTime($end_date);
                $current = new DateTime();

           
                // $interval = $end_date_calc->diff($current);
                // $months = ($interval->y * 12) + $interval->m;

                  $interval = $start_date_calc->diff($current);
                $months = ($interval->y * 12) + $interval->m;

                $consumed_cover_days = $start_date_calc->diff($current)->days;
                $total_cover_days = $start_date_calc->diff($end_date_calc)->days + 1;


                //previous anniv dates
                if ($request->anniv > 1) {
                    $corp_cover_dates = CorpAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyyMMdd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date"),
                    )->where('anniv', $request->anniv - 1)->where('corp_id', $request->corporate)->get();
                    $start_date_1 = $corp_cover_dates[0]->start_date;
                    $end_date_1 = $corp_cover_dates[0]->end_date;
                }
                if ($request->anniv > 2) {
                    $corp_cover_dates = CorpAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyyMMdd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date"),
                    )->where('anniv', $request->anniv - 2)->where('corp_id', $request->corporate)->get();
                    $start_date_2 = $corp_cover_dates[0]->start_date;
                    $end_date_2 = $corp_cover_dates[0]->end_date;
                }

                if ($request->anniv > 3) {
                    $corp_cover_dates = CorpAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyyMMdd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date"),
                    )->where('anniv', $request->anniv - 3)->where('corp_id', $request->corporate)->get();
                    $start_date_3 = $corp_cover_dates[0]->start_date;
                    $end_date_3 = $corp_cover_dates[0]->end_date;
                }


                $inv_amts = Bills::select(
                    DB::raw("SUM(ISNULL(bills.INVOICED_AMOUNT, 0)) AS ttl_inv_amt"),
                    DB::raw("CASE WHEN $months <> 0
                        THEN SUM(ISNULL(bills.INVOICED_AMOUNT, 0)) / $months
                        ELSE 0
                    END AS ttl_inv_amt_avrg"),
                    DB::raw("COUNT(DISTINCT bills.member_no) AS lives_that_used"),
                    'benefit.benefit'
                )
                    ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->whereBetween(
                        DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"),
                        [$start_date, $today]
                    )
                    ->groupBy('benefit.benefit')
                    ->orderBy('benefit.benefit', 'asc')
                    ->get();


                foreach ($inv_amts as $inv_amt) {
                    $ttl_inv_amt += $inv_amt->ttl_inv_amt;
                    $ttl_inv_amt_avrg += $inv_amt->ttl_inv_amt_avrg;
                    $ttl_lives_that_used += $inv_amt->lives_that_used;
                }

                //gwp          
                if (!empty($start_date) && !empty($end_date)) {
                    $gwp = PremiumInvoice::whereBetween(
                        DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"),
                        [$start_date, $end_date]
                    )
                        ->where('corp_id', $request->corporate)
                        ->sum(DB::raw("isnull(premium_net, 0)"));
                } else {
                    $gwp = 0;
                }

                //gwp previous 1
                if (!empty($start_date_1) && !empty($end_date_1)) {
                    $gwp_previous_1 = PremiumInvoice::whereBetween(
                        DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"),
                        [$start_date_1, $end_date_1]
                    )
                        ->where('corp_id', $request->corporate)
                        ->sum(DB::raw("isnull(premium_net, 0)"));
                } else {
                    $gwp_previous_1 = 0;
                }
                //gwp previous 2
                if (!empty($start_date_2) && !empty($end_date_2)) {
                    $gwp_previous_2 = PremiumInvoice::whereBetween(
                        DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"),
                        [$start_date_2, $end_date_2]
                    )
                        ->where('corp_id', $request->corporate)
                        ->sum(DB::raw("isnull(premium_net, 0)"));
                } else {
                    $gwp_previous_2 = 0;
                }
                //gwp previous 3
                if (!empty($start_date_3) && !empty($end_date_3)) {
                    $gwp_previous_3 = PremiumInvoice::whereBetween(
                        DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"),
                        [$start_date_3, $end_date_3]
                    )
                        ->where('corp_id', $request->corporate)
                        ->sum(DB::raw("isnull(premium_net, 0)"));
                } else {
                    $gwp_previous_3 = 0;
                }


                //max corp anniversary
                $max_anniv = CorpAnniversary::where('corp_id', $request->corporate)->max('anniv');

                $lives_on_cover = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->where('member_anniversary.anniv', $request->anniv)
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->count('member_info.member_no');
                $lives_current = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->whereBetween(DB::raw("FORMAT(member_anniversary.end_date, 'yyyyMMdd')"), [$start_date, $end_date])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->count('member_info.member_no');
                $lives_previous_1 = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->whereBetween(DB::raw("FORMAT(member_anniversary.end_date, 'yyyyMMdd')"), [$start_date_1, $end_date_1])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->count('member_info.member_no');
                $lives_previous_2 = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->whereBetween(DB::raw("FORMAT(member_anniversary.end_date, 'yyyyMMdd')"), [$start_date_2, $end_date_2])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->count('member_info.member_no');
                $lives_previous_3 = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->whereBetween(DB::raw("FORMAT(member_anniversary.end_date, 'yyyyMMdd')"), [$start_date_3, $end_date_3])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->count('member_info.member_no');

                $data_current = Bills::select(
                    DB::raw("sum(isnull(bills.INVOICED_AMOUNT, 0)) as ttl_inv_amt"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 2 then bills.INVOICED_AMOUNT end, 0)) as op_claims"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 1 then bills.INVOICED_AMOUNT end, 0)) as ip_claims"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 2 then bills.CLAIM_NO end, '')) as op_visits"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 1 then bills.CLAIM_NO end, '')) as ip_visits"),
                    DB::raw("op_claims / op_visits as av_cost_op"),
                    DB::raw("ip_claims / ip_visits as av_cost_ip"),
                    DB::raw("ip_claims + op_claims as claims_current"),
                )
                    ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$start_date, $end_date])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    // ->whereIn('bills.claim_nature', [1, 4, 7, 8, 9, 54, 60, 24, 11, 26, 27, 36, 38, 40])
                    ->get();

                $data_previous_1 = Bills::select(
                    DB::raw("sum(isnull(bills.INVOICED_AMOUNT, 0)) as ttl_inv_amt"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 2 then bills.INVOICED_AMOUNT end, 0)) as op_claims"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 1 then bills.INVOICED_AMOUNT end, 0)) as ip_claims"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 2 then bills.CLAIM_NO end, '')) as op_visits"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 1 then bills.CLAIM_NO end, '')) as ip_visits"),
                    DB::raw("op_claims / op_visits as av_cost_op"),
                    DB::raw("ip_claims / ip_visits as av_cost_ip"),
                    DB::raw("ip_claims + op_claims as claims_previous_1"),

                )
                    ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$start_date_1, $end_date_1])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->get();

                $data_previous_2 = Bills::select(
                    DB::raw("sum(isnull(bills.INVOICED_AMOUNT, 0)) as ttl_inv_amt"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 2 then bills.INVOICED_AMOUNT end, 0)) as op_claims"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 1 then bills.INVOICED_AMOUNT end, 0)) as ip_claims"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 2 then bills.CLAIM_NO end, '')) as op_visits"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 1 then bills.CLAIM_NO end, '')) as ip_visits"),
                    DB::raw("op_claims / op_visits as av_cost_op"),
                    DB::raw("ip_claims / ip_visits as av_cost_ip"),
                    DB::raw("ip_claims + op_claims as claims_previous_2")
                )
                    ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$start_date_2, $end_date_2])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    // ->whereIn('bills.claim_nature', [1, 4, 7, 8, 9, 54, 60, 24, 11, 26, 27, 36, 38, 40])
                    ->get();
                $data_previous_3 = Bills::select(
                    DB::raw("sum(isnull(bills.INVOICED_AMOUNT, 0)) as ttl_inv_amt"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 2 then bills.INVOICED_AMOUNT end, 0)) as op_claims"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 1 then bills.INVOICED_AMOUNT end, 0)) as ip_claims"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 2 then bills.CLAIM_NO end, '')) as op_visits"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 1 then bills.CLAIM_NO end, '')) as ip_visits"),
                    DB::raw("op_claims / op_visits as av_cost_op"),
                    DB::raw("ip_claims / ip_visits as av_cost_ip"),
                    DB::raw("ip_claims + op_claims as claims_previous_3")
                )
                    ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$start_date_3, $end_date_3])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    // ->whereIn('bills.claim_nature', [1, 4, 7, 8, 9, 54, 60, 24, 11, 26, 27, 36, 38, 40])
                    ->get();


                $corp_datas = ClientRates::select(
                    'product_name',
                    'health_plan',
                    'premium',
                    'min_age',
                    'max_age',
                    'premium_type as age',
                    'commis_rate as population'
                )

                    ->where('corp_id', $request->corporate)
                    ->get();

                foreach ($corp_datas as $corp_data) {
                    $pltn = MemberAnniversary::join('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
                        ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->where('member_anniversary.health_plan', $corp_data->health_plan)
                        ->where('member_anniversary.h_option', $corp_data->product_name)
                        ->where('member_anniversary.anniv', $request->anniv)
                        ->where('principal_applicant.corp_id', $request->corporate)
                        ->count();
                    $corp_data->population = $pltn;
                    $corp_data->premium = number_format($corp_data->premium, 0);

                    if (is_null($corp_data->max_age) && !is_null($corp_data->max_age)) {
                        $corp_data->age = $corp_data->min_age . '+';
                    } else if (is_null($corp_data->min_age) && is_null($corp_data->max_age)) {
                        $corp_data->age = '';
                    } else {
                        $corp_data->age = $corp_data->min_age . ' - ' . $corp_data->max_age;
                    }
                }




                break;

            case '2': //member
                //total amount payable
                $corp_cover_dates = MemberAnniversary::select(
                    DB::raw("FORMAT(start_date, 'yyyyMMdd') as start_date"),
                    DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date"),
                )->where('anniv', $request->anniv)->where('member_no', $request->member)->get();

                $start_date = $corp_cover_dates[0]->start_date;
                $end_date = $corp_cover_dates[0]->end_date;

                $today = date('Ymd');

                $start_date_calc = new DateTime($start_date);
                $end_date_calc = new DateTime($end_date);
                $current = new DateTime();

             
                // $interval = $end_date_calc->diff($current);
                // $months = ($interval->y * 12) + $interval->m;
                $interval = $start_date_calc->diff($current);
                $months = ($interval->y * 12) + $interval->m;

                $consumed_cover_days = $start_date_calc->diff($current)->days;
                $total_cover_days = $start_date_calc->diff($end_date_calc)->days + 1;


                //previous anniv dates
                if ($request->anniv > 1) {
                    $corp_cover_dates = MemberAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyyMMdd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date"),
                    )->where('anniv', $request->anniv - 1)->where('member_no', $request->member)->get();
                    $start_date_1 = $corp_cover_dates[0]->start_date;
                    $end_date_1 = $corp_cover_dates[0]->end_date;
                }
                if ($request->anniv > 2) {
                    $corp_cover_dates = MemberAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyyMMdd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date"),
                    )->where('anniv', $request->anniv - 2)->where('member_no', $request->member)->get();
                    $start_date_2 = $corp_cover_dates[0]->start_date;
                    $end_date_2 = $corp_cover_dates[0]->end_date;
                }

                if ($request->anniv > 3) {
                    $corp_cover_dates = MemberAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyyMMdd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date"),
                    )->where('anniv', $request->anniv - 3)->where('member_no', $request->member)->get();
                    $start_date_3 = $corp_cover_dates[0]->start_date;
                    $end_date_3 = $corp_cover_dates[0]->end_date;
                }


                $inv_amts = Bills::select(
                    DB::raw("SUM(ISNULL(bills.INVOICED_AMOUNT, 0)) AS ttl_inv_amt"),
                    DB::raw("CASE WHEN $months <> 0
                        THEN SUM(ISNULL(bills.INVOICED_AMOUNT, 0)) / $months
                        ELSE 0
                    END AS ttl_inv_amt_avrg"),
                    DB::raw("COUNT(DISTINCT bills.member_no) AS lives_that_used"),
                    'benefit.benefit'
                )
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->where('bills.member_no', $request->member)
                    ->whereBetween(
                        DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"),
                        [$start_date, $today]
                    )
                    ->groupBy('benefit.benefit')
                    ->orderBy('benefit.benefit', 'asc')
                    ->get();


                foreach ($inv_amts as $inv_amt) {
                    $ttl_inv_amt += $inv_amt->ttl_inv_amt;
                    $ttl_inv_amt_avrg += $inv_amt->ttl_inv_amt_avrg;
                    $ttl_lives_that_used += $inv_amt->lives_that_used;
                }

                //gwp          
                if (!empty($start_date) && !empty($end_date)) {
                    $gwp = PremiumInvoice::whereBetween(
                        DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"),
                        [$start_date, $end_date]
                    )
                        ->where('member_no', $request->member)
                        ->sum(DB::raw("isnull(premium_net, 0)"));
                } else {
                    $gwp = 0;
                }

                //gwp previous 1
                if (!empty($start_date_1) && !empty($end_date_1)) {
                    $gwp_previous_1 = PremiumInvoice::whereBetween(
                        DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"),
                        [$start_date_1, $end_date_1]
                    )
                        ->where('member_no', $request->member)
                        ->sum(DB::raw("isnull(premium_net, 0)"));
                } else {
                    $gwp_previous_1 = 0;
                }
                //gwp previous 2
                if (!empty($start_date_2) && !empty($end_date_2)) {
                    $gwp_previous_2 = PremiumInvoice::whereBetween(
                        DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"),
                        [$start_date_2, $end_date_2]
                    )
                        ->where('member_no', $request->member)
                        ->sum(DB::raw("isnull(premium_net, 0)"));
                } else {
                    $gwp_previous_2 = 0;
                }
                //gwp previous 3
                if (!empty($start_date_3) && !empty($end_date_3)) {
                    $gwp_previous_3 = PremiumInvoice::whereBetween(
                        DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"),
                        [$start_date_3, $end_date_3]
                    )
                        ->where('member_no', $request->member)
                        ->sum(DB::raw("isnull(premium_net, 0)"));
                } else {
                    $gwp_previous_3 = 0;
                }


                //max corp anniversary
                $max_anniv = MemberAnniversary::where('member_no', $request->member)->max('anniv');

                $lives_on_cover = 1;
                $lives_current = 1;
                $lives_previous_1 = 1;
                $lives_previous_2 = 1;
                $lives_previous_3 = 1;

                $data_current = Bills::select(
                    DB::raw("sum(isnull(bills.INVOICED_AMOUNT, 0)) as ttl_inv_amt"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 2 then bills.INVOICED_AMOUNT end, 0)) as op_claims"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 1 then bills.INVOICED_AMOUNT end, 0)) as ip_claims"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 2 then bills.CLAIM_NO end, '')) as op_visits"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 1 then bills.CLAIM_NO end, '')) as ip_visits"),
                    DB::raw("op_claims / op_visits as av_cost_op"),
                    DB::raw("ip_claims / ip_visits as av_cost_ip"),
                    DB::raw("ip_claims + op_claims as claims_current"),
                )
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$start_date, $end_date])
                    ->where('bills.member_no', $request->member)
                    // ->whereIn('bills.claim_nature', [1, 4, 7, 8, 9, 54, 60, 24, 11, 26, 27, 36, 38, 40])
                    ->get();

                $data_previous_1 = Bills::select(
                    DB::raw("sum(isnull(bills.INVOICED_AMOUNT, 0)) as ttl_inv_amt"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 2 then bills.INVOICED_AMOUNT end, 0)) as op_claims"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 1 then bills.INVOICED_AMOUNT end, 0)) as ip_claims"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 2 then bills.CLAIM_NO end, '')) as op_visits"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 1 then bills.CLAIM_NO end, '')) as ip_visits"),
                    DB::raw("op_claims / op_visits as av_cost_op"),
                    DB::raw("ip_claims / ip_visits as av_cost_ip"),
                    DB::raw("ip_claims + op_claims as claims_previous_1"),

                )
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$start_date_1, $end_date_1])
                    ->where('bills.member_no', $request->member)
                    ->get();

                $data_previous_2 = Bills::select(
                    DB::raw("sum(isnull(bills.INVOICED_AMOUNT, 0)) as ttl_inv_amt"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 2 then bills.INVOICED_AMOUNT end, 0)) as op_claims"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 1 then bills.INVOICED_AMOUNT end, 0)) as ip_claims"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 2 then bills.CLAIM_NO end, '')) as op_visits"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 1 then bills.CLAIM_NO end, '')) as ip_visits"),
                    DB::raw("op_claims / op_visits as av_cost_op"),
                    DB::raw("ip_claims / ip_visits as av_cost_ip"),
                    DB::raw("ip_claims + op_claims as claims_previous_2")
                )
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$start_date_2, $end_date_2])
                    ->where('bills.member_no', $request->member)
                    // ->whereIn('bills.claim_nature', [1, 4, 7, 8, 9, 54, 60, 24, 11, 26, 27, 36, 38, 40])
                    ->get();
                $data_previous_3 = Bills::select(
                    DB::raw("sum(isnull(bills.INVOICED_AMOUNT, 0)) as ttl_inv_amt"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 2 then bills.INVOICED_AMOUNT end, 0)) as op_claims"),
                    DB::raw("sum(isnull(case when benefit.bene_class = 1 then bills.INVOICED_AMOUNT end, 0)) as ip_claims"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 2 then bills.CLAIM_NO end, '')) as op_visits"),
                    DB::raw("count(distinct isnull(case when benefit.bene_class = 1 then bills.CLAIM_NO end, '')) as ip_visits"),
                    DB::raw("op_claims / op_visits as av_cost_op"),
                    DB::raw("ip_claims / ip_visits as av_cost_ip"),
                    DB::raw("ip_claims + op_claims as claims_previous_3")
                )
                    ->join('benefit', 'benefit.code', '=', 'bills.claim_nature')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$start_date_3, $end_date_3])
                    ->where('bills.member_no', $request->member)
                    // ->whereIn('bills.claim_nature', [1, 4, 7, 8, 9, 54, 60, 24, 11, 26, 27, 36, 38, 40])
                    ->get();

                $fam_no = explode('-', $request->member);
                $fam_no = $fam_no[0];

                $corp_datas = ClientRates::select(
                    'product_name',
                    'health_plan',
                    'premium',
                    'min_age',
                    'max_age',
                    'premium_type as age',
                    'commis_rate as population'
                )

                    ->where('family_no', $fam_no)
                    ->get();

                foreach ($corp_datas as $corp_data) {
                    $pltn = MemberAnniversary::where('member_anniversary.health_plan', $corp_data->health_plan)
                        ->where('member_anniversary.h_option', $corp_data->product_name)
                        ->where('member_anniversary.anniv', $request->anniv)
                        ->where('member_no', $request->member)
                        ->count();
                    $corp_data->population = $pltn;
                    $corp_data->premium = number_format($corp_data->premium, 0);

                    if (is_null($corp_data->max_age) && !is_null($corp_data->max_age)) {
                        $corp_data->age = $corp_data->min_age . '+';
                    } else if (is_null($corp_data->min_age) && is_null($corp_data->max_age)) {
                        $corp_data->age = '';
                    } else {
                        $corp_data->age = $corp_data->min_age . ' - ' . $corp_data->max_age;
                    }
                }
                break;
        }

        // Get previous 2 years
        $current_year = !empty($start_date)
            ? Carbon::createFromFormat('Ymd', $start_date)->year
            : null;

        $previous_year_1 = !empty($start_date_1)
            ? Carbon::createFromFormat('Ymd', $start_date_1)->year
            : null;

        $previous_year_2 = !empty($start_date_2)
            ? Carbon::createFromFormat('Ymd', $start_date_2)->year
            : null;

        $previous_year_3 = !empty($start_date_3)
            ? Carbon::createFromFormat('Ymd', $start_date_3)->year
            : null;


        // Totals IP and OP between the last two cover periods

        $claims_current = $data_current[0]->claims_current;
        $claims_previous_1 = $data_previous_1[0]->claims_previous_1;
        $claims_previous_2 = $data_previous_2[0]->claims_previous_2;
        $claims_previous_3 = $data_previous_3[0]->claims_previous_3;


        // Calculate GEP safely
        $gep = $total_cover_days != 0
            ? ($consumed_cover_days / $total_cover_days) * $gwp
            : 0;


        // Claims ratios
        $claims_ratio_current = $gep != 0
            ? ($claims_current / $gep) * 100
            : 0;

        $claims_ratio_previous_1 = $gwp_previous_1 != 0
            ? ($claims_previous_1 / $gwp_previous_1) * 100
            : 0;

        $claims_ratio_previous_2 = $gwp_previous_2 != 0
            ? ($claims_previous_2 / $gwp_previous_2) * 100
            : 0;

        $claims_ratio_previous_3 = $gwp_previous_3 != 0
            ? ($claims_previous_3 / $gwp_previous_3) * 100
            : 0;


        // IBNR and projected claims
        $ibnr = ($months * $ttl_inv_amt_avrg) * 1.3;

        $projected_claims_amount = $ttl_inv_amt + $ibnr;


        // Running claims ratio
        $runing_cr = $gep != 0
            ? ($ttl_inv_amt / $gep) * 100
            : 0;


        // Projected claims ratio
        $projected_claim_ration = $gwp != 0
            ? ($projected_claims_amount / $gwp) * 100
            : 0;


        // Average premium per life
        $average_premium = $lives_on_cover != 0
            ? $gwp / $lives_on_cover
            : 0;


        // Average claims per life that used services
        $average_claims = $ttl_lives_that_used != 0
            ? $projected_claims_amount / $ttl_lives_that_used
            : 0;


        $totals = [
            'ttl_inv_amt' => number_format($ttl_inv_amt, 0),
            'date_diff' => $months,
            'ibnr' => number_format($ibnr, 0),
            'projected_claims_amount' => number_format($projected_claims_amount, 0),
            'gwp' => number_format($gwp, 0),
            'gep' => number_format($gep, 0),
            'runing_cr' => number_format($runing_cr, 0),
            'projected_claim_ration' => number_format($projected_claim_ration, 0),
            'lives_on_cover' => number_format($lives_on_cover, 0),
            'ttl_lives_that_used' => number_format($ttl_lives_that_used, 0),
            'average_premium' => number_format($average_premium, 0),
            'average_claims' =>  number_format($average_claims, 0),
        ];

        return [
            'report' => $inv_amts,
            'totals' => $totals,
            'data_current' => $data_current,
            'data_previous_1' => $data_previous_1,
            'data_previous_2' => $data_previous_2,
            'data_previous_3' => $data_previous_3,
            'claims_current' => $claims_current,
            'claims_previous_1' => $claims_previous_1,
            'claims_previous_2' => $claims_previous_2,
            'claims_previous_3' => $claims_previous_3,
            'gwp_current' => $gwp,
            'gwp_previous_1' => $gwp_previous_1,
            'gwp_previous_2' => $gwp_previous_2,
            'gwp_previous_3' => $gwp_previous_3,
            'claims_ratio_current' => number_format($claims_ratio_current, 0),
            'claims_ratio_previous_1' => number_format($claims_ratio_previous_1, 0),
            'claims_ratio_previous_2' => number_format($claims_ratio_previous_2, 0),
            'claims_ratio_previous_3' => number_format($claims_ratio_previous_3, 0),
            'lives_current' => $lives_current,
            'lives_previous_1' => $lives_previous_1,
            'lives_previous_2' => $lives_previous_2,
            'lives_previous_3' => $lives_previous_3,
            'corp_datas' => $corp_datas,
            'current_year' => $current_year,
            'previous_year_1' => $previous_year_1,
            'previous_year_2' => $previous_year_2,
            'previous_year_3' => $previous_year_3,
        ];
    }
}
