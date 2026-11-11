<?php

namespace App\Http\Controllers\Finance\Reports;

use App\Http\Controllers\Controller;
use App\Models\PremiumInvoice;
use App\Models\PremiumReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PremiumStatementController extends Controller
{
    //fetch premium statement
    public function premiumStatementReport(Request $request){
        //return $request;
        $response = array();
        $receipts = [];
        $invoices = [];
        $inv_debits_ttl = $inv_credits_ttl = $rcpts_credits_ttl = $credits_ttl = $balance_amt = 0;
        switch ($request->option){
            //premium statement - corporate option
            case '1':
                $invoices = PremiumInvoice::select('premium_invoice.invoice_date', 'premium_invoice.invoice_net',
                    'premium_invoice.invoice_no', 'premium_invoice.credit_note', 'corporate.corporate', 'agent.agent_name')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                    ->leftJoin('agent', 'agent.agent_id', '=', 'corporate.agent_id')
                    ->where(DB::raw("isnull(premium_invoice.nullify,0)"),0)
                    ->where('premium_invoice.corp_id', $request->corporate)
                    ->where('premium_invoice.invoice_date', '>=', $request->date_from)
                    ->where('premium_invoice.invoice_date', '<=', $request->date_to)
                    ->get();
//                return $invoices;
                foreach ($invoices as $data){
                    if ($data->credit_note == '1'){
                        $data->debit = $data->invoice_net;
                        $data->credit = 0;
                        $inv_debits_ttl += $data->invoice_net;
                    }else{
                        $data->debit = 0;
                        $data->credit = (0 - $data->invoice_net);
                        $rcpts_credits_ttl += $data->credit;
                    }
                }
                $receipts = PremiumReceipt::select('premium_receipt.receipt_no', 'premium_receipt.receipt_date',
                    'premium_receipt.receipt_amount', 'corporate.corporate', 'agent.agent_name')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_receipt.corp_id')
                    ->leftJoin('agent', 'agent.agent_id', '=', 'corporate.agent_id')
                    ->where('premium_receipt.corp_id', $request->corporate)
                    ->where('premium_receipt.receipt_date', '>=', $request->date_from)
                    ->where('premium_receipt.receipt_date', '<=', $request->date_to)
                    ->get();

                foreach ($receipts as $data){
                    $data->transaction_receipt_no = 'Receipt No. '.$data->receipt_no;
                    $data->debit = 0;
                    $data->credit = $data->receipt_amount;
                    //$rcpts_debits_ttl += $data->invoice_net;
                    $rcpts_credits_ttl += $data->receipt_amount;
                }
                //sum all credits from credited invoices to receipts amounts
                $credits_ttl = $inv_credits_ttl + $rcpts_credits_ttl;
                //find balance
                $balance_amt = ($inv_debits_ttl - $credits_ttl);
                return [
                    'invoices' => json_encode($invoices, JSON_INVALID_UTF8_IGNORE),
                    'receipts' => json_encode($receipts, JSON_INVALID_UTF8_IGNORE),
                    'invoice_ttl' => $inv_debits_ttl,
                    'receipts_ttl' => $credits_ttl,
                    'balance_amt' => (float)number_format($balance_amt, 2, '.', ''),
                    ];
                break;
            //premium statement - principal option
            case '2':
                $invoices = PremiumInvoice::select('premium_invoice.invoice_date', 'premium_invoice.invoice_net',
                    'premium_invoice.invoice_no', 'premium_invoice.credit_note',
                   /* DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),*/
                    'corporate.corporate', 'agent.agent_name'
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.member_no', '=', 'member_info.member_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('agent', 'agent.agent_id', '=', 'principal_applicant.agent_id')
                    ->where(DB::raw("isnull(premium_invoice.nullify,0)"),0)
                    ->where('premium_invoice.member_no', $request->principal)
                    ->where('premium_invoice.invoice_date', '>=', $request->date_from)
                    ->where('premium_invoice.invoice_date', '<=', $request->date_to)
                    ->get();
//                return $invoices;
                foreach ($invoices as $data){
                    if ($data->credit_note == '1'){
                        $data->debit = $data->invoice_net;
                        $data->credit = 0;
                        $inv_debits_ttl += $data->invoice_net;
                    }else{
                        $data->debit = 0;
                        $data->credit = (0 - $data->invoice_net);
                        $rcpts_credits_ttl += $data->credit;
                    }
                }
                $receipts = PremiumReceipt::select('premium_receipt.receipt_no', 'premium_receipt.receipt_date',
                    'premium_receipt.receipt_amount', 'corporate.corporate', 'agent.agent_name')
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_receipt.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.member_no', '=', 'member_info.member_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('agent', 'agent.agent_id', '=', 'principal_applicant.agent_id')
                    ->where('premium_receipt.member_no', $request->principal)
                    ->where('premium_receipt.receipt_date', '>=', $request->date_from)
                    ->where('premium_receipt.receipt_date', '<=', $request->date_to)
                    ->get();

                foreach ($receipts as $data){
                    $data->transaction_receipt_no = 'Receipt No. '.$data->receipt_no;
                    $data->debit = 0;
                    $data->credit = $data->receipt_amount;
                    //$rcpts_debits_ttl += $data->invoice_net;
                    $rcpts_credits_ttl += $data->receipt_amount;

                }
                //sum all credits from credited invoices to receipts amounts
                $credits_ttl = $inv_credits_ttl + $rcpts_credits_ttl;
                //find balance
                $balance_amt = ($inv_debits_ttl - $credits_ttl);
                return [
                    'invoices' => json_encode($invoices, JSON_INVALID_UTF8_IGNORE),
                    'receipts' => json_encode($receipts, JSON_INVALID_UTF8_IGNORE),
                    'invoice_ttl' => $inv_debits_ttl,
                    'receipts_ttl' => $credits_ttl,
                    'balance_amt' => (float)number_format($balance_amt, 2, '.', ''),
                ];
                break;
            //premium statement - all clients option
            case '3':
                $invoices = PremiumInvoice::select('premium_invoice.invoice_date', 'premium_invoice.invoice_net',
                    'premium_invoice.invoice_no', 'premium_invoice.credit_note', 'premium_invoice.agent_id',
                    'agent.agent_name', 'premium_invoice.corp_id', 'corporate.corporate', 'premium_invoice.member_no',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                )
                    ->leftJoin('agent', 'agent.agent_id', '=', 'premium_invoice.agent_id')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                    ->where(DB::raw("isnull(nullify,0)"),0)
                    ->where('invoice_date', '>=', $request->date_from)
                    ->where('invoice_date', '<=', $request->date_to)
                    ->get();
//                return $invoices;
                foreach ($invoices as $data){
                    if (!is_null($data->corporate)){
                        $data->client_name = $data->corporate;
                    }
                    elseif (!is_null($data->member_name)){
                        $data->client_name = $data->member_name;
                    }
                    else{
                        $data->client_name = '';
                    }
                    if ($data->credit_note == '1'){
                        $data->debit = $data->invoice_net;
                        $data->credit = 0;
                        $inv_debits_ttl += $data->invoice_net;
                    }else{
                        $data->debit = 0;
                        $data->credit = (0 - $data->invoice_net);
                        $inv_credits_ttl += $data->credit;
                    }
                }
                $receipts = PremiumReceipt::select('premium_receipt.receipt_no', 'premium_receipt.receipt_date',
                    'premium_receipt.receipt_amount', 'premium_receipt.agent_id', 'agent.agent_name',
                    'premium_receipt.corp_id', 'corporate.corporate', 'premium_receipt.member_no',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                )
                    ->leftJoin('agent', 'agent.agent_id', '=', 'premium_receipt.agent_id')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_receipt.corp_id')
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_receipt.member_no')
                    ->where('receipt_date', '>=', $request->date_from)
                    ->where('receipt_date', '<=', $request->date_to)
                    ->orderBy('agent.agent_name')
                    ->get();

                foreach ($receipts as $data){
                    if (!is_null($data->corporate)){
                        $data->client_name = $data->corporate;
                    }
                    elseif (!is_null($data->member_name)){
                        $data->client_name = $data->member_name;
                    }
                    else{
                        $data->client_name = '';
                    }
                    $data->transaction_receipt_no = 'Receipt No. '.$data->receipt_no;
                    $data->debit = 0;
                    $data->credit = $data->receipt_amount;
                    //$rcpts_debits_ttl += $data->invoice_net;
                    $rcpts_credits_ttl += $data->receipt_amount;
                }
                //sum all credits from credited invoices to receipts amounts
                $credits_ttl = $inv_credits_ttl + $rcpts_credits_ttl;
                //find balance
                $balance_amt = ($inv_debits_ttl - $credits_ttl);
                return [
                    'invoices' => json_encode($invoices, JSON_INVALID_UTF8_IGNORE),
                    'receipts' => json_encode($receipts, JSON_INVALID_UTF8_IGNORE),
                    'invoice_ttl' => $inv_debits_ttl,
                    'receipts_ttl' => $credits_ttl,
                    'balance_amt' => (float)number_format($balance_amt, 2, '.', ''),
                ];
                break;
            //premium statement - intermediary option
            case '4':
                $invoices = PremiumInvoice::select('premium_invoice.invoice_date', 'premium_invoice.invoice_net',
                    'premium_invoice.invoice_no', 'premium_invoice.credit_note', 'corporate.corporate',
                    'premium_invoice.corp_id', 'premium_invoice.premium_net', 'premium_invoice.member_no',
                    'premium_invoice.stamp_duty', 'premium_invoice.tl', 'premium_invoice.phcf','agent.agent_name',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                )
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                    ->leftJoin('agent', 'agent.agent_id', '=', 'premium_invoice.agent_id')
                    ->where(DB::raw("isnull(nullify,0)"),0)
                    ->where('premium_invoice.agent_id', $request->agent)
                    ->where('premium_invoice.invoice_date', '>=', $request->date_from)
                    ->where('premium_invoice.invoice_date', '<=', $request->date_to)
                    ->get();
                //return ['invo' => $invoices, 'opt'=>$request->option];
                foreach ($invoices as $data){
                    if (!is_null($data->corporate)){
                        $data->client_name = $data->corporate;
                    }
                    elseif (!is_null($data->member_name)){
                        $data->client_name = $data->member_name;
                    }
                    else{
                        $data->client_name = '';
                    }
                    if ($data->credit_note == '1'){
                        $data->debit_gross = $data->premium_net;
                        $data->debit = $data->invoice_net;
                        $data->credit = 0;
                        $inv_debits_ttl += $data->invoice_net;
                    }else{
                        $data->debit_gross = (0 - $data->premium_net);
                        $data->debit = 0;
                        $data->credit = (0 - $data->invoice_net);
                        $inv_credits_ttl += $data->credit;
                    }
                }

                $receipts = PremiumReceipt::select('premium_receipt.receipt_no', 'premium_receipt.receipt_date',
                    'premium_receipt.receipt_amount', 'premium_receipt.cheque_no', 'premium_receipt.member_no',
                    'premium_receipt.corp_id', 'corporate.corporate',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                )
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_receipt.corp_id')
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_receipt.member_no')
                    ->where('premium_receipt.agent_id', $request->agent)
                    ->where('premium_receipt.receipt_date', '>=', $request->date_from)
                    ->where('premium_receipt.receipt_date', '<=', $request->date_to)
                    ->get();

                foreach ($receipts as $data){
                    if (!is_null($data->corporate)){
                        $data->client_name = $data->corporate;
                    }
                    elseif (!is_null($data->member_name)){
                        $data->client_name = $data->member_name;
                    }
                    else{
                        $data->client_name = '';
                    }
                    $data->transaction_receipt_no = 'Receipt No. '.$data->receipt_no;
                    $data->debit = 0;
                    $data->credit = $data->receipt_amount;
                    //$rcpts_debits_ttl += $data->invoice_net;
                    $rcpts_credits_ttl += $data->receipt_amount;

                }
                //sum all credits from credited invoices to receipts amounts
                $credits_ttl = $inv_credits_ttl + $rcpts_credits_ttl;
                //find balance
                $balance_amt = ($inv_debits_ttl - $credits_ttl);
                return [
                    'invoices' => json_encode($invoices, JSON_INVALID_UTF8_IGNORE),
                    'receipts' => json_encode($receipts, JSON_INVALID_UTF8_IGNORE),
                    'invoice_ttl' => $inv_debits_ttl,
                    'receipts_ttl' => $credits_ttl,
                    'balance_amt' => (float)number_format($balance_amt, 2, '.', ''),
                ];
                break;
            //premium statement - corporate premium recorded option
            case '5':
                $invoices = PremiumInvoice::select('premium_invoice.invoice_date', 'premium_invoice.invoice_net',
                    'premium_invoice.invoice_no', 'premium_invoice.credit_note',
                    'premium_invoice.corp_id', 'corporate.corporate')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                    ->where(DB::raw("isnull(premium_invoice.nullify,0)"),0)
                    ->where('premium_invoice.corp_id', $request->corporate)
                    ->where('premium_invoice.invoice_date', '>=', $request->date_from)
                    ->where('premium_invoice.invoice_date', '<=', $request->date_to)
                    ->get();
//                return $invoices;
                foreach ($invoices as $data){
                    if ($data->credit_note == '1'){
                        $data->debit = $data->invoice_net;
                        $data->credit = 0;
                        $inv_debits_ttl += $data->invoice_net;
                    }else{
                        $data->debit = 0;
                        $data->credit = (0 - $data->invoice_net);
                        $inv_credits_ttl += $data->credit;
                    }
                }
                $receipts = PremiumReceipt::select('receipt_no', 'receipt_date', 'receipt_amount')
                    ->where('corp_id', $request->corporate)
                    ->where('receipt_date', '>=', $request->date_from)
                    ->where('receipt_date', '<=', $request->date_to)
                    ->get();

                foreach ($receipts as $data){
                    $data->transaction_receipt_no = 'Receipt No. '.$data->receipt_no;
                    $data->debit = 0;
                    $data->credit = $data->receipt_amount;
                    //$rcpts_debits_ttl += $data->invoice_net;
                    $rcpts_credits_ttl += $data->receipt_amount;

                }
                //sum all credits from credited invoices to receipts amounts
                $credits_ttl = $inv_credits_ttl + $rcpts_credits_ttl;
                //find balance
                $balance_amt = ($inv_debits_ttl - $credits_ttl);
                return [
                    'invoices' => json_encode($invoices, JSON_INVALID_UTF8_IGNORE),
                    'receipts' => json_encode($receipts, JSON_INVALID_UTF8_IGNORE),
                    'invoice_ttl' => $inv_debits_ttl,
                    'receipts_ttl' => $credits_ttl,
                    'balance_amt' => $balance_amt,
                ];
                break;
            //premium statement - all corporates premium recorded option
            case '6':
                $invoices = PremiumInvoice::select('premium_invoice.invoice_date', 'premium_invoice.invoice_net',
                    'premium_invoice.invoice_no', 'premium_invoice.credit_note', 'premium_invoice.agent_id',
                    'agent.agent_name', 'premium_invoice.corp_id', 'corporate.corporate', 'premium_invoice.member_no',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                )
                    ->leftJoin('agent', 'agent.agent_id', '=', 'premium_invoice.agent_id')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                    ->where(DB::raw("isnull(premium_invoice.nullify,0)"),0)
                    ->where('premium_invoice.invoice_date', '>=', $request->date_from)
                    ->where('premium_invoice.invoice_date', '<=', $request->date_to)
                    ->get();
//                return $invoices;
                foreach ($invoices as $data){
                    if (!is_null($data->corporate)){
                        $data->client_name = $data->corporate;
                    }
                    elseif (!is_null($data->member_name)){
                        $data->client_name = $data->member_name;
                    }
                    else{
                        $data->client_name = '';
                    }
                    if ($data->credit_note == '1'){
                        $data->debit = $data->invoice_net;
                        $data->credit = 0;
                        $inv_debits_ttl += $data->invoice_net;
                    }else{
                        $data->debit = 0;
                        $data->credit = (0 - $data->invoice_net);
                        $inv_credits_ttl += $data->credit;
                    }
                }
                //sum all credits from credited invoices to receipts amounts
                $credits_ttl = $inv_credits_ttl + $rcpts_credits_ttl;
                //find balance
                $balance_amt = ($inv_debits_ttl - $credits_ttl);
                return [
                    'invoices' => json_encode($invoices, JSON_INVALID_UTF8_IGNORE),
                    'receipts' => json_encode($receipts, JSON_INVALID_UTF8_IGNORE), 
                    'invoice_ttl' => $inv_debits_ttl,
                    'receipts_ttl' => $credits_ttl,
                    'balance_amt' => $balance_amt,
                    ];
                break;
            default:
                return [];
                break;
        }
    }
}
