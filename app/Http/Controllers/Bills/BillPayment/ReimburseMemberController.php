<?php

namespace App\Http\Controllers\Bills\BillPayment;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\LastNumber;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReimburseMemberController extends Controller
{

    public function FetchMemberReimburse($option)
    {
        switch ($option) {
            case '1':
                $reimburse_member = Bills::select(
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as names"),
                    'bills.family_no'
                )
                    ->join('principal_applicant', 'bills.family_no', '=', 'principal_applicant.family_no')
                    ->where('bills.vet_status', 1)
                    ->where('bills.refund', 2)
                    ->where(DB::raw("isnull(fund,0)"), 0)
                    ->where(DB::raw("isnull(paid,0)"), 0)
                    ->whereNull('bills.voucher_no')
                    ->distinct('names')
                    ->orderBy('names')
                    ->get();

                return $reimburse_member;

                break;
            case '2':
                $reimburse_member = Bills::select(
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as names"),
                    'bills.family_no'
                )
                    ->join('principal_applicant', 'bills.family_no', '=', 'principal_applicant.family_no')
                    ->where('bills.vet_status', 1)
                    ->where('bills.refund', 2)
                    ->where('fund', 1)
                    ->where(DB::raw("isnull(paid,0)"), 0)
                    ->whereNull('bills.voucher_no')
                    ->distinct('names')
                    ->orderBy('names')
                    ->get();

                return $reimburse_member;
                break;
        }
    }

    public function fetchBillsForMemberReimbursement($fam_no)
    {
        $reimburse_member_data = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            'service.service',
            'service.code as service_code',
            'bills.CLAIM_NO',
            'bills.INVOICE_NO',
            'bills.MEMBER_NO',
            'bills.INVOICED_AMOUNT',
            'bills.DEDUCTION_AMOUNT',
            'bills.DEDUCTION_REASON',
            'bills.AMOUNT_PAYABLE',
            'bills.DEDUCTION_NOTES',
            'bills.PAID',
            'bills.id',
            'bills.currency',
            'bills.foreigns',
            'bills.foreign_amt',
            'bills.conversion_rate',

        )
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->whereNull('bills.voucher_no')
            ->where('bills.vet_status', 1)
            ->where('bills.refund', 2)
            ->where('bills.family_no', $fam_no)
            ->get();

        return $reimburse_member_data;
    }

    public function reimburseMember(Request $request)
    {
        try {
            $response = array();
            $max_voucher_no = LastNumber::where('task', 2)->pluck('last_no');
            $max_voucher_no = $max_voucher_no[0] + 1;
            $voucher_date = date("Y-m-d");

            DB::transaction(function () use ($request, $max_voucher_no, $voucher_date) {
                //set inserted, updated, and deleted items
                $inserted_item = null;
                $updated_item = "voucher_no : $max_voucher_no, voucher_date : $voucher_date";
                $deleted_item = null;

                for ($i = 0; $i < count($request->pay); $i++) {
                    //pay only selected claims
                    if ($request->pay[$i] != 0) {
                        Bills::where('id', $request->id[$i])->update([
                            'voucher_no' => 'PV' . $max_voucher_no,
                            'voucher_date' => $voucher_date,
                            'voucher_user' => $request->user,
                            'paid' => 1
                        ]);
                    }
                }
                //update last number
                LastNumber::where('task', 2)->update(['last_no' => $max_voucher_no]);
                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });
            array_push($response, "Member(s) Reimbursed successfully with voucher no PV $max_voucher_no");
        } catch (Exception $e) {
            array_push($response, $e->getMessage());
        }

        return $response;
    }
}
