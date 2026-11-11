<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetails;
use App\Models\PremiumInvoiceDetailsSammary;
use App\Models\PremiumInvoiceMembers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebitReversalController extends Controller
{
    //
    public function fetchDebitNumberDetails($invoice_no)
    {
        $data = [];
        // $reversal_no = explode('-', $invoice_no);

        // $reversal_no = $reversal_no[1];
        // $reversal_no = 'REV-' . $reversal_no;
        $reversal_no = 'REV-' . $invoice_no;
        $invoice_no = 'DBT-' . $invoice_no;

        $invoice_count = PremiumInvoice::select(
            DB::raw("count(*) as invoice_count"))
            ->where('invoice_no', $invoice_no)
            ->get();
        //return $count;
        //return $count = $invoice_count[0]['invoice_count'];
        if ((int)$invoice_count[0]['invoice_count'] > 0) {
            //check if already reversed
            $reversed_count = PremiumInvoice::where('invoice_no', $reversal_no)->count();
            if ($reversed_count <= 0) {
                $credit_note = PremiumInvoice::where('invoice_no', $invoice_no)->get('credit_note');
                //return $credit_note[0]['credit_note'];
                //if it's a debit note then credit_note column == 1
                if ((int)$credit_note[0]['credit_note'] == 1) {
                    $summary_count = PremiumInvoiceDetailsSammary::select(
                        DB::raw("count(*) as summary_count"))
                        ->where('invoice_no', $invoice_no)
                        ->get();
                    //return $summary_count[0]['summary_count'];
                    if ((int)$summary_count[0]['summary_count'] > 0) {
                        $commis_amt = PremiumInvoiceDetailsSammary::select(
                            DB::raw("(select isnull(sum(isnull(commission, 0))+sum(isnull(bdm_amt, 0))+sum(isnull(override_amt, 0))+sum(isnull(hos_amt, 0)), 0)) as commis_amt")
                        )
                            ->distinct()
                            ->where('invoice_no', $invoice_no)
                            ->get();
                        //return $commis_amt[0]['commis_amt'];
                        if ($commis_amt[0]['commis_amt'] > 0) {
                            return [
                                'data' => $data,
                                'message' => 'Notice! Commission already paid'
                            ];
                        } else {
                            $invoice_no_data = PremiumInvoice::select(
                                DB::raw("
                                case when premium_invoice.corp_id is null then 
                                    CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)
                                else corporate.corporate
                                END as client                            
                            "),
                                'premium_invoice.invoice_no',
                                'premium_invoice.corp_id',
                                'premium_invoice.invoice_date',
                                'premium_invoice.invoice_total',
                                'premium_invoice.anniv',
                                'premium_invoice.invoice_net',
                                'premium_invoice.agent_id',
                                'premium_invoice.premium_net',
                                'premium_invoice.member_no',
                                'premium_invoice.insurer',
                                'premium_invoice.cover_start_date',
                                'premium_invoice.new_renewal',
                                'premium_invoice.phcf',
                                'premium_invoice.tl',
                                'premium_invoice.prorate_days',
                                'premium_invoice.prorate_days_month',
                                'premium_invoice.end_date',
                                'premium_invoice.days',
                                'premium_invoice.contigency',
                                'premium_invoice.smart_cost',
                                'premium_invoice.access_fee',
                                'premium_invoice.gift_items',
                                'premium_invoice.first_aid', /*'reversed',*/
                                'premium_invoice.sync',
                                'premium_invoice.integ_date',
                                'premium_invoice.retained',
                                'premium_invoice.ceded')
                                ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                                ->where('premium_invoice.invoice_no', $invoice_no)
                                ->get();
                            array_push($data, $invoice_no_data);
                            return [
                                'data' => $invoice_no_data[0],
                                'message' => 'Fetch OK'
                            ];
                        }
                    } else {
                        $invoice_no_data = PremiumInvoice::select(
                            DB::raw("
                            case when premium_invoice.corp_id is null then 
                                CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)
                            else corporate.corporate
                            END as client                            
                            "),
                            'premium_invoice.invoice_no',
                            'premium_invoice.corp_id',
                            'premium_invoice.invoice_date',
                            'premium_invoice.invoice_total',
                            'premium_invoice.anniv',
                            'premium_invoice.invoice_net',
                            'premium_invoice.agent_id',
                            'premium_invoice.premium_net',
                            'premium_invoice.member_no',
                            'premium_invoice.insurer',
                            'premium_invoice.cover_start_date',
                            'premium_invoice.new_renewal',
                            'premium_invoice.phcf',
                            'premium_invoice.tl',
                            'premium_invoice.prorate_days',
                            'premium_invoice.prorate_days_month',
                            'premium_invoice.end_date',
                            'premium_invoice.days',
                            'premium_invoice.contigency',
                            'premium_invoice.smart_cost',
                            'premium_invoice.access_fee',
                            'premium_invoice.gift_items',
                            'premium_invoice.first_aid', /*'reversed',*/
                            'premium_invoice.sync',
                            'premium_invoice.integ_date',
                            'premium_invoice.retained',
                            'premium_invoice.ceded')
                            ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
                            ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
                            ->where('premium_invoice.invoice_no', $invoice_no)
                            ->get();
                        array_push($data, $invoice_no_data);
                        return [
                            'data' => $invoice_no_data[0],
                            'message' => 'Fetch OK'
                        ];
                    }
                } else {
                    return [
                        'data' => $data,
                        'message' => 'Notice! You are only allowed to reverse a debit note'
                    ];
                }
            } else {
                return [
                    'data' => $data,
                    'message' => 'Notice! Invoice already reversed.'
                ];
            }
        } else {
            return [
                'data' => $data,
                'message' => 'Notice! Invalid Invoice No.'
            ];
        }
    }

    //save debit/invoice reversal
    public function saveDebitReversal(Request $request)
    {
        //return $request;
        try {
            DB::transaction(function () use ($request) {
                $today = date('Y-m-d');
                $max_idx = PremiumInvoice::max('idx') + 1;
                //update entry in premium invoice set reversed 1
                /*PremiumInvoice::where([
                    'invoice_no' => $request->transaction_no
                ])->update([
                    'reversed' => 1
                ]);*/
                //create new entry for reversal

                //set inserted, updated, and deleted items
                $inserted_item = "invoice_no :  $request->reversal_no, member_no : $request->member_no, corp_id : $request->corp_id";
                $updated_item = null;
                $deleted_item = "invoice_no : $request->transaction_no";

                PremiumInvoice::create([
                    'INVOICE_NO' => $request->reversal_no,
                    'MEMBER_NO' => $request->member_no,
                    'CORP_ID' => $request->corp_id,
                    'INVOICE_DATE' => $request->invoice_date,
                    'INVOICE_TOTAL' => $request->invoice_total,
                    'ANNIV' => $request->anniv,
                    'USER_ID' => $request->user,
                    'DATE_ENTERED' => $today,
                    'INVOICE_NET' => $request->invoice_net,
                    'AGENT_ID' => $request->agent_id,
                    'PREMIUM_NET' => $request->premium_net,
                    'insurer' => $request->insurer,
                    'cover_start_date' => $request->cover_start_date,
                    'new_renewal' => $request->new_renewal,
                    'phcf' => $request->iiu_tax_amt,
                    'tl' => $request->ira_tax_amt,
                    'credit_note' => 2,
                    'prorate_days' => $request->prorate_days,
                    'idx' => $max_idx,
                    'prorate_days_month' => $request->prorate_days_month,
                    'end_date' => $request->end_date,
                    'days' => $request->days,
                    'contigency' => $request->contigency_amt,
                    'sync' => $request->sync,
                    'integ_date' => $request->integ_date,
                    'smart_cost' => $request->new_cards_amt,
                    'access_fee' => $request->access_fee_amt,
                    'gift_items' => $request->gift_items_amt,
                    'first_aid' => $request->first_aid_amt,
                    //'reversed' => 1,
                ]);
                //delete entry from premium invoice details sammary
                PremiumInvoiceDetailsSammary::where('invoice_no', $request->transaction_no)->delete();                
                //delete from premium invoice details
                PremiumInvoiceDetails::where('invoice_no', $request->transaction_no)->delete();              
                //delete from premium invoice members
                PremiumInvoiceMembers::where('invoice_no', $request->transaction_no)->delete();

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    
            });
            return ["message" => 'Notice ! Debit Reversal Save Successful'];
        } catch (\Exception $e) {
            return ["message" => 'Notice! Debit Transaction Reversal Save Failed'];
        }
    }
}
