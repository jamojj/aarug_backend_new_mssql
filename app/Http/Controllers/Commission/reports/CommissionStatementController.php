<?php

namespace App\Http\Controllers\Commission\reports;

use App\Http\Controllers\Controller;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetailsSammary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionStatementController extends Controller
{
    public function fetchCommissionStatement(Request $request)
    {
        switch ($request->task) {
            case '1':
                $data = PremiumInvoice::select(
                    DB::raw("CONCAT(member_info.first_name,' ',member_info.other_names,' ',member_info.surname) as member_names"),
                    DB::raw("FORMAT(cheque_payment.cheque_date, 'yyyy-mm-dd') as cheque_date"),
                    'premium_invoice_details_sammary.allocated_amt',
                    'premium_invoice_details_sammary.commission',
                    'premium_invoice_details_sammary.wh_tax',
                    'premium_invoice_details_sammary.commis_rate',
                    'corporate.corporate',
                    'premium_invoice_details_sammary.class',
                    'cheque_payment.cheque_no',
                    'agent.agent_name',
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                    ->leftJoin('agent', 'agent.agent_id', '=', 'premium_invoice.agent_id')
                    ->leftJoin('premium_invoice_details_sammary', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
                    ->leftJoin('cheque_payment', 'cheque_payment.payment_no', '=', 'premium_invoice_details_sammary.commis_payno')
                    ->where(DB::raw("FORMAT(cheque_payment.cheque_date, 'yyyy')"), $request->year)
                    ->get();
                return $data;
                break;
            case '2':
                $month = explode("/", $request->month);
                $month = $month[1] . $month[0];
                $data = PremiumInvoice::select(
                    DB::raw("CONCAT(member_info.first_name,' ',member_info.other_names,' ',member_info.surname) as member_names"),
                    DB::raw("FORMAT(cheque_payment.cheque_date, 'yyyy-mm-dd') as cheque_date"),
                    'premium_invoice_details_sammary.allocated_amt',
                    'premium_invoice_details_sammary.commission',
                    'premium_invoice_details_sammary.wh_tax',
                    'premium_invoice_details_sammary.commis_rate',
                    'corporate.corporate',
                    'premium_invoice_details_sammary.class',
                    'cheque_payment.cheque_no',
                    'agent.agent_name',
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                    ->leftJoin('agent', 'agent.agent_id', '=', 'premium_invoice.agent_id')
                    ->leftJoin('premium_invoice_details_sammary', 'premium_invoice_details_sammary.invoice_no', '=', 'premium_invoice.invoice_no')
                    ->leftJoin('cheque_payment', 'cheque_payment.payment_no', '=', 'premium_invoice_details_sammary.commis_payno')
                    ->where(DB::raw("FORMAT(cheque_payment.cheque_date, 'yyyymm')"), $month)
                    ->get();
                return $data;
                break;
            case '3':
                $data = PremiumInvoice::select(
                    DB::raw("CONCAT(member_info.first_name,' ',member_info.other_names,' ',member_info.surname) as member_names"),
                    DB::raw("FORMAT(cheque_payment.cheque_date, 'yyyy-mm-dd') as cheque_date"),
                    'premium_invoice_details_sammary.allocated_amt',
                    'premium_invoice_details_sammary.commission',
                    'premium_invoice_details_sammary.wh_tax',
                    'premium_invoice_details_sammary.commis_rate',
                    'corporate.corporate',
                    'premium_invoice_details_sammary.class',
                    'cheque_payment.cheque_no',
                    'agent.agent_name',
                )
                    ->join('agent', 'premium_invoice.agent_id', '=', 'agent.agent_id')
                    ->join('premium_invoice_details_sammary', 'premium_invoice.invoice_no', '=', 'premium_invoice_details_sammary.invoice_no')
                    ->join('cheque_payment', 'premium_invoice_details_sammary.commis_payno', '=', 'cheque_payment.payment_no')
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                    ->leftJoin('corporate', 'premium_invoice.corp_id', '=', 'corporate.corp_id')
                    ->where('premium_invoice.agent_id', $request->agent)
                    ->get();
                return $data;
                break;
        }
    }
}
