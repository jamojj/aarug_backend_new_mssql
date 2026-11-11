<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillVet;
use App\Models\MemberDiagnosis;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VettedBillsController extends Controller
{   

    public function fetchVettedBills(Request $request)
    {
        ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', 0);

        $fromDate = Carbon::createFromFormat('m/Y', $request->from)->startOfMonth();
        $toDate   = Carbon::createFromFormat('m/Y', $request->to)->endOfMonth();

        $billVetSub = DB::table('bill_vet')
            ->whereBetween('date_entered', [$fromDate, $toDate])
            ->select(
                'claim_no',
                'invoice_no',
                'service',
                'user_id',
                'date_entered'
            )
            ->groupBy('claim_no', 'invoice_no', 'service', 'user_id', 'date_entered');

        $response = Bills::select(
            'bills.id',
            'provider.provider',
            'benefit.benefit',
            'service.service',
            'corporate.corporate',
            'bill_vet.user_id as bill_vet_user',
            'bill_vet.date_entered as vet_date',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.member_no',
            'bills.invoice_date',
            'bills.invoiced_amount',
            'bills.deduction_amount',
            'bills.amount_payable',
            'bills.paid',
            'bills.voucher_no',
            'bills.payment_no',
            'bills.batch_no',
            'bills.payment_date',
            'bills.user_id as bill_user',
            'bills.date_entered',
            'bills.anniv',
            'bills.date_received',
            'bills.vet_status',
            'bills.voucher_user',
            'bills.voucher_date',
            'bills.smart_bill_id',
            'bills.category',
            'bills.diag_code'
        )
            ->joinSub($billVetSub, 'bill_vet', function ($join) {
                $join->on('bills.claim_no', '=', 'bill_vet.claim_no');
                $join->on('bills.invoice_no', '=', 'bill_vet.invoice_no');
                $join->on('bills.service', '=', 'bill_vet.service');
            })
            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->orderBy('bills.claim_no', 'ASC')
            ->get();



        $diagnosisMap = DB::table('member_diagnosis')
            ->join('clinical_diagnosis', 'member_diagnosis.diagnosis', '=', 'clinical_diagnosis.idx')
            ->join('bill_vet', 'member_diagnosis.claim_no', '=', 'bill_vet.claim_no') // Join directly to the filter table
            ->whereBetween('bill_vet.date_entered', [$fromDate, $toDate]) // Same filter as your main query
            ->select('member_diagnosis.claim_no', 'clinical_diagnosis.clinical_diagnosis')
            ->get()
            ->groupBy('claim_no');

        foreach ($response as $res) {
            if (isset($diagnosisMap[$res->claim_no])) {

                $res->diag_code = $diagnosisMap[$res->claim_no]
                    ->pluck('clinical_diagnosis')
                    ->implode(' , ');
            } else {
                $res->diag_code = '';
            }
        }


        // foreach ($response as $res) {
        //     $diag_string = '';
        //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'member_diagnosis.diagnosis', 'clinical_diagnosis.idx')
        //         ->where('claim_no', $res->claim_no)
        //         ->pluck('clinical_diagnosis.clinical_diagnosis');


        //     if (count($diagnosis) !== 0) {
        //         foreach ($diagnosis as $key => $value) {
        //             if ($key == 0) {
        //                 $diag_string .= $value;
        //             } else {
        //                 $diag_string .= " , $value";
        //             }
        //         }
        //         $res->diag_code = $diag_string;
        //     } else {
        //         $res->diag_code = '';
        //     }
        // }


        return json_encode($response, JSON_INVALID_UTF8_IGNORE);
    }

     public function fetchVettedBills2(Request $request)
    {
        set_time_limit(0);

        $fromDate = Carbon::createFromFormat('m/Y', $request->from)->startOfMonth();
        $toDate   = Carbon::createFromFormat('m/Y', $request->to)->endOfMonth();

        $response = new StreamedResponse(function () use ($fromDate, $toDate) {
            $handle = fopen('php://output', 'w');

            // Add CSV Headers
            fputcsv($handle, [
                'ID',
                'Provider',
                'Benefit',
                'Service',
                'Corporate',
                'Vetted By',
                'Vet Date',
                'Claim No',
                'Invoice No',
                'Member No',
                'Amount',
                'Diagnosis'
            ]);

            // 1. Diagnosis Subquery (SQL Anywhere LIST function)
            $diagnosisSub = DB::table('member_diagnosis')
                ->join('clinical_diagnosis', 'member_diagnosis.diagnosis', 'clinical_diagnosis.idx')
                ->select('claim_no', DB::raw("LIST(clinical_diagnosis.clinical_diagnosis, ' , ') as diag_list"))
                ->groupBy('claim_no');

            // 2. Main Query using cursor() to keep memory low
            $query = DB::table('bills')
                ->select(
                    'bills.id',
                    'provider.provider',
                    'benefit.benefit',
                    'service.service',
                    'corporate.corporate',
                    'bill_vet.user_id as bill_vet_user',
                    'bill_vet.date_entered as vet_date',
                    'bills.claim_no',
                    'bills.invoice_no',
                    'bills.member_no',
                    'bills.invoiced_amount',
                    'diag_lookup.diag_list'
                )
                ->join('bill_vet', function ($join) use ($fromDate, $toDate) {
                    $join->on('bills.claim_no', '=', 'bill_vet.claim_no')
                        ->on('bills.invoice_no', '=', 'bill_vet.invoice_no')
                        ->on('bills.service', '=', 'bill_vet.service')
                        ->whereBetween('bill_vet.date_entered', [$fromDate, $toDate]);
                })
                ->leftJoinSub($diagnosisSub, 'diag_lookup', 'diag_lookup.claim_no', '=', 'bills.claim_no')
                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                ->leftJoin('service', 'service.code', '=', 'bills.service')
                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->orderBy('bills.claim_no', 'ASC');

            // Use cursor() to loop through 165k rows without loading them all into RAM
            foreach ($query->cursor() as $row) {
                fputcsv($handle, (array)$row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vetted_bills_export.csv"',
        ]);

        return $response;
    }

 
}
