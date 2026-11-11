<?php

namespace App\Http\Controllers\Management\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\PremiumInvoice;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CorporatePerformanceController extends Controller
{
    public function fetchCorporatePerformanceReport(Request $request)
    {

        ini_set('max_execution_time', '0');
        $report = [];
        $year = date("Y", strtotime($request->date));
        $start_date_of_report = $year . '-01' . '-01';
        $end_date_of_report = $year . '-12' . '-31';
        $earned_days = $earned_days_previous_policy = $total_earned_days = $earned_premium_current = $earned_premium_prev = $ttl_earned_premium = $net_premium_current = $net_premium_prev  = $risk_premium_per_life = $claims_ratio = 0;
        $premium_category = "";

        $corps = Corporate::select(
            DB::raw('max(corp_anniversary.start_date) as start_date'),
            DB::raw('max(corp_anniversary.end_date) as end_date'),
            DB::raw('max(corp_anniversary.anniv) as anniv'),
            'corporate.corporate',
            'corporate.corp_id',

        )
            ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
            ->orderBy('corporate', 'ASC')
            ->groupBy('corporate.corporate', 'corporate.corp_id')
            ->where(DB::raw("isnull(corporate.cancelled, 0)"), 0)
            ->where(DB::raw("FORMAT(corp_anniversary.end_date, 'YYYY')"), '>=', $year)
            ->get();



        foreach ($corps as $corp) {
            //end and start date diff
            $date_diff = date_diff(date_create($corp->start_date), date_create($corp->end_date))->format('%R%a');
            //lives
            $lives = MemberInfo::select(
                DB::raw('count(member_info.member_no) as lives'),

            )
                ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->where(DB::raw('isnull(member_info.cancelled, 0)'), 0)
                ->where('corporate.corp_id', $corp->corp_id)
                ->get();

            $lives = count($lives) != 0 ? $lives[0]->lives : 0;

            //calculate earned days for current policy
            if (($corp->start_date <= $start_date_of_report) && ($corp->end_date < $end_date_of_report)) {
                $earned_days = date_diff(date_create($start_date_of_report), date_create($corp->end_date))->format('%R%a');
            } else if (($corp->start_date > $start_date_of_report) && ($corp->end_date > $end_date_of_report)) {
                $earned_days = date_diff(date_create($corp->start_date), date_create($end_date_of_report))->format('%R%a');
            } else if (($corp->start_date >= $start_date_of_report) && ($corp->end_date < $end_date_of_report)) {
                $earned_days = $date_diff;
            } else {
                $earned_days = 365;
            }

            //calculate earned days for previous policy
            if ($corp->anniv != 1) {
                $year_previous = date("Y", strtotime($corp->start_date));
                if ($year_previous == $year) {
                    $earned_days_previous_policy = date_diff(date_create($start_date_of_report), date_create($corp->start_date))->format('%R%a');
                }
            }

            //calculate total earned days
            $total_earned_days = $earned_days + $earned_days_previous_policy;


            //net premium for current anniv
            $net_premium_current = PremiumInvoice::select(
                DB::raw("sum(isnull(premium_net, 0)) as lc_gross_prem"),
            )
                ->where('anniv', $corp->anniv)
                ->where('corp_id', $corp->corp_id)
                ->get();

            $net_premium_current = count($net_premium_current) != 0 ? $net_premium_current[0]->lc_gross_prem : 0;
            //net premium for previous anniv
            $net_premium_prev = PremiumInvoice::select(
                DB::raw("sum(isnull(premium_net, 0)) as lc_gross_prem"),
            )
                ->where('anniv', $corp->anniv - 1)
                ->where('corp_id', $corp->corp_id)
                ->get();

            $net_premium_prev = count($net_premium_prev) != 0 ? $net_premium_prev[0]->lc_gross_prem : 0;

            //earned premium for current anniv
            if ($date_diff < 364) {
                if ($earned_days != 0 && $date_diff != 0) {
                    $earned_premium_current = ($earned_days / $date_diff) * $net_premium_current;
                }
            } else {
                if ($earned_days != 0) {
                    $earned_premium_current = ($earned_days / 365) * $net_premium_current;
                }
            }

            //earned premium previous
            if ($earned_days_previous_policy) {
                $earned_premium_prev = $net_premium_prev  * ($earned_days_previous_policy / 365);
            }

            //total earned premium

            $ttl_earned_premium = $earned_premium_current + $earned_premium_prev;

            //risk premium per life
            if (($net_premium_current != 0 && $lives != 0)) {
                $risk_premium_per_life = $net_premium_current / $lives;
            }

            //premium category

            if ($risk_premium_per_life <= 650000) {
                $premium_category = '650,000 and below';
            } else if ($risk_premium_per_life > 650000 && $risk_premium_per_life <= 750000) {
                $premium_category = '650,000 to 750,000';
            } else if ($risk_premium_per_life > 750000 && $risk_premium_per_life < 850000) {
                $premium_category = '750,000 to 850,000';
            } else if ($risk_premium_per_life > 850000 && $risk_premium_per_life < 1000000) {
                $premium_category = '850,000 to 1,000,000';
            } else {
                $premium_category = 'Greater than 1,000,000';
            }

            //claims total

            $claims_ttl = Bills::select(
                DB::raw("isnull(sum(isnull(invoiced_amount, 0)), 0) as inv_ttl")
            )
                ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->where(DB::raw('bills.anniv'),  $corp->anniv)
                ->where('principal_applicant.corp_id', $corp->corp_id)
                ->get();

            $claims_ttl = count($claims_ttl) != 0 ? $claims_ttl[0]->inv_ttl : 0;

            //claims ratio
            if (($claims_ttl != 0 && $ttl_earned_premium != 0)) {
                $claims_ratio = ($claims_ttl / $ttl_earned_premium) * 100;
            }

            $dt = [
                'corporate' => $corp->corporate,
                'lives' => $lives,
                'start_date' => date("d/m/Y", strtotime($corp->start_date)),
                'end_date' => date("d/m/Y", strtotime($corp->end_date)),
                'earned_days' => trim($earned_days, "+"),
                'earned_days_previous_policy' => trim($earned_days_previous_policy, "+"),
                'total_earned_days' => $total_earned_days,
                'earned_premium_current' => number_format($earned_premium_current, 2),
                'earned_premium_prev' => number_format($earned_premium_prev, 2),
                'ttl_earned_premium' => number_format($ttl_earned_premium, 2),
                'net_premium_current' => number_format($net_premium_current, 2),
                'net_premium_prev' => number_format($net_premium_prev, 2),
                'risk_premium_per_life' => number_format($risk_premium_per_life, 2),
                'premium_category' => $premium_category,
                'claims_ttl' => number_format($claims_ttl, 2),
                'claims_ratio' => number_format($claims_ratio, 2),
                'max_anniv'=>$corp->anniv,
                'corp_id'=> $corp->corp_id
            ];

            array_push($report, $dt);
        }

        return $report;
    }
}
