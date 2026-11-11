<?php

namespace App\Http\Controllers\Care\CareReports;

use App\Http\Controllers\Controller;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CareAdmissionsReportController extends Controller
{
    public function fetchCareAdmissions(Request $request)
    {
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));

        $report = PrincipalApplicant::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as member_name"),
            'principal_applicant.corp_id',
            'corporate.corporate',
            'care_admission.member_no',
            'care_admission.pre_auth_no',
            'provider.provider',
            DB::raw("FORMAT(care_admission.date_admitted, 'yyyy-mm-dd') as date_admitted"),
            'care_admission.admission_no',
            'care_admission.ward',
            'care_admission.room_no',
            'care_admission.bed_no',
        )
            ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->leftJoin('care_admission', 'care_admission.member_no', '=', 'member_info.member_no')
            ->leftJoin('corporate', 'corporate.corp_id', 'principal_applicant.corp_id')
            ->leftJoin('provider', 'care_admission.provider', 'provider.code')
            ->whereNull('care_admission.date_discharged')
            ->whereBetween(DB::raw("FORMAT(care_admission.date_admitted, 'yyyyMMdd')"), [$from, $to])
            ->get();

        return $report;
    }
}
