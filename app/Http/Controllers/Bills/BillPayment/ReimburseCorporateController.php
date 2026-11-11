<?php

namespace App\Http\Controllers\Bills\BillPayment;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\Corporate\Corporate;
use App\Models\LastNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReimburseCorporateController extends Controller
{
    //fetch corporates to reimburse
    public function fetchCorporatesToReimburse()
    {
        $corporates = Corporate::select('corporate.corp_id', 'corporate.corporate')
            ->leftJoin('bills', 'corporate.corp_id', '=', 'bills.corp_id')
            ->where('bills.refund', 1)
            ->where(function ($q) {
                $q->where('paid', 0)->orWhere('paid', null);
            })
            ->distinct()
            ->orderBy('corporate')
            ->get();
        return $corporates;
    }

    public function fetchCorpClaimReimburse($corp_id)
    {
        $invoiced_total = 0;
        $payable_total = 0;
        $deduction_total = 0;

        $claims = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            'bills.member_no',
            'bills.claim_no',
            'bills.invoice_no',
            'service.code as service_code',
            'service.service',
            'bills.paid',
            'bills.id',
            'bills.invoiced_amount',
            'bills.deduction_amount',
            'bills.deduction_reason',
            'bills.deduction_notes',
            'bills.currency',
            'bills.foreigns',
            'bills.conversion_rate',
            'bills.foreign_amt',
            'bills.amount_payable',
            'corporate.corp_id',
            'corporate.corporate',

        )
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('corporate', 'bills.corp_id', '=', 'corporate.corp_id')
            ->where('bills.refund', 1)
            ->where(function ($q) {
                $q->where('paid', 0)->orWhere('paid', null);
            })
            ->where('bills.corp_id', $corp_id)
            ->get();

        foreach ($claims as $claim) {
            $invoiced_total += $claim->invoiced_amount;
        }
        foreach ($claims as $claim) {
            $payable_total += $claim->amount_payable;
        }
        foreach ($claims as $claim) {
            $deduction_total += $claim->deduction_amount;
        }

        $data = [
            'claims' => $claims,
            'claims_count' => count($claims),
            'invoiced_total' => number_format($invoiced_total, 2),
            'payable_total' => number_format($payable_total, 2),
            'deduction_total' => number_format($deduction_total, 2)
        ];

        return $data;
    }
    //save Reimburse Corporate
    public function saveReimburseCorporate(Request $request)
    {
        try {
            // return $request;
            $max_voucher_no = LastNumber::where('task', 2)->pluck('last_no');
            $max_voucher_no = $max_voucher_no[0] + 1;
            $voucher_no = 'PV' . $max_voucher_no;
            $voucher_date = date("Y-m-d");
            DB::transaction(function () use ($request, $max_voucher_no, $voucher_no, $voucher_date) {
                //set inserted, updated, and deleted items
                $inserted_item = null;
                $updated_item = "voucher_no : $voucher_no, voucher_date : $voucher_date";
                $deleted_item = null;

                for ($i = 0; $i < count($request->id); $i++) {
                    //pay only selected claims
                    if ($request->paid[$i] == '1') {
                        Bills::where([
                            'id' => $request->id[$i]
                        ])->update([
                            'paid' => 1,
                            'voucher_no' => $voucher_no,
                            'voucher_date' => $voucher_date,
                            'voucher_user' => $request->user_id
                        ]);
                    }
                }

                //increment latest voucher number in last number
                LastNumber::where('task', 2)->update(['last_no' => $max_voucher_no]);

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });

            return ['message' => 'Save OK. The Voucher No is :' . $voucher_no];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
