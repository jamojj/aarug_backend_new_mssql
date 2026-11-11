<?php

namespace App\Http\Controllers\Management\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\Corporate\Corporate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndividualAndCompaniesController extends Controller
{
    public function fetchIndividualAndCompanies(Request $request)
    {
        ini_set('max_execution_time', 80000);

        $rows_total_all_corporates = [];
        $previous_year = date('Y', strtotime("-1 year"));
        $current_year = date("Y");

        $current_month = date('m');
        $current_month = (int)$current_month;
        $ttl_jan_previous =  $ttl_feb_previous =  $ttl_mar_previous =  $ttl_apr_previous =  $ttl_may_previous = 0;
        $ttl_jun_previous =  $ttl_jul_previous =  $ttl_aug_previous =  $ttl_sept_previous =  $ttl_oct_previous =  0;
        $ttl_nov_previous =  $ttl_dec_previous =  0;

        $ttl_jan_previous2 =  $ttl_feb_previous2 =  $ttl_mar_previous2 =  $ttl_apr_previous2 =  $ttl_may_previous2 = 0;
        $ttl_jun_previous2 =  $ttl_jul_previous2 =  $ttl_aug_previous2 =  $ttl_sept_previous2 =  $ttl_oct_previous2 =  0;
        $ttl_nov_previous2 =  $ttl_dec_previous2 =  0;


        $ttl_jan_current =  $ttl_feb_current =  $ttl_mar_current =  $ttl_apr_current =  $ttl_may_current = 0;
        $ttl_jun_current =  $ttl_jul_current =  $ttl_aug_current =  $ttl_sept_current =  $ttl_oct_current =  0;
        $ttl_nov_current =  $ttl_dec_current =  0;

        $ttl_jan_current2 =  $ttl_feb_current2 =  $ttl_mar_current2 =  $ttl_apr_current2 =  $ttl_may_current2 = 0;
        $ttl_jun_current2 =  $ttl_jul_current2 =  $ttl_aug_current2 =  $ttl_sept_current2 =  $ttl_oct_current2 =  0;
        $ttl_nov_current2 =  $ttl_dec_current2 =  0;



        switch ($request->type) {
                // individual
            case '1':
                $data = Bills::select(
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-01-01' and invoice_date < '$previous_year-02-01' then invoiced_amount END, 0))as jan_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-01-01' and invoice_date < '$previous_year-02-01' then amount_payable END, 0))as jan_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-02-01' and invoice_date < '$previous_year-03-01' then invoiced_amount END, 0))as feb_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-02-01' and invoice_date < '$previous_year-03-01' then amount_payable END, 0))as feb_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-03-01' and invoice_date < '$previous_year-04-01' then invoiced_amount END, 0))as mar_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-03-01' and invoice_date < '$previous_year-04-01' then amount_payable END, 0))as mar_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-04-01' and invoice_date < '$previous_year-05-01' then invoiced_amount END, 0))as apr_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-04-01' and invoice_date < '$previous_year-05-01' then amount_payable END, 0))as apr_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-05-01' and invoice_date < '$previous_year-06-01' then invoiced_amount END, 0))as may_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-05-01' and invoice_date < '$previous_year-06-01' then amount_payable END, 0))as may_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-06-01' and invoice_date < '$previous_year-07-01' then invoiced_amount END, 0))as jun_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-06-01' and invoice_date < '$previous_year-07-01' then amount_payable END, 0))as jun_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-07-01' and invoice_date < '$previous_year-08-01' then invoiced_amount END, 0))as jul_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-07-01' and invoice_date < '$previous_year-08-01' then amount_payable END, 0))as jul_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-08-01' and invoice_date < '$previous_year-09-01' then invoiced_amount END, 0))as aug_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-08-01' and invoice_date < '$previous_year-09-01' then amount_payable END, 0))as aug_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-09-01' and invoice_date < '$previous_year-10-01' then invoiced_amount END, 0))as sept_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-09-01' and invoice_date < '$previous_year-10-01' then amount_payable END, 0))as sept_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-10-01' and invoice_date < '$previous_year-11-01' then invoiced_amount END, 0))as oct_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-10-01' and invoice_date < '$previous_year-11-01' then amount_payable END, 0))as oct_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-11-01' and invoice_date < '$previous_year-12-01' then invoiced_amount END, 0))as nov_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-11-01' and invoice_date < '$previous_year-12-01' then amount_payable END, 0))as nov_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-12-01' and invoice_date < '$current_year-01-01' then invoiced_amount END, 0))as dec_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-12-01' and invoice_date < '$current_year-01-01' then amount_payable END, 0))as dec_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-01-01' and invoice_date < '$current_year-02-01' then invoiced_amount END, 0))as jan_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-01-01' and invoice_date < '$current_year-02-01' then amount_payable END, 0))as jan_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-02-01' and invoice_date < '$current_year-03-01' then invoiced_amount END, 0))as feb_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-02-01' and invoice_date < '$current_year-03-01' then amount_payable END, 0))as feb_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-03-01' and invoice_date < '$current_year-04-01' then invoiced_amount END, 0))as mar_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-03-01' and invoice_date < '$current_year-04-01' then amount_payable END, 0))as mar_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-04-01' and invoice_date < '$current_year-05-01' then invoiced_amount END, 0))as apr_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-04-01' and invoice_date < '$current_year-05-01' then amount_payable END, 0))as apr_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-05-01' and invoice_date < '$current_year-06-01' then invoiced_amount END, 0))as may_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-05-01' and invoice_date < '$current_year-06-01' then amount_payable END, 0))as may_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-06-01' and invoice_date < '$current_year-07-01' then invoiced_amount END, 0))as jun_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-06-01' and invoice_date < '$current_year-07-01' then amount_payable END, 0))as jun_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-07-01' and invoice_date < '$current_year-08-01' then invoiced_amount END, 0))as jul_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-07-01' and invoice_date < '$current_year-08-01' then amount_payable END, 0))as jul_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-08-01' and invoice_date < '$current_year-09-01' then invoiced_amount END, 0))as aug_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-08-01' and invoice_date < '$current_year-09-01' then amount_payable END, 0))as aug_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-09-01' and invoice_date < '$current_year-10-01' then invoiced_amount END, 0))as sept_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-09-01' and invoice_date < '$current_year-10-01' then amount_payable END, 0))as sept_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-10-01' and invoice_date < '$current_year-11-01' then invoiced_amount END, 0))as oct_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-10-01' and invoice_date < '$current_year-11-01' then amount_payable END, 0))as oct_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-11-01' and invoice_date < '$current_year-12-01' then invoiced_amount END, 0))as nov_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-11-01' and invoice_date < '$current_year-12-01' then amount_payable END, 0))as nov_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-12-01' and invoice_date < '$current_year-12-31' then invoiced_amount END, 0))as dec_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-12-01' and invoice_date < '$current_year-12-31' then amount_payable END, 0))as dec_current2"),
                    DB::raw("jan_previous + feb_previous + mar_previous + apr_previous + may_previous +  jun_previous + jul_previous + aug_previous + sept_previous + oct_previous + nov_previous + dec_previous + jan_current +  feb_current + mar_current + apr_current + may_current + jun_current + jul_current + aug_current +  sept_current + oct_current + nov_current + dec_current as grand_total"),
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                    'member_info.member_no',
                )

                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->orderBy('names', 'ASC')
                    ->groupBy(['member_info.member_no', 'names'])
                    ->where('principal_applicant.corp_id', 14)
                    ->get();

                foreach ($data as $dt) {
                    //sum of previous invoiced amount
                    $ttl_jan_previous += $dt->jan_previous;
                    $ttl_feb_previous += $dt->feb_previous;
                    $ttl_mar_previous += $dt->mar_previous;
                    $ttl_apr_previous += $dt->apr_previous;
                    $ttl_may_previous += $dt->may_previous;
                    $ttl_jun_previous += $dt->jun_previous;
                    $ttl_jul_previous += $dt->jul_previous;
                    $ttl_aug_previous += $dt->aug_previous;
                    $ttl_sept_previous += $dt->sept_previous;
                    $ttl_oct_previous += $dt->oct_previous;
                    $ttl_nov_previous += $dt->nov_previous;
                    $ttl_dec_previous += $dt->dec_previous;
                    //sum of previous amount payable
                    $ttl_jan_previous2 += $dt->jan_previous2;
                    $ttl_feb_previous2 += $dt->feb_previous2;
                    $ttl_mar_previous2 += $dt->mar_previous2;
                    $ttl_apr_previous2 += $dt->apr_previous2;
                    $ttl_may_previous2 += $dt->may_previous2;
                    $ttl_jun_previous2 += $dt->jun_previous2;
                    $ttl_jul_previous2 += $dt->jul_previous2;
                    $ttl_aug_previous2 += $dt->aug_previous2;
                    $ttl_sept_previous2 += $dt->sept_previous2;
                    $ttl_oct_previous2 += $dt->oct_previous2;
                    $ttl_nov_previous2 += $dt->nov_previous2;
                    $ttl_dec_previous2 += $dt->dec_previous2;

                    //sum of previous invoiced amount
                    $ttl_jan_current += $dt->jan_current;
                    $ttl_feb_current += $dt->feb_current;
                    $ttl_mar_current += $dt->mar_current;
                    $ttl_apr_current += $dt->apr_current;
                    $ttl_may_current += $dt->may_current;
                    $ttl_jun_current += $dt->jun_current;
                    $ttl_jul_current += $dt->jul_current;
                    $ttl_aug_current += $dt->aug_current;
                    $ttl_sept_current += $dt->sept_current;
                    $ttl_oct_current += $dt->oct_current;
                    $ttl_nov_current += $dt->nov_current;
                    $ttl_dec_current += $dt->dec_current;
                    //sum of current amount payable
                    $ttl_jan_current2 += $dt->jan_current2;
                    $ttl_feb_current2 += $dt->feb_current2;
                    $ttl_mar_current2 += $dt->mar_current2;
                    $ttl_apr_current2 += $dt->apr_current2;
                    $ttl_may_current2 += $dt->may_current2;
                    $ttl_jun_current2 += $dt->jun_current2;
                    $ttl_jul_current2 += $dt->jul_current2;
                    $ttl_aug_current2 += $dt->aug_current2;
                    $ttl_sept_current2 += $dt->sept_current2;
                    $ttl_oct_current2 += $dt->oct_current2;
                    $ttl_nov_current2 += $dt->nov_current2;
                    $ttl_dec_current2 += $dt->dec_current2;
                }
                $ttl_invoiced_amt = [
                    $ttl_jan_previous,  $ttl_feb_previous,  $ttl_mar_previous,  $ttl_apr_previous,  $ttl_may_previous,
                    $ttl_jun_previous,  $ttl_jul_previous,  $ttl_aug_previous,  $ttl_sept_previous,  $ttl_oct_previous,
                    $ttl_nov_previous,  $ttl_dec_previous,  $ttl_jan_current,
                    $ttl_feb_current,  $ttl_mar_current,  $ttl_apr_current,  $ttl_may_current, $ttl_jun_current,  $ttl_jul_current,
                    $ttl_aug_current,  $ttl_sept_current,  $ttl_oct_current, $ttl_nov_current,  $ttl_dec_current
                ];
                $ttl_amount_payable = [
                    $ttl_jan_previous2,  $ttl_feb_previous2,  $ttl_mar_previous2,
                    $ttl_apr_previous2,  $ttl_may_previous2, $ttl_jun_previous2,  $ttl_jul_previous2,  $ttl_aug_previous2,
                    $ttl_sept_previous2,  $ttl_oct_previous2, $ttl_nov_previous2,  $ttl_dec_previous2, $ttl_jan_current2,  $ttl_feb_current2,  $ttl_mar_current2,  $ttl_apr_current2,  $ttl_may_current2,
                    $ttl_jun_current2,  $ttl_jul_current2,  $ttl_aug_current2,  $ttl_sept_current2,  $ttl_oct_current2,
                    $ttl_nov_current2,  $ttl_dec_current2
                ];

                $ttl_difference = [
                    $ttl_jan_previous -  $ttl_jan_previous2,  $ttl_feb_previous - $ttl_feb_previous2,  $ttl_mar_previous - $ttl_mar_previous2,  $ttl_apr_previous - $ttl_apr_previous2,  $ttl_may_previous - $ttl_may_previous2,
                    $ttl_jun_previous - $ttl_jun_previous2,  $ttl_jul_previous - $ttl_jul_previous2,  $ttl_aug_previous - $ttl_aug_previous2,  $ttl_sept_previous - $ttl_sept_previous2,  $ttl_oct_previous - $ttl_oct_previous2,
                    $ttl_nov_previous - $ttl_nov_previous2,  $ttl_dec_previous - $ttl_dec_previous2, $ttl_jan_current -  $ttl_jan_current2,  $ttl_feb_current - $ttl_feb_current2,  $ttl_mar_current - $ttl_mar_current2,  $ttl_apr_current - $ttl_apr_current2,  $ttl_may_current - $ttl_may_current2,
                    $ttl_jun_current - $ttl_jun_current2,  $ttl_jul_current - $ttl_jul_current2,  $ttl_aug_current - $ttl_aug_current2,  $ttl_sept_current - $ttl_sept_current2,  $ttl_oct_current - $ttl_oct_current2,
                    $ttl_nov_current - $ttl_nov_current2,  $ttl_dec_current - $ttl_dec_current2
                ];             
            
              

                //grand total for column totals
                $dt_grand_total_invoiced_amount = [
                    $ttl_jan_previous +  $ttl_feb_previous +  $ttl_mar_previous +  $ttl_apr_previous +  $ttl_may_previous +
                        $ttl_jun_previous +  $ttl_jul_previous +  $ttl_aug_previous +  $ttl_sept_previous +  $ttl_oct_previous +
                        $ttl_nov_previous +  $ttl_dec_previous +  $ttl_jan_current +
                        $ttl_feb_current +  $ttl_mar_current +  $ttl_apr_current +  $ttl_may_current + $ttl_jun_current +  $ttl_jul_current +
                        $ttl_aug_current +  $ttl_sept_current +  $ttl_oct_current + $ttl_nov_current +  $ttl_dec_current
                ];

                $dt_grand_total_amount_payable = [
                    $ttl_jan_previous2 +  $ttl_feb_previous2 +  $ttl_mar_previous2 +
                        $ttl_apr_previous2 +  $ttl_may_previous2 + $ttl_jun_previous2 +  $ttl_jul_previous2 +  $ttl_aug_previous2 +
                        $ttl_sept_previous2 +  $ttl_oct_previous2 + $ttl_nov_previous2 +  $ttl_dec_previous2 +  $ttl_jan_current2 +  $ttl_feb_current2 +  $ttl_mar_current2 +  $ttl_apr_current2 +  $ttl_may_current2 +
                        $ttl_jun_current2 +  $ttl_jul_current2 +  $ttl_aug_current2 +  $ttl_sept_current2 +  $ttl_oct_current2 +
                        $ttl_nov_current2 +  $ttl_dec_current2
                ];

                $dt_grand_total_difference = [
                    ($ttl_jan_previous -  $ttl_jan_previous2) +  ($ttl_feb_previous - $ttl_feb_previous2) +  ($ttl_mar_previous - $ttl_mar_previous2) +  ($ttl_apr_previous - $ttl_apr_previous2) +  ($ttl_may_previous - $ttl_may_previous2) +
                        ($ttl_jun_previous - $ttl_jun_previous2) +  ($ttl_jul_previous - $ttl_jul_previous2) +  ($ttl_aug_previous - $ttl_aug_previous2) +  ($ttl_sept_previous - $ttl_sept_previous2) +  ($ttl_oct_previous - $ttl_oct_previous2) +
                        ($ttl_nov_previous - $ttl_nov_previous2) +  ($ttl_dec_previous - $ttl_dec_previous2) +  ($ttl_jan_current -  $ttl_jan_current2) +  ($ttl_feb_current - $ttl_feb_current2) +  ($ttl_mar_current - $ttl_mar_current2) +  ($ttl_apr_current - $ttl_apr_current2) +  ($ttl_may_current - $ttl_may_current2) +
                        ($ttl_jun_current - $ttl_jun_current2) +  ($ttl_jul_current - $ttl_jul_current2) +  ($ttl_aug_current - $ttl_aug_current2) +  ($ttl_sept_current - $ttl_sept_current2) +  ($ttl_oct_current - $ttl_oct_current2) +
                        ($ttl_nov_current - $ttl_nov_current2) +  ($ttl_dec_current - $ttl_dec_current2)
                ];

                //column headers
                $py = date('y', strtotime("-1 year"));
                $cy = date("y");
                $dt_column_headers = ['Member Name', 'Member No', 'Jan-' . $py, 'Feb-' . $py, 'Mar-' . $py, 'Apr-' . $py, 'May-' . $py, 'Jun-' . $py, 'Jul-' . $py, 'Aug-' . $py, 'Sep-' . $py, 'Oct-' . $py, 'Nov-' . $py, 'Dec-' . $py, 'Jan-' . $cy, 'Feb-' . $cy, 'Mar-' . $cy, 'Apr-' . $cy, 'May-' . $cy, 'Jun-' . $cy, 'Jul-' . $cy, 'Aug-' . $cy, 'Sep-' . $cy, 'Oct-' . $cy, 'Nov-' . $cy, 'Dec-' . $cy, 'Grand Total'];


                return [
                    'body' => $data,
                    'ttl_invoiced_amt' => $ttl_invoiced_amt,
                    'ttl_amount_payable' => $ttl_amount_payable,
                    'ttl_difference' => $ttl_difference,                  
                    'rows_total_all_corporates' => $rows_total_all_corporates,
                    'dt_grand_total_invoiced_amount' => $dt_grand_total_invoiced_amount,
                    'dt_grand_total_amount_payable' => $dt_grand_total_amount_payable,
                    'dt_grand_total_difference' => $dt_grand_total_difference,
                    'dt_column_headers' => $dt_column_headers
                ];
                break;
                // corporate
            case '2':
                $data = Bills::select(
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-01-01' and invoice_date < '$previous_year-02-01' then invoiced_amount END, 0))as jan_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-01-01' and invoice_date < '$previous_year-02-01' then amount_payable END, 0))as jan_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-02-01' and invoice_date < '$previous_year-03-01' then invoiced_amount END, 0))as feb_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-02-01' and invoice_date < '$previous_year-03-01' then amount_payable END, 0))as feb_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-03-01' and invoice_date < '$previous_year-04-01' then invoiced_amount END, 0))as mar_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-03-01' and invoice_date < '$previous_year-04-01' then amount_payable END, 0))as mar_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-04-01' and invoice_date < '$previous_year-05-01' then invoiced_amount END, 0))as apr_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-04-01' and invoice_date < '$previous_year-05-01' then amount_payable END, 0))as apr_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-05-01' and invoice_date < '$previous_year-06-01' then invoiced_amount END, 0))as may_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-05-01' and invoice_date < '$previous_year-06-01' then amount_payable END, 0))as may_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-06-01' and invoice_date < '$previous_year-07-01' then invoiced_amount END, 0))as jun_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-06-01' and invoice_date < '$previous_year-07-01' then amount_payable END, 0))as jun_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-07-01' and invoice_date < '$previous_year-08-01' then invoiced_amount END, 0))as jul_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-07-01' and invoice_date < '$previous_year-08-01' then amount_payable END, 0))as jul_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-08-01' and invoice_date < '$previous_year-09-01' then invoiced_amount END, 0))as aug_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-08-01' and invoice_date < '$previous_year-09-01' then amount_payable END, 0))as aug_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-09-01' and invoice_date < '$previous_year-10-01' then invoiced_amount END, 0))as sept_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-09-01' and invoice_date < '$previous_year-10-01' then amount_payable END, 0))as sept_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-10-01' and invoice_date < '$previous_year-11-01' then invoiced_amount END, 0))as oct_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-10-01' and invoice_date < '$previous_year-11-01' then amount_payable END, 0))as oct_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-11-01' and invoice_date < '$previous_year-12-01' then invoiced_amount END, 0))as nov_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-11-01' and invoice_date < '$previous_year-12-01' then amount_payable END, 0))as nov_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-12-01' and invoice_date < '$current_year-01-01' then invoiced_amount END, 0))as dec_previous"),
                    DB::raw("sum(isnull(case when invoice_date >= '$previous_year-12-01' and invoice_date < '$current_year-01-01' then amount_payable END, 0))as dec_previous2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-01-01' and invoice_date < '$current_year-02-01' then invoiced_amount END, 0))as jan_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-01-01' and invoice_date < '$current_year-02-01' then amount_payable END, 0))as jan_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-02-01' and invoice_date < '$current_year-03-01' then invoiced_amount END, 0))as feb_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-02-01' and invoice_date < '$current_year-03-01' then amount_payable END, 0))as feb_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-03-01' and invoice_date < '$current_year-04-01' then invoiced_amount END, 0))as mar_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-03-01' and invoice_date < '$current_year-04-01' then amount_payable END, 0))as mar_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-04-01' and invoice_date < '$current_year-05-01' then invoiced_amount END, 0))as apr_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-04-01' and invoice_date < '$current_year-05-01' then amount_payable END, 0))as apr_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-05-01' and invoice_date < '$current_year-06-01' then invoiced_amount END, 0))as may_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-05-01' and invoice_date < '$current_year-06-01' then amount_payable END, 0))as may_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-06-01' and invoice_date < '$current_year-07-01' then invoiced_amount END, 0))as jun_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-06-01' and invoice_date < '$current_year-07-01' then amount_payable END, 0))as jun_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-07-01' and invoice_date < '$current_year-08-01' then invoiced_amount END, 0))as jul_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-07-01' and invoice_date < '$current_year-08-01' then amount_payable END, 0))as jul_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-08-01' and invoice_date < '$current_year-09-01' then invoiced_amount END, 0))as aug_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-08-01' and invoice_date < '$current_year-09-01' then amount_payable END, 0))as aug_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-09-01' and invoice_date < '$current_year-10-01' then invoiced_amount END, 0))as sept_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-09-01' and invoice_date < '$current_year-10-01' then amount_payable END, 0))as sept_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-10-01' and invoice_date < '$current_year-11-01' then invoiced_amount END, 0))as oct_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-10-01' and invoice_date < '$current_year-11-01' then amount_payable END, 0))as oct_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-11-01' and invoice_date < '$current_year-12-01' then invoiced_amount END, 0))as nov_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-11-01' and invoice_date < '$current_year-12-01' then amount_payable END, 0))as nov_current2"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-12-01' and invoice_date < '$current_year-12-31' then invoiced_amount END, 0))as dec_current"),
                    DB::raw("sum(isnull(case when invoice_date >= '$current_year-12-01' and invoice_date < '$current_year-12-31' then amount_payable END, 0))as dec_current2"),
                    DB::raw("jan_previous + feb_previous + mar_previous + apr_previous + may_previous +  jun_previous + jul_previous + aug_previous + sept_previous + oct_previous + nov_previous + dec_previous + jan_current +  feb_current + mar_current + apr_current + may_current + jun_current + jul_current + aug_current +  sept_current + oct_current + nov_current + dec_current as grand_total"),
                    'corporate.corporate',
                )

                    ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->orderBy('corporate.corporate', 'ASC')
                    ->groupBy(['corporate.corporate'])
                    ->get();

                foreach ($data as $dt) {                   

                    //sum of previous invoiced amount
                    $ttl_jan_previous += $dt->jan_previous;
                    $ttl_feb_previous += $dt->feb_previous;
                    $ttl_mar_previous += $dt->mar_previous;
                    $ttl_apr_previous += $dt->apr_previous;
                    $ttl_may_previous += $dt->may_previous;
                    $ttl_jun_previous += $dt->jun_previous;
                    $ttl_jul_previous += $dt->jul_previous;
                    $ttl_aug_previous += $dt->aug_previous;
                    $ttl_sept_previous += $dt->sept_previous;
                    $ttl_oct_previous += $dt->oct_previous;
                    $ttl_nov_previous += $dt->nov_previous;
                    $ttl_dec_previous += $dt->dec_previous;
                    //sum of previous amount payable
                    $ttl_jan_previous2 += $dt->jan_previous2;
                    $ttl_feb_previous2 += $dt->feb_previous2;
                    $ttl_mar_previous2 += $dt->mar_previous2;
                    $ttl_apr_previous2 += $dt->apr_previous2;
                    $ttl_may_previous2 += $dt->may_previous2;
                    $ttl_jun_previous2 += $dt->jun_previous2;
                    $ttl_jul_previous2 += $dt->jul_previous2;
                    $ttl_aug_previous2 += $dt->aug_previous2;
                    $ttl_sept_previous2 += $dt->sept_previous2;
                    $ttl_oct_previous2 += $dt->oct_previous2;
                    $ttl_nov_previous2 += $dt->nov_previous2;
                    $ttl_dec_previous2 += $dt->dec_previous2;

                    //sum of curent invoiced amount
                    $ttl_jan_current += $dt->jan_current;
                    $ttl_feb_current += $dt->feb_current;
                    $ttl_mar_current += $dt->mar_current;
                    $ttl_apr_current += $dt->apr_current;
                    $ttl_may_current += $dt->may_current;
                    $ttl_jun_current += $dt->jun_current;
                    $ttl_jul_current += $dt->jul_current;
                    $ttl_aug_current += $dt->aug_current;
                    $ttl_sept_current += $dt->sept_current;
                    $ttl_oct_current += $dt->oct_current;
                    $ttl_nov_current += $dt->nov_current;
                    $ttl_dec_current += $dt->dec_current;
                    //sum of current amount payable
                    $ttl_jan_current2 += $dt->jan_current2;
                    $ttl_feb_current2 += $dt->feb_current2;
                    $ttl_mar_current2 += $dt->mar_current2;
                    $ttl_apr_current2 += $dt->apr_current2;
                    $ttl_may_current2 += $dt->may_current2;
                    $ttl_jun_current2 += $dt->jun_current2;
                    $ttl_jul_current2 += $dt->jul_current2;
                    $ttl_aug_current2 += $dt->aug_current2;
                    $ttl_sept_current2 += $dt->sept_current2;
                    $ttl_oct_current2 += $dt->oct_current2;
                    $ttl_nov_current2 += $dt->nov_current2;
                    $ttl_dec_current2 += $dt->dec_current2;
                }
                $ttl_invoiced_amt = [
                    $ttl_jan_previous,  $ttl_feb_previous,  $ttl_mar_previous,  $ttl_apr_previous,  $ttl_may_previous,
                    $ttl_jun_previous,  $ttl_jul_previous,  $ttl_aug_previous,  $ttl_sept_previous,  $ttl_oct_previous,
                    $ttl_nov_previous,  $ttl_dec_previous,  $ttl_jan_current,
                    $ttl_feb_current,  $ttl_mar_current,  $ttl_apr_current,  $ttl_may_current, $ttl_jun_current,  $ttl_jul_current,
                    $ttl_aug_current,  $ttl_sept_current,  $ttl_oct_current, $ttl_nov_current,  $ttl_dec_current
                ];
                $ttl_amount_payable = [
                    $ttl_jan_previous2,  $ttl_feb_previous2,  $ttl_mar_previous2,
                    $ttl_apr_previous2,  $ttl_may_previous2, $ttl_jun_previous2,  $ttl_jul_previous2,  $ttl_aug_previous2,
                    $ttl_sept_previous2,  $ttl_oct_previous2, $ttl_nov_previous2,  $ttl_dec_previous2, $ttl_jan_current2,  $ttl_feb_current2,  $ttl_mar_current2,  $ttl_apr_current2,  $ttl_may_current2,
                    $ttl_jun_current2,  $ttl_jul_current2,  $ttl_aug_current2,  $ttl_sept_current2,  $ttl_oct_current2,
                    $ttl_nov_current2,  $ttl_dec_current2
                ];

                $ttl_difference = [
                    $ttl_jan_previous -  $ttl_jan_previous2,  $ttl_feb_previous - $ttl_feb_previous2,  $ttl_mar_previous - $ttl_mar_previous2,  $ttl_apr_previous - $ttl_apr_previous2,  $ttl_may_previous - $ttl_may_previous2,
                    $ttl_jun_previous - $ttl_jun_previous2,  $ttl_jul_previous - $ttl_jul_previous2,  $ttl_aug_previous - $ttl_aug_previous2,  $ttl_sept_previous - $ttl_sept_previous2,  $ttl_oct_previous - $ttl_oct_previous2,
                    $ttl_nov_previous - $ttl_nov_previous2,  $ttl_dec_previous - $ttl_dec_previous2, $ttl_jan_current -  $ttl_jan_current2,  $ttl_feb_current - $ttl_feb_current2,  $ttl_mar_current - $ttl_mar_current2,  $ttl_apr_current - $ttl_apr_current2,  $ttl_may_current - $ttl_may_current2,
                    $ttl_jun_current - $ttl_jun_current2,  $ttl_jul_current - $ttl_jul_current2,  $ttl_aug_current - $ttl_aug_current2,  $ttl_sept_current - $ttl_sept_current2,  $ttl_oct_current - $ttl_oct_current2,
                    $ttl_nov_current - $ttl_nov_current2,  $ttl_dec_current - $ttl_dec_current2
                ];            
            
              

                //grand total for column totals
                $dt_grand_total_invoiced_amount = [
                    $ttl_jan_previous +  $ttl_feb_previous +  $ttl_mar_previous +  $ttl_apr_previous +  $ttl_may_previous +
                        $ttl_jun_previous +  $ttl_jul_previous +  $ttl_aug_previous +  $ttl_sept_previous +  $ttl_oct_previous +
                        $ttl_nov_previous +  $ttl_dec_previous +  $ttl_jan_current +
                        $ttl_feb_current +  $ttl_mar_current +  $ttl_apr_current +  $ttl_may_current + $ttl_jun_current +  $ttl_jul_current +
                        $ttl_aug_current +  $ttl_sept_current +  $ttl_oct_current + $ttl_nov_current +  $ttl_dec_current
                ];

                $dt_grand_total_amount_payable = [
                    $ttl_jan_previous2 +  $ttl_feb_previous2 +  $ttl_mar_previous2 +
                        $ttl_apr_previous2 +  $ttl_may_previous2 + $ttl_jun_previous2 +  $ttl_jul_previous2 +  $ttl_aug_previous2 +
                        $ttl_sept_previous2 +  $ttl_oct_previous2 + $ttl_nov_previous2 +  $ttl_dec_previous2 +  $ttl_jan_current2 +  $ttl_feb_current2 +  $ttl_mar_current2 +  $ttl_apr_current2 +  $ttl_may_current2 +
                        $ttl_jun_current2 +  $ttl_jul_current2 +  $ttl_aug_current2 +  $ttl_sept_current2 +  $ttl_oct_current2 +
                        $ttl_nov_current2 +  $ttl_dec_current2
                ];

                $dt_grand_total_difference = [
                    ($ttl_jan_previous -  $ttl_jan_previous2) +  ($ttl_feb_previous - $ttl_feb_previous2) +  ($ttl_mar_previous - $ttl_mar_previous2) +  ($ttl_apr_previous - $ttl_apr_previous2) +  ($ttl_may_previous - $ttl_may_previous2) +
                        ($ttl_jun_previous - $ttl_jun_previous2) +  ($ttl_jul_previous - $ttl_jul_previous2) +  ($ttl_aug_previous - $ttl_aug_previous2) +  ($ttl_sept_previous - $ttl_sept_previous2) +  ($ttl_oct_previous - $ttl_oct_previous2) +
                        ($ttl_nov_previous - $ttl_nov_previous2) +  ($ttl_dec_previous - $ttl_dec_previous2) +  ($ttl_jan_current -  $ttl_jan_current2) +  ($ttl_feb_current - $ttl_feb_current2) +  ($ttl_mar_current - $ttl_mar_current2) +  ($ttl_apr_current - $ttl_apr_current2) +  ($ttl_may_current - $ttl_may_current2) +
                        ($ttl_jun_current - $ttl_jun_current2) +  ($ttl_jul_current - $ttl_jul_current2) +  ($ttl_aug_current - $ttl_aug_current2) +  ($ttl_sept_current - $ttl_sept_current2) +  ($ttl_oct_current - $ttl_oct_current2) +
                        ($ttl_nov_current - $ttl_nov_current2) +  ($ttl_dec_current - $ttl_dec_current2)
                ];

                //column headers
                $py = date('y', strtotime("-1 year"));
                $cy = date("y");
                $dt_column_headers = ['Corporate', 'Jan-' . $py, 'Feb-' . $py, 'Mar-' . $py, 'Apr-' . $py, 'May-' . $py, 'Jun-' . $py, 'Jul-' . $py, 'Aug-' . $py, 'Sep-' . $py, 'Oct-' . $py, 'Nov-' . $py, 'Dec-' . $py, 'Jan-' . $cy, 'Feb-' . $cy, 'Mar-' . $cy, 'Apr-' . $cy, 'May-' . $cy, 'Jun-' . $cy, 'Jul-' . $cy, 'Aug-' . $cy, 'Sep-' . $cy, 'Oct-' . $cy, 'Nov-' . $cy, 'Dec-' . $cy, 'Grand Total'];


                return [
                    'body' => $data,
                    'ttl_invoiced_amt' => $ttl_invoiced_amt,
                    'ttl_amount_payable' => $ttl_amount_payable,
                    'ttl_difference' => $ttl_difference,                  
                    'rows_total_all_corporates' => $rows_total_all_corporates,
                    'dt_grand_total_invoiced_amount' => $dt_grand_total_invoiced_amount,
                    'dt_grand_total_amount_payable' => $dt_grand_total_amount_payable,
                    'dt_grand_total_difference' => $dt_grand_total_difference,
                    'dt_column_headers' => $dt_column_headers
                ];

                break;
        }
    }


    public function checkLeapYear($year)
    {
        $leap_year = 0;
        if (($year % 400 == 0) || (($year % 100 == 0) && ($year % 4 == 0))) {
            $leap_year = 29;
        } else {
            $leap_year = 28;
        }
        return $leap_year;
    }
}
