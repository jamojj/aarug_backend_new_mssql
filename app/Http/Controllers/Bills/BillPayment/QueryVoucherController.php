<?php

namespace App\Http\Controllers\Bills\BillPayment;

use App\Http\Controllers\Controller;
use App\Mail\HaisMail;
use App\Models\Bills;
use App\Models\BillsPayment;
use App\Models\LastNumber;
use App\Models\Provider;
use Dompdf\Dompdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class QueryVoucherController extends Controller
{
    public function fetchVoucherBillsPayment()
    {
        $vouchers = Bills::select('voucher_no')
            ->where('paid', 1)
            ->where(DB::raw("isnull(payment_no, 0)"), 0)
            ->distinct('voucher_no')
            ->orderBy('voucher_no', 'ASC')
            ->get();
        return $vouchers;
    }

    public function fetchVoucherData(Request $request)
    {
        $voucher_no = 'PV' . $request->voucher_no;
        $voucher_exists = Bills::where('bills.voucher_no', $voucher_no)->get();
        $voucher_not_paid = Bills::where(DB::raw("isnull(bills.payment_no, 0)"), 0)
            ->where('bills.voucher_no', $voucher_no)
            ->get();

        if (count($voucher_exists) !== 0) {
            if (count($voucher_not_paid) > 0) {
                $claims = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                    'service.service',
                    'service.code as service_code',
                    'provider.provider',
                    'provider.code as provider_code',
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
                    'bills.admin_fee',
                    'bills.INVOICE_DATE',
                    'bills.voucher_no'

                )
                    ->leftJoin('service', 'service.code', '=', 'bills.service')
                    ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->where(DB::raw("isnull(bills.payment_no, 0)"), 0)
                    ->where('bills.voucher_no', $voucher_no)
                    ->get();

                $others = Bills::select(
                    DB::raw("FORMAT(bills.voucher_date, 'yyyy-mm-dd') as v_date"),
                    'provider.provider',
                    'bills.voucher_date'
                )
                    ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                    ->where('bills.voucher_no', $voucher_no)
                    ->get();

                $data = [
                    'claims' => json_encode($claims, JSON_INVALID_UTF8_IGNORE),
                    'others' => json_encode($others, JSON_INVALID_UTF8_IGNORE)
                ];
                return $data;
            } else {
                return ['Voucher has already been paid!'];
            }
        } else {
            return ['Voucher does not exist or does not contain claims!'];
        }
    }

    public function updateQueryVoucher(Request $request)
    {
        try {
            $max_payment_no = LastNumber::where('task', 3)->pluck('last_no');
            $max_payment_no = $max_payment_no[0] + 1;
            $date_entered = date("Y-m-d");
            $amount_payable = floatval(preg_replace('/[^\d.]/', '', $request->amount_payable));
            $cheque_amount = floatval(preg_replace('/[^\d.]/', '', $request->cheque_amount));
            $wh_tax = floatval(preg_replace('/[^\d.]/', '', $request->wh_tax));

            //set inserted, updated, and deleted items
            $inserted_item = "payment_no : $max_payment_no, cheque_amount : $cheque_amount, wtax :  $wh_tax";
            $updated_item = "payment_no : $max_payment_no";
            $deleted_item = null;
            DB::transaction(function () use ($request, $max_payment_no, $cheque_amount, $date_entered, $amount_payable, $wh_tax, $inserted_item, $updated_item, $deleted_item) {


                BillsPayment::create([
                    'PAYMENT_NO' => $max_payment_no,
                    'CHEQUE_NO' => $request->cheque_no,
                    'CHEQUE_DATE' => $request->cheque_date,
                    'CHEQUE_AMOUNT' => $cheque_amount,
                    'PROVIDER' => $request->provider,
                    'CORP_ID' => $request->corporate,
                    'USER_ID' => $request->user,
                    'DATE_ENTERED' => $date_entered,
                    'admin_fee' => $request->admin_fee,
                    'notes' => $request->notes,
                    'payrun_date' => date('Y-m-d', strtotime($request->payrun_date)),
                    'amount_payable' => $amount_payable,
                    'discounts' => $request->discounts,
                    'wtax' => $wh_tax,
                    'bank_account' => $request->account,
                    'payment_mode' => $request->payment_mode,
                ]);

                // //update last number
                LastNumber::where('task', 3)->update(['last_no' => $max_payment_no]);
                // //update payment nos
                $this->updatePaymentNos($request, $max_payment_no);
                
                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });
            //send email to provider
            // if (!is_array($this->sendEmail($request))) {
            //     return ["Provider doesn't have an email address!"];
            // }
            return ['Voucher successful.'];
           
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function updatePaymentNos($request, $max_payment_no)
    {

        if (isset($request->id)) {
            for ($i = 0; $i < count($request->id); $i++) {
                Bills::where('id', $request->id[$i])->update([
                    'payment_no' => $max_payment_no,
                ]);
            }
        }
    }

    public function splitVoucher(Request $request)
    {
        try {
            $update_voucher = 0;
            $var = [];
            $arr = array();
            $ids = array();
            $final = array();
            $response = array();
            for ($i = 0; $i < count($request->pay); $i++) {
                if ($request->pay[$i] != 0) {
                    $var  = explode('-', $request->voucher_no_split[$i]);
                    array_push($arr, $var[0]);
                    array_push($ids, $request->id[$i]);
                }
            }

            $vs = Bills::where('voucher_no', 'like', '%' . $arr[0] . '%')->pluck('voucher_no');
            foreach ($vs as $v) {
                $var2  = explode('-', $v);
                if (count($var2) == 2) {
                    array_push($final, $var2[1]);
                } else {
                    array_push($final, 0);
                }
            }
            $max = max($final) + 1;
            $new_pv = $var[0] . '-' . $max;
            //set inserted, updated, and deleted items
            $inserted_item = null;
            $updated_item = "voucher_no : $new_pv";
            $deleted_item = null;

            foreach ($ids as $id) {
                $update_voucher = Bills::where('id', $id)->update([
                    'voucher_no' => $new_pv
                ]);
            }

            if ($update_voucher != 0) {
                array_push($response, "Voucher(s) split successfully with voucher no " . $new_pv);
                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            }
        } catch (Exception $e) {
            array_push($response, $e->getMessage());
        }

        return $response;
    }

 private function sendEmail($request) {
    ini_set('max_execution_time', '0');
       
        $prov =  Provider::select('email', 'provider')->where('code', $request->provider_code)->get();
        $email = count( $prov) !== 0 ? $prov[0]->email : '';
        // $email = 'jamox22@gmail.com';
        $provider_name = count( $prov) !== 0 ? $prov[0]->provider : '';
        $subject = "RE: PAYMENT OF $provider_name";
        try {
            if ($email !== '') {
                $viewName = 'emails.RemitanceMail';
                $details = [
                    'title' => 'Dear Team,',
                    // 'body' => "Greeting from AAR Insurance. I hope this finds you well.<br><br>Please acknowledge receipt of UGX $amt_payable/=   deposited on your account in settlement of medical bills from $from - $to.<br><br>Attached is a payment breakdown for you to review and revert in case of any clarification needed."
                    'body' => "Greeting from AAR Insurance. I hope this finds you well.<br><br>Please acknowledge receipt of UGX $request->amount_payable/= deposited on your account in settlement of medical bills."
                ];
             
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
                    ->send(new HaisMail($subject, $details, $viewName,  null, null, null, $ccRecipients));
                return ["Email Sent."];                
            }else{
                return "noemail";          
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
 }
}
