<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Mail\HaisMail;
use App\Models\Bills;
use App\Models\Provider;
use App\Models\UnresolvedClaims;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use Dompdf\Dompdf;

class RemittanceController extends Controller
{
    public function fetchRemittancePerBatchNo(Request $request)
    {
        $invoiced_total = 0;
        $payable_total = 0;
        $deduction_total = 0;
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));

        $claims = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("FORMAT(bills.invoice_date, 'dd/mm/yyyy') as invoice_date"),
            DB::raw("CASE WHEN bills.vet_status = 1 then 'APPROVED' ELSE 'REJECTED' END as vet_status"),
            'bills.id',
            'bills.claim_no',
            'bills.member_no',
            'bills.invoiced_amount',
            'bills.amount_payable',
            'bills.deduction_amount',
            'deduction_reason.deduct_reason',
            'bills.deduction_notes',
            'reject_reason.reject_reason'
        )
            ->join('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->leftJoin('deduction_reason', 'bills.deduction_reason', '=', 'deduction_reason.code')
            ->leftJoin('reject_reason', 'bills.reject_reason', '=', 'reject_reason.code')
            ->orderBy('claim_no', 'ASC')
            ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [1, 2])
            // ->where(DB::raw("isnull(bills.paid, 0)"), 1)
            // ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
            ->where('bills.provider', $request->provider)
            ->where('bills.batch_no', $request->batch_no)
            ->get();

        foreach ($claims as $claim) {
            $invoiced_total += $claim->invoiced_amount;
            $payable_total += $claim->amount_payable;
            $deduction_total += $claim->deduction_amount;
        }

        $unresolved = UnresolvedClaims::select(
            'idx',
            'member_name',
            'member_no',
            'dos',
            'claimed_amt',
            'deduction_amt',
            'amount_payable',
            'issue'

        )
            // ->whereBetween(DB::raw("FORMAT(dos, 'yyyyMMdd')"), [$from, $to])
            ->where('provider', $request->provider)
            ->where('batch_no', $request->batch_no)
            ->get();

        foreach ($unresolved as $val) {
            $invoiced_total += $val->claimed_amt;
            $payable_total += $val->amount_payable;
            $deduction_total += $val->deduction_amt;
        }


        $data = [
            'claims' => json_encode($claims, JSON_INVALID_UTF8_IGNORE),
            'unresolved' => json_encode($unresolved, JSON_INVALID_UTF8_IGNORE),
            'claims_count' => count($claims),
            'invoiced_total' => number_format($invoiced_total, 2),
            'payable_total' => number_format($payable_total, 2),
            'deduction_total' => number_format($deduction_total, 2)
        ];

        return $data;
    }

    public function sendRemittanceMail(Request $request)
    {
        ini_set('max_execution_time', '0');
        $dompdf = new Dompdf();
        $dompdf->loadHtml($request->file);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $file =  $dompdf->output();
        $amt_payable = $request->amt_payable;
        $batch_date = date('M, Y', strtotime($request->batch_date));
        // $from = date("M'y", strtotime( $request->from));
        // $to = date("M'y", strtotime( $request->to));
        $provider =  Provider::select('provider')->where('code', $request->provider)->get();
        $provider = count( $provider) !== 0 ? $provider[0]->provider : '';
        try {
            $subject = "RE: PAYMENT OF $provider";
            $viewName = 'emails.RemitanceMail';
            $details = [
                'title' => 'Dear Team,',
                // 'body' => "Greeting from AAR Insurance. I hope this finds you well.<br><br>Please acknowledge receipt of UGX $amt_payable/=   deposited on your account in settlement of medical bills from $from - $to.<br><br>Attached is a payment breakdown for you to review and revert in case of any clarification needed."
                'body' => "Greeting from AAR Insurance. I hope this finds you well.<br><br>Please acknowledge receipt of UGX $amt_payable/=   deposited on your account in settlement of medical bills for the period $batch_date.<br><br>Attached is a payment breakdown for you to review and revert in case of any clarification needed."
            ];
            $attachment = $file;
            $attachmentName = 'remittance.pdf';
            $attachmentMime = 'application/pdf';
            $emails  = $request->provider_email;
            // $emails  = 'jamox22@gmail.com';

            $email = array_map('trim', explode(',', $emails));
            $ccRecipients = [
                'francis.kagalama@aar-insurance.com',
                'moses.lubuulwa@aar-insurance.com',
                'ronald.mayanja@aar-insurance.com',
                'elsie.acam@aar-insurance.com',
                'didas.mugume@aar-insurance.com',
                'brian.mbabazi@aar-insurance.com',
                'allclinicalnetwork.ug@aar-insurance.com',
                'jesca.nambalirwa@aar-insurance.com'
            ];
            // $ccRecipients = [
            //     'jkahiko@prosperitykenya.net',                
            // ];
          


            Mail::to($email)
                ->cc($ccRecipients)
                ->send(new HaisMail($subject, $details, $viewName,  $attachment, $attachmentName, $attachmentMime, $ccRecipients));
            return ["Email Sent."];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
