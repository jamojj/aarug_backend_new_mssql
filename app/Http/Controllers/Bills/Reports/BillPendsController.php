<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillPendsController extends Controller
{
    public function fetchBillPends()
    {
        ini_set('max_execution_time', '0');
        $total = 0;
        $data = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
            'corporate.corporate',
            'bills.member_no',
            'bills.claim_no',
            'bills.invoice_no',
            'provider.provider',
            'service.service',
            'bills.refund',
            'benefit.benefit',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where(DB::raw("isnull(bills.vet_status, 0)"), 0)
            ->get();

        foreach ($data as $dt) {
            $total += $dt['invoiced_amount'];
        }

        return ['report' => json_encode($data, JSON_INVALID_UTF8_IGNORE), 'total' => number_format($total, 2)];
    }
}
