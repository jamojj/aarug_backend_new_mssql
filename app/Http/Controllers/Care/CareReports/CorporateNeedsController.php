<?php

namespace App\Http\Controllers\Care\CareReports;

use App\Http\Controllers\Controller;
use App\Models\CareCorpVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CorporateNeedsController extends Controller
{
    public function fetchCorporateNeeds(Request $request)
    {
        // return $request;
        $corp_needs = CareCorpVisit::select(
            'care_corp_visit.corp_id',
            'care_corp_visit.visit_no',
            DB::raw("FORMAT(care_corp_visit.visit_date, 'yyyy-mm-dd') as visit_date"),
            'care_corp_visit.visited_by',
            'care_corp_visit.met_with',
            'care_corp_visit_issue.issue_no',
            'corporate.corporate'
        )
            ->leftJoin('care_corp_visit_issue', 'care_corp_visit_issue.visit_no', '=', 'care_corp_visit.visit_no')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'care_corp_visit.corp_id')
            ->where('care_corp_visit.corp_id', $request->corp_id)
            ->get();

        return $corp_needs;
    }
}
