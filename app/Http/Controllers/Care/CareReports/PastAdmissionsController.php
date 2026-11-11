<?php

namespace App\Http\Controllers\Care\CareReports;

use App\Http\Controllers\Controller;
use App\Models\CareAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PastAdmissionsController extends Controller
{
    //fetch past admissions
    public function fetchPastAdmissions(Request $request)
    {
        $date_from = date('Ymd', strtotime($request->date_from));
        $date_to = date('Ymd', strtotime($request->date_to));
        $past_admissions = [];

        //search by member no and dates
        if ($request->options === "1") {
            $past_admissions = CareAdmission::select(
                'corporate.corporate',
                'care_admission.member_no',
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                'care_admission.pre_auth_no',
                'care_admission.date_admitted',
                'provider.provider',
                'care_admission.admission_no',
                'care_admission.ward',
                'care_admission.room_no',
                'care_admission.bed_no'
            )
                ->leftJoin('member_info', 'member_info.member_no', '=', 'care_admission.member_no')
                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->leftJoin('provider', 'provider.code', '=', 'care_admission.provider')
                ->whereBetween(DB::raw("FORMAT(care_admission.date_admitted, 'yyyyMMdd')"), [$date_from, $date_to])
                ->where('care_admission.member_no', $request->member_no)
                ->get();
            //search all by dates
        } else {
            $past_admissions = CareAdmission::select(
                'corporate.corporate',
                'care_admission.member_no',
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                'care_admission.pre_auth_no',
                'care_admission.date_admitted',
                'provider.provider',
                'care_admission.admission_no',
                'care_admission.ward',
                'care_admission.room_no',
                'care_admission.bed_no'
            )
                ->leftJoin('member_info', 'member_info.member_no', '=', 'care_admission.member_no')
                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->leftJoin('provider', 'provider.code', '=', 'care_admission.provider')
                ->whereBetween(DB::raw("FORMAT(care_admission.date_admitted, 'yyyyMMdd')"), [$date_from, $date_to])
                ->get();
        }


        $count = count($past_admissions);
        return [
            'past_admissions' => $past_admissions,
            'count' => $count
        ];
    }
}
