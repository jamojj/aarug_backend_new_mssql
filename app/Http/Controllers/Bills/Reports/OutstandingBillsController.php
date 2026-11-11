<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\Corporate\Corporate;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isNull;

class OutstandingBillsController extends Controller
{
    public function fetchOutstandingBills(Request $request)
    {
        // return $request;
        ini_set('max_execution_time', '0');
        $lc_total = 0;
        $bills_array = array();
        switch ($request->option) {
            case '1':
                $data = Provider::select('provider.code as provider', 'provider.provider as provider_name')
                    ->leftJoin('bills', 'provider.code', '=', 'bills.provider')
                    ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->whereNull('bills.payment_no')
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->distinct()
                    ->get();
                foreach ($data as $dt) {
                    //at vet (entered but not vetted)
                    $lc_vet = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount'))
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->where('principal_applicant.corp_id', $request->corporate)
                        ->get();

                    $lc_vet = count($lc_vet) === 0 ? 0 : $lc_vet[0]->invoiced_amount;
                    if (is_null($lc_vet)) {
                        $lc_vet = 0;
                    }
                    //at payment (approved but not paid or vouchered)
                    $lc_pay = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
                        ->where(DB::raw('isnull(bills.paid, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->where('principal_applicant.corp_id', $request->corporate)
                        ->get();
                    $lc_pay = count($lc_pay) === 0 ? 0 : $lc_pay[0]->amount_payable;
                    if (is_null($lc_pay)) {
                        $lc_pay = 0;
                    }
                    //at voucher (vouchered but no cheque details)
                    $lc_vou = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills.paid, 0)'), 1)
                        ->whereNull('bills.payment_no')
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->where('principal_applicant.corp_id', $request->corporate)
                        ->get();
                    $lc_vou = count($lc_vou) === 0 ? 0 : $lc_vou[0]->amount_payable;
                    if (is_null($lc_vou)) {
                        $lc_vou = 0;
                    }
                    //at cheques (cheque details entered but not yet dispatched)
                    $lc_cheque = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills_payment.dispatched, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->where('principal_applicant.corp_id', $request->corporate)
                        ->get();
                    $lc_cheque = count($lc_cheque) === 0 ? 0 : $lc_cheque[0]->amount_payable;
                    if (is_null($lc_cheque)) {
                        $lc_cheque = 0;
                    }
                    //rejected at vetting
                    $lc_rejected  = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount'))
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 2)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->where('principal_applicant.corp_id', $request->corporate)
                        ->get();
                    $lc_rejected = count($lc_rejected) === 0 ? 0 : $lc_rejected[0]->invoiced_amount;
                    if (is_null($lc_rejected)) {
                        $lc_rejected = 0;
                    }


                    $lc_total = $lc_vet + $lc_pay + $lc_vou + $lc_cheque;

                    $dts = [
                        'provider' => $dt->provider_name,
                        'at_vetting' => $lc_vet,
                        'at_payment' => $lc_pay,
                        'in_voucher' => $lc_vou,
                        'in_cheque' => $lc_cheque,
                        'rejected' => $lc_rejected,
                        'total' => $lc_total,
                    ];
                    array_push($bills_array, $dts);
                }
                return json_encode($bills_array, JSON_INVALID_UTF8_IGNORE);
                break;
            case '2':
                $data = Provider::select('provider.code as provider', 'provider.provider as provider_name')
                    ->leftJoin('bills', 'provider.code', '=', 'bills.provider')
                    ->whereNull('bills.payment_no')
                    ->distinct()
                    ->get();
                foreach ($data as $dt) {
                    //at vet (entered but not vetted)
                    $lc_vet = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount'))
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->get();
                    $lc_vet = count($lc_vet) === 0 ? 0 : $lc_vet[0]->invoiced_amount;
                    if (is_null($lc_vet)) {
                        $lc_vet = 0;
                    }

                    //at payment (approved but not paid or vouchered)
                    $lc_pay = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
                        ->where(DB::raw('isnull(bills.paid, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->get();
                    $lc_pay = count($lc_pay) === 0 ? 0 : $lc_pay[0]->amount_payable;
                    if (is_null($lc_pay)) {
                        $lc_pay = 0;
                    }
                    //in voucher (vouchered but no cheque details)
                    $lc_vou = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount,0)) - sum(isnull(bills.deduction_amount,0)) as amount_payable'))
                        ->where(DB::raw('isnull(bills.paid, 0)'), 1)
                        ->whereNull('bills.payment_no')
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->get();
                    $lc_vou = count($lc_vou) === 0 ? 0 : $lc_vou[0]->amount_payable;
                    if (is_null($lc_vou)) {
                        $lc_vou = 0;
                    }
                    //in cheques (cheque details entered but not yet dispatched)
                    $lc_cheque = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
                        ->where(DB::raw('isnull(bills_payment.dispatched, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->get();
                    $lc_cheque = count($lc_cheque) === 0 ? 0 : $lc_cheque[0]->amount_payable;
                    if (is_null($lc_cheque)) {
                        $lc_cheque = 0;
                    }
                    //rejected at vetting
                    $lc_rejected  = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount'))
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 2)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $dt->provider)
                        ->get();
                    $lc_rejected = count($lc_rejected) === 0 ? 0 : $lc_rejected[0]->invoiced_amount;
                    if (is_null($lc_rejected)) {
                        $lc_rejected = 0;
                    }
                    $lc_total = $lc_vet + $lc_pay + $lc_vou + $lc_cheque;
                    $dts = [
                        'provider' => $dt->provider_name,
                        'at_vetting' => $lc_vet,
                        'at_payment' => $lc_pay,
                        'in_voucher' => $lc_vou,
                        'in_cheque' => $lc_cheque,
                        'rejected' => $lc_rejected,
                        'total' => $lc_total,
                    ];
                    array_push($bills_array, $dts);
                }
                return json_encode($bills_array, JSON_INVALID_UTF8_IGNORE);
                break;
            case '3':
                $data = Corporate::select('corporate.corp_id', 'corporate.corporate')
                    ->leftJoin('principal_applicant', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('bills', 'bills.member_no', '=', 'member_info.member_no')
                    ->whereNull('bills.payment_no')
                    ->where('bills.provider', $request->provider)
                    ->distinct()
                    ->get();
                foreach ($data as $dt) {
                    //at vet (entered but not vetted)
                    $lc_vet = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount'))
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $request->provider)
                        ->where('principal_applicant.corp_id', $dt->corp_id)
                        ->get();
                    $lc_vet = count($lc_vet) === 0 ? 0 : $lc_vet[0]->invoiced_amount;
                    if (is_null($lc_vet)) {
                        $lc_vet = 0;
                    }
                    //at payment (approved but not paid or vouchered)
                    $lc_pay = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
                        ->where(DB::raw('isnull(bills.paid, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $request->provider)
                        ->where('principal_applicant.corp_id', $dt->corp_id)
                        ->get();
                    $lc_pay = count($lc_pay) === 0 ? 0 : $lc_pay[0]->amount_payable;
                    if (is_null($lc_pay)) {
                        $lc_pay = 0;
                    }
                    //at voucher (vouchered but no cheque details)
                    $lc_vou = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills.paid, 0)'), 1)
                        ->whereNull('bills.payment_no')
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $request->provider)
                        ->where('principal_applicant.corp_id', $dt->corp_id)
                        ->get();
                    $lc_pay = count($lc_pay) === 0 ? 0 : $lc_pay[0]->amount_payable;
                    if (is_null($lc_pay)) {
                        $lc_pay = 0;
                    }
                    //at cheques (cheque details entered but not yet dispatched)
                    $lc_cheque = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills_payment.dispatched, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $request->provider)
                        ->where('principal_applicant.corp_id', $dt->corp_id)
                        ->get();
                    $lc_cheque = count($lc_cheque) === 0 ? 0 : $lc_cheque[0]->amount_payable;
                    if (is_null($lc_cheque)) {
                        $lc_cheque = 0;
                    }
                    //rejected at vetting
                    $lc_rejected  = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount'))
                        ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                        ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 2)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('bills.provider',  $request->provider)
                        ->where('principal_applicant.corp_id', $dt->corp_id)
                        ->get();
                    $lc_rejected = count($lc_rejected) === 0 ? 0 : $lc_rejected[0]->invoiced_amount;
                    if (is_null($lc_rejected)) {
                        $lc_rejected = 0;
                    }
                    $lc_total = $lc_vet + $lc_pay + $lc_vou + $lc_cheque;
                    $dts = [
                        'corporate' => $dt->corporate,
                        'at_vetting' => $lc_vet,
                        'at_payment' => $lc_pay,
                        'in_voucher' => $lc_vou,
                        'in_cheque' => $lc_cheque,
                        'rejected' => $lc_rejected,
                        'total' => $lc_total,
                    ];
                    array_push($bills_array, $dts);
                }
                return json_encode($bills_array, JSON_INVALID_UTF8_IGNORE);
                break;
            case '4':
                $data = Corporate::select('corporate.corp_id', 'corporate.corporate')
                    ->leftJoin('principal_applicant', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('bills', 'bills.member_no', '=', 'member_info.member_no')
                    ->whereNull('bills.payment_no')
                    ->distinct()
                    ->get();
                foreach ($data as $dt) {
                    //at vet (entered but not vetted)
                    $lc_vet = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount'))
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('principal_applicant.corp_id',  $dt->corp_id)
                        ->get();
                    $lc_vet = count($lc_vet) === 0 ? 0 : $lc_vet[0]->invoiced_amount;
                    if (is_null($lc_vet)) {
                        $lc_vet = 0;
                    }
                    //at payment (approved but not paid or vouchered)
                    $lc_pay = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
                        ->where(DB::raw('isnull(bills.paid, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('principal_applicant.corp_id',  $dt->corp_id)
                        ->get();
                    $lc_pay = count($lc_pay) === 0 ? 0 : $lc_pay[0]->amount_payable;
                    if (is_null($lc_pay)) {
                        $lc_pay = 0;
                    }
                    //in voucher (vouchered but no cheque details)
                    $lc_vou = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount,0)) - sum(isnull(bills.deduction_amount,0)) as amount_payable'))
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->where(DB::raw('isnull(bills.paid, 0)'), 1)
                        ->whereNull('bills.payment_no')
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('principal_applicant.corp_id',  $dt->corp_id)
                        ->get();
                    $lc_vou = count($lc_vou) === 0 ? 0 : $lc_vou[0]->amount_payable;
                    if (is_null($lc_vou)) {
                        $lc_vou = 0;
                    }
                    //in cheques (cheque details entered but not yet dispatched)
                    $lc_cheque = Bills::select(DB::raw('sum(isnull(bills.amount_payable, 0)) as amount_payable'))
                        ->leftJoin('bills_payment', 'bills.payment_no', '=', 'bills_payment.payment_no')
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->where(DB::raw('isnull(bills_payment.dispatched, 0)'), 0)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('principal_applicant.corp_id',  $dt->corp_id)
                        ->get();
                    $lc_cheque = count($lc_cheque) === 0 ? 0 : $lc_cheque[0]->amount_payable;
                    if (is_null($lc_cheque)) {
                        $lc_cheque = 0;
                    }
                    //rejected at vetting
                    $lc_rejected  = Bills::select(DB::raw('sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount'))
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->where(DB::raw('isnull(bills.vet_status, 0)'), 2)
                        ->where('bills.invoice_date', '<=', $request->as_at)
                        ->where('principal_applicant.corp_id',  $dt->corp_id)
                        ->get();
                    $lc_rejected = count($lc_rejected) === 0 ? 0 : $lc_rejected[0]->invoiced_amount;
                    if (is_null($lc_rejected)) {
                        $lc_rejected = 0;
                    }
                    $lc_total = $lc_vet + $lc_pay + $lc_vou + $lc_cheque;
                    $dts = [
                        'corporate' => $dt->corporate,
                        'at_vetting' => $lc_vet,
                        'at_payment' => $lc_pay,
                        'in_voucher' => $lc_vou,
                        'in_cheque' => $lc_cheque,
                        'rejected' => $lc_rejected,
                        'total' => $lc_total,
                    ];
                    array_push($bills_array, $dts);
                }
                return json_encode($bills_array, JSON_INVALID_UTF8_IGNORE);
                break;

            default:
                # code...
                break;
        }
    }
}
