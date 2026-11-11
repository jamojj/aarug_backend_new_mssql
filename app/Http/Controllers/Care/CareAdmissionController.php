<?php

namespace App\Http\Controllers\Care;

use App\Http\Controllers\Controller;
use App\Models\CareAdmission;
use App\Models\PreAuthorization;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CareAdmissionController extends Controller
{
    //fetch previous member care admissions
    public function fetchMemberCareAdmissions($member_no)
    {
        $pre_auths = PreAuthorization::select(
            'pre_authorization.code',
            'pre_authorization.member_no',
            DB::raw("CONCAT(member_info.surname, ' ',member_info.first_name,' ',member_info.other_names) as member_names"),
            'pre_authorization.provider as provider_code',
            'provider.provider as provider',
            'pre_authorization.reported_by',
            'pre_authorization.authorized_by',
            DB::raw("FORMAT(pre_authorization.date_reported, 'yyyy-mm-dd') as date_reported"),
            DB::raw("FORMAT(pre_authorization.date_authorized, 'yyyy-mm-dd') as date_authorized"),
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'pre_authorization.member_no')
            ->leftJoin('provider', 'provider.code', '=', 'pre_authorization.provider')
            ->where('pre_authorization.member_no', $member_no)
            ->get();

        return [
            'pre_auths' => $pre_auths,
        ];
    }

    //fetch care admissions details by code
    public function fetchCareAdmissionsDetails($pre_auth_code)
    {
        $data = PreAuthorization::select(
            'pre_authorization.code',
            'pre_authorization.member_no',
            'pre_authorization.provider as provider_code',
            'provider.provider as provider'
        )
            ->leftJoin('provider', 'provider.code', '=', 'pre_authorization.provider')
            ->where('pre_authorization.code', $pre_auth_code)
            ->get();

        return ['response' => $data[0]];
    }

    //save care admission
    public function saveCareAdmission(Request $request)
    {
        try {
            //return $request;
            //save in care admissions table

            //set inserted, updated, and deleted items
            $inserted_item = "member_no : $request->member_no, pre_auth_no : $request->authorisation_no, provider : $request->provider";
            $updated_item = null;
            $deleted_item = null;

            CareAdmission::create([
                'member_no' => $request->member_no,
                'pre_auth_no' => $request->authorisation_no,
                'provider' => $request->provider,
                'date_admitted' => $request->date_admitted,
                'admitting_doc' => $request->admitting_doc,
                'admission_no' => $request->admission_no,
                'ward' => $request->ward,
                'room_no' => $request->room_no,
                'bed_no' => $request->bed_no,
                'diagnosis' => $request->diagnosis,
                'date_discharged' => $request->date_discharged,
            ]);

            //save access log
            app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);


            return ['message' => 'Care Admission Saved Successfully'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
