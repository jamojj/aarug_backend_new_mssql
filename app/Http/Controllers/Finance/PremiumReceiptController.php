<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Corporate\Corporate;
use App\Models\ExchangeRates;
use App\Models\LastNumber;
use App\Models\PremiumInvoice;
use App\Models\PremiumReceipt;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\stringContains;

class PremiumReceiptController extends Controller
{
    //fetch premium receipts data
    public function fetchPremiumReceipts($selected_rd_value, $selected_value)
    {
        switch ($selected_rd_value) {
            case "1":
                $corp_data = Corporate::select(
                    'AGENT.agent_name',
                    'CORPORATE.corporate',
                    'CORPORATE.corp_id',
                    'CORPORATE.agent_id'
                )
                    ->leftJoin('AGENT', 'AGENT.agent_id', '=', 'CORPORATE.agent_id')
                    ->where('CORPORATE.corp_id', $selected_value)
                    ->get();
                $premium_receipts = PremiumInvoice::select(
                    'CORPORATE.corporate',
                    'PREMIUM_INVOICE.invoice_no',
                    'PREMIUM_INVOICE.invoice_date',
                    'PREMIUM_INVOICE.anniv',
                    'PREMIUM_INVOICE.agent_id',
                    'AGENT.agent_name',
                    'PREMIUM_INVOICE.invoice_net'
                )
                    ->leftJoin('CORPORATE', 'CORPORATE.corp_id', '=', 'PREMIUM_INVOICE.corp_id')
                    ->leftJoin('AGENT', 'AGENT.code', '=', 'PREMIUM_INVOICE.agent_id')
                    ->where('PREMIUM_INVOICE.corp_id', $selected_value)
                    ->orderBy('PREMIUM_INVOICE.anniv', 'desc')
                    ->get();
                return $data = [
                    'corp_data' => $corp_data,
                    'premium_receipts' => $premium_receipts,
                ];
                break;
            case "2":
                $corp_data = PrincipalApplicant::select(
                    'AGENT.agent_name',
                    'PRINCIPAL_APPLICANT.agent_id',
                    'PRINCIPAL_APPLICANT.member_no',
                    DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),

                )
                    ->leftJoin('AGENT', 'AGENT.agent_id', '=', 'PRINCIPAL_APPLICANT.agent_id')
                    ->where('PRINCIPAL_APPLICANT.member_no', $selected_value)
                    ->get();
                $premium_receipts = PremiumInvoice::select(
                    'PREMIUM_INVOICE.invoice_no',
                    'PREMIUM_INVOICE.invoice_date',
                    'PREMIUM_INVOICE.anniv',
                    'PREMIUM_INVOICE.agent_id',
                    'AGENT.agent_name',
                    'PREMIUM_INVOICE.invoice_net'
                )
                    ->leftJoin('AGENT', 'AGENT.code', '=', 'PREMIUM_INVOICE.agent_id')
                    ->where('PREMIUM_INVOICE.member_no', $selected_value)
                    ->orderBy('PREMIUM_INVOICE.anniv', 'desc')
                    ->get();
                return $data = [
                    'corp_data' => $corp_data,
                    'premium_receipts' => $premium_receipts,
                ];
        }
    }

    //fetch exchange rates
    public function fetchExchangeRates($currency, $receipt_date)
    {
        //        $today = today();
        //        $today  = date_format('$today', 'Y-m-d');

        $rate = ExchangeRates::where('currency', $currency)
            ->where(function ($date) use ($receipt_date) {
                return $date->where('fromdt', '<=', $receipt_date)
                    ->where('todt', '>=', $receipt_date);
            })->pluck('rate');
        //        return $rate = $rate[0];
        return ['rate' => $rate[0]];
    }
    //save premium receipts
    public function savePremiumReceipts(Request $request)
    {
        //return $request;
        $receipt_amount =  intval(preg_replace('/[^\d.]/', '', $request->receipt_amount));    
        $receipt_no = $this->getLatestReceiptNumber();
        $receipt_no = json_decode($receipt_no);
        //save premium receipt
        switch ($request->selected_radio) {
                // corporate
            case "1":
                //set inserted, updated, and deleted items
                $inserted_item = "receipt_no : $receipt_no->receipt_no, corp_id : $request->corp_id";
                $updated_item = null;
                $deleted_item = null;

                PremiumReceipt::create([
                    'receipt_no' => $receipt_no->receipt_no,
                    'receipt_amount' => $receipt_amount,
                    'receipt_date' => $request->receipt_date,
                    'payment_mode' => $request->payment_mode,
                    'cheque_no' => $request->cheque_no,
                    'financer' => $request->financer,
                    'corp_id' => $request->corp_id,
                    //'member_no' => $request -> member_no,
                    'user_id' => $request->user,
                    'date_entered' => $request->date_entered,
                    'receipt_bank' => $request->bank,
                    'agent_id' => $request->agent_id,
                    'account_toallocate' => $request->bank_account,
                    'sys_rate' => (int)$request->sys_rate,
                    'currency' => $request->currency,
                    'trans_amt' => $request->transact_amount,
                    'reversed' => 0
                ]);
                break;
                // individual
            case "2":
                //set inserted, updated, and deleted items
                $inserted_item = "receipt_no : $receipt_no->receipt_no,  member_no : $request->member_no";
                $updated_item = null;
                $deleted_item = null;
                PremiumReceipt::create([
                    'receipt_no' => $receipt_no->receipt_no,
                    'receipt_amount' => $receipt_amount,
                    'receipt_date' => $request->receipt_date,
                    'payment_mode' => $request->payment_mode,
                    'cheque_no' => $request->cheque_no,
                    'financer' => $request->financer,
                    //'corp_id' => $request -> corp_id,
                    'member_no' => $request->member_no,
                    'user_id' => $request->user,
                    'date_entered' => $request->date_entered,
                    'receipt_bank' => $request->bank,
                    'agent_id' => $request->agent_id,
                    'account_toallocate' => $request->bank_account,
                    'sys_rate' => (int)$request->sys_rate,
                    'currency' => $request->currency,
                    'trans_amt' => $request->transact_amount,
                    'reversed' => 0
                ]);
                break;
            default:
                break;
        }

        //update last number
        $last_no = explode('-', $receipt_no->receipt_no);
        $last_no = $last_no[1];
        LastNumber::where('TASK', 5)->update([
            'last_no' => $last_no
        ]);

        //save access log
        app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    

        return response()->json([
            'message' => 'Receipt saved successfully. Receipt No. ' . $receipt_no->receipt_no
        ]);
    }
    //fetch latest receipt number from last number
    public function getLatestReceiptNumber()
    {
        $last_receipt_no = LastNumber::select('last_no')->where('task', 5)->get();
        $last_receipt_no = count($last_receipt_no) !== 0 ? $last_receipt_no[0]->last_no : 0;
        $last_receipt_no++;
        $receipt_no = 'ALA' . '-' . $last_receipt_no . '-' . (new \DateTime())->format("Y");

        // return ['receipt_no'=>$receipt_no];
        return  json_encode(['receipt_no' => $receipt_no], JSON_PRETTY_PRINT);
    }
}
