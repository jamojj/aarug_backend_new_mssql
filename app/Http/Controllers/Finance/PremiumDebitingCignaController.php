<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\cigna_member_anniversary;
use App\Models\CignaClientRates;
use App\Models\CignaMemberInfo;
use App\Models\CignaPremiumInvoiceDetailsSummary;
use App\Models\PremiumInvoiceCigna;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PremiumDebitingCignaController extends Controller
{

    public function calculateCigmaPremiumDebit(Request $request)
    {
        // return $request;
        $today = date("Y-m-d");
        $plan_prem = 0;
        $ttl_prem = 0;
        $net_premium = 0;
        $individual_premiums = [];
        $debit_totals = [];
        $max_anniv = cigna_member_anniversary::where('family_no', $request->family)->where('person_no', 1)->max('anniv');
        $family = cigna_member_anniversary::select('family_no', 'person_no', 'start_date')->where('family_no', $request->family)->where('person_no', 1)->where('anniv', $max_anniv)->get();


        $principal_family_no = count($family) !== 0 ? $family[0]->family_no : '';
        $principal_person_no = count($family) !== 0 ? $family[0]->person_no : '';
        $start_date = count($family) !== 0 ? $family[0]->start_date : '';
        if (isset($request->family_no)) {
            for ($i = 0; $i < count($request->family_no); $i++) {
                //calculate age
                $age = date_diff(date_create($request->DOB[$i]), date_create($start_date))->y;
                //get premiums for plan
                $plan_premium = CignaClientRates::select(DB::raw("TOP 1 premium"))
                    ->where('AOC', $request->AOC[$i])
                    ->where('new_or_renewal', $request->new_or_renewal[$i])
                    ->where('plan', $request->plan[$i])
                    ->where(function ($val) use ($age) {
                        $val->where('min_age', '<=', $age)
                            ->where('max_age', '>=', $age);
                        $val->orWhere('min_age', '<=', $age)
                            ->whereNull('max_age');
                        $val->orWhere('age', '=', $age);
                    })
                    ->orderBy('date_entered', 'desc')
                    ->get();
                //calculate dental premium
                $dental_premium = CignaClientRates::select(DB::raw("TOP 1 premium"))
                    ->where('AOC', $request->AOC[$i])
                    ->where('new_or_renewal', $request->new_or_renewal[$i])
                    ->where('plan', $request->dental[$i])
                    ->where(function ($val) use ($age) {
                        $val->where('min_age', '<=', $age)
                            ->where('max_age', '>=', $age);
                        $val->orWhere('min_age', '<=', $age)
                            ->whereNull('max_age');
                        $val->orWhere('age', '=', $age);
                    })
                    ->orderBy('date_entered', 'desc')
                    ->get();

                $plan_premium = count($plan_premium) !== 0 ? $plan_premium[0]->premium : 0;
                $plan_prem =  $plan_premium;
                //calculate premium deduction and subtract from plan premium
                if ($request->premium_deduction[$i] > 0) {
                    $plan_prem =  $plan_prem - (($request->premium_deduction[$i] / 100) * $plan_prem);
                }
                //calculate premium loading and add to plan premium
                if ($request->premium_loading[$i] > 0) {
                    $plan_prem =  (($request->premium_loading[$i] / 100) * $plan_prem) + $plan_prem;
                }


                $dental_premium = count($dental_premium) !== 0 ? $dental_premium[0]->premium : 0;
                $ttl_prem = $plan_prem + $dental_premium;

                $dt = [
                    'name' => $request->name[$i],
                    'family_no' => $request->family_no[$i],
                    'person_no' => $request->person_no[$i],
                    'dob' => $request->DOB[$i],
                    'AOC' => $request->AOC[$i],
                    'payment_frequency' => $request->payment_frequency[$i],
                    'age' => $age,
                    'plan' => $request->plan[$i],
                    'dental' => $request->dental[$i],
                    'plan_premium' => number_format(
                        $plan_premium,
                        2
                    ),
                    'dental_premium' => number_format(
                        $dental_premium,
                        2
                    ),
                    'loading' => $request->premium_loading[$i],
                    'deduction' => $request->premium_deduction[$i]
                ];

                array_push($individual_premiums, $dt);
                //calculate net premium
                $net_premium += $ttl_prem;
            }
            //calculate cumulative premium and taxes
            $iiu = (0.5 / 100) * $net_premium;
            // stamp duty on cigna has been removed completely
            // $stamp_duty = 9.45; stamp duty on cigna has been removed completely
            // $total_premium = $net_premium + $iiu + $stamp_duty;
            $total_premium = $net_premium + $iiu;

            $dt = [
                'family_no' => $principal_family_no,
                'person_no' => $principal_person_no,
                'iiu' => number_format(
                    $iiu,
                    2
                ),
                // 'stamp_duty' => number_format(
                //     $stamp_duty,
                //     2
                // ),
                'net_premium' => number_format(
                    $net_premium,
                    2
                ),
                'total_premium' => number_format(
                    $total_premium,
                    2
                )
            ];
            array_push($debit_totals, $dt);
        }
        return ['individual_premiums' => $individual_premiums, 'debit_totals' => $debit_totals];
    }


    public function saveCignaDebiting(Request $request)
    {
        try {
            // return $request;
            if (isset($request->name)) {

                //create invoice no
                $date_entered = date("Y-m-d");
                $year = date("Y");
                $max_idx_family = PremiumInvoiceCigna::max('idx');
                $max_idx_family = is_null($max_idx_family) ? 0 : $max_idx_family;
                $max_idx_family += 1;
                $invoice_no = 'CGN' . "-" . $max_idx_family;
                // $policy_no = 'CGN' . "-" . $year . "-" . $max_idx_family;
                $policy_no = $request->family_no_principal;

                $max_idx_members = CignaPremiumInvoiceDetailsSummary::max('idx');
                $max_idx_members = is_null($max_idx_members) ? 0 : $max_idx_members;
                DB::transaction(function () use ($request, $invoice_no, $date_entered, $max_idx_members, $max_idx_family, $policy_no) {
                    //set inserted, updated, and deleted items
                    $inserted_item = "invoice_no : $invoice_no";
                    $updated_item = null;
                    $deleted_item = null;

                    //save in premium invoice cigna
                    PremiumInvoiceCigna::create([
                        'invoice_no' => $invoice_no,
                        'policy_no' => $policy_no,
                        'family_no' => $request->family_no_principal,
                        'person_no' => $request->person_no_principal,
                        // 'stamp_duty' => str_replace(",", "", $request->stamp_duty),
                        'iiu' => str_replace(",", "", $request->iiu),
                        'premium_net' => str_replace(",", "", $request->premium_net),
                        'total_premium' => str_replace(",", "", $request->total_premium),
                        'date_entered' => $date_entered,
                        'user_id' => $request->user_id,
                        'idx' => $max_idx_family
                    ]);

                    //save in cigna premium invoice details summary
                    for ($i = 0; $i < count($request->name); $i++) {
                        $max_idx_members += 1;
                        CignaPremiumInvoiceDetailsSummary::create([
                            'idx' => $max_idx_members,
                            'name' => $request->name[$i],
                            'family_no' => $request->family_no[$i],
                            'person_no' => $request->person_no[$i],
                            'plan' => $request->plan[$i],
                            'dental' => $request->dental[$i],
                            'dob' => $request->dob[$i],
                            'age' => $request->age[$i],
                            'AOC' => $request->AOC[$i],
                            'payment_frequency' => $request->payment_frequency[$i],
                            'invoice_no' => $invoice_no,
                            'plan_premium' => str_replace(",", "", $request->plan_premium[$i]),
                            'dental_premium' => str_replace(",", "", $request->dental_premium[$i]),
                            'loading' => str_replace(",", "", $request->loading[$i]),
                            'deduction' => str_replace(",", "", $request->deduction[$i]),
                            'date_entered' => $date_entered,
                            'user_id' => $request->user_id
                        ]);
                    }

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                });
                return ['Invoice saved successfully with Invoice no ' . $invoice_no, $invoice_no];
            } else {
                return ['Notice, No members to debit!'];
            }
        } catch (Exception $e) {
            return ['Error, ' . $e->getMessage()];
        }
    }

    public function generateInvoice($invoice_no)
    {
        $plan_table = $dental_table = $frequency_table = $payment_frequency = $family_no = $invoice_period = '';
        $plan_prem  = 0;
        $raw_total_premium = 0;
        $year = date("Y");

        $premium_invoice_data = PremiumInvoiceCigna::select(
            DB::raw("FORMAT(premium_invoice_cigna.date_entered, 'dd/mm/YYYY') as date_entered"),
            'premium_invoice_cigna.idx',
            'premium_invoice_cigna.invoice_no',
            'premium_invoice_cigna.policy_no',
            'premium_invoice_cigna.family_no',
            'premium_invoice_cigna.person_no',
            'premium_invoice_cigna.stamp_duty',
            'premium_invoice_cigna.iiu',
            'premium_invoice_cigna.premium_net',
            'premium_invoice_cigna.total_premium',
            'premium_invoice_cigna.user_id',
            'cigna_member_info.name',
            'cigna_member_info.address_1',
            'cigna_member_info.address_2',
            'cigna_member_info.address_3',
            'cigna_member_info.location',
            'cigna_member_info.email as start_date'
        )
            ->join('cigna_member_info', 'cigna_member_info.family_no', '=', 'premium_invoice_cigna.family_no')
            ->where('premium_invoice_cigna.invoice_no', $invoice_no)
            ->where('cigna_member_info.person_no', 1)
            ->get();
        foreach ($premium_invoice_data as $pid) {
            //get max startdate
            $max_anniv = cigna_member_anniversary::where('family_no', $pid->family_no)->where('person_no', $pid->person_no)->max('anniv');
            $start_date = cigna_member_anniversary::select('start_date')->where('family_no', $pid->family_no)->where('person_no', $pid->person_no)->where('anniv', $max_anniv)->get();
            $pid->start_date = count($start_date) != 0 ? $start_date[0]->start_date : '';

            $raw_total_premium = $pid->total_premium;
            //cigna stamp duty has been removed completely
            //add stamp duty to already calculated premium net because when generating an invoice it's added to principal
            // $pid->premium_net = $pid->premium_net + 9.45;
            $pid->premium_net = $pid->premium_net;
            $pid->premium_net = number_format($pid->premium_net, 2);
            $pid->iiu = number_format($pid->iiu, 2);
            // $pid->stamp_duty = number_format($pid->stamp_duty, 2);
            $pid->total_premium = number_format($pid->total_premium, 2);
        }
        //invoice period
        $start_date = count($premium_invoice_data) != 0 ? $premium_invoice_data[0]->start_date : '';
        $end_date = $start_date != '' ? date('Y-m-d', strtotime($start_date . "+1 Year -1 Day")) : '';
        $invoice_period = date('d/m/Y', strtotime($start_date)) . ' to ' . date('d/m/Y', strtotime($end_date));
        // $stamp_duty = count($premium_invoice_data) != 0 ?  $premium_invoice_data[0]->stamp_duty : 0;

        //summary data
        $summary_data = CignaPremiumInvoiceDetailsSummary::select(
            'cigna_member_info.name',
            'cigna_member_info.person_no',
            'cigna_member_info.family_no',
            'cigna_member_info.address_1 as plan',
            'cigna_member_info.address_2 as dental',
            'cigna_member_info.address_3 as AOC',
            'cigna_member_info.address_4 as payment_frequency',
            'cigna_premium_invoice_details_summary.plan_premium',
            'cigna_premium_invoice_details_summary.dental_premium',
            'cigna_premium_invoice_details_summary.loading',
            'cigna_premium_invoice_details_summary.deduction',
        )
            ->join('cigna_member_info', function ($join) {
                $join->on('cigna_member_info.family_no', '=', 'cigna_premium_invoice_details_summary.family_no')
                    ->on('cigna_member_info.person_no', '=', 'cigna_premium_invoice_details_summary.person_no');
            })
            ->where('cigna_premium_invoice_details_summary.invoice_no', $invoice_no)
            ->get();


        foreach ($summary_data as $dt) {

            //get max startdate
            $max_anniv = cigna_member_anniversary::where('family_no', $dt->family_no)->where('person_no', $dt->person_no)->max('anniv');
            $member_anniv_data = cigna_member_anniversary::select('plan', 'dental', 'AOC', 'payment_frequency')->where('family_no', $dt->family_no)->where('person_no', $dt->person_no)->where('anniv', $max_anniv)->get();
            $dt->plan = count($member_anniv_data) !== 0 ? $member_anniv_data[0]->plan : "";
            $dt->dental = count($member_anniv_data) !== 0 ? $member_anniv_data[0]->dental : "";
            $dt->AOC = count($member_anniv_data) !== 0 ? $member_anniv_data[0]->AOC : "";
            $dt->payment_frequency = count($member_anniv_data) !== 0 ? $member_anniv_data[0]->payment_frequency : "";


            $plan_prem = $dt->plan_premium;

            //calculate deduction
            if (!is_null($dt->deduction) && $dt->deduction != 0) {
                $plan_prem = $plan_prem - (($dt->deduction / 100) * $plan_prem);
            }
            //calculate loading
            if (!is_null($dt->loading) && $dt->loading != 0) {
                $plan_prem = (($dt->loading / 100) * $plan_prem) + $plan_prem;
            }

            // stamp duty for cigna has been removed completely
            //check if principal to add stamp duty
            // if ($dt->person_no == 1) {
            //     $plan_prem =  $plan_prem + $stamp_duty;
            // }
            $payment_frequency = $dt->payment_frequency;
            $family_no = $dt->family_no;
            $plan_table .= "<tr style='border:none;'>
            <td>$dt->name</td>
            <td>$dt->AOC</td>
            <td>$dt->plan</td>
            <td>" . number_format($plan_prem, 2) . " USD</td>
            </tr>";

            if ($dt->dental_premium > 0) {
                $dental_table .= "<tr style='border:none;'>
                                    <td>$dt->name</td>
                                    <td>$dt->AOC</td>
                                    <td>$dt->dental</td>
                                    <td>" . number_format($dt->dental_premium, 2) . " USD</td>
                                   </tr>";
            }
        }
        //get principle's start date
        $max_anniv = cigna_member_anniversary::where('family_no', $family_no)->where('person_no', 1)->max('anniv');
        $start_date = cigna_member_anniversary::select('start_date')->where('family_no', $family_no)->where('person_no', 1)->where('anniv', $max_anniv)->get();
        $start_date = count($start_date) !== 0 ? $start_date[0]->start_date : '';

        //breakdown payment frequency
        if ($payment_frequency == "Q") {
            // $inst1 = "31/03/" . $year;
            // $inst2 = "30/06/" . $year;
            // $inst3 = "30/09/" . $year;
            // $inst4 = "31/12/" . $year;

            $inst1 = date("Y-m-d", strtotime($start_date));
            $inst2 = date("Y-m-d", strtotime($start_date . "+3 Month"));
            $inst3 = date("Y-m-d", strtotime($start_date . "+6 Month"));
            $inst4 = date("Y-m-d", strtotime($start_date . "+9 Month"));
            // $inst4 = date("Y-m-d", strtotime($start_date . "+12 Month -1 day"));

            $raw_total_premium = $raw_total_premium / 4;
            $frequency_table = "<table style='text-align:center;margin-top:10px;width:100%'>
                            <head>
                                <tr style='border:none;'>
                                    <th style='width:30%'>Quarterly</th>
                                    <th style='width:30%'>Due Date</th>
                                    <th style='width:40%'>Amount Due</th>
                                </tr>
                            </head>
                            <body>
                                <tr style='border:none;'>
                                        <td>Installement 1</td>
                                        <td>" . date("d/m/Y", strtotime($inst1))  . "</td>
                                        <td>" . number_format($raw_total_premium, 2) . " USD</td>
                                </tr>
                                <tr style='border:none;'>
                                        <td>Installement 2</td>
                                        <td>" . date("d/m/Y", strtotime($inst2))  . "</td>
                                        <td>" . number_format($raw_total_premium, 2) . " USD</td>
                                </tr>
                                <tr style='border:none;'>
                                        <td>Installement 3</td>
                                        <td>" . date("d/m/Y", strtotime($inst3))  . "</td>
                                        <td>" . number_format($raw_total_premium, 2) . " USD</td>
                                </tr>
                                <tr style='border:none;'>
                                        <td>Installement 4</td>
                                        <td>" . date("d/m/Y", strtotime($inst4))  . "</td>
                                        <td>" . number_format($raw_total_premium, 2) . " USD</td>
                                </tr>
                            </body>
                            </table>";
        } else if ($payment_frequency == "H") {
            // $inst1 = "30/06/" . $year;
            // $inst2 = "31/12/" . $year;
            $inst1 = date("Y-m-d", strtotime($start_date));
            $inst2 = date("Y-m-d", strtotime($start_date . "+6 Month"));
            // $inst2 = date("Y-m-d", strtotime($start_date . "+12 Month -1 day"));

            $raw_total_premium = $raw_total_premium / 2;
            $frequency_table = "<table style='text-align:center;margin-top:10px;width:100%'>
                            <head>
                                <tr style='border:none;'>
                                    <th style='width:30%'>Half Year</th>
                                    <th style='width:30%'>Due Date</th>
                                    <th style='width:40%'>Amount Due</th>
                                </tr>
                            </head>
                            <body>         
                                    <tr style='border:none;'>
                                        <td>Installement 1</td>
                                        <td>" . date("d/m/Y", strtotime($inst1))  . "</td>
                                        <td>" . number_format($raw_total_premium, 2) . " USD</td>
                                    </tr>
                                    <tr style='border:none;'>
                                        <td>Installement 2</td>
                                        <td>" . date("d/m/Y", strtotime($inst2))  . "</td>
                                        <td>" . number_format($raw_total_premium, 2) . " USD</td>
                                    </tr>                                     
                                </body>
                                </table>";
        } else {
            // $day = $year . "-12-31";
            $inst1 = date("Y-m-d", strtotime($start_date));
            // $inst1 = date("Y-m-d", strtotime($start_date . "+12 Month -1 day"));
            $frequency_table = "<table style='text-align:center;margin-top:10px;width:100%'>
                                <head>
                                    <tr style='border:none;'>
                                        <th style='width:30%'>Annual</th>
                                        <th style='width:30%'>Due Date</th>
                                        <th style='width:40%'>Amount Due</th>
                                    </tr>
                                </head>
                                <body>
                                    <tr style='border:none;'>
                                        <td>Installement 1</td>
                                        <td>" . date("d/m/Y", strtotime($inst1))  . "</td>
                                        <td>" . number_format($raw_total_premium, 2) . " USD</td>
                                    </tr>
                                </body>
                            </table>";
        }


        return ['premium_invoice_data' => $premium_invoice_data, 'invoice_period' => $invoice_period, 'plan_table' => $plan_table, 'dental_table' => $dental_table, 'frequency_table' => $frequency_table];
    }
}
