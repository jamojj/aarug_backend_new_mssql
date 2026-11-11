<?php

namespace App\Http\Controllers\Reinsurance\QuotaShare;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\PremiumInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PremiumsQuotaShareController extends Controller
{
    //get the premiums quota share
    public function fetchPremiumsQuotaShareData(Request $request)
    {
        $date_from = date("Ymd", strtotime($request->date_from));
        $date_to = date("Ymd", strtotime($request->date_to));
        $total_net_premium = $total_retain_prem = $total_cede_prem = $total_tax_amt = $total_commis = $total_re_prem = 0;
        //return $request;
        $data = PremiumInvoice::select('corporate.corp_id', 'corporate.corporate')
            ->distinct('premium_invoice.corp_id')
            ->join('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
            ->whereBetween(DB::raw("FORMAT(premium_invoice.cover_start_date, 'yyyyMMdd')"), [$date_from, $date_to])
            ->where(function ($q) {
                $q->whereNotNull('premium_invoice.corp_id')
                    ->orWhere('premium_invoice.corp_id', "!=", "");
            })
            // ->groupBy('corporate.corp_id', 'corporate.corporate')
            ->orderBy('corporate.corporate')
            ->get();
        foreach ($data as $datum) {
            //only for corporates not individuals
            // if (!is_null($datum->corporate) || $datum->corp_id != "") {

            //corp anniversary dates
            $corp_anniv_dt = CorpAnniversary::select(DB::raw("max(start_date) as start_date"), DB::raw("max(end_date) as end_date"))
                ->where('corp_id', $datum->corp_id)
                ->get();
            $datum->start_date = $corp_anniv_dt[0]['start_date'];
            $datum->end_date = $corp_anniv_dt[0]['end_date'];

            //lives
            $lives = PremiumInvoice::select(DB::raw("count(isnull(premium_invoice_details.member_no,0)) as lives"))
                ->leftJoin('premium_invoice_details', 'premium_invoice_details.invoice_no', '=', 'premium_invoice.invoice_no')
                ->where('premium_invoice.corp_id', $datum->corp_id)
                ->whereBetween(DB::raw("FORMAT(premium_invoice.cover_start_date, 'yyyyMMdd')"), [$date_from, $date_to])
                ->get();
            $datum->lives = $lives[0]['lives'];

            //premiums
            $premiums = PremiumInvoice::select(DB::raw("sum(isnull(premium_invoice_details_sammary.net_premium, 0)) as net_premium"))
                ->leftJoin('premium_invoice_details_sammary', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
                ->where('premium_invoice.corp_id', $datum->corp_id)
                ->where('premium_invoice_details_sammary.class', 1)
                ->whereBetween(DB::raw("FORMAT(premium_invoice.cover_start_date, 'yyyyMMdd')"), [$date_from, $date_to])
                ->get();

            $lc_ip_prem = (float)$premiums[0]['net_premium'];
            $lc_retain_prem = (float)($lc_ip_prem * ((int)($request->retention_rate) / 100));
            $lc_cede_prem = (float)($lc_ip_prem - $lc_retain_prem);
            //$lc_cede_prem = (double)($lc_cede_prem * ((int)($request->cede_rate) / 100));
            $lc_tax_amt = (float)($lc_cede_prem * ((int)($request->tax_rate) / 100));
            $lc_commis = (float)($lc_cede_prem * ((int)($request->commission_rate) / 100));
            $lc_re_prem = (float)($lc_cede_prem - ($lc_commis + $lc_tax_amt));

            $datum->net_premium = $lc_ip_prem;
            $datum->retain_prem = $lc_retain_prem;
            $datum->cede_prem = $lc_cede_prem;
            $datum->tax_amt = $lc_tax_amt;
            $datum->commis = $lc_commis;
            $datum->re_prem = $lc_re_prem;

            $total_net_premium += $lc_ip_prem;
            $total_retain_prem += $lc_retain_prem;
            $total_cede_prem += $lc_cede_prem;
            $total_tax_amt += $lc_tax_amt;
            $total_commis += $lc_commis;
            $total_re_prem += $lc_re_prem;
            // }
        }


        return [
            'data' => $data,
            'total_net_premium' => number_format($total_net_premium, '2'),
            'total_retain_prem' => number_format($total_retain_prem, '2'),
            'total_cede_prem' => number_format($total_cede_prem, '2'),
            'total_tax_amt' => number_format($total_tax_amt, '2'),
            'total_commis' => number_format($total_commis, '2'),
            'total_re_prem' => number_format($total_re_prem, '2'),
        ];
    }
}
