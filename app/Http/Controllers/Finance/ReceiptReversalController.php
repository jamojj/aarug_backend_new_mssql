<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ReversalReason;
use App\Models\PremiumInvoiceDetailsSammary;
use App\Models\PremiumReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptReversalController extends Controller
{
    //fetch Reversal Reasons
    public function fetchReversalReasons()
    {
        $reversal_reason = ReversalReason::all();
        return $reversal_reason;
    }

    public function receiptReversal($receipt_no)
    {
        $response = array();
        $ll_count = PremiumReceipt::select('*')
            ->where('receipt_no', $receipt_no)
            ->count();
        if ($ll_count > 0) {
            $ll_rev = PremiumReceipt::where('receipt_no', $receipt_no)
                ->pluck('reversed');
            if (($ll_rev[0]) == 0) {
                $ll_allcnt = PremiumInvoiceDetailsSammary::select('*')
                    ->where('receipt_no', $receipt_no)
                    ->count();
                if ($ll_allcnt > 0) {
                    $lc_comm = PremiumInvoiceDetailsSammary::select(DB::raw("isnull(sum(isnull(commission, 0))+sum(isnull(bdm_amt, 0))+sum(isnull(override_amt, 0))+sum(isnull(hos_amt, 0)), 0) as commis"))
                        ->where('receipt_no', $receipt_no)
                        ->pluck('commis');
                    if (($lc_comm[0] > 0)) {
                        array_push($response, 'Notice, Commission already paid');
                        return ['message' => $response[0]];
                    } else {
                        $receipt_reversal = PremiumReceipt::select(
                            'PREMIUM_RECEIPT.receipt_no',
                            'PREMIUM_RECEIPT.receipt_amount',
                            'PREMIUM_RECEIPT.receipt_date',
                            'PREMIUM_RECEIPT.payment_mode as mode',
                            'PREMIUM_RECEIPT.cheque_no',
                            'PREMIUM_RECEIPT.date_entered',
                            'PREMIUM_RECEIPT.financer as fin_code',
                            'PREMIUM_RECEIPT.corp_id',
                            'PREMIUM_RECEIPT.member_no',
                            'PREMIUM_RECEIPT.receipt_bank',
                            'PREMIUM_RECEIPT.user_id',
                            'PREMIUM_RECEIPT.commis_paid',
                            'PREMIUM_RECEIPT.agent_id',
                            'PREMIUM_RECEIPT.account_toallocate as bank_code',
                            'PREMIUM_RECEIPT.sys_rate',
                            'PREMIUM_RECEIPT.user_rate',
                            'PREMIUM_RECEIPT.currency as cur_code',
                            'PREMIUM_RECEIPT.trans_amt',
                            'CORPORATE.corporate',
                            'AGENT.agent_name',
                            't_payment_mode.payment_mode',
                            'BANKS.Bank',
                            'financer.financer',
                            't_currency.currency',
                            'bank_account.bank_account',
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as full_name")
                        )
                            ->leftJoin('CORPORATE', 'PREMIUM_RECEIPT.corp_id', '=', 'CORPORATE.corp_id')
                            ->leftJoin('AGENT', 'PREMIUM_RECEIPT.agent_id', '=', 'AGENT.agent_id')
                            ->leftJoin('MEMBER_INFO', 'PREMIUM_RECEIPT.member_no', '=', 'MEMBER_INFO.member_no')
                            ->leftJoin('t_payment_mode', 'PREMIUM_RECEIPT.payment_mode', '=', 't_payment_mode.code')
                            ->leftJoin('BANKS', 'PREMIUM_RECEIPT.receipt_bank', '=', 'BANKS.code')
                            ->leftJoin('financer', 'PREMIUM_RECEIPT.financer', '=', 'financer.fin_code')
                            ->leftJoin('t_currency', 'PREMIUM_RECEIPT.currency', '=', 't_currency.code')
                            ->leftJoin('bank_account', 'PREMIUM_RECEIPT.account_toallocate', '=', 'bank_account.code')
                            ->where('PREMIUM_RECEIPT.receipt_no', $receipt_no)
                            ->get();

                        array_push($response, $receipt_reversal);
                        return ['receipt_reversal' => $response[0]];
                    }
                } else {
                    $receipt_reversal = PremiumReceipt::select(
                        'PREMIUM_RECEIPT.receipt_no',
                        'PREMIUM_RECEIPT.receipt_amount',
                        'PREMIUM_RECEIPT.receipt_date',
                        'PREMIUM_RECEIPT.payment_mode as mode',
                        'PREMIUM_RECEIPT.cheque_no',
                        'PREMIUM_RECEIPT.date_entered',
                        'PREMIUM_RECEIPT.financer as fin_code',
                        'PREMIUM_RECEIPT.corp_id',
                        'PREMIUM_RECEIPT.member_no',
                        'PREMIUM_RECEIPT.receipt_bank',
                        'PREMIUM_RECEIPT.user_id',
                        'PREMIUM_RECEIPT.commis_paid',
                        'PREMIUM_RECEIPT.agent_id',
                        'PREMIUM_RECEIPT.account_toallocate as bank_code',
                        'PREMIUM_RECEIPT.sys_rate',
                        'PREMIUM_RECEIPT.user_rate',
                        'PREMIUM_RECEIPT.currency as cur_code',
                        'PREMIUM_RECEIPT.trans_amt',
                        'CORPORATE.corporate',
                        'AGENT.agent_name',
                        't_payment_mode.payment_mode',
                        'BANKS.Bank',
                        'financer.financer',
                        't_currency.currency',
                        'bank_account.bank_account',
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as full_name")
                    )
                        ->leftJoin('CORPORATE', 'PREMIUM_RECEIPT.corp_id', '=', 'CORPORATE.corp_id')
                        ->leftJoin('AGENT', 'PREMIUM_RECEIPT.agent_id', '=', 'AGENT.agent_id')
                        ->leftJoin('MEMBER_INFO', 'PREMIUM_RECEIPT.member_no', '=', 'MEMBER_INFO.member_no')
                        ->leftJoin('t_payment_mode', 'PREMIUM_RECEIPT.payment_mode', '=', 't_payment_mode.code')
                        ->leftJoin('BANKS', 'PREMIUM_RECEIPT.receipt_bank', '=', 'BANKS.code')
                        ->leftJoin('financer', 'PREMIUM_RECEIPT.financer', '=', 'financer.fin_code')
                        ->leftJoin('t_currency', 'PREMIUM_RECEIPT.currency', '=', 't_currency.code')
                        ->leftJoin('bank_account', 'PREMIUM_RECEIPT.account_toallocate', '=', 'bank_account.code')
                        ->where('PREMIUM_RECEIPT.receipt_no', $receipt_no)
                        ->get();

                    array_push($response, $receipt_reversal);
                    return ['receipt_reversal' => $response[0]];
                }
            } else {
                if (($ll_rev[0]) == 1) {
                    array_push($response, 'Notice, Receipt already reversed');
                    return ['message' => $response[0]];
                } else {
                    array_push($response, 'Notice, Receipt can not be reversed');
                    return ['message' => $response[0]];
                }
            }
        } else {
            array_push($response, 'Notice, Invalid Receipt No');
            return ['message' => $response[0]];
        }
        return $response;
    }

    public function setReversalNo(Request $request)
    {
        $response = array();
        $receipt = $request->input('receipt');
        $reversal_no = str_replace("ALA", "REV", $receipt);
        $transaction_no = $request->input('receipt');

        $data = [
            'reversal_no' => $reversal_no,
            'transaction_no' => $transaction_no
        ];
        array_push($response, $data);
        return $response;
    }
    //save receipt reversal
    public function saveReceiptReversal(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                //set inserted, updated, and deleted items
                $inserted_item = "receipt_no : $request->receipt_no, receipt_amount : $request->receipt_amount";
                $updated_item = "receipt_no : $request->receipt, reversed : 1";
                $deleted_item = null;

                PremiumReceipt::create([
                    'receipt_no' => $request->receipt_no,
                    'receipt_amount' => $request->receipt_amount,
                    'receipt_date' => $request->receipt_date,
                    'payment_mode' => $request->payment_mode,
                    'cheque_no' => $request->cheque_no,
                    'financer' => $request->financer,
                    'corp_id' => $request->corp_id,
                    'member_no' => $request->member_no,
                    'user_id' => $request->user,
                    'date_entered' => $request->date_entered,
                    'receipt_bank' => $request->bank,
                    'agent_id' => $request->intermediary,
                    'account_toallocate' => $request->bank_account,
                    'sys_rate' => (int)$request->sys_rate,
                    'user_rate' => $request->user_rate,
                    'currency' => $request->currency,
                    'trans_amt' => $request->receipt_amount,
                    'reversed' => 2
                ]);
                PremiumReceipt::where([
                    'receipt_no' => $request->receipt
                ])->update(
                    [
                        'reversed' => 1
                    ]
                );

                PremiumInvoiceDetailsSammary::where('receipt_no', $request->receipt)->delete();

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    
            });
            return  ['Receipt Reversal Saved Successfully'];
        } catch (\Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    //print receipt
    public function printReceiptData($receipt_no)
    {
        $print_receipt = PremiumReceipt::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as member_name"),
            'corporate.corporate',
            'PREMIUM_RECEIPT.user_id',
            'PREMIUM_RECEIPT.receipt_no',
            'PREMIUM_RECEIPT.receipt_amount',
            'PREMIUM_RECEIPT.receipt_date',
            'PREMIUM_RECEIPT.cheque_no',
            'PREMIUM_RECEIPT.payment_mode as mode',
            't_payment_mode.payment_mode',
            'premium_receipt.currency',
            't_exchange_rate.rate',
        )
            ->leftJoin('t_exchange_rate', 't_exchange_rate.currency', '=', 'premium_receipt.currency')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_receipt.corp_id')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_receipt.member_no')
            ->leftJoin('t_payment_mode', 'PREMIUM_RECEIPT.payment_mode', '=', 't_payment_mode.code')
            ->where('PREMIUM_RECEIPT.receipt_no', $receipt_no)
            ->get();

        return $print_receipt;
    }
}
