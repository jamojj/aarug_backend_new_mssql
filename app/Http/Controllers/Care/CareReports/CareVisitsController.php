<?php

namespace App\Http\Controllers\Care\CareReports;

use App\Http\Controllers\Controller;
use App\Models\CareVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CareVisitsController extends Controller
{
     public function fetchCareVisits(Request $request)
     {
          $report = CareVisit::select(
               DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as member_name"),
               'principal_applicant.corp_id',
               'corporate.corporate',
               'care_visit.member_no',
               'care_visit.pre_auth_no',
               DB::raw("FORMAT(care_visit.visit_date, 'yyyy-mm-dd') as date_admitted"),
               DB::raw("FORMAT(care_visit.visit_date, 'yyyy-mm-dd') as visit_date"),
               'care_visit.visited_by',
               'care_visit.incurred_amt',
          )
               ->leftJoin('principal_applicant', 'principal_applicant.member_no', '=', 'care_visit.member_no')
               ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
               ->leftJoin('corporate', 'corporate.corp_id', 'principal_applicant.corp_id')
               ->where('care_visit.member_no', $request->member_no)
               ->get();

          return $report;
     }
}
