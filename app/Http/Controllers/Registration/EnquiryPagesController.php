<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnquiryPagesController extends Controller
{
    public function fetchEnquiryPagesData(Request $request)
    {
       $max_anniv = MemberAnniversary::where('member_no', $request->member_no)->max('anniv');
        $member_data = MemberInfo::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as full_names"),
            'member_info.DOB',
            'family_relation.relation',
            'corporate.corporate',
            'member_anniversary.health_plan'
        )
            ->leftJoin('member_anniversary', 'member_info.member_no', '=', 'member_anniversary.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->leftJoin('family_relation', 'member_info.relation_to_principal', '=', 'family_relation.code')
            ->where('member_info.member_no', $request->member_no)
            ->where('member_anniversary.anniv', $max_anniv)
            ->get();
       

        switch ($request->policy_options) {
            case '0':
                return [];
                break;
            case '1':
                $cover_period = MemberAnniversary::select(DB::raw("min(start_date) start_date, max(end_date) end_date"))
                    ->where('member_no', $request->member_no)
                    ->get();

                $claims_info = Bills::select(
                    'bills.claim_no',
                    'provider.provider',
                    'bills.date_received',
                    'bills.invoice_date',
                    'benefit.benefit',
                    'bills.invoiced_amount',
                    'bills.amount_payable'
                )
                    ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
                    ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
                    ->where('member_no', $request->member_no)
                    ->whereBetween('bills.invoice_date', [$cover_period[0]->start_date, $cover_period[0]->end_date])
                    ->get();

                    $data=[
                        'member_data'=>$member_data,
                        'cover_period'=>$cover_period,
                        'claim_info'=>$claims_info,
                    ];

                    return $data;
                break;
            case '2':
                $cover_period = MemberAnniversary::select(DB::raw("min(start_date) start_date, max(end_date) end_date"))
                    ->where('anniv', $request->anniv)
                    ->where('member_no', $request->member_no)
                    ->get();


                $claims_info = Bills::select(
                    'bills.claim_no',
                    'provider.provider',
                    'bills.date_received',
                    'bills.invoice_date',
                    'benefit.benefit',
                    'bills.invoiced_amount',
                    'bills.amount_payable'
                )
                    ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
                    ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
                    ->where('member_no', $request->member_no)
                    ->whereBetween('bills.invoice_date', [$cover_period[0]->start_date, $cover_period[0]->end_date])
                    ->get();

                $data = [
                    'member_data' => $member_data,
                    'cover_period' => $cover_period,
                    'claim_info' => $claims_info,
                ];

                return $data;
                break;
        }
    }
}
