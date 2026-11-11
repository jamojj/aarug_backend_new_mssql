<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetailsSammary;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class ChangeAgentRates extends Controller
{
    public function fetchDebitData($invoice_no)
    {

        $debit_data = PremiumInvoice::select(
            DB::raw(
                "
            case when premium_invoice.member_no is not null then 
                           CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)
            else
                corporate.corporate
            END as client
            "
            ),
            'premium_invoice.invoice_no',
            'premium_invoice.agent_id',
            'premium_invoice.agent_rate',
            'premium_invoice.bdm_rate',
            'premium_invoice.ass_hos_rate',
            'premium_invoice.hos_rate',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice.member_no')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'premium_invoice.corp_id')
            ->where('premium_invoice.invoice_no', $invoice_no)
            ->get();

        return json_encode($debit_data, JSON_INVALID_UTF8_IGNORE);
    }
    public function saveChangeAgentRates(Request $request)
    {
        try {
            // $commission_paid = PremiumInvoiceDetailsSammary::select('paid')->where('invoice_no', $request->invoice_no)->get();
            //check if commission is paid
            $commission_paid = PremiumInvoiceDetailsSammary::select(
                DB::raw("(select isnull(sum(isnull(commission, 0))+sum(isnull(bdm_amt, 0))+sum(isnull(override_amt, 0))+sum(isnull(hos_amt, 0)), 0)) as commis_amt")
            )
                ->distinct()
                ->where('invoice_no', $request->invoice_no)
                ->get();
            $commission_paid = count($commission_paid) !== 0 ? $commission_paid[0]->commis_amt : 0;
            //check if commission is paid
            if ($commission_paid <= 0) {
                DB::transaction(function () use ($request) {
                    //set inserted, updated, and deleted items
                    $inserted_item = null;
                    $updated_item =  "agent_id : $request->agent_id, agent_rate : $request->agent_rate, bdm_rate : $request->bdm_rate, ass_hos_rate : $request->ass_hos_rate, hos_rate : $request->hos_rate";
                    $deleted_item = null;
                    PremiumInvoice::where('invoice_no', $request->invoice_no)
                        ->update([
                            'agent_id' => $request->agent_id,
                            'agent_rate' => $request->agent_rate,
                            'bdm_rate' => $request->bdm_rate,
                            'ass_hos_rate' => $request->ass_hos_rate,
                            'hos_rate' => $request->hos_rate
                        ]);

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    

                });
                return ['Agent rates changed successfully.'];
            } else {
                return ['Commission is already paid!'];
            }
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function updateAgentRatesUpload(Request $request)
    {
        $res = [];
        try {
            if (isset($request->invoice_no)) {
                for ($i = 0; $i < count($request->invoice_no); $i++) {
                    //check if invoice no exists
                    $invoice_exists = PremiumInvoice::where('invoice_no', $request->invoice_no[$i])->count();
                    if ($invoice_exists !== 0) {
                        //check if agent exists
                        $agent_exists = Agent::where('agent_id', $request->agent_id[$i])->count();
                        if ($agent_exists !== 0) {
                            //check if commission is paid
                            $commission_paid = PremiumInvoiceDetailsSammary::select(
                                DB::raw("(select isnull(sum(isnull(commission, 0))+sum(isnull(bdm_amt, 0))+sum(isnull(override_amt, 0))+sum(isnull(hos_amt, 0)), 0)) as commis_amt")
                            )
                                ->distinct()
                                ->where('invoice_no', $request->invoice_no[$i])
                                ->get();
                            $commission_paid = count($commission_paid) !== 0 ? $commission_paid[0]->commis_amt : 0;
                            //check if commission is paid
                            if ($commission_paid <= 0) {
                                DB::transaction(function () use ($request, $i) {
                                    //set inserted, updated, and deleted items
                                    $inserted_item = null;
                                    $updated_item =  "agent_id : " . $request->agent_id[$i] . ", agent_rate : " . $request->agent_rate[$i] . ", bdm_rate : " . $request->bdm_rate[$i] . ", ass_hos_rate : " . $request->ass_hos_rate[$i] . ", hos_rate : " . $request->hos_rate[$i];
                                    $deleted_item = null;
                                    PremiumInvoice::where('invoice_no', $request->invoice_no[$i])
                                        ->update([
                                            'agent_id' => $request->agent_id[$i],
                                            'agent_rate' => $request->agent_rate[$i],
                                            'bdm_rate' => $request->bdm_rate[$i],
                                            'ass_hos_rate' => $request->ass_hos_rate[$i],
                                            'hos_rate' => $request->hos_rate[$i]
                                        ]);

                                    //save access log
                                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    
                                });
                                $dt = [
                                    'index' => $request->index[$i],
                                    'res' => "Agent rates changed successfully."
                                ];
                                array_push($res, $dt);
                            } else {
                                $dt = [
                                    'index' => $request->index[$i],
                                    'res' => "Notice, Commission is already paid!"
                                ];
                                array_push($res, $dt);
                            }
                        }else{

                            $dt = [
                                'index' => $request->index[$i],
                                'res' => "Notice, Agent does not exist!"
                            ];
                            array_push($res, $dt);
                        }
                    }else{
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => "Notice, Invoice does not exist!"
                        ];
                        array_push($res, $dt);
                    }
                }
            }
        } catch (Exception $e) {
            $dt = [
                'index' => $request->index[$i],
                'res' => 'Error ' . $e->getMessage()
            ];
            array_push($res, $dt);
        }

        return $res;
    }
}
