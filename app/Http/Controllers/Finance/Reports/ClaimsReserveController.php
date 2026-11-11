<?php

namespace App\Http\Controllers\Finance\Reports;

use App\Http\Controllers\Controller;
use App\Models\ClaimsReserve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimsReserveController extends Controller
{
    public function claimsReserve(){
        $claim_reserves = ClaimsReserve::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
            'claims_reserve.id',
            'claims_reserve.pre_auth_no',
            'claims_reserve.member_no',
            'claims_reserve.trans_type',
            'claims_reserve.debit',
            'claims_reserve.credit',
            'claims_reserve.user_id',
            'claims_reserve.date_entered',
        )
        ->leftJoin('member_info', 'claims_reserve.member_no', '=', 'member_info.member_no')
        ->get();
        $claim_reserves = json_encode($claim_reserves, JSON_INVALID_UTF8_IGNORE);
        return $claim_reserves;
    }
}
