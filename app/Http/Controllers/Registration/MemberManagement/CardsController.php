<?php

namespace App\Http\Controllers\Registration\MemberManagement;

use App\Http\Controllers\Controller;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardsController extends Controller
{
    public function cardData($member_no)
    {
        $mem_data = MemberInfo::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("min(member_anniversary.start_date) start_date"),
            DB::raw("max(member_anniversary.anniv) as anniv"),
            'member_benefits.category',
            't_product_name.product_name',
            'corporate.corporate',
            'member_info.member_no',
            'member_info.dob'
        )
            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->leftJoin('member_anniversary', 'member_info.member_no', '=', 'member_anniversary.member_no')
            ->leftJoin('member_benefits', 'member_info.member_no', '=', 'member_benefits.member_no')
            ->leftJoin('t_product_name', 'member_benefits.product_name', '=', 't_product_name.code')
            ->groupBy(
                'names',
                'category',
                't_product_name.product_name',
                'corporate',
                'member_info.member_no',
                'member_info.dob'
            )
            ->where('member_info.member_no', $member_no)
            ->get();

        return $mem_data;
    }
}
