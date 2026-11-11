<?php

namespace App\Http\Controllers\Finance\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\PremiumInvoice;
use App\Models\PremiumReceipt;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapaController extends Controller
{
    // public function fetchMapaOld(Request $request)
    // {
    //     // ini_set('memory_limit', '1524M');
    //     ini_set('max_execution_time', '0');

    //     $report = [];
    //     $ll_loss_ratio_ep = $ll_loss_ratio_cp = 0;
    //     $ld_asat = date("Y-m-d", strtotime($request->date));
    //     $ls_asat = date("Ymd", strtotime($request->date));

    //     $datas = Corporate::select(
    //         DB::raw("datediff(day, FORMAT(corp_anniversary.start_date, 'yyyyMMdd'), $ls_asat)+1 as ll_ear_days"),
    //         DB::raw("datediff(day, $ls_asat, FORMAT(corp_anniversary.end_date, 'yyyyMMdd')) as ll_un_ear_days"),
    //         DB::raw("FORMAT(corp_anniversary.start_date, 'yyyy-mm-dd') as start_date"),
    //         DB::raw("FORMAT(corp_anniversary.end_date, 'yyyy-mm-dd') as end_date"),
    //         DB::raw("case 
    //                     when corporate.individual = 1 then 'CORPORATE' 
    //                     when corporate.individual = 2 then 'RETAIL' 
    //                     when corporate.individual = 3 then 'SME' 
    //                 END as corp_type
    //         "),
    //         'corp_anniversary.anniv',
    //         'corporate.corporate',
    //         'corporate.corp_id',
    //     )
    //         ->join('corp_anniversary', 'corp_anniversary.corp_id', 'corporate.corp_id')
    //         // ->where('corporate.individual', [1, 3])
    //         ->where('corp_anniversary.start_date', "<=", $ld_asat)
    //         ->where('corp_anniversary.end_date', ">=", $ld_asat)
    //         ->whereNotIn('corporate.corp_id', [14, 658])
    //         ->orderBy('corporate.corporate', 'ASC')
    //         ->get();


    //     //corps
    //     foreach ($datas as $data) {
    //         $ls_name = $data->corporate;
    //         $ls_corp_id = $data->corp_id;
    //         $corp_type = 'Corporate';
    //         $ll_anniv = $data->anniv;
    //         // $ll_anniv = CorpAnniversary::select('anniv')->where('start_date', $data->start_date)->where('end_date', $data->end_date)->get();
    //         // $ll_anniv = count($ll_anniv) !== 0 ? $ll_anniv[0]->anniv : '';
    //         $ld_str = $data->start_date;
    //         $ls_str = date("Ymd", strtotime($data->start_date));
    //         $ld_end_date = $data->end_date;
    //         $ll_ear_days = $data->ll_ear_days;
    //         $ll_un_ear_days = $data->ll_un_ear_days;

    //         //max corp anniversary
    //         $max_anniv = CorpAnniversary::where('corp_id', $ls_corp_id)->max('anniv');



    //         $ll_mems = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
    //             ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
    //             ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
    //             ->where('member_anniversary.anniv', $max_anniv)
    //             ->where('principal_applicant.corp_id', $ls_corp_id)
    //             ->count('member_info.member_no');


    //         $ll_mems_strt = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
    //             ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
    //             ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
    //             ->where(DB::raw("FORMAT(member_anniversary.start_date, 'yyyyMMdd')"), $ls_str)
    //             ->where('principal_applicant.corp_id', $ls_corp_id)
    //             ->count('member_info.member_no');

    //         $prem_invoice_data = PremiumInvoice::select(
    //             DB::raw("sum(isnull(premium_net, 0)) as lc_gross_prem"),
    //             DB::raw("sum(isnull(retained, 0)) as lc_retained"),
    //             DB::raw("sum(isnull(ceded, 0)) as lc_ceded"),
    //         )
    //             ->whereBetween(DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"), [$ls_str, $ls_asat])
    //             // ->where(DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"), '<=', $ls_asat)
    //             // ->where('anniv', $ll_anniv)
    //             ->where('corp_id', $ls_corp_id)
    //             ->get();

    //         $lc_gross_prem = count($prem_invoice_data) == 0 ? 0 : $prem_invoice_data[0]->lc_gross_prem;
    //         $lc_retained = count($prem_invoice_data) == 0 ? 0 : $prem_invoice_data[0]->lc_retained;
    //         $lc_ceded = count($prem_invoice_data) == 0 ? 0 : $prem_invoice_data[0]->lc_ceded;

    //         $lc_receipt = PremiumReceipt::select(
    //             DB::raw("sum(isnull(receipt_amount, 0)) as lc_receipt")
    //         )
    //             ->whereBetween(
    //                 DB::raw("FORMAT(receipt_date, 'yyyyMMdd')"),
    //                 [$ls_str, $ls_asat]
    //             )
    //             ->where('corp_id', $ls_corp_id)
    //             ->get();

    //         $lc_receipt = count($lc_receipt) !== 0 ? $lc_receipt[0]->lc_receipt : 0;
    //         //upr
    //         $lc_earned_prem = ($ll_ear_days * $lc_gross_prem) / 365;
    //         $lc_upr = $lc_gross_prem - $lc_earned_prem;
    //         //claims
    //         $lc_vet_sum = Bills::select(
    //             DB::raw("sum(isnull(bills.amount_payable,0)) as amount_payable")
    //         )
    //             ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
    //             ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.member_no')
    //             ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
    //             ->where(DB::raw("isnull(bills.fund, 0)"), 0)
    //             ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [1, 3])
    //             ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$ls_str, $ls_asat])
    //             ->where('principal_applicant.corp_id', $ls_corp_id)
    //             ->get();



    //         $lc_vet_sum =  count($lc_vet_sum) !== 0 ? $lc_vet_sum[0]->amount_payable : 0;


    //         $lc_inv_amount = Bills::select(
    //             DB::raw("sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount")
    //         )
    //             ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
    //             ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.member_no')
    //             ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
    //             ->where(DB::raw("isnull(bills.fund, 0)"), 0)
    //             ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$ls_str, $ls_asat])
    //             ->where('principal_applicant.corp_id', $ls_corp_id)
    //             ->get();

    //         $lc_inv_amount =  count($lc_inv_amount) !== 0 ? $lc_inv_amount[0]->invoiced_amount : 0;

    //         //calculating average invoice premium
    //         $invoice_total  = PremiumInvoice::select(DB::raw("sum(isnull(invoice_total, 0)) as invoice_total"))
    //             // ->where('anniv', $max_anniv)
    //             ->whereIn('new_renewal', [1, 2, 7])
    //             ->whereBetween(DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"), [$ls_str, $ls_asat])
    //             ->where('corp_id', $ls_corp_id)
    //             ->get();
    //         $invoice_total  = count($invoice_total) !== 0 ? $invoice_total[0]->invoice_total : 0;
    //         $average_invoice_premium = $ll_mems_strt === 0 ? 0 : ($invoice_total / $ll_mems_strt);


    //         if ($lc_vet_sum > 0 && $lc_earned_prem > 0) {
    //             $ll_loss_ratio_ep = ($lc_vet_sum / $lc_earned_prem) * 100;
    //         }
    //         if ($lc_vet_sum > 0 && $lc_receipt > 0) {
    //             $ll_loss_ratio_cp = ($lc_vet_sum / $lc_receipt) * 100;
    //         }


    //         $dt = [
    //             'corporate' => json_encode($ls_name, JSON_INVALID_UTF8_IGNORE),
    //             'lives' => $ll_mems,
    //             'live_on_star_date' => $ll_mems_strt,
    //             'invoice_total' => number_format(
    //                 $invoice_total,
    //                 2
    //             ),
    //             'average_invoice_premium' => number_format(
    //                 $average_invoice_premium,
    //                 2
    //             ),
    //             'corp_type' => $corp_type,
    //             'effective_date' => date("d/m/Y", strtotime($ld_str)),
    //             'end_date' => date("d/m/Y", strtotime($ld_end_date)),
    //             'earned_days' => $ll_ear_days,
    //             'unexpired_days' => $ll_un_ear_days,
    //             'gross_written_premium' => number_format(
    //                 $lc_gross_prem,
    //                 2
    //             ),
    //             'premium_retained' => number_format(
    //                 $lc_retained,
    //                 2
    //             ),
    //             'premium_ceded' => number_format(
    //                 $lc_ceded,
    //                 2
    //             ),
    //             'collected_prem' => number_format(
    //                 $lc_receipt,
    //                 2
    //             ),
    //             'earned_prem' => number_format(
    //                 $lc_earned_prem,
    //                 2
    //             ),
    //             'upr' => number_format(
    //                 $lc_upr,
    //                 2
    //             ),
    //             'tot_claims' => number_format(
    //                 $lc_vet_sum,
    //                 2
    //             ),
    //             'invoiced_claims' => number_format(
    //                 $lc_inv_amount,
    //                 2
    //             ),
    //             'loss_ratio' => number_format(
    //                 $ll_loss_ratio_ep,
    //                 2
    //             ),
    //             'loss_ratio_cp' => number_format($ll_loss_ratio_cp, 2),
    //             'current_anniv'=> $max_anniv
    //         ];

    //         array_push($report, $dt);
    //     }

    //     //inds
    //     $mem_data = PrincipalApplicant::select(
    //         DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as names"),
    //         DB::raw("datediff(day, FORMAT(member_anniversary.start_date, 'yyyyMMdd'), $ls_asat)+1 as ll_ear_days"),
    //         DB::raw("datediff(day, $ls_asat, FORMAT(member_anniversary.end_date, 'yyyyMMdd')) as ll_un_ear_days"),
    //         DB::raw("FORMAT(member_anniversary.start_date, 'yyyy-mm-dd') as start_date"),
    //         DB::raw("FORMAT(member_anniversary.end_date, 'yyyy-mm-dd') as end_date"),
    //         'principal_applicant.family_no',
    //         'principal_applicant.member_no',
    //         'member_anniversary.anniv'
    //     )
    //         ->join('member_info', 'member_info.member_no', '=', 'principal_applicant.member_no')
    //         ->join('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
    //         ->orderBy('names', 'ASC')
    //         ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
    //         ->where('member_anniversary.start_date', '<=', $ld_asat)
    //         ->where('member_anniversary.end_date', '>=', $ld_asat)
    //         // ->where('principal_applicant.individual', 2)
    //         ->whereIn('principal_applicant.corp_id', [14, 658])
    //         ->get();

    //     foreach ($mem_data as $mdata) {
    //         $ls_family_no = $mdata->family_no;
    //         $ls_mem_no = $mdata->member_no;
    //         $ls_name = $mdata->names;
    //         $ll_anniv = $mdata->anniv;
    //         $ld_str = $mdata->start_date;
    //         $ls_str = date("Ymd", strtotime($ld_str));
    //         $ld_end_date = $mdata->end_date;
    //         $ll_ear_days = $mdata->ll_ear_days;
    //         $ll_un_ear_days = $mdata->ll_un_ear_days;
    //         $corp_type = 'Individual';

    //         //max family anniv
    //         $max_anniv = MemberAnniversary::where('member_no', $ls_mem_no)->max('anniv');


    //         $ll_mems = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
    //             ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
    //             ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
    //             ->where('member_anniversary.anniv', $max_anniv)
    //             ->where('principal_applicant.family_no', $ls_family_no)
    //             ->count('member_info.member_no');

    //         $ll_mems_strt = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
    //             ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
    //             ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
    //             ->where(DB::raw("FORMAT(member_anniversary.start_date, 'yyyyMMdd')"), $ls_str)
    //             ->where('principal_applicant.family_no', $ls_family_no)
    //             ->count('member_info.member_no');


    //         $prem_invoice_data = PremiumInvoice::select(
    //             DB::raw("sum(isnull(premium_net, 0)) as lc_gross_prem"),
    //             DB::raw("sum(isnull(retained, 0)) as lc_retained"),
    //             DB::raw("sum(isnull(ceded, 0)) as lc_ceded"),
    //         )
    //             ->whereBetween(DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"), [$ls_str, $ls_asat])
    //             // ->where(DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"), '<=', $ls_asat)
    //             // ->where('anniv', $ll_anniv)
    //             ->where('member_no', $ls_mem_no)
    //             ->get();

    //         $lc_gross_prem = count($prem_invoice_data) == 0 ? 0 : $prem_invoice_data[0]->lc_gross_prem;
    //         $lc_retained = count($prem_invoice_data) == 0 ? 0 : $prem_invoice_data[0]->lc_retained;
    //         $lc_ceded = count($prem_invoice_data) == 0 ? 0 : $prem_invoice_data[0]->lc_ceded;



    //         $lc_receipt = PremiumReceipt::select(
    //             DB::raw("sum(isnull(receipt_amount, 0)) as lc_receipt")
    //         )
    //             ->whereBetween(
    //                 DB::raw("FORMAT(receipt_date, 'yyyyMMdd')"),
    //                 [$ls_str, $ls_asat]
    //             )
    //             ->where('member_no', $ls_mem_no)
    //             ->get();

    //         $lc_receipt = count($lc_receipt) !== 0 ? $lc_receipt[0]->lc_receipt : 0;


    //         //upr
    //         $lc_earned_prem = ($ll_ear_days * $lc_gross_prem) / 365;
    //         $lc_upr = $lc_gross_prem - $lc_earned_prem;
    //         //claims totals payable
    //         $lc_vet_sum = Bills::select(
    //             DB::raw("sum(isnull(bills.amount_payable, 0)) as amount_payable")
    //         )
    //             ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
    //             ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [1, 3])
    //             ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$ls_str, $ls_asat])
    //             ->where('member_info.family_no', $ls_family_no)
    //             ->get();

    //         $lc_vet_sum =  count($lc_vet_sum) !== 0 ? $lc_vet_sum[0]->amount_payable : 0;

    //         $lc_inv_amount = Bills::select(
    //             DB::raw("sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount")
    //         )
    //             ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
    //             ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$ls_str, $ls_asat])
    //             ->where('member_info.family_no', $ls_family_no)
    //             ->get();

    //         $lc_inv_amount =  count($lc_inv_amount) !== 0 ? $lc_inv_amount[0]->invoiced_amount : 0;

    //         //calculating average invoice premium
    //         $invoice_total  = PremiumInvoice::select(
    //             DB::raw("isnull(invoice_total, 0) as invoice_total")
    //         )
    //             ->whereIn('new_renewal', [1, 2])
    //             ->where('anniv', $max_anniv)
    //             ->where('member_no', $ls_mem_no)
    //             ->get();
    //         $invoice_total  = count($invoice_total) !== 0 ? $invoice_total[0]->invoice_total : 0;
    //         $average_invoice_premium = $ll_mems_strt === 0 ? 0 : ($invoice_total / $ll_mems_strt);

    //         if ($lc_vet_sum > 0 && $lc_earned_prem > 0) {
    //             $ll_loss_ratio_ep = ($lc_vet_sum / $lc_earned_prem) * 100;
    //         }
    //         if ($lc_vet_sum > 0 && $lc_receipt > 0) {
    //             $ll_loss_ratio_cp = ($lc_vet_sum / $lc_receipt) * 100;
    //         }

    //         $dt = [
    //             'corporate' => json_encode($ls_name, JSON_INVALID_UTF8_IGNORE),
    //             'lives' => $ll_mems,
    //             'live_on_star_date' => $ll_mems_strt,
    //             'invoice_total' => number_format(
    //                 $invoice_total,
    //                 2
    //             ),
    //             'average_invoice_premium' => number_format(
    //                 $average_invoice_premium,
    //                 2
    //             ),
    //             'corp_type' => $corp_type,
    //             'effective_date' => date("d/m/Y", strtotime($ld_str)),
    //             'end_date' => date("d/m/Y", strtotime($ld_end_date)),
    //             'earned_days' => $ll_ear_days,
    //             'unexpired_days' => $ll_un_ear_days,
    //             'gross_written_premium' => number_format(
    //                 $lc_gross_prem,
    //                 2
    //             ),
    //             'premium_retained' => number_format(
    //                 $lc_retained,
    //                 2
    //             ),
    //             'premium_ceded' => number_format(
    //                 $lc_ceded,
    //                 2
    //             ),
    //             'collected_prem' => number_format(
    //                 $lc_receipt,
    //                 2
    //             ),
    //             'earned_prem' => number_format(
    //                 $lc_earned_prem,
    //                 2
    //             ),
    //             'upr' => number_format(
    //                 $lc_upr,
    //                 2
    //             ),
    //             'tot_claims' => number_format(
    //                 $lc_vet_sum,
    //                 2
    //             ),
    //             'invoiced_claims' => number_format(
    //                 $lc_inv_amount,
    //                 2
    //             ),
    //             'loss_ratio' => number_format(
    //                 $ll_loss_ratio_ep,
    //                 2
    //             ),
    //             'loss_ratio_cp' => number_format($ll_loss_ratio_cp, 2),
    //             'current_anniv'=>$max_anniv
    //         ];

    //         array_push($report, $dt);
    //     }

    //     return $report;
    // }
    public function fetchMapa(Request $request)
{
    // ini_set('memory_limit', '1524M');
    // ini_set('max_execution_time', '0'); // You might even be able to remove this now!

    $report = [];
    $ld_asat = date("Y-m-d", strtotime($request->date));
    $ls_asat = date("Ymd", strtotime($request->date));

    // =========================================================================
    // SECTION 1: CORPORATE PROCESSING
    // =========================================================================

    $datas = Corporate::select(
        DB::raw("datediff(day, FORMAT(corp_anniversary.start_date, 'yyyyMMdd'), $ls_asat)+1 as ll_ear_days"),
        DB::raw("datediff(day, $ls_asat, FORMAT(corp_anniversary.end_date, 'yyyyMMdd')) as ll_un_ear_days"),
        DB::raw("FORMAT(corp_anniversary.start_date, 'yyyy-mm-dd') as start_date"),
        DB::raw("FORMAT(corp_anniversary.end_date, 'yyyy-mm-dd') as end_date"),
        DB::raw("case 
                    when corporate.individual = 1 then 'CORPORATE' 
                    when corporate.individual = 2 then 'RETAIL' 
                    when corporate.individual = 3 then 'SME' 
                END as corp_type"),
        'corp_anniversary.anniv',
        'corporate.corporate',
        'corporate.corp_id'
    )
    ->join('corp_anniversary', 'corp_anniversary.corp_id', 'corporate.corp_id')
    ->where('corp_anniversary.start_date', "<=", $ld_asat)
    ->where('corp_anniversary.end_date', ">=", $ld_asat)
    ->whereNotIn('corporate.corp_id', [14, 658])
    ->orderBy('corporate.corporate', 'ASC')
    ->get();

    if ($datas->isNotEmpty()) {
        $corpIds = $datas->pluck('corp_id')->unique()->toArray();
        
        // Batch 1: Max Anniversary per Corporate
        $corpMaxAnnivs = CorpAnniversary::whereIn('corp_id', $corpIds)
            ->groupBy('corp_id')
            ->select('corp_id', DB::raw('max(anniv) as max_anniv'))
            ->pluck('max_anniv', 'corp_id');

        // Batch 2: Members Count (lives) grouped by corp_id
        // (Note: To handle the variable $max_anniv dynamically per corp, we join against our pre-calculated max annivs)
        $corpMems = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
            ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
            ->joinSub(
                CorpAnniversary::whereIn('corp_id', $corpIds)->groupBy('corp_id')->select('corp_id', DB::raw('max(anniv) as max_anniv')),
                'max_annivs',
                'max_annivs.corp_id',
                '=',
                'principal_applicant.corp_id'
            )
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->whereRaw('member_anniversary.anniv = max_annivs.max_anniv')
            ->whereIn('principal_applicant.corp_id', $corpIds)
            ->groupBy('principal_applicant.corp_id')
            ->select('principal_applicant.corp_id', DB::raw('count(member_info.member_no) as aggregate'))
            ->pluck('aggregate', 'corp_id');

        // Batch 3: Premium Invoices grouped by corp_id and start_date combinations
        // We structure key as "corp_id-start_date" for distinct filtering matching your loop logic
        $corpInvoiceData = PremiumInvoice::whereIn('corp_id', $corpIds)
            ->groupBy('corp_id', DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"))
            ->select(
                'corp_id',
                DB::raw("FORMAT(invoice_date, 'yyyyMMdd') as formatted_invoice_date"),
                DB::raw("sum(isnull(premium_net, 0)) as lc_gross_prem"),
                DB::raw("sum(isnull(retained, 0)) as lc_retained"),
                DB::raw("sum(isnull(ceded, 0)) as lc_ceded")
            )->get()->groupBy('corp_id');

        // Batch 4: Receipts
        $corpReceiptsData = PremiumReceipt::whereIn('corp_id', $corpIds)
            ->groupBy('corp_id', DB::raw("FORMAT(receipt_date, 'yyyyMMdd')"))
            ->select(
                'corp_id',
                DB::raw("FORMAT(receipt_date, 'yyyyMMdd') as formatted_receipt_date"),
                DB::raw("sum(isnull(receipt_amount, 0)) as lc_receipt")
            )->get()->groupBy('corp_id');

        // Batch 5: Consolidated Claims (Bills) - Pulling both payable and invoiced at once!
        $corpBillsData = Bills::leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.member_no')
            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
            ->where(DB::raw("isnull(bills.fund, 0)"), 0)
            ->whereIn('principal_applicant.corp_id', $corpIds)
            ->groupBy('principal_applicant.corp_id', DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"))
            ->select(
                'principal_applicant.corp_id',
                DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd') as formatted_invoice_date"),
                DB::raw("sum(case when isnull(bills.vet_status, 0) in (1, 3) then isnull(bills.amount_payable,0) else 0 end) as amount_payable"),
                DB::raw("sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount")
            )->get()->groupBy('corp_id');

        // Batch 6: Average Invoice Total Premium
        $corpInvoiceTotals = PremiumInvoice::whereIn('corp_id', $corpIds)
            ->whereIn('new_renewal', [1, 2, 7])
            ->groupBy('corp_id', DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"))
            ->select(
                'corp_id',
                DB::raw("FORMAT(invoice_date, 'yyyyMMdd') as formatted_invoice_date"),
                DB::raw("sum(isnull(invoice_total, 0)) as invoice_total")
            )->get()->groupBy('corp_id');
            
        // Batch 7: Members Start Count
        $corpMemsStart = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
            ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->whereIn('principal_applicant.corp_id', $corpIds)
            ->groupBy('principal_applicant.corp_id', DB::raw("FORMAT(member_anniversary.start_date, 'yyyyMMdd')"))
            ->select(
                'principal_applicant.corp_id',
                DB::raw("FORMAT(member_anniversary.start_date, 'yyyyMMdd') as start_date_formatted"),
                DB::raw('count(member_info.member_no) as aggregate')
            )->get()->groupBy('corp_id');

        // Loop execution with $O(1)$ memory lookups
        foreach ($datas as $data) {
            $ll_loss_ratio_ep = $ll_loss_ratio_cp = 0;
            $ls_corp_id = $data->corp_id;
            $ls_str = date("Ymd", strtotime($data->start_date));

            $max_anniv = $corpMaxAnnivs->get($ls_corp_id, 0);
            $ll_mems = $corpMems->get($ls_corp_id, 0);

            // Filter counts from pre-calculated start dates
            $ll_mems_strt = 0;
            if ($corpMemsStart->has($ls_corp_id)) {
                $ll_mems_strt = $corpMemsStart->get($ls_corp_id)->where('start_date_formatted', $ls_str)->sum('aggregate');
            }

            // Invoices Filter Between Loop
            $lc_gross_prem = $lc_retained = $lc_ceded = 0;
            if ($corpInvoiceData->has($ls_corp_id)) {
                $filteredInvoices = $corpInvoiceData->get($ls_corp_id)->whereBetween('formatted_invoice_date', [$ls_str, $ls_asat]);
                $lc_gross_prem = $filteredInvoices->sum('lc_gross_prem');
                $lc_retained = $filteredInvoices->sum('lc_retained');
                $lc_ceded = $filteredInvoices->sum('lc_ceded');
            }

            // Receipts Filter Between Loop
            $lc_receipt = 0;
            if ($corpReceiptsData->has($ls_corp_id)) {
                $lc_receipt = $corpReceiptsData->get($ls_corp_id)->whereBetween('formatted_receipt_date', [$ls_str, $ls_asat])->sum('lc_receipt');
            }

            $lc_earned_prem = ($data->ll_ear_days * $lc_gross_prem) / 365;
            $lc_upr = $lc_gross_prem - $lc_earned_prem;

            // Consolidated Bills Filter
            $lc_vet_sum = $lc_inv_amount = 0;
            if ($corpBillsData->has($ls_corp_id)) {
                $filteredBills = $corpBillsData->get($ls_corp_id)->whereBetween('formatted_invoice_date', [$ls_str, $ls_asat]);
                $lc_vet_sum = $filteredBills->sum('amount_payable');
                $lc_inv_amount = $filteredBills->sum('invoiced_amount');
            }

            // Invoice Total Filter
            $invoice_total = 0;
            if ($corpInvoiceTotals->has($ls_corp_id)) {
                $invoice_total = $corpInvoiceTotals->get($ls_corp_id)->whereBetween('formatted_invoice_date', [$ls_str, $ls_asat])->sum('invoice_total');
            }
            $average_invoice_premium = $ll_mems_strt === 0 ? 0 : ($invoice_total / $ll_mems_strt);

            if ($lc_vet_sum > 0 && $lc_earned_prem > 0) {
                $ll_loss_ratio_ep = ($lc_vet_sum / $lc_earned_prem) * 100;
            }
            if ($lc_vet_sum > 0 && $lc_receipt > 0) {
                $ll_loss_ratio_cp = ($lc_vet_sum / $lc_receipt) * 100;
            }

            $report[] = [
                'corporate' => json_encode($data->corporate, JSON_INVALID_UTF8_IGNORE),
                'lives' => $ll_mems,
                'live_on_star_date' => $ll_mems_strt,
                'invoice_total' => number_format($invoice_total, 2),
                'average_invoice_premium' => number_format($average_invoice_premium, 2),
                'corp_type' => 'Corporate',
                'effective_date' => date("d/m/Y", strtotime($data->start_date)),
                'end_date' => date("d/m/Y", strtotime($data->end_date)),
                'earned_days' => $data->ll_ear_days,
                'unexpired_days' => $data->ll_un_ear_days,
                'gross_written_premium' => number_format($lc_gross_prem, 2),
                'premium_retained' => number_format($lc_retained, 2),
                'premium_ceded' => number_format($lc_ceded, 2),
                'collected_prem' => number_format($lc_receipt, 2),
                'earned_prem' => number_format($lc_earned_prem, 2),
                'upr' => number_format($lc_upr, 2),
                'tot_claims' => number_format($lc_vet_sum, 2),
                'invoiced_claims' => number_format($lc_inv_amount, 2),
                'loss_ratio' => number_format($ll_loss_ratio_ep, 2),
                'loss_ratio_cp' => number_format($ll_loss_ratio_cp, 2),
                'current_anniv'=> $max_anniv
            ];
        }
    }

    // =========================================================================
    // SECTION 2: INDIVIDUAL PROCESSING
    // =========================================================================

    $mem_data = PrincipalApplicant::select(
        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as names"),
        DB::raw("datediff(day, FORMAT(member_anniversary.start_date, 'yyyyMMdd'), $ls_asat)+1 as ll_ear_days"),
        DB::raw("datediff(day, $ls_asat, FORMAT(member_anniversary.end_date, 'yyyyMMdd')) as ll_un_ear_days"),
        DB::raw("FORMAT(member_anniversary.start_date, 'yyyy-mm-dd') as start_date"),
        DB::raw("FORMAT(member_anniversary.end_date, 'yyyy-mm-dd') as end_date"),
        'principal_applicant.family_no',
        'principal_applicant.member_no',
        'member_anniversary.anniv'
    )
    ->join('member_info', 'member_info.member_no', '=', 'principal_applicant.member_no')
    ->join('member_anniversary', 'member_anniversary.member_no', '=', 'principal_applicant.member_no')
    ->orderBy('names', 'ASC')
    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
    ->where('member_anniversary.start_date', '<=', $ld_asat)
    ->where('member_anniversary.end_date', '>=', $ld_asat)
    ->whereIn('principal_applicant.corp_id', [14, 658])
    ->get();

    if ($mem_data->isNotEmpty()) {
        $memNos = $mem_data->pluck('member_no')->unique()->toArray();
        $familyNos = $mem_data->pluck('family_no')->unique()->toArray();

        // Batch 1: Max Anniversary per Member
        $memMaxAnnivs = MemberAnniversary::whereIn('member_no', $memNos)
            ->groupBy('member_no')
            ->select('member_no', DB::raw('max(anniv) as max_anniv'))
            ->pluck('max_anniv', 'member_no');

        // Batch 2: Lives grouped by family_no
        $indMems = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
            ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
            ->joinSub(
                MemberAnniversary::whereIn('member_no', $memNos)->groupBy('member_no')->select('member_no', DB::raw('max(anniv) as max_anniv')),
                'max_annivs',
                'max_annivs.member_no',
                '=',
                'principal_applicant.member_no'
            )
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->whereRaw('member_anniversary.anniv = max_annivs.max_anniv')
            ->whereIn('principal_applicant.family_no', $familyNos)
            ->groupBy('principal_applicant.family_no')
            ->select('principal_applicant.family_no', DB::raw('count(member_info.member_no) as aggregate'))
            ->pluck('aggregate', 'family_no');

        // Batch 3: Live Start Count grouped by family_no
        $indMemsStart = MemberInfo::leftJoin('principal_applicant', 'principal_applicant.family_no', 'member_info.family_no')
            ->leftJoin('member_anniversary', 'member_anniversary.member_no', 'member_info.member_no')
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->whereIn('principal_applicant.family_no', $familyNos)
            ->groupBy('principal_applicant.family_no', DB::raw("FORMAT(member_anniversary.start_date, 'yyyyMMdd')"))
            ->select(
                'principal_applicant.family_no',
                DB::raw("FORMAT(member_anniversary.start_date, 'yyyyMMdd') as start_date_formatted"),
                DB::raw('count(member_info.member_no) as aggregate')
            )->get()->groupBy('family_no');

        // Batch 4: Invoices grouped by member_no
        $indInvoiceData = PremiumInvoice::whereIn('member_no', $memNos)
            ->groupBy('member_no', DB::raw("FORMAT(invoice_date, 'yyyyMMdd')"))
            ->select(
                'member_no',
                DB::raw("FORMAT(invoice_date, 'yyyyMMdd') as formatted_invoice_date"),
                DB::raw("sum(isnull(premium_net, 0)) as lc_gross_prem"),
                DB::raw("sum(isnull(retained, 0)) as lc_retained"),
                DB::raw("sum(isnull(ceded, 0)) as lc_ceded")
            )->get()->groupBy('member_no');

        // Batch 5: Receipts grouped by member_no
        $indReceiptsData = PremiumReceipt::whereIn('member_no', $memNos)
            ->groupBy('member_no', DB::raw("FORMAT(receipt_date, 'yyyyMMdd')"))
            ->select(
                'member_no',
                DB::raw("FORMAT(receipt_date, 'yyyyMMdd') as formatted_receipt_date"),
                DB::raw("sum(isnull(receipt_amount, 0)) as lc_receipt")
            )->get()->groupBy('member_no');

        // Batch 6: Consolidated Bills grouped by family_no
        $indBillsData = Bills::leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->whereIn('member_info.family_no', $familyNos)
            ->groupBy('member_info.family_no', DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"))
            ->select(
                'member_info.family_no',
                DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd') as formatted_invoice_date"),
                DB::raw("sum(case when isnull(bills.vet_status, 0) in (1, 3) then isnull(bills.amount_payable,0) else 0 end) as amount_payable"),
                DB::raw("sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount")
            )->get()->groupBy('family_no');

        // Batch 7: Average Invoice Total Premium grouped by member_no
        $indInvoiceTotals = PremiumInvoice::whereIn('member_no', $memNos)
            ->whereIn('new_renewal', [1, 2])
            ->groupBy('member_no', 'anniv')
            ->select(
                'member_no',
                'anniv',
                DB::raw("sum(isnull(invoice_total, 0)) as invoice_total")
            )->get()->groupBy('member_no');

        // Loop execution
        foreach ($mem_data as $mdata) {
            $ll_loss_ratio_ep = $ll_loss_ratio_cp = 0;
            $ls_family_no = $mdata->family_no;
            $ls_mem_no = $mdata->member_no;
            $ls_str = date("Ymd", strtotime($mdata->start_date));

            $max_anniv = $memMaxAnnivs->get($ls_mem_no, 0);
            $ll_mems = $indMems->get($ls_family_no, 0);

            // Filter counts from pre-calculated start dates
            $ll_mems_strt = 0;
            if ($indMemsStart->has($ls_family_no)) {
                $ll_mems_strt = $indMemsStart->get($ls_family_no)->where('start_date_formatted', $ls_str)->sum('aggregate');
            }

            // Invoices Filter Between Loop
            $lc_gross_prem = $lc_retained = $lc_ceded = 0;
            if ($indInvoiceData->has($ls_mem_no)) {
                $filteredInvoices = $indInvoiceData->get($ls_mem_no)->whereBetween('formatted_invoice_date', [$ls_str, $ls_asat]);
                $lc_gross_prem = $filteredInvoices->sum('lc_gross_prem');
                $lc_retained = $filteredInvoices->sum('lc_retained');
                $lc_ceded = $filteredInvoices->sum('lc_ceded');
            }

            // Receipts Filter Between Loop
            $lc_receipt = 0;
            if ($indReceiptsData->has($ls_mem_no)) {
                $lc_receipt = $indReceiptsData->get($ls_mem_no)->whereBetween('formatted_receipt_date', [$ls_str, $ls_asat])->sum('lc_receipt');
            }

            $lc_earned_prem = ($mdata->ll_ear_days * $lc_gross_prem) / 365;
            $lc_upr = $lc_gross_prem - $lc_earned_prem;

            // Consolidated Bills Filter
            $lc_vet_sum = $lc_inv_amount = 0;
            if ($indBillsData->has($ls_family_no)) {
                $filteredBills = $indBillsData->get($ls_family_no)->whereBetween('formatted_invoice_date', [$ls_str, $ls_asat]);
                $lc_vet_sum = $filteredBills->sum('amount_payable');
                $lc_inv_amount = $filteredBills->sum('invoiced_amount');
            }

            // Invoice Total Filter matching max_anniv logic precisely
            $invoice_total = 0;
            if ($indInvoiceTotals->has($ls_mem_no)) {
                $invoice_total = $indInvoiceTotals->get($ls_mem_no)->where('anniv', $max_anniv)->sum('invoice_total');
            }
            $average_invoice_premium = $ll_mems_strt === 0 ? 0 : ($invoice_total / $ll_mems_strt);

            if ($lc_vet_sum > 0 && $lc_earned_prem > 0) {
                $ll_loss_ratio_ep = ($lc_vet_sum / $lc_earned_prem) * 100;
            }
            if ($lc_vet_sum > 0 && $lc_receipt > 0) {
                $ll_loss_ratio_cp = ($lc_vet_sum / $lc_receipt) * 100;
            }

            $report[] = [
                'corporate' => json_encode($mdata->names, JSON_INVALID_UTF8_IGNORE),
                'lives' => $ll_mems,
                'live_on_star_date' => $ll_mems_strt,
                'invoice_total' => number_format($invoice_total, 2),
                'average_invoice_premium' => number_format($average_invoice_premium, 2),
                'corp_type' => 'Individual',
                'effective_date' => date("d/m/Y", strtotime($mdata->start_date)),
                'end_date' => date("d/m/Y", strtotime($mdata->end_date)),
                'earned_days' => $mdata->ll_ear_days,
                'unexpired_days' => $mdata->ll_un_ear_days,
                'gross_written_premium' => number_format($lc_gross_prem, 2),
                'premium_retained' => number_format($lc_retained, 2),
                'premium_ceded' => number_format($lc_ceded, 2),
                'collected_prem' => number_format($lc_receipt, 2),
                'earned_prem' => number_format($lc_earned_prem, 2),
                'upr' => number_format($lc_upr, 2),
                'tot_claims' => number_format($lc_vet_sum, 2),
                'invoiced_claims' => number_format($lc_inv_amount, 2),
                'loss_ratio' => number_format($ll_loss_ratio_ep, 2),
                'loss_ratio_cp' => number_format($ll_loss_ratio_cp, 2),
                'current_anniv'=> $max_anniv
            ];
        }
    }

    return $report;
}
}
