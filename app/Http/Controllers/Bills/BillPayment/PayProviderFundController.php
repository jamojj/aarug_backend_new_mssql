<?php

namespace App\Http\Controllers\Bills\BillPayment;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\Corporate\Corporate;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayProviderFundController extends Controller
{
    //fetch provider fund for dropdown
    public function fetchFundCorporates(){
        $fund_corporates = Corporate::select('corporate.corporate', 'corporate.corp_id')
            ->distinct()
            ->leftJoin('principal_applicant', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->leftJoin('bills', 'bills.family_no', '=', 'principal_applicant.family_no')
            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->where(DB::raw('isnull(bills.refund, 0)'), 0)
            ->where(DB::raw('isnull(bills.paid, 0)'), 0)
            ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
            ->where('bills.fund', 1)
            ->get();

        return ['fund_corporates' => $fund_corporates];
        }

    //fetch provider fund for dropdown
    public function fetchProviderFund($corp_id){
        $fund_providers = Provider::select('provider.provider', 'provider.code')
            ->distinct()
            ->leftJoin('bills', 'bills.provider', '=', 'provider.code')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->where(DB::raw('isnull(bills.refund, 0)'), 0)
            ->where(DB::raw('isnull(bills.paid, 0)'), 0)
            ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
            ->where('bills.fund', 1)
            ->where('principal_applicant.corp_id', $corp_id)
            ->get();

        return ['providers' => $fund_providers];
    }

    //fetch all claims for pay provider fund
    public function fetchPayProviderFundClaims($prov_code)
    {
        $total_invoiced_amt = $total_amount_payable =
        $total_deducted_amt = $total_admin_fee = 0;
        $claims = Bills::select('bills.id', 'bills.claim_no', 'bills.invoice_no', 'bills.service as service_code',
            'service.service', 'bills.paid', 'bills.invoiced_amount', 'bills.deduction_amount',
            'bills.deduction_reason as deduction_reason_code', 'deduction_reason.deduct_reason',
            'bills.deduction_notes', 'bills.amount_payable', 'bills.invoice_date',
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
            'bills.foreigns', 'bills.currency as currency_code', 'conversion_rates.currency',
            'bills.conversion_rate as rate', 't_exchange_rate.rate as conversion_rate', 'bills.foreign_amt',
            'bills.member_no', 'bills.admin_fee')
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->leftJoin('deduction_reason', 'deduction_reason.code', '=', 'bills.deduction_reason')
            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
            ->leftJoin('conversion_rates', 'conversion_rates.idx', '=', 'bills.currency')
            ->leftJoin('t_exchange_rate', 't_exchange_rate.rate', '=', 'bills.conversion_rate')
            ->where('bills.provider', $prov_code)
            ->where(function ($paid) {
                $paid->where('bills.paid', null)
                    ->orWhere('bills.paid', 0);
            })
            ->where(DB::raw('isnull(bills.vet_status, 0)'), 1)
            ->where(DB::raw("isnull(bills.refund, 0)"), 0)
            // ->where('bills.refund', 0)
            ->where(function ($payment_no) {
                $payment_no->where('bills.payment_no', 0)
                    ->orWhere('bills.payment_no', null);
            })
            ->where('bills.fund', 1)
            ->get();

        foreach ($claims as $claim) {
            $total_invoiced_amt += $claim->invoiced_amount;
            $total_deducted_amt += $claim->deduction_amount;
            $total_amount_payable += $claim->amount_payable;
            $total_admin_fee += $claim->admin_fee;
        }

        return ['claims' => $claims,
            'claims_count' => count($claims),
            'totals' => [
                'total_invoiced_amt' => $total_invoiced_amt,
                'total_deduction_amount' => $total_deducted_amt,
                'total_amount_payable' => $total_amount_payable,
                'total_admin_fee' => $total_admin_fee
            ],];
    }
}
