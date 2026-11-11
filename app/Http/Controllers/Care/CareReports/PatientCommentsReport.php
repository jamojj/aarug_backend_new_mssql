<?php

namespace App\Http\Controllers\Care\CareReports;

use App\Http\Controllers\Controller;
use App\Models\PatientComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientCommentsReport extends Controller
{
    public function fetchPatientComments(Request $request)
    {
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));

        $report = PatientComment::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as member_name"),
            DB::raw("FORMAT(care_visit.visit_date, 'yyyy-mm-dd') as visit_date"),
            'principal_applicant.corp_id',
            'corporate.corporate',
            'provider.provider',
            'patient_comment.visit_no',
            'patient_comment.member_no',
            't_inpatient_comments.inpatient_comments as patient_comment',
            'care_visit.pre_auth_no',
            'care_visit.visited_by',

        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'patient_comment.member_no')
            ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->leftJoin('care_visit', 'care_visit.visit_no', '=', 'patient_comment.visit_no')
            ->leftJoin('care_admission', 'care_visit.pre_auth_no', '=', 'care_admission.pre_auth_no')
            ->leftJoin('corporate', 'corporate.corp_id', 'principal_applicant.corp_id')
            ->leftJoin('provider', 'care_admission.provider', 'provider.code')
            ->leftJoin('t_inpatient_comments', 't_inpatient_comments.code', 'patient_comment.patient_comment')

            ->whereBetween(DB::raw("FORMAT(care_admission.date_admitted, 'yyyyMMdd')"), [$from, $to])
            ->get();

        return $report;
    }
}
