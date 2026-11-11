<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillVet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VetApprovalController extends Controller
{
    public function fetchVettedBillsToApprove()
    {
        ini_set('max_execution_time', '0');
        $data = BillVet::select(
            DB::raw("FORMAT(bill_vet.date_entered, 'yyyy-mm-dd') as date_entered"),
            DB::raw("FORMAT(bill_vet.approval_date, 'yyyy-mm-dd') as approval_date"),
            DB::raw("case 
                        when bill_vet.approval_vet_status = 1 then 'Approve'
                        when bill_vet.approval_vet_status = 2 then 'Reject'
                        when bill_vet.approval_vet_status = 3 then 'Suspend'                        
                    END as approval_vet_status"),
            'bill_vet.approval_vet_status as approval_vet_status_code',
            'bill_vet.id',
            'bill_vet.bill_approval_id',
            'bill_vet.claim_no',
            'bill_vet.invoice_no',
            'service.service',
            'provider.provider',
            'bill_vet.user_id',
            'bill_vet.approval_reason',
            'bill_vet.approval_user_id',
        )
            ->leftJoin('service', 'service.code', '=', 'bill_vet.service')
            ->leftJoin('provider', 'provider.code', '=', 'bill_vet.provider')
            ->where('bill_vet.vet_status', 5)
            ->get();

        return $data;
    }

    public function saveVettingApproval(Request $request)
    {
        ini_set('max_execution_time', '0');
        $today = date("Y-m-d");
        // return $request;
        try {
            DB::transaction(function () use ($request, $today) {
                //new members approval
                if (isset($request->id)) {
                    for ($i = 0; $i < count($request->id); $i++) {
                        if ($request->approve_vetted[$i] === '1') {
                            //UPDATE bill vet and bills table
                            BillVet::where([
                                'id' => $request->id[$i],
                            ])
                                ->update([
                                    'vet_status' => $request->approval_vet_status_code[$i],
                                    'approval_user_id' => $request->approval_user_id[$i],
                                    'approval_date' => $today,
                                ]);

                            Bills::where([
                                'id' => $request->bill_approval_id[$i],
                            ])
                                ->update([
                                    'vet_status' => $request->approval_vet_status_code[$i],
                                ]);
                        }
                    }
                }
            });
            return ['Vetted bills approved successfully.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
