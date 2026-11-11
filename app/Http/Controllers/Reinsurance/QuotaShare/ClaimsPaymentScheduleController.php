<?php

namespace App\Http\Controllers\Reinsurance\QuotaShare;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\ClaimsReserve;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimsPaymentScheduleController extends Controller
{
    public function fetchClaimsPaymentScheduleData(Request $request)
    {
        // return $request;
        $claims_paid = $claims_unpaid = $ttl_reserves = $claims_paid_cede = $claims_unpaid_cede = $reserve_cede = 0;
        $claims = Bills::select(
            'bills.member_no',
            'bills.claim_no',
            'bills.payment_no',
            DB::raw(" FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            'bills.invoiced_amount',
            'bills.amount_payable',
            'bills.anniv',
            'bills.claim_nature',
            'corporate.corp_id',
            'corporate.corporate',
            'provider.provider',
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
        )
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->where('bills.claim_nature', 1)
            ->where(DB::raw("isnull(corporate.cancelled,0)"), 0)
            ->where('bills.invoice_date', '>=', $request->date_from)
            ->where('bills.invoice_date', '<=', $request->date_to)
            ->get();

        foreach ($claims as $claim) {
            if (!is_null($claim->payment_no)) {
                $lc_ratain_amt = ($claim->amount_payable * ($request->retain_rate / 100));
                $lc_cede_amt = ($claim->amount_payable - $lc_ratain_amt);
                $lc_cede_amt = ($lc_cede_amt * ($request->cede_rate / 100));

                $claim->cede_amt = (float)number_format($lc_cede_amt, 2, '.', '');

                //claculate all paid claims
                $claims_paid += $claim->amount_payable;
                //claculate total cede_amt
                $claims_paid_cede += $claim->cede_amt;
            } elseif (is_null($claim->payment_no)) {
                $lc_ratain_amt = ($claim->invoiced_amount * ($request->retain_rate / 100));
                $lc_cede_amt = ($claim->invoiced_amount - $lc_ratain_amt);
                $lc_cede_amt = ($lc_cede_amt * ($request->cede_rate / 100));

                $claim->cede_amt = (float)number_format($lc_cede_amt, 2, '.', '');

                //claculate all unpaid claims
                $claims_unpaid += $claim->invoiced_amount;
                //claculate total cede_amt
                $claims_unpaid_cede += $claim->cede_amt;
            }

            $cover_dates = CorpAnniversary::select(
                DB::raw(" FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                DB::raw(" FORMAT(end_date, 'yyyy-mm-dd') as end_date")
            )
                ->where('anniv', $claim->anniv)
                ->where('corp_id', $claim->corp_id)
                ->get();

            $claim->start_date = $cover_dates[0]['start_date'];
            $claim->end_date = $cover_dates[0]['end_date'];
        }

        $reserves = ClaimsReserve::select('claims_reserve.member_no')
            ->leftJoin('pre_authorization', 'claims_reserve.pre_auth_no', '=', 'pre_authorization.code')
            ->where('pre_authorization.authority_type', 1)
            ->where('pre_authorization.date_authorized', '>=', $request->date_from)
            ->where('pre_authorization.date_authorized', '<=', $request->date_to)
            ->distinct()
            ->get();

        foreach ($reserves as $reserve) {
            $mem_name = MemberInfo::select(
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name")
            )
                ->where('member_no', $reserve->member_no)
                ->get();
            $reserve->member_name = $mem_name[0]['member_name'];

            $corp = Corporate::select('corporate.corporate')
                ->leftJoin('principal_applicant', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->leftJoin('member_info', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->where('member_info.member_no', $reserve->member_no)
                ->get();
            $reserve->corporate = $corp[0]['corporate'];

            $sum = ClaimsReserve::select(DB::raw('sum(isnull(credit, 0)) as credit'), DB::raw('sum(isnull(debit, 0)) as debit'))
                ->where('member_no', $reserve->member_no)
                ->where('date_entered', '>=', $request->date_from)
                ->where('date_entered', '<=', $request->date_to)
                ->get();
            $reserve->credit = $sum[0]['credit'];
            $reserve->debit = $sum[0]['debit'];
            $reserve->reserve_amt = $sum[0]['credit'] - $sum[0]['debit'];
            //claculate all total reserves
            $ttl_reserves += $reserve->reserve_amt;

            $reserve->claim = 'Reserves';

            $lc_ratain_amt = ($reserve->reserve_amt * ($request->retain_rate / 100));
            $lc_cede_amt = ($reserve->reserve_amt - $lc_ratain_amt);
            $lc_cede_amt = ($lc_cede_amt * ($request->cede_rate / 100));

            $reserve->cede_amt = (float)number_format($lc_cede_amt, 2, '.', '');
            //claculate total cede_amt
            $reserve_cede += $reserve->cede_amt;
        }
        //total from claims and reserves
        $total_unpaid =  $claims_unpaid + $ttl_reserves;
        //total cede_amt from claims and reserves
        $total_cede = $claims_paid_cede + $claims_unpaid_cede + $reserve_cede;
        $total_cede = (float)number_format($total_cede, 2, '.', '');

        $data = [
            'claims' => $claims,
            'reserves' => $reserves,
            'total_paid' => $claims_paid,
            'total_unpaid' => $total_unpaid,
            'total_cede' => $total_cede,
        ];
        return $data;
    }
}
