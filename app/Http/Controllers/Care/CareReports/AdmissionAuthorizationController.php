<?php

namespace App\Http\Controllers\Care\CareReports;

use App\Http\Controllers\Controller;
use App\Models\PreAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionAuthorizationController extends Controller
{
    public function fetchAdmissionAuthorizations(Request $request)
    {
        $date_from = date('Ymd', strtotime($request->date_from));
        $date_to = date('Ymd', strtotime($request->date_to));
        $data = [];

        //search by member no and dates
        if ($request->options === "1") {
            $data = PreAuthorization::select(
                'pre_authorization.code',
                'pre_authorization.member_no',
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                DB::raw("FORMAT(pre_authorization.date_reported, 'yyyy-mm-dd') as date_reported"),
                DB::raw("FORMAT(pre_authorization.date_authorized, 'yyyy-mm-dd') as date_authorized"),
                DB::raw("FORMAT(pre_authorization.admission_date, 'yyyy-mm-dd') as admission_date"),
                DB::raw("FORMAT(pre_authorization.discharge_date, 'yyyy-mm-dd') as discharge_date"),
                'pre_authorization.pre_diagnosis',
                'provider.provider',
                'corporate.corporate'
            )
                ->leftJoin('member_info', 'member_info.member_no', '=', 'pre_authorization.member_no')
                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->leftJoin('provider', 'provider.code', '=', 'pre_authorization.provider')
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->whereBetween(DB::raw("FORMAT(pre_authorization.admission_date, 'yyyyMMdd')"), [$date_from, $date_to])
                ->where('pre_authorization.member_no', $request->member_no)
                ->get();

            //search all by dates
        } else {
            $data = PreAuthorization::select(
                'pre_authorization.code',
                'pre_authorization.member_no',
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                DB::raw("FORMAT(pre_authorization.date_reported, 'yyyy-mm-dd') as date_reported"),
                DB::raw("FORMAT(pre_authorization.date_authorized, 'yyyy-mm-dd') as date_authorized"),
                DB::raw("FORMAT(pre_authorization.admission_date, 'yyyy-mm-dd') as admission_date"),
                DB::raw("FORMAT(pre_authorization.discharge_date, 'yyyy-mm-dd') as discharge_date"),
                'pre_authorization.pre_diagnosis',
                'provider.provider',
                'corporate.corporate'
            )
                ->leftJoin('member_info', 'member_info.member_no', '=', 'pre_authorization.member_no')
                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->leftJoin('provider', 'provider.code', '=', 'pre_authorization.provider')
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->whereBetween(DB::raw("FORMAT(pre_authorization.admission_date, 'yyyyMMdd')"), [$date_from, $date_to])
                ->get();
        }


        return  json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
