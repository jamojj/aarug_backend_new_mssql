<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\MemberDiagnosis;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverUtilizationController extends Controller
{
    public function fetchOverUtilizedBills(Request $request)
    {
        $data = Bills::select(
            DB::raw("count(distinct bills.claim_no) as visits"),
            DB::raw("member_info.surname || ' ' || member_info.first_name || ' ' || member_info.other_names as names"),
            'member_info.tel_no as member_contact',
            'principal_applicant.tel_no as principal_contact',
            'bills.member_no as mem_no',
            'bills.anniv as mem_anniv',
            'bills.remarks',
            'bills.expectation',
            'bills.followup'
        )
            ->join('member_anniversary', 'member_anniversary.member_no', 'bills.member_no')
            ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->orderBy('mem_no', 'asc')
            ->groupBy('mem_no', 'names', 'mem_anniv', 'member_contact', 'principal_contact', 'remarks', 'expectation', 'followup')
            ->havingRaw('count(distinct bills.claim_no) >= 6')
            ->where('bills.anniv', $request->anniv)
            ->where('principal_applicant.corp_id', $request->corporate)
            ->get();

        return $data;
    }


    public function fetchOverUtilizedVisits(Request $request)
    {
        $data = Bills::select(
            DB::raw("member_info.surname || ' ' || member_info.first_name || ' ' || member_info.other_names as names"),
            DB::raw("FORMAT(bills.invoice_date, 'dd/mm/YYYY') as invoice_date"),
            'bills.member_no',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.remarks',
            'bills.expectation',
            'bills.followup',
            'service.service',
            'provider.provider',
            'benefit.benefit',
            'referred_by as diag'
        )
            ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->orderBy('bills.claim_no', 'asc')
            ->where('bills.anniv', $request->anniv)
            ->where('bills.member_no', $request->member_no)
            ->get();

        foreach ($data as $datus) {
            $diags = MemberDiagnosis::select(
                'clinical_diagnosis.clinical_diagnosis'
            )
                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', 'member_diagnosis.diagnosis')
                ->where('member_no', $datus->member_no)
                ->where('claim_no', $datus->claim_no)
                ->get();

            foreach ($diags as $diag) {
                $datus->diag .=  $diag->clinical_diagnosis  . ', ';
            }
        }

        return $data;
    }

    public function updateMemberVisit(Request $request)
    {
        // return $request;
        try {
            DB::transaction(function () use ($request) {
                if (isset($request->member_no)) {
                    for ($i = 0; $i < count($request->member_no); $i++) {
                        Bills::where(['member_no' => $request->member_no[$i], 'anniv' => $request->anniv[$i]])->update([
                            'remarks' => $request->remarks[$i],
                            'expectation' => $request->expectation[$i],
                            'followup' => $request->followup[$i]
                        ]);
                    }
                }
            });
            return ['Update successful'];
        } catch (Exception $e) {
            return ['error ' . $e->getMessage()];
        }
    }
}
