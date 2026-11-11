<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\ClientAddress;
use App\Models\Corporate\Corporate;
use App\Models\LastNumber;
use App\Models\PremiumInvoicingCorporates;
use App\Models\PremiumQuotationData;
use App\Models\PremiumQuotationRates;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PremiumQuotation extends Controller
{
    public function fetchPremiumQuotationAllCorporates()
    {
        $query1 = PremiumInvoicingCorporates::select('corporate');
        $query2 = Corporate::select('corporate');
        $result = $query1->union($query2)->get();
        return $result;
    }
    public function fetchPremiumQuotationQuotes(Request $request)
    {
        $quotes = PremiumQuotationData::select('quote_id')->where('corporate', $request->corporate)->distinct('quote_id')->get();
        return $quotes;
    }
    public function fetchPremiumQuotationQuoteData(Request $request)
    {
        $quote_data = PremiumQuotationData::where('quote_id', $request->quote_id)->get();
        return $quote_data;
    }



    // public  function premiumQuotationFunction(Request $request)
    // {
    //     // return $request;
    //     //males and females
    //     $quote_data = PremiumQuotationData::where('id', $request->id)->get();
    //     $dt = $quote_data[0];
    //     $males = $dt->males;
    //     $females = $dt->females;
    //     $number = $males + $females;
    //     $start_date = $dt->start_date;
    //     $end_date = $dt->end_date;
    //     $corporate = $dt->corporate;

    //     // $end_date = date("Y-m-d", strtotime("last day of previous month"));
    //     // $start_date = date("Y-m-d", strtotime("$end_date - 1year + 1day"));

    //     //lives
    //     $lives = MemberInfo::select(
    //         DB::raw("count(member_info.member_no) as active_lives")
    //     )
    //         ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
    //         ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
    //         ->join('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
    //         ->join('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
    //         ->where(function ($q) use ($end_date) {
    //             $q->where('member_anniversary.start_date', '<=', $end_date);
    //             $q->where('member_anniversary.end_date', '>=', $end_date);
    //         })
    //         ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
    //         ->whereIn('member_acceptance.status', [1, 2])
    //         ->where('corporate.corporate', 'not like', '%hospital cash%')  //hospital cash
    //         ->where('corporate.corp_id', '!=', 758)  // airtel
    //         ->where(DB::raw("isnull(corporate.enhanced, 0)"), '!=', 4)      // kitanda  
    //         ->get();

    //     $lives = $lives[0]->active_lives;



    //     //get premium quotation rates
    //     $inpatient_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2),amount)"))->where('category', 'inpatient')->where('overall_annual_limit', $dt->uganda_illness)->get();
    //     $inpatient_rate = count($inpatient_rate) != 0 ? $inpatient_rate[0]->amount : 0;
    //     $maternity_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2),amount)"))->where('category', 'maternity')->where('overall_annual_limit', $dt->health_plan_maternity)->get();
    //     $maternity_rate = count($maternity_rate) != 0 ? $maternity_rate[0]->amount : 0;
    //     $outpatient_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2),amount)"))->where('category', 'outpatient')->where('overall_annual_limit', $dt->health_plan_op)->get();
    //     $outpatient_rate = count($outpatient_rate) != 0 ? $outpatient_rate[0]->amount : 0;
    //     $dental_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2),amount)"))->where('category', 'dental')->where('overall_annual_limit', $dt->health_plan_dental)->get();
    //     $dental_rate = count($dental_rate) != 0 ? $dental_rate[0]->amount : 0;
    //     //create quotation no
    //     $quotation_no = LastNumber::select('last_no')->where('task', 7)->get();
    //     $quotation_no = count($quotation_no) !== 0 ? $quotation_no[0]->last_no : 0;
    //     $quotation_no = $quotation_no + 1;
    //     //generate report
    //     $report = Bills::select(

    //         //population data
    //         DB::raw("'AHSU$quotation_no' as quotation_no"),
    //         DB::raw("$number as number"),
    //         DB::raw("$females as females"),
    //         DB::raw("44663 as smart_cost"),
    //         DB::raw("$lives as lives"),
    //         DB::raw("'$start_date' as start_date"),
    //         DB::raw("'$end_date' as end_date"),
    //         DB::raw("CONVERT(NUMERIC(20, 2),(CAST($dt->inflation_discount AS DECIMAL(10, 2)) / 100)) as inflation"),
    //         DB::raw("CONVERT(NUMERIC(20, 2),(CAST($dt->commission_rate AS DECIMAL(10, 2)) / 100)) as commission_rate"),
    //         DB::raw("CONVERT(NUMERIC(20, 2),(CAST($dt->admin_rate AS DECIMAL(10, 2)) / 100)) as admin_rate"),
    //         DB::raw("CONVERT(NUMERIC(20, 2),(CAST($dt->profit_rate AS DECIMAL(10, 2)) / 100)) as profit_rate"),

    //         //visits per benefit category 
    //         DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (27, 28, 31, 43, 46, 78) THEN bills.claim_no END, '')) as illness_visits"),
    //         DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (7, 55, 25, 29, 58, 53, 54) THEN bills.claim_no END, '')) as chronic_ip_visits"),
    //         DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (1, 2, 3, 30, 45) THEN bills.claim_no END, '')) as accident_visits"),
    //         DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (59, 33, 32, 34, 35, 24, 68) THEN bills.claim_no END, '')) as maternity_visits"),
    //         DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (38, 39, 40, 5) THEN bills.claim_no END, '')) as op_visits"),
    //         DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (8, 56, 26, 44, 9, 60) THEN bills.claim_no END, '')) as chronic_op_visits"),
    //         DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (11, 12) THEN bills.claim_no END, '')) as dental_visits"),
    //         DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (83, 36, 57) THEN bills.claim_no END, '')) as optical_visits"),


    //         //total cost per benefit
    //         DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (27, 28, 31, 43, 46, 78) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as illness"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (7, 55, 25, 29, 58,  53, 54) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as chronic_ip"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (1, 2, 3, 30, 45) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as accident"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (59, 33, 32, 34, 35, 24, 68 ) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as maternity"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (38, 39, 40, 5) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as op"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (8, 56, 26, 44, 9, 60) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as chronic_op"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in ( 11, 12) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as dental"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (83, 36, 57) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as optical"),

    //         //average cost per benefit        
    //         DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN illness != 0 and illness_visits != 0 THEN illness / illness_visits ELSE 0 END)) as illness_average_cost"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN chronic_ip != 0 and chronic_ip_visits != 0 THEN chronic_ip / chronic_ip_visits ELSE 0 END)) as chronic_ip_average_cost"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN accident != 0 and accident_visits != 0 THEN accident / accident_visits ELSE 0 END)) as accident_average_cost"),
    //         DB::raw("$dt->maternity_avg as maternity_average_cost"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN op != 0 and op_visits != 0 THEN op / op_visits ELSE 0 END)) as op_average_cost"),
    //         DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN chronic_op != 0 and chronic_op_visits != 0 THEN chronic_op / chronic_op_visits ELSE 0 END)) as chronic_op_average_cost"),
    //         DB::raw("$dt->dental_avg as dental_average_cost"),
    //         DB::raw("$dt->optical as optical_average_cost"),


    //         //incidence rates
    //         DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), illness_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), illness_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as illness_ir"),
    //         DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), chronic_ip_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), chronic_ip_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as chronic_ip_ir"),
    //         DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), accident_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), accident_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as accident_ir"),
    //         DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), maternity_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), maternity_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as maternity_ir"),
    //         DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), op_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), op_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as op_ir"),
    //         DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), chronic_op_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), chronic_op_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as chronic_op_ir"),
    //         DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), dental_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), dental_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as dental_ir"),
    //         DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), optical_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), optical_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as optical_ir"),

    //         //expected cost
    //         DB::raw("CONVERT(NUMERIC(20, 0),($number * (illness_ir / 100) * (illness_average_cost*(1 + inflation)))) as illness_ec"),
    //         DB::raw("CONVERT(NUMERIC(20, 0),($number * (chronic_ip_ir/100) * (chronic_ip_average_cost*(1 + inflation)))) as chronic_ip_ec"),
    //         DB::raw("CONVERT(NUMERIC(20, 0),($number * (accident_ir/100) * (accident_average_cost*(1 + inflation)))) as accident_ec"),
    //         DB::raw("CONVERT(NUMERIC(20, 0),($females * (maternity_ir/100) * (maternity_average_cost*(1 + inflation)))) as maternity_ec"),
    //         DB::raw("CONVERT(NUMERIC(20, 0),($number * (op_ir/100) * (op_average_cost*(1 + inflation)))) as op_ec"),
    //         DB::raw("CONVERT(NUMERIC(20, 0),($number * (chronic_op_ir/100) * (chronic_op_average_cost*(1 + inflation)))) as chronic_op_ec"),
    //         DB::raw("CONVERT(NUMERIC(20, 0),($number * (dental_ir/100) * (dental_average_cost*(1 + inflation)))) as dental_ec"),
    //         DB::raw("CONVERT(NUMERIC(20, 0),($number * (optical_ir/100) * (optical_average_cost*(1 + inflation)))) as optical_ec"),
    //         DB::raw("CONVERT(NUMERIC(20, 0),(illness_ec + chronic_ip_ec + accident_ec + maternity_ec + op_ec + chronic_op_ec + dental_ec + optical_ec )) as total_expected_cost"),

    //         //illness premium calculation 
    //         DB::raw("
    //         CONVERT(
    //             NUMERIC(20, 0),(
    //                 (illness_ec / (100/100)) * (1 + $inpatient_rate)
    //                 )
    //                 ) 
    //                 as illness_premium

    //                 "),

    //         //chronic ip premium calculation
    //         DB::raw("CONVERT(NUMERIC(20, 0),(chronic_ip_ec * (100/100))) as chronic_ip_premium"),

    //         //accident premium calculation
    //         DB::raw("
    //         CONVERT(
    //             NUMERIC(20, 0),(
    //                 (accident_ec / (100/100)) * (1 + $inpatient_rate)
    //                 )
    //                 ) 
    //                 as accident_premium

    //                 "),
    //         //maternity premium calculation
    //         DB::raw("
    //                 CONVERT(
    //                     NUMERIC(20, 0),(
    //                         (maternity_ec / (100/100)) + ($females * $maternity_rate)
    //                         )
    //                         ) 
    //                         as maternity_premium

    //                         "),
    //         //outpatient premium calculation
    //         DB::raw("
    //                 CONVERT(
    //                     NUMERIC(20, 0),(
    //                         (op_ec / (100/100)) * (1 + $outpatient_rate)
    //                         )
    //                         ) 
    //                         as op_premium

    //                         "),

    //         //chronic op premium calculation
    //         DB::raw("CONVERT(NUMERIC(20, 0),((chronic_op_ec / (100/100)) * (1 + 0.05))) as chronic_op_premium"),

    //         //dental premium calculation
    //         DB::raw("
    //                 CONVERT(
    //                     NUMERIC(20, 0),(
    //                         (dental_ec / (100/100)) * (1 + $dental_rate)
    //                         )
    //                         ) 
    //                         as dental_premium

    //                         "),

    //         //optical op premium calculation
    //         DB::raw("CONVERT(NUMERIC(20, 0),(optical_ec * (100/100))) as optical_premium"),

    //         //total premium
    //         DB::raw("CONVERT(NUMERIC(20, 0),(illness_premium + chronic_ip_premium + accident_premium + maternity_premium + op_premium + chronic_op_premium + dental_premium + optical_premium )) as total_premium"),

    //         //premium per life calculation
    //         DB::raw("CONVERT(NUMERIC(20, 0),(total_premium /  $number)) as premium_per_life"),
    //         //risk premium
    //         DB::raw("CONVERT(NUMERIC(20, 0),(premium_per_life * 0.98)) as risk_premium"),
    //         //premium
    //         DB::raw("CONVERT(NUMERIC(20, 0),(risk_premium * 1.015)) as premium"),
    //         //commission premium
    //         DB::raw("CONVERT(NUMERIC(20, 0),(commission_rate * (premium  + smart_cost))) as commission_premium"),
    //         //admin premium
    //         DB::raw("CONVERT(NUMERIC(20, 0),(admin_rate * (premium  + smart_cost))) as admin_premium"),
    //         //profit premium
    //         DB::raw("CONVERT(NUMERIC(20, 0),(profit_rate * (premium  + smart_cost))) as profit_premium"),
    //         //total premium per life
    //         DB::raw("CONVERT(NUMERIC(20, 0),(premium + commission_premium + admin_premium + profit_premium + smart_cost)) as total_premium_per_life "),


    //         //premium percentages
    //         DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (illness_premium / total_premium) * 100 ELSE 0 END) as illness_premium_percentage"),
    //         DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (accident_premium / total_premium) * 100 ELSE 0 END) as accident_premium_percentage"),
    //         DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (maternity_premium / total_premium) * 100 ELSE 0 END) as maternity_premium_percentage"),
    //         DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (op_premium / total_premium) * 100 ELSE 0 END) as op_premium_percentage"),
    //         DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (dental_premium / total_premium) * 100 ELSE 0 END) as dental_premium_percentage"),
    //         DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (optical_premium / total_premium) * 100 ELSE 0 END) as optical_premium_percentage"),


    //         //print pdf info
    //         DB::raw("'$corporate' as corporate"),
    //         DB::raw("CASE WHEN $dt->ug_illness <= 50000000 THEN  'Corporate Comprehensive' WHEN $dt->ug_illness < 100000000 THEN 'Bronze Comprehensive' WHEN $dt->ug_illness < 300000000 THEN 'Silver Comprehensive' ELSE 'Gold' END AS  d4"),
    //         DB::raw("CASE WHEN $dt->ug_illness <= 50000000 THEN  'Uganda Covered' WHEN $dt->ug_illness < 100000000 THEN 'East Africa Covered' ELSE 'World Wide Covered' END AS  d6"),
    //         DB::raw("$dt->ug_illness as uganda_illness"),
    //         DB::raw("($dt->ug_illness / 5) as d14"),
    //         DB::raw("CASE WHEN $number < 20 THEN  'Not Covered' WHEN $number < 100 THEN 'Covered upto CDL Limit within the CDL' ELSE 'Covered upto CDL Limit' END AS  d15"),
    //         DB::raw("CASE WHEN $number < 100 THEN  'Not Covered' WHEN $number > 100 THEN 'Covered upto CDL Limit within the CDL' ELSE 'Covered upto CDL Limit' END AS  d16"),
    //         DB::raw("(25.0/100) * d14 as d17"),
    //         DB::raw("(4.0/100) * $dt->ug_illness as d20"),
    //         DB::raw("(4.0/100) * $dt->ug_illness as d21"),
    //         DB::raw("(4.0/100) * $dt->ug_illness as d22"),
    //         DB::raw("d14 as d27"),
    //         DB::raw("d14 as d30"),
    //         DB::raw("maternity_average_cost + 1000000 + 500000 as d35"),
    //         DB::raw("maternity_average_cost as d36"),
    //         DB::raw("CASE WHEN $number >= 20 THEN 3000000 / 4 ELSE 'Not covered'  END AS  d43"),
    //         DB::raw("CASE WHEN $number >= 100 THEN  'Covered' ELSE 'Not Covered' END AS  d44"),
    //         DB::raw("(25.0 / 100) *  chronic_op_average_cost as d45"),
    //         DB::raw("d43 as d51"),
    //         DB::raw("d51 as d52"),
    //         DB::raw("optical_average_cost as d55"),
    //         DB::raw("dental_average_cost as d59"),
    //         DB::raw("$dt->ug_illness / 10  as d72"),
    //         DB::raw("$dt->ug_illness / 6  as d73"),
    //         DB::raw("CASE WHEN $number <= 9 THEN  '90 days' ELSE 'No waiting periods' END AS  d79"),
    //         DB::raw("CASE WHEN $number < 9 THEN  '12 Months' ELSE 'No waiting periods' END AS  d80"),
    //         DB::raw("CASE WHEN $number <= 19 THEN  '12 Months' ELSE 'No waiting periods' END AS  d81"),
    //         DB::raw("total_premium_per_life * $number as total"),
    //         DB::raw("(0.5 / 100) * total_premium as iiu_levy"),
    //         DB::raw("35000 as stamp_duty"),
    //         DB::raw("total + iiu_levy + stamp_duty as ttl_premium")

    //     )
    //         ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
    //         ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
    //         ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
    //         ->whereBetween('bills.invoice_date', [$start_date, $end_date])
    //         ->where('corporate.corporate', 'not like', '%hospital cash%')  //hospital cash
    //         ->where('corporate.corp_id', '!=', 758)  // airtel
    //         ->where(DB::raw("isnull(corporate.enhanced, 0)"), '!=', 4)      // kitanda  
    //         ->get();

    //     //update quotation no
    //     LastNumber::where('task', 7)->update([
    //         'last_no' => $quotation_no
    //     ]);


    //     return $report;
    // }

    public function premiumQuotationFunction(Request $request)
    {
        // Males and females
        $quote_data = PremiumQuotationData::where('id', $request->id)->get();
        $dt = $quote_data[0];

        $males = $dt->males;
        $females = $dt->females;
        $number = $males + $females;
        $start_date = $dt->start_date;
        $end_date = $dt->end_date;
        $corporate = $dt->corporate;

        // Lives
        $lives = MemberInfo::select(
            DB::raw("count(member_info.member_no) as active_lives")
        )
            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->join('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
            ->join('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
            ->where('member_anniversary.start_date', '<=', $end_date)
            ->where('member_anniversary.end_date', '>=', $end_date)
            ->where(DB::raw("ISNULL(member_info.cancelled, 0)"), 0)
            ->whereIn('member_acceptance.status', [1, 2])
            ->where('corporate.corporate', 'not like', '%hospital cash%')
            ->where('corporate.corp_id', '!=', 758)
            ->where(DB::raw("ISNULL(corporate.enhanced, 0)"), '!=', 4)
            ->get();

        $lives = $lives[0]->active_lives;

        // Get premium quotation rates
        $inpatient_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2), amount) as amount"))
            ->where('category', 'inpatient')
            ->where('overall_annual_limit', $dt->uganda_illness)
            ->get();

        $inpatient_rate = count($inpatient_rate) != 0 ? $inpatient_rate[0]->amount : 0;

        $maternity_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2), amount) as amount"))
            ->where('category', 'maternity')
            ->where('overall_annual_limit', $dt->health_plan_maternity)
            ->get();

        $maternity_rate = count($maternity_rate) != 0 ? $maternity_rate[0]->amount : 0;

        $outpatient_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2), amount) as amount"))
            ->where('category', 'outpatient')
            ->where('overall_annual_limit', $dt->health_plan_op)
            ->get();

        $outpatient_rate = count($outpatient_rate) != 0 ? $outpatient_rate[0]->amount : 0;

        $dental_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2), amount) as amount"))
            ->where('category', 'dental')
            ->where('overall_annual_limit', $dt->health_plan_dental)
            ->get();

        $dental_rate = count($dental_rate) != 0 ? $dental_rate[0]->amount : 0;

        // Create quotation number
        $quotation_no = LastNumber::select('last_no')->where('task', 7)->get();
        $quotation_no = count($quotation_no) !== 0 ? $quotation_no[0]->last_no : 0;
        $quotation_no = $quotation_no + 1;

        // BASE QUERY: Visits, total costs and fixed values
        $base = Bills::select(
            DB::raw("'AHSU$quotation_no' as quotation_no"),
            DB::raw("$number as number"),
            DB::raw("$females as females"),
            DB::raw("44663 as smart_cost"),
            DB::raw("$lives as lives"),
            DB::raw("'$start_date' as start_date"),
            DB::raw("'$end_date' as end_date"),
            DB::raw("CONVERT(NUMERIC(20, 2), (CAST($dt->inflation_discount AS DECIMAL(10, 2)) / 100)) as inflation"),
            DB::raw("CONVERT(NUMERIC(20, 2), (CAST($dt->commission_rate AS DECIMAL(10, 2)) / 100)) as commission_rate"),
            DB::raw("CONVERT(NUMERIC(20, 2), (CAST($dt->admin_rate AS DECIMAL(10, 2)) / 100)) as admin_rate"),
            DB::raw("CONVERT(NUMERIC(20, 2), (CAST($dt->profit_rate AS DECIMAL(10, 2)) / 100)) as profit_rate"),

            // Visits
            DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (27, 28, 31, 43, 46, 78) THEN bills.claim_no END) as illness_visits"),
            DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (7, 55, 25, 29, 58, 53, 54) THEN bills.claim_no END) as chronic_ip_visits"),
            DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (1, 2, 3, 30, 45) THEN bills.claim_no END) as accident_visits"),
            DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (59, 33, 32, 34, 35, 24, 68) THEN bills.claim_no END) as maternity_visits"),
            DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (38, 39, 40, 5) THEN bills.claim_no END) as op_visits"),
            DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (8, 56, 26, 44, 9, 60) THEN bills.claim_no END) as chronic_op_visits"),
            DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (11, 12) THEN bills.claim_no END) as dental_visits"),
            DB::raw("COUNT(DISTINCT CASE WHEN bills.claim_nature IN (83, 36, 57) THEN bills.claim_no END) as optical_visits"),

            // Total cost per benefit
            DB::raw("CONVERT(NUMERIC(20, 0), SUM(CASE WHEN bills.claim_nature IN (27, 28, 31, 43, 46, 78) THEN ISNULL(bills.invoiced_amount, 0) ELSE 0 END)) as illness"),
            DB::raw("CONVERT(NUMERIC(20, 0), SUM(CASE WHEN bills.claim_nature IN (7, 55, 25, 29, 58, 53, 54) THEN ISNULL(bills.invoiced_amount, 0) ELSE 0 END)) as chronic_ip"),
            DB::raw("CONVERT(NUMERIC(20, 0), SUM(CASE WHEN bills.claim_nature IN (1, 2, 3, 30, 45) THEN ISNULL(bills.invoiced_amount, 0) ELSE 0 END)) as accident"),
            DB::raw("CONVERT(NUMERIC(20, 0), SUM(CASE WHEN bills.claim_nature IN (59, 33, 32, 34, 35, 24, 68) THEN ISNULL(bills.invoiced_amount, 0) ELSE 0 END)) as maternity"),
            DB::raw("CONVERT(NUMERIC(20, 0), SUM(CASE WHEN bills.claim_nature IN (38, 39, 40, 5) THEN ISNULL(bills.invoiced_amount, 0) ELSE 0 END)) as op"),
            DB::raw("CONVERT(NUMERIC(20, 0), SUM(CASE WHEN bills.claim_nature IN (8, 56, 26, 44, 9, 60) THEN ISNULL(bills.invoiced_amount, 0) ELSE 0 END)) as chronic_op"),
            DB::raw("CONVERT(NUMERIC(20, 0), SUM(CASE WHEN bills.claim_nature IN (11, 12) THEN ISNULL(bills.invoiced_amount, 0) ELSE 0 END)) as dental"),
            DB::raw("CONVERT(NUMERIC(20, 0), SUM(CASE WHEN bills.claim_nature IN (83, 36, 57) THEN ISNULL(bills.invoiced_amount, 0) ELSE 0 END)) as optical"),

            // Print information that does not depend on calculated aliases
            DB::raw("'$corporate' as corporate"),
            DB::raw("CASE WHEN $dt->ug_illness <= 50000000 THEN 'Corporate Comprehensive' WHEN $dt->ug_illness < 100000000 THEN 'Bronze Comprehensive' WHEN $dt->ug_illness < 300000000 THEN 'Silver Comprehensive' ELSE 'Gold' END as d4"),
            DB::raw("CASE WHEN $dt->ug_illness <= 50000000 THEN 'Uganda Covered' WHEN $dt->ug_illness < 100000000 THEN 'East Africa Covered' ELSE 'World Wide Covered' END as d6"),
            DB::raw("$dt->ug_illness as uganda_illness"),
            DB::raw("($dt->ug_illness / 5) as d14"),
            DB::raw("CASE WHEN $number < 20 THEN 'Not Covered' WHEN $number < 100 THEN 'Covered upto CDL Limit within the CDL' ELSE 'Covered upto CDL Limit' END as d15"),
            DB::raw("CASE WHEN $number < 100 THEN 'Not Covered' WHEN $number > 100 THEN 'Covered upto CDL Limit within the CDL' ELSE 'Covered upto CDL Limit' END as d16"),
            DB::raw("(25.0 / 100) * ($dt->ug_illness / 5) as d17"),
            DB::raw("(4.0 / 100) * $dt->ug_illness as d20"),
            DB::raw("(4.0 / 100) * $dt->ug_illness as d21"),
            DB::raw("(4.0 / 100) * $dt->ug_illness as d22"),
            DB::raw("($dt->ug_illness / 5) as d27"),
            DB::raw("($dt->ug_illness / 5) as d30"),
            DB::raw("$dt->maternity_avg + 1000000 + 500000 as d35"),
            DB::raw("$dt->maternity_avg as d36"),
            DB::raw("CASE WHEN $number >= 20 THEN 3000000 / 4 ELSE 'Not covered' END as d43"),
            DB::raw("CASE WHEN $number >= 100 THEN 'Covered' ELSE 'Not Covered' END as d44"),
            DB::raw("(25.0 / 100) * $dt->optical as d45"),
            DB::raw("$dt->optical as d55"),
            DB::raw("$dt->dental_avg as d59"),
            DB::raw("$dt->ug_illness / 10 as d72"),
            DB::raw("$dt->ug_illness / 6 as d73"),
            DB::raw("CASE WHEN $number <= 9 THEN '90 days' ELSE 'No waiting periods' END as d79"),
            DB::raw("CASE WHEN $number < 9 THEN '12 Months' ELSE 'No waiting periods' END as d80"),
            DB::raw("CASE WHEN $number <= 19 THEN '12 Months' ELSE 'No waiting periods' END as d81")
        )
            ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->whereBetween('bills.invoice_date', [$start_date, $end_date])
            ->where('corporate.corporate', 'not like', '%hospital cash%')
            ->where('corporate.corp_id', '!=', 758)
            ->where(DB::raw("ISNULL(corporate.enhanced, 0)"), '!=', 4);

        // LEVEL 1: Average costs and incidence rates
        $level1 = DB::query()->fromSub($base, 'q')->select(
            'q.*',

            DB::raw("CONVERT(NUMERIC(20, 0), CASE WHEN q.illness != 0 AND q.illness_visits != 0 THEN q.illness / q.illness_visits ELSE 0 END) as illness_average_cost"),
            DB::raw("CONVERT(NUMERIC(20, 0), CASE WHEN q.chronic_ip != 0 AND q.chronic_ip_visits != 0 THEN q.chronic_ip / q.chronic_ip_visits ELSE 0 END) as chronic_ip_average_cost"),
            DB::raw("CONVERT(NUMERIC(20, 0), CASE WHEN q.accident != 0 AND q.accident_visits != 0 THEN q.accident / q.accident_visits ELSE 0 END) as accident_average_cost"),
            DB::raw("$dt->maternity_avg as maternity_average_cost"),
            DB::raw("CONVERT(NUMERIC(20, 0), CASE WHEN q.op != 0 AND q.op_visits != 0 THEN q.op / q.op_visits ELSE 0 END) as op_average_cost"),
            DB::raw("CONVERT(NUMERIC(20, 0), CASE WHEN q.chronic_op != 0 AND q.chronic_op_visits != 0 THEN q.chronic_op / q.chronic_op_visits ELSE 0 END) as chronic_op_average_cost"),
            DB::raw("$dt->dental_avg as dental_average_cost"),
            DB::raw("$dt->optical as optical_average_cost"),

            DB::raw("CASE WHEN q.illness_visits > 0 THEN CONVERT(NUMERIC(20, 2), q.illness_visits / NULLIF(CONVERT(NUMERIC(20, 2), q.lives), 0) * 100) ELSE 0 END as illness_ir"),
            DB::raw("CASE WHEN q.chronic_ip_visits > 0 THEN CONVERT(NUMERIC(20, 2), q.chronic_ip_visits / NULLIF(CONVERT(NUMERIC(20, 2), q.lives), 0) * 100) ELSE 0 END as chronic_ip_ir"),
            DB::raw("CASE WHEN q.accident_visits > 0 THEN CONVERT(NUMERIC(20, 2), q.accident_visits / NULLIF(CONVERT(NUMERIC(20, 2), q.lives), 0) * 100) ELSE 0 END as accident_ir"),
            DB::raw("CASE WHEN q.maternity_visits > 0 THEN CONVERT(NUMERIC(20, 2), q.maternity_visits / NULLIF(CONVERT(NUMERIC(20, 2), q.lives), 0) * 100) ELSE 0 END as maternity_ir"),
            DB::raw("CASE WHEN q.op_visits > 0 THEN CONVERT(NUMERIC(20, 2), q.op_visits / NULLIF(CONVERT(NUMERIC(20, 2), q.lives), 0) * 100) ELSE 0 END as op_ir"),
            DB::raw("CASE WHEN q.chronic_op_visits > 0 THEN CONVERT(NUMERIC(20, 2), q.chronic_op_visits / NULLIF(CONVERT(NUMERIC(20, 2), q.lives), 0) * 100) ELSE 0 END as chronic_op_ir"),
            DB::raw("CASE WHEN q.dental_visits > 0 THEN CONVERT(NUMERIC(20, 2), q.dental_visits / NULLIF(CONVERT(NUMERIC(20, 2), q.lives), 0) * 100) ELSE 0 END as dental_ir"),
            DB::raw("CASE WHEN q.optical_visits > 0 THEN CONVERT(NUMERIC(20, 2), q.optical_visits / NULLIF(CONVERT(NUMERIC(20, 2), q.lives), 0) * 100) ELSE 0 END as optical_ir")
        );

        // LEVEL 2: Expected costs
        $level2 = DB::query()->fromSub($level1, 'q')->select(
            'q.*',
            DB::raw("CONVERT(NUMERIC(20, 0), ($number * (q.illness_ir / 100) * (q.illness_average_cost * (1 + q.inflation)))) as illness_ec"),
            DB::raw("CONVERT(NUMERIC(20, 0), ($number * (q.chronic_ip_ir / 100) * (q.chronic_ip_average_cost * (1 + q.inflation)))) as chronic_ip_ec"),
            DB::raw("CONVERT(NUMERIC(20, 0), ($number * (q.accident_ir / 100) * (q.accident_average_cost * (1 + q.inflation)))) as accident_ec"),
            DB::raw("CONVERT(NUMERIC(20, 0), ($females * (q.maternity_ir / 100) * (q.maternity_average_cost * (1 + q.inflation)))) as maternity_ec"),
            DB::raw("CONVERT(NUMERIC(20, 0), ($number * (q.op_ir / 100) * (q.op_average_cost * (1 + q.inflation)))) as op_ec"),
            DB::raw("CONVERT(NUMERIC(20, 0), ($number * (q.chronic_op_ir / 100) * (q.chronic_op_average_cost * (1 + q.inflation)))) as chronic_op_ec"),
            DB::raw("CONVERT(NUMERIC(20, 0), ($number * (q.dental_ir / 100) * (q.dental_average_cost * (1 + q.inflation)))) as dental_ec"),
            DB::raw("CONVERT(NUMERIC(20, 0), ($number * (q.optical_ir / 100) * (q.optical_average_cost * (1 + q.inflation)))) as optical_ec")
        );

        // LEVEL 3: Premium calculations
        $level3 = DB::query()->fromSub($level2, 'q')->select(
            'q.*',
            DB::raw("CONVERT(NUMERIC(20, 0), q.illness_ec + q.chronic_ip_ec + q.accident_ec + q.maternity_ec + q.op_ec + q.chronic_op_ec + q.dental_ec + q.optical_ec) as total_expected_cost"),

            DB::raw("CONVERT(NUMERIC(20, 0), (q.illness_ec / (100 / 100)) * (1 + $inpatient_rate)) as illness_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), q.chronic_ip_ec * (100 / 100)) as chronic_ip_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), (q.accident_ec / (100 / 100)) * (1 + $inpatient_rate)) as accident_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), (q.maternity_ec / (100 / 100)) + ($females * $maternity_rate)) as maternity_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), (q.op_ec / (100 / 100)) * (1 + $outpatient_rate)) as op_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), (q.chronic_op_ec / (100 / 100)) * (1 + 0.05)) as chronic_op_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), (q.dental_ec / (100 / 100)) * (1 + $dental_rate)) as dental_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), q.optical_ec * (100 / 100)) as optical_premium")
        );

        // LEVEL 4: Total premium and premium percentages
        $level4 = DB::query()->fromSub($level3, 'q')->select(
            'q.*',
            DB::raw("CONVERT(NUMERIC(20, 0), q.illness_premium + q.chronic_ip_premium + q.accident_premium + q.maternity_premium + q.op_premium + q.chronic_op_premium + q.dental_premium + q.optical_premium) as total_premium")
        );

        // LEVEL 5: Remaining premium calculations
        $level5 = DB::query()->fromSub($level4, 'q')->select(
            'q.*',
            DB::raw("CONVERT(NUMERIC(20, 0), q.total_premium / NULLIF($number, 0)) as premium_per_life"),
            DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN q.total_premium != 0 THEN (q.illness_premium / q.total_premium) * 100 ELSE 0 END) as illness_premium_percentage"),
            DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN q.total_premium != 0 THEN (q.accident_premium / q.total_premium) * 100 ELSE 0 END) as accident_premium_percentage"),
            DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN q.total_premium != 0 THEN (q.maternity_premium / q.total_premium) * 100 ELSE 0 END) as maternity_premium_percentage"),
            DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN q.total_premium != 0 THEN (q.op_premium / q.total_premium) * 100 ELSE 0 END) as op_premium_percentage"),
            DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN q.total_premium != 0 THEN (q.dental_premium / q.total_premium) * 100 ELSE 0 END) as dental_premium_percentage"),
            DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN q.total_premium != 0 THEN (q.optical_premium / q.total_premium) * 100 ELSE 0 END) as optical_premium_percentage")
        );

        // LEVEL 6: Risk premium, premium, commission, administration and profit
        $level6 = DB::query()->fromSub($level5, 'q')->select(
            'q.*',
            DB::raw("CONVERT(NUMERIC(20, 0), q.premium_per_life * 0.98) as risk_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), (q.premium_per_life * 0.98) * 1.015) as premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), q.commission_rate * ((q.premium_per_life * 0.98) * 1.015 + q.smart_cost)) as commission_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), q.admin_rate * ((q.premium_per_life * 0.98) * 1.015 + q.smart_cost)) as admin_premium"),
            DB::raw("CONVERT(NUMERIC(20, 0), q.profit_rate * ((q.premium_per_life * 0.98) * 1.015 + q.smart_cost)) as profit_premium")
        );

        // LEVEL 7: Final totals
        $level7 = DB::query()->fromSub($level6, 'q')->select(
            'q.*',
            DB::raw("CONVERT(NUMERIC(20, 0), q.premium + q.commission_premium + q.admin_premium + q.profit_premium + q.smart_cost) as total_premium_per_life")
        );

        $level8 = DB::query()->fromSub($level7, 'q')->select(
            'q.*',
            DB::raw("q.total_premium_per_life * $number as total"),
            DB::raw("(0.5 / 100) * q.total_premium as iiu_levy"),
            DB::raw("35000 as stamp_duty")
        );

        $report = DB::query()->fromSub($level8, 'q')->select(
            'q.*',
            DB::raw("q.total + q.iiu_levy + q.stamp_duty as ttl_premium"),
            DB::raw("q.d43 as d51"),
            DB::raw("q.d43 as d52")
        )->get();

        // Update quotation number
        LastNumber::where('task', 7)->update([
            'last_no' => $quotation_no
        ]);

        return $report;
    }

    public  function premiumQuotationAllProductsFunction(Request $request)
    {

        // return $request;
        $data = [];
        $quote_data = PremiumQuotationData::where('quote_id', $request->quote_id)->get();

        //males and females
        foreach ($quote_data as $qd) {
            $males = $qd->males;
            $females = $qd->females;
            $number = $males + $females;
            $start_date = $qd->start_date;
            $end_date = $qd->end_date;
            $corporate = $qd->corporate;



            //lives
            $lives = MemberInfo::select(
                DB::raw("count(member_info.member_no) as active_lives")
            )
                ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                ->join('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                ->join('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
                ->where(function ($q) use ($end_date) {
                    $q->where('member_anniversary.start_date', '<=', $end_date);
                    $q->where('member_anniversary.end_date', '>=', $end_date);
                })
                ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                ->whereIn('member_acceptance.status', [1, 2])
                ->where('corporate.corporate', 'not like', '%hospital cash%')  //hospital cash
                ->where('corporate.corp_id', '!=', 758)  // airtel
                ->where(DB::raw("isnull(corporate.enhanced, 0)"), '!=', 4)      // kitanda  
                ->get();

            $lives = $lives[0]->active_lives;



            //get premium quotation rates
            $inpatient_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2),amount)"))->where('category', 'inpatient')->where('overall_annual_limit', $qd->uganda_illness)->get();
            $inpatient_rate = count($inpatient_rate) != 0 ? $inpatient_rate[0]->amount : 0;
            $maternity_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2),amount)"))->where('category', 'maternity')->where('overall_annual_limit', $qd->health_plan_maternity)->get();
            $maternity_rate = count($maternity_rate) != 0 ? $maternity_rate[0]->amount : 0;
            $outpatient_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2),amount)"))->where('category', 'outpatient')->where('overall_annual_limit', $qd->health_plan_op)->get();
            $outpatient_rate = count($outpatient_rate) != 0 ? $outpatient_rate[0]->amount : 0;
            $dental_rate = PremiumQuotationRates::select(DB::raw("CONVERT(NUMERIC(20, 2),amount)"))->where('category', 'dental')->where('overall_annual_limit', $qd->health_plan_dental)->get();
            $dental_rate = count($dental_rate) != 0 ? $dental_rate[0]->amount : 0;
            //create quotation no
            $quotation_no = LastNumber::select('last_no')->where('task', 7)->get();
            $quotation_no = count($quotation_no) !== 0 ? $quotation_no[0]->last_no : 0;
            $quotation_no = $quotation_no + 1;
            //generate report
            $report = Bills::select(

                //population data
                DB::raw("'AHSU$quotation_no' as quotation_no"),
                DB::raw("$number as number"),
                DB::raw("$females as females"),
                DB::raw("44663 as smart_cost"),
                DB::raw("$lives as lives"),
                DB::raw("'$start_date' as start_date"),
                DB::raw("'$end_date' as end_date"),
                DB::raw("CONVERT(NUMERIC(20, 2),(CAST($qd->inflation_discount AS DECIMAL(10, 2)) / 100)) as inflation"),
                DB::raw("CONVERT(NUMERIC(20, 2),(CAST($qd->commission_rate AS DECIMAL(10, 2)) / 100)) as commission_rate"),
                DB::raw("CONVERT(NUMERIC(20, 2),(CAST($qd->admin_rate AS DECIMAL(10, 2)) / 100)) as admin_rate"),
                DB::raw("CONVERT(NUMERIC(20, 2),(CAST($qd->profit_rate AS DECIMAL(10, 2)) / 100)) as profit_rate"),

                //visits per benefit category
                // DB::raw("5000000 as illness_visits"),
                // DB::raw("5000000 as chronic_ip_visits"),

                DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (27, 28, 31, 43, 46, 78) THEN bills.claim_no END, '')) as illness_visits"),
                DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (7, 55, 25, 29, 58, 53, 54) THEN bills.claim_no END, '')) as chronic_ip_visits"),
                DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (1, 2, 3, 30, 45) THEN bills.claim_no END, '')) as accident_visits"),
                DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (59, 33, 32, 34, 35, 24, 68) THEN bills.claim_no END, '')) as maternity_visits"),
                DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (38, 39, 40, 5) THEN bills.claim_no END, '')) as op_visits"),
                DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (8, 56, 26, 44, 9, 60) THEN bills.claim_no END, '')) as chronic_op_visits"),
                DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (11, 12) THEN bills.claim_no END, '')) as dental_visits"),
                DB::raw("COUNT(DISTINCT COALESCE(CASE WHEN bills.claim_nature IN (83, 36, 57) THEN bills.claim_no END, '')) as optical_visits"),


                //total cost per benefit
                DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (27, 28, 31, 43, 46, 78) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as illness"),
                DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (7, 55, 25, 29, 58,  53, 54) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as chronic_ip"),
                DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (1, 2, 3, 30, 45) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as accident"),
                DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (59, 33, 32, 34, 35, 24, 68 ) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as maternity"),
                DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (38, 39, 40, 5) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as op"),
                DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (8, 56, 26, 44, 9, 60) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as chronic_op"),
                DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in ( 11, 12) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as dental"),
                DB::raw("CONVERT(NUMERIC(20, 0), (sum(CASE WHEN bills.claim_nature in (83, 36, 57) THEN isnull(bills.invoiced_amount, 0) ELSE 0 END))) as optical"),

                //average cost per benefit        
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN illness != 0 and illness_visits != 0 THEN illness / illness_visits ELSE 0 END)) as illness_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN chronic_ip != 0 and chronic_ip_visits != 0 THEN chronic_ip / chronic_ip_visits ELSE 0 END)) as chronic_ip_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN accident != 0 and accident_visits != 0 THEN accident / accident_visits ELSE 0 END)) as accident_average_cost"),
                // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN maternity != 0 and maternity_visits != 0 THEN maternity / maternity_visits ELSE 0 END)) as maternity_average_cost"),
                DB::raw("$qd->maternity_avg as maternity_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN op != 0 and op_visits != 0 THEN op / op_visits ELSE 0 END)) as op_average_cost"),
                DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN chronic_op != 0 and chronic_op_visits != 0 THEN chronic_op / chronic_op_visits ELSE 0 END)) as chronic_op_average_cost"),
                // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN dental != 0 and dental_visits != 0 THEN dental / dental_visits ELSE 0 END)) as dental_average_cost"),
                DB::raw("$qd->dental_avg as dental_average_cost"),
                // DB::raw("CONVERT(NUMERIC(20, 0), (CASE WHEN optical != 0 and optical_visits != 0 THEN optical / optical_visits ELSE 0 END)) as optical_average_cost"),
                DB::raw("$qd->optical as optical_average_cost"),


                //incidence rates
                DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), illness_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), illness_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as illness_ir"),
                DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), chronic_ip_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), chronic_ip_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as chronic_ip_ir"),
                DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), accident_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), accident_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as accident_ir"),
                DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), maternity_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), maternity_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as maternity_ir"),
                DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), op_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), op_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as op_ir"),
                DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), chronic_op_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), chronic_op_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as chronic_op_ir"),
                DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), dental_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), dental_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as dental_ir"),
                DB::raw("(CASE WHEN CONVERT(NUMERIC(20, 0), optical_visits) > 0 THEN CONVERT(NUMERIC(20, 2),(CONVERT(NUMERIC(20, 2), optical_visits) / CONVERT(NUMERIC(20, 2), $lives) * 100)) ELSE 0 END) as optical_ir"),

                //expected cost
                DB::raw("CONVERT(NUMERIC(20, 0),($number * (illness_ir / 100) * (illness_average_cost*(1 + inflation)))) as illness_ec"),
                DB::raw("CONVERT(NUMERIC(20, 0),($number * (chronic_ip_ir/100) * (chronic_ip_average_cost*(1 + inflation)))) as chronic_ip_ec"),
                DB::raw("CONVERT(NUMERIC(20, 0),($number * (accident_ir/100) * (accident_average_cost*(1 + inflation)))) as accident_ec"),
                DB::raw("CONVERT(NUMERIC(20, 0),($females * (maternity_ir/100) * (maternity_average_cost*(1 + inflation)))) as maternity_ec"),
                DB::raw("CONVERT(NUMERIC(20, 0),($number * (op_ir/100) * (op_average_cost*(1 + inflation)))) as op_ec"),
                DB::raw("CONVERT(NUMERIC(20, 0),($number * (chronic_op_ir/100) * (chronic_op_average_cost*(1 + inflation)))) as chronic_op_ec"),
                DB::raw("CONVERT(NUMERIC(20, 0),($number * (dental_ir/100) * (dental_average_cost*(1 + inflation)))) as dental_ec"),
                DB::raw("CONVERT(NUMERIC(20, 0),($number * (optical_ir/100) * (optical_average_cost*(1 + inflation)))) as optical_ec"),
                DB::raw("CONVERT(NUMERIC(20, 0),(illness_ec + chronic_ip_ec + accident_ec + maternity_ec + op_ec + chronic_op_ec + dental_ec + optical_ec )) as total_expected_cost"),

                //illness premium calculation 
                DB::raw("
            CONVERT(
                NUMERIC(20, 0),(
                    (illness_ec / (100/100)) * (1 + $inpatient_rate)
                    )
                    ) 
                    as illness_premium
                    
                    "),

                //chronic ip premium calculation
                DB::raw("CONVERT(NUMERIC(20, 0),(chronic_ip_ec * (100/100))) as chronic_ip_premium"),

                //accident premium calculation
                DB::raw("
            CONVERT(
                NUMERIC(20, 0),(
                    (accident_ec / (100/100)) * (1 + $inpatient_rate)
                    )
                    ) 
                    as accident_premium
                        
                    "),
                //maternity premium calculation
                DB::raw("
                    CONVERT(
                        NUMERIC(20, 0),(
                            (maternity_ec / (100/100)) + ($females * $maternity_rate)
                            )
                            ) 
                            as maternity_premium
                            
                            "),
                //outpatient premium calculation
                DB::raw("
                    CONVERT(
                        NUMERIC(20, 0),(
                            (op_ec / (100/100)) * (1 + $outpatient_rate)
                            )
                            ) 
                            as op_premium
                            
                            "),

                //chronic op premium calculation
                DB::raw("CONVERT(NUMERIC(20, 0),((chronic_op_ec / (100/100)) * (1 + 0.05))) as chronic_op_premium"),

                //dental premium calculation
                DB::raw("
                    CONVERT(
                        NUMERIC(20, 0),(
                            (dental_ec / (100/100)) * (1 + $dental_rate)
                            )
                            ) 
                            as dental_premium
                            
                            "),

                //optical op premium calculation
                DB::raw("CONVERT(NUMERIC(20, 0),(optical_ec * (100/100))) as optical_premium"),

                //total premium
                DB::raw("CONVERT(NUMERIC(20, 0),(illness_premium + chronic_ip_premium + accident_premium + maternity_premium + op_premium + chronic_op_premium + dental_premium + optical_premium )) as total_premium"),

                //premium per life calculation
                DB::raw("CONVERT(NUMERIC(20, 0),(total_premium /  $number)) as premium_per_life"),
                //risk premium
                DB::raw("CONVERT(NUMERIC(20, 0),(premium_per_life * 0.98)) as risk_premium"),
                //premium
                DB::raw("CONVERT(NUMERIC(20, 0),(risk_premium * 1.015)) as premium"),
                //commission premium
                DB::raw("CONVERT(NUMERIC(20, 0),(commission_rate * (premium  + smart_cost))) as commission_premium"),
                //admin premium
                DB::raw("CONVERT(NUMERIC(20, 0),(admin_rate * (premium  + smart_cost))) as admin_premium"),
                //profit premium
                DB::raw("CONVERT(NUMERIC(20, 0),(profit_rate * (premium  + smart_cost))) as profit_premium"),
                //total premium per life
                DB::raw("CONVERT(NUMERIC(20, 0),(premium + commission_premium + admin_premium + profit_premium + smart_cost)) as total_premium_per_life "),

                DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (illness_premium / total_premium) * 100 ELSE 0 END) as illness_premium_percentage"),
                DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (accident_premium / total_premium) * 100 ELSE 0 END) as accident_premium_percentage"),
                DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (maternity_premium / total_premium) * 100 ELSE 0 END) as maternity_premium_percentage"),
                DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (op_premium / total_premium) * 100 ELSE 0 END) as op_premium_percentage"),
                DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (dental_premium / total_premium) * 100 ELSE 0 END) as dental_premium_percentage"),
                DB::raw("CONVERT(NUMERIC(20, 2), CASE WHEN total_premium != 0 THEN (optical_premium / total_premium) * 100 ELSE 0 END) as optical_premium_percentage"),


                //print pdf info
                DB::raw("'$corporate' as corporate"),
                DB::raw("CASE WHEN $qd->ug_illness <= 50000000 THEN  'Corporate Comprehensive' WHEN $qd->ug_illness < 100000000 THEN 'Bronze Comprehensive' WHEN $qd->ug_illness < 300000000 THEN 'Silver Comprehensive' ELSE 'Gold' END AS  d4"),
                DB::raw("CASE WHEN $qd->ug_illness <= 50000000 THEN  'Uganda Covered' WHEN $qd->ug_illness < 100000000 THEN 'East Africa Covered' ELSE 'World Wide Covered' END AS  d6"),
                DB::raw("$qd->ug_illness as uganda_illness"),
                DB::raw("($qd->ug_illness / 5) as d14"),
                DB::raw("CASE WHEN $number < 20 THEN  'Not Covered' WHEN $number < 100 THEN 'Covered upto CDL Limit within the CDL' ELSE 'Covered upto CDL Limit' END AS  d15"),
                DB::raw("CASE WHEN $number < 100 THEN  'Not Covered' WHEN $number > 100 THEN 'Covered upto CDL Limit within the CDL' ELSE 'Covered upto CDL Limit' END AS  d16"),
                DB::raw("(25.0/100) * d14 as d17"),
                DB::raw("(4.0/100) * $qd->ug_illness as d20"),
                DB::raw("(4.0/100) * $qd->ug_illness as d21"),
                DB::raw("(4.0/100) * $qd->ug_illness as d22"),
                DB::raw("d14 as d27"),
                DB::raw("d14 as d30"),
                DB::raw("maternity_average_cost + 1000000 + 500000 as d35"),
                DB::raw("maternity_average_cost as d36"),
                DB::raw("CASE WHEN $number >= 20 THEN 3000000 / 4 ELSE 'Not covered'  END AS  d43"),
                DB::raw("CASE WHEN $number >= 100 THEN  'Covered' ELSE 'Not Covered' END AS  d44"),
                DB::raw("(25.0 / 100) *  chronic_op_average_cost as d45"),
                DB::raw("d43 as d51"),
                DB::raw("d51 as d52"),
                DB::raw("optical_average_cost as d55"),
                DB::raw("dental_average_cost as d59"),
                DB::raw("$qd->ug_illness / 10  as d72"),
                DB::raw("$qd->ug_illness / 6  as d73"),
                DB::raw("CASE WHEN $number <= 9 THEN  '90 days' ELSE 'No waiting periods' END AS  d79"),
                DB::raw("CASE WHEN $number < 9 THEN  '12 Months' ELSE 'No waiting periods' END AS  d80"),
                DB::raw("CASE WHEN $number <= 19 THEN  '12 Months' ELSE 'No waiting periods' END AS  d81"),
                DB::raw("total_premium_per_life * $number as total"),
                DB::raw("(0.5 / 100) * total_premium as iiu_levy"),
                DB::raw("35000 as stamp_duty"),
                DB::raw("total + iiu_levy + stamp_duty as ttl_premium")

            )
                ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                ->whereBetween('bills.invoice_date', [$start_date, $end_date])
                ->where('corporate.corporate', 'not like', '%hospital cash%')  //hospital cash
                ->where('corporate.corp_id', '!=', 758)  // airtel
                ->where(DB::raw("isnull(corporate.enhanced, 0)"), '!=', 4)      // kitanda  
                ->get();

            array_push($data, $report);

            //update quotation no
            LastNumber::where('task', 7)->update([
                'last_no' => $quotation_no
            ]);
        }

        // return $data;
        return $this->createTable($data);
    }


    private function createTable($data)
    {
        $address = ClientAddress::where('code', 4)->get();
        $address = $address[0];
        $page_header = '
                        <ul style="list-style-type: none">
                        <li>' . $address->client_name . '</li>
                        <li>' . $address->physical_location . '</li>
                        <li>' . $address->box_no . '</li>
                        <li>' . $address->tel_cell . '</li>
                        <li>' . $address->fax . '</li>
                        <li>' . $address->email . '</li>
                        <li>' . $address->url . '</li>
                        </ul>
                        ';

        $doc = '
        <div style="height:1122px;"
            <p style="text-align:center;"><img src="' . $this->getImage(asset('images/quotation/1.png')) . '" width="790"></p><p style="text-align:center;">PROPOSAL TO PROVIDE</p>
            <p style="text-align:center;">MEDICAL INSURANCE SERVICES TO</p>
            <p style="text-align:center;">' . $data[0][0]->corporate . '</p>
            <p style="text-align:center;">' . $page_header . '</p>      
            <p style="text-align:center;">Quotation No - ' . $data[0][0]->quotation_no . '</p>  
        </div>
        <div style="height: 1122px;">
            <p><img src="' . $this->getImage(asset('images/quotation/2.png'))    . '" style="width: 800px; height: 1122px;"></p>
        </div>
        <div style="height: 1122px;">
            <p><img src="' . $this->getImage(asset('images/quotation/3.png'))    . '" style="width: 800px; height: 1122px;"></p>
        </div>        
        <table style="margin-left:10px;">
            <tbody>
                    <tr><td style="width:1000px;" colspan="' . (count($data)) . '">Health Plans</td><td style="width:310px;">' . $data[0][0]->d4 . '</td></tr>
                    <tr><td style="width:1000px;" colspan="' . (count($data)) . '">No. Lives</td><td style="width:310px;">' . number_format($data[0][0]->number ?? 0) . '</td></tr>
                    <tr><td style="width:1000px;" colspan="' . (count($data)) . '">Geographical Zone</td><td style="width:310px;">' . $data[0][0]->d6 . '</td></tr>                 
            </tbody>
        </table>   
        <table style="width:300px;margin-left:10px;">
            <tbody>
                <tr>
                    <td rowspan=2 style="background-color:#C04000;color:white;text-align:center;width:270px;">Inpatient Benefits (Preauthorization required)</td>';

        foreach ($data as $key => $value) {
            $key++;
            $doc .= '<td style="background-color:#C04000;color:white;text-align:center;width:150px;">option' . $key  . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr><td style="background-color:#C04000;color:white;text-align:center;width:150px;" colspan="' . (count($data)) . '">COVERAGE/LIMIT</td></tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Overall Annual Limit (OAL) per person</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->ug_illnesses ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Illness non-chronic/Accident hospitalization limit</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered upto OAL</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Admissions to Intensive Care & high care units</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered Upto overall Limit of the respective benefit Limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Hospital Room Limit Per Night</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td></td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Preexisting/Chronic conditions developed on cover; within Chronic Disease List (CDL) Limit</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d14 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Oncology tests, drugs & consultation, Chemotherapy and radiotherapy</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered upto CDL Limit within the CDL</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Treatment for COVID -19 and related co-morbidities</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d17 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Nursing fees, medical expenses and ancillary charges</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit of the respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">General Surgery, Surgeons’, consultants’, anaesthetists’, medical practitioners’ fees</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit of the respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Organ transplant; excuding donor fees</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered upto CDL Limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Gynecological Surgery (excluding Infertility treatment)</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td></td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Reconstructive surgery</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td></td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Inpatient Opthamology</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d21 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Inpatient Dentistry</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d22 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Prescribed medicines and drugs</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit of the respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Internal Prostheses</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit of the respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">X-rays, MRI, PET Scans, CT scans and imaging tests including angiography (Covered upon Doctor\'s recommendation)</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit of the respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Pathology, diagnostic tests & procedures</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit of the respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td style="width:150px;">Psychiatric treatment</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d27 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                <td style="width:150px;">Physiotherapy</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the Respective Benefit Limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                <td style="width:150px;">Parent Accommodation, member parent with a member child under 18 years of age in hospital</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered upto Room Limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                <td style="width:150px;">Congenital and genetic conditions defects</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d30 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Inpatient treatment of HIV/AIDS and all opportunistic infections</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered upto CDL Limit</td>';
        }

        $doc .= '</tr>';

        $doc .= ' <tr ><td style="background-color:#C04000;color:white;text-align:center;">Rescue And Evacuation</td><td colspan="' . (count($data)) . '" style="background-color:#C04000;color:white;text-align:center;">COVERAGE/LIMIT</td></tr>';
        $doc .= '<tr>
            <td style="width:150px;">Emergency road ambulance and  Evacuation</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit of the respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr style="background-color:#C04000"><td style="background-color:#C04000;color:white;text-align:center;">Maternity Cover</td><td colspan="' . (count($data)) . '" style="background-color:#C04000;color:white;text-align:center;">COVERAGE/LIMIT</td></tr>';
        $doc .= '<tr>
            <td style="width:150px;">Over All Maternity Limit (OML); Limited to principal or spouse only, Inclusive of Room Limit, available in Uganda only</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d35 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Delivery and Postnatal Treatment; Within (OML)</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d36 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Premature Cover; Within (OML) Limit</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td></td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">New Born Baby Care</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within Delivery Limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Complications during pregnancy and with in 6 weeks after delivery</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within Delivery Limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr style="background-color:#C04000"><td style="background-color:#C04000;color:white;text-align:center;">Out-patient Benefits (within Uganda)</td><td colspan="' . (count($data)) . '"   style="background-color:#C04000;color:white;text-align:center;">COVERAGE/LIMIT</td></tr>';
        $doc .= '<tr>
            <td style="width:150px;">Overall Annual Outpatient Limit per person (OAOL)</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td></td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Antenatal (with in OAOL)</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td></td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Preexisting/ Chronic conditions as per Chronic Disease List (CDL) Within Over All Outpitient Limit</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d43 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Oncology tests, drugs & consultation, Chemotherapy and radiotherapy</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit of the respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Treatment for COVID -19 and related co-morbidities; (Within OAOL)</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d45 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">General Practitioners and Specialists\' Consultation fees</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit ofthe respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Prescribed medicines, drugs and dressings</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit ofthe respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Physiotherapy</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the Respective Benefit Limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Post Hospitalisation treatment</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit ofthe respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Out-patient surgical operations</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered withing overall limit ofthe respective benefit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Psychiatric Treatment</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d51 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Congenital and genetic conditions defects</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d52 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Outpatient treatment of HIV/AIDS and all opportunistic infections</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered  Upto  CDL Limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr style="background-color:#C04000"><td style="background-color:#C04000;color:white;text-align:center;">OPTOMETRY  COVER</td><td colspan="' . (count($data)) . '" style="background-color:#C04000;color:white;text-align:center;">COVERAGE/LIMIT</td></tr>';
        $doc .= '<tr>
            <td style="width:150px;">Optical Overall</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d55 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Opthamology - outpatient</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within optical overall</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Lens, Frames & Glasses (Replaced once every 2 years)</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within optical overall</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr style="background-color:#C04000"><td style="background-color:#C04000;color:white;text-align:center;">DENTAL COVER</td><td colspan="' . (count($data)) . '"  style="background-color:#C04000;color:white;text-align:center;">COVERAGE/LIMIT</td></tr>';
        $doc .= '<tr>
            <td style="width:150px;">Dental Outpatient Benefit Limit</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d59 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Consultation & Treatment</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the dental overall limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Extractions</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the dental overall limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Infections</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the dental overall limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Fillings</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the dental overall limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Minor Surgeries</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the dental overall limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Root Canal</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the dental overall limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Xrays</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the dental overall limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Non-Surgical Exractions</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the dental overall limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
            <td style="width:150px;">Scaling & Polishing</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>Covered within the dental overall limit</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr style="background-color:#C04000;text-align:center;"><td style="background-color:#C04000;color:white;">Annual Health Checks</td><td colspan="' . (count($data)) . '" style="background-color:#C04000;color:white;">COVERAGE/LIMIT</td></tr>';
        $doc .= '<tr>
            <td style="width:150px;">Annual Health Checks(Basic Medex): Complete blood count test,  Random blood sugar test, Breast Exam and VIA for Females above 30 years, PSA for males above 45 years</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td></td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>
                    <td  rowspan=2 style=" width:150px;">Personal Accident with Permanent Total Disability Cover (Within Accident Limit):</br> Child:Adult</td>';

        foreach ($data  as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d72 ?? 0) . '</td>';
        }

        $doc .= '</tr>';
        $doc .= '<tr>';
        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d73 ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr style="background-color:#C04000;text-align:center;color:white;"><td colspan="' . (count($data) + 1) . '" style="background-color:#C04000">Health Promotions (Upon Request from the Client)</td></tr>';
        $doc .= '<tr><td colspan="' . (count($data) + 1) . '" style="text-align:center;">Upto Four health talks per year</td></tr>';
        $doc .= ' <tr style="background-color:#C04000;color:white;text-align:center;"><td style="background-color:#C04000">Last Expenses</td><td colspan="' . (count($data)) . '" style="background-color:#C04000;color:white;">COVERAGE/LIMIT</td></tr>';
        $doc .= '<tr><td>Payable on confirmation of death of a covered member within the period of cover</td>';
        foreach ($data  as $product) {
            $p = $product[0];
            $doc .= '<td></td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr style="background-color:#C04000;text-align:center;color:white;"><td colspan="' . (count($data) + 1) . '" style="background-color:#C04000">Waiting Period</td></tr>';
        $doc .= '<tr><td>Illness hospitalization limit</td>';
        foreach ($data  as $product) {
            $p = $product[0];
            $doc .= '<td>no waiting periods</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td>Maternity Cover</td>';
        foreach ($data  as $product) {
            $p = $product[0];
            $doc .= '<td>no waiting periods</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td>Chronics Developed on cover</td>';
        foreach ($data  as $product) {
            $p = $product[0];
            $doc .= '<td>no waiting periods</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td>Chronics Developed on cover</td>';
        foreach ($data  as $product) {
            $p = $product[0];
            $doc .= '<td>no waiting periods</td>';
        }
        $doc .= '</tr>';
        $doc .= '</tbody></table>';
        $doc .= '<div style="margin:10px;color:#C04000;font-weight:bold;">Exclusions Include;</div>';
        $doc .= '<div style="margin:10px;">Treatment for injuries arising out of voluntary participation in riots, demonstrations, unrest, civil war or war</div>';
        $doc .= '
                            <ul style="list-style-type: decimal;margin:10px;">
                            <li>Travel expenses other than emergency ambulance costs</li>
                            <li>Domestic and biochemical remedies</li>
                            <li>Expenses related to self-inflicted injury, attempted suicide or suicide</li>
                            <li>All costs arising out of injuries sustained whilst participating in professional sports</li>
                            <li>Cosmetic procedures such as bat ears, blepharoplasty</li>
                            <li>Nutritional food supplements</li>
                            <li>Fertility and impotence treatment</li>
                            <li>Alternative treatment such as chiropractors herbal treatment, acupuncture treatment, etc</li>
                            <li>War, Riot and civil commotion</li>
                            <li>Political Violence, Terrorism and Sabotage</li>
                            </ul>
                            ';
        $doc .= '<div style="margin:10px;color:#C04000;font-weight:bold;">FINANCIAL IMPLICATION</div>';
        $doc .= '<table style="width:1000px;margin-left:10px;"><tbody>';
        $doc .= '<tr>
                        <td style="width:130px;background-color:#C04000;color:white;text-align:center;">Benefit</td>
                        <td style="width:130px;background-color:#C04000;color:white;text-align:center;">Cover Type</td>';

        foreach ($data as $index => $product) {
            $index++;
            $doc .= '<td style="width:150px;background-color:#C04000;color:white;text-align:center;">EA. Cover Option ' . $index . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td>Inpatient</td><td>Per person</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->uganda_illness ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td>Outpatient</td><td>Per person</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td></td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td>Maternity</td><td>Per person</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d35 ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td>Optical</td><td>Per person</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d55 ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td>Dental</td><td>Per person</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->d59 ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td colspan=2>No. of Lives</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->number ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td colspan=2>Premium Per person</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->total_premium_per_life ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td colspan=2>Total Premium</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->total ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td colspan=2>IIU Levy</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->iiu_levy ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td colspan=2>Stamp Duty</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->stamp_duty ?? 0) . '</td>';
        }
        $doc .= '</tr>';
        $doc .= '<tr><td colspan=2>Total Premium</td>';

        foreach ($data as $product) {
            $p = $product[0];
            $doc .= '<td>UGX' . number_format($p->ttl_premium ?? 0) . '</td>';
        }
        $doc .= '</tr>';

        $doc .= '</tbody></table>';
        $doc .= '<div style="margin:10px;color:#C04000;font-weight:bold;margin-top:5px;"><u>Note:</u></div>';
        $doc .= '
                    <ul style="list-style-type: decimal;margin-right:10px;margin-left:10px;">
                        <li>Premium Payment: As per the press release by the insurance regulator insurance act
                        2017, premiums must be paid on or before the date of inception of the policy.</li>
                        <li>Premiums quoted are based on 3 Lives.</li>
                        <li>At least 90% of the lives quoted for are  for the scheme to commence.</li>
                        <li>The maximum age for joining cover is 60 years and medical examination shall be
                        for all members above 45 years.</li>
                        <li>All benefit limits are on per person basis.</li>
                        <li>This proposal is valid for 90 days from the date of submission.</li>
                    </ul>
                        ';
        $doc .= '<div style="margin:10px;">All premiums shown are on annual basis and payable in advance in Ugandan shillings. All limits are also in Ugandan Shillings.</div>';

        return [$doc];
    }


    public function savePremiumQuotation(Request $request)
    {
        // return $request;

        try {
            $quote_no = 0;
            $max_id = empty(PremiumQuotationData::max('id')) ? 1 : PremiumQuotationData::max('id') + 1;
            $premium_quote_id = 'pqd' . $max_id;

            if (isset($request->start_date)) {
                for ($i = 0; $i < count($request->start_date); $i++) {
                    $quote_no++;
                    DB::transaction(function () use ($request, $premium_quote_id, $quote_no, $i) {
                        PremiumQuotationData::create(
                            [
                                'corporate' => $request->corporate,
                                'quote_id' => $premium_quote_id,
                                'quote_no' => $quote_no,
                                'females' => $request->females[$i],
                                'males' => $request->males[$i],
                                'inflation_discount' => $request->inflation_discount[$i],
                                'ug_illness' => $request->ug_illness[$i],
                                'hp_maternity' => $request->health_plan_maternity[$i],
                                'hp_outpatient' => $request->health_plan_op[$i],
                                'hp_dental' => $request->health_plan_dental[$i],
                                'dental_avg' => $request->dental_avg[$i],
                                'maternity_avg' => $request->maternity_avg[$i],
                                'optical' => $request->optical_avg[$i],
                                'commission_rate' => $request->commission_rate[$i],
                                'admin_rate' => $request->admin_rate[$i],
                                'profit_rate' => $request->profit_rate[$i],
                                'smart_cost' => $request->smart_cost[$i],
                                'active_lives' => $request->active_lives[$i],
                                'start_date' => $request->start_date[$i],
                                'end_date' => $request->end_date[$i]
                            ]
                        );
                    });
                }
                return ['Successful'];
            } else {
                return ['No data to save!'];
            }
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }

    private function getImage($path)
    {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
