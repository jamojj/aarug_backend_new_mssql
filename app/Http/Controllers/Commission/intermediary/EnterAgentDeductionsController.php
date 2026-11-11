<?php

namespace App\Http\Controllers\Commission\intermediary;

use App\Http\Controllers\Controller;
use App\Models\AgentDeductions;
use App\Models\PremiumReceipt;
use Illuminate\Http\Request;

class EnterAgentDeductionsController extends Controller
{
    //fetch agent receipts
    public function fetchAgentReceipts($agent_id)
    {
        $data = PremiumReceipt::select('premium_receipt.receipt_no', 'premium_receipt.receipt_amount',
            'premium_receipt.receipt_date', 'premium_receipt.payment_mode as payment_mode_code', 't_payment_mode.payment_mode',
            'premium_receipt.payment_terms', 'premium_receipt.cheque_no', 'premium_receipt.financer',
            'premium_receipt.corp_id', 'premium_receipt.member_no', 'corporate.corporate')
            ->join('t_payment_mode', 't_payment_mode.code', '=', 'premium_receipt.payment_mode')
            ->join('corporate', 'corporate.corp_id', '=', 'premium_receipt.corp_id')
            ->where('premium_receipt.agent_id', $agent_id)
            ->get();

        return $data;
    }

    //fetch agent deductions for query
    public function fetchAgentDeductions($agent_id)
    {
        $data = AgentDeductions::select('agent_deductions.idx', 'agent_deductions.deduction_type as deduction_type_code', 't_agent_deduction.ded_reason',
            'amount')
            ->join('t_agent_deduction', 't_agent_deduction.code', '=', 'agent_deductions.deduction_type')
            ->where('agent_deductions.agent_id', $agent_id)
            ->get();

        return $data;
    }

    //save enter agent deductions
    public function saveAgentDeductions(Request $request)
    {
        //return $request;
        try {

            for ($i = 0; $i < count($request->deduction_type); $i++) {
                $max_idx = AgentDeductions::select('idx')->max('idx');

                if (is_null($max_idx)) {
                    $max_idx = 0;
                }
                $max_idx++;
                //set inserted, updated, and deleted items
                $inserted_item = "idx : $max_idx";
                $updated_item = null;
                $deleted_item = null;
                
                AgentDeductions::create([
                    'idx' => $max_idx,
                    'agent_id' => $request->agent_id,
                    'deduction_type' => $request->deduction_type[$i],
                    'amount' => $request->amount[$i],
                    'date_entered' => today(),
                    'user_id' => $request->user,
                    'recover',
                    'vno',
                    'pay_no'
                ]);

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);

            }
            return ['message' => 'Save OK !'];
        } catch (\Exception $e) {
            //return ['message' => 'Save Failed !'];
            return ['error' => $e->getMessage()];
        }

    }

    //update agent deduction after query
    public function updateAgentDeductions(Request $request)
    {
        //return $request;
        for ($i = 0; $i < count($request->deduction_type); $i++) {
            AgentDeductions::where(
                [
                    'idx' => $request->idx[$i],
                    'agent_id' => $request->agent_id
                ])->update(
                [
                    'deduction_type' => $request->deduction_type[$i],
                    'amount' => $request->amount[$i]
                ]
            );
        }
        return ['message' => 'Update OK !'];
    }
}
