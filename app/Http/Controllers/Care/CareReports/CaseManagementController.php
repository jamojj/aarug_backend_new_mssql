<?php

namespace App\Http\Controllers\Care\CareReports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaseManagementController extends Controller
{
    public function fetchCaseManagement(Request $request)
    {
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));
        $response = [];

        switch ($request->options) {
                //corporate
            case '1':
                $response = Bills::select(
                    DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
                    'bills.claim_no',
                    'bills.invoice_no',
                    'provider.provider',
                    'service.service',
                    'bills.invoiced_amount',
                    'clinical_diagnosis.clinical_diagnosis'
                )

                    ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                    ->leftJoin('service', 'service.code', '=', 'bills.service')
                    ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                    ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                    ->whereNotIn('bills.vet_status', [2])
                    ->where('bills.corp_id', $request->corporate)
                    ->get();

                break;
                //member
            case '2':
                $response = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as member_name"),
                    DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
                    'bills.claim_no',
                    'bills.invoice_no',
                    'provider.provider',
                    'service.service',
                    'bills.invoiced_amount',
                    'clinical_diagnosis.clinical_diagnosis'
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                    ->leftJoin('service', 'service.code', '=', 'bills.service')
                    ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                    ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                    ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                    ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3])
                    ->where('bills.member_no', $request->member)
                    ->get();
                break;
        }

        $response = json_encode($response, JSON_INVALID_UTF8_IGNORE);
        return $response;
    }
}
