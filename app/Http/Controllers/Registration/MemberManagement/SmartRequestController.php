<?php

namespace App\Http\Controllers\Registration\MemberManagement;

use App\Http\Controllers\Controller;
use App\Models\HaisSmartTriggers;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmartRequestController extends Controller
{
    public function smartRequestData($member_no)
    {
        $mem_data = MemberInfo::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            'corporate.corporate',
            'member_info.member_no'

        )
            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->where('member_info.member_no', $member_no)
            ->get();

        return $mem_data;
    }

    public function saveSmartRequest(Request $request)
    {
        $response = array();
        $save_smart_request = HaisSmartTriggers::create([
            'date_added' => date("Y-m-d"),
            'user_id' => $request->user,
            'trigger_id' => $request->request_type,
            'trigger_ref' => $request->member_no,
            'reason' => $request->reason,
            'reprint_reason' => $request->reprint_reason,
            'smart_synced' => 0,
        ]);
        if ($save_smart_request->wasRecentlyCreated === true) {
            array_push($response, "Smart card request saved successfully");
        } else {
            array_push($response, "Error saving smart card request");
        }

        return $response;
    }
}
