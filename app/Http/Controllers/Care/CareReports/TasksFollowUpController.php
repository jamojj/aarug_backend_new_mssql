<?php

namespace App\Http\Controllers\Care\CareReports;

use App\Http\Controllers\Controller;
use App\Models\CareCorpVisitIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TasksFollowUpController extends Controller
{
    //fetch care tasks follows up
    public function fetchTasksFollowUps(Request $request)
    {

        switch ($request->option) {
            case '1':
                $completed_data = CareCorpVisitIssue::select(
                    'corporate.corporate',
                    DB::raw("FORMAT(care_corp_visit.visit_date, 'yyyy-mm-dd') as visit_date"),
                    DB::raw("FORMAT(care_corp_visit_issue.completed_on, 'yyyy-mm-dd') as completed_on"),
                    DB::raw("FORMAT(care_corp_visit_issue.complete_by, 'yyyy-mm-dd') as complete_by"),
                    'care_corp_visit.visited_by',
                    't_corp_visit_issue.comments',
                    'care_corp_visit_issue.assign_to'
                )
                    ->leftJoin('care_corp_visit', 'care_corp_visit.visit_no', '=', 'care_corp_visit_issue.visit_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'care_corp_visit.corp_id')
                    ->leftJoin('t_corp_visit_issue', 't_corp_visit_issue.code', '=', 'care_corp_visit_issue.issue')
                    ->whereNotNull('care_corp_visit_issue.completed_on')
                    ->get();

                return ['response' => $completed_data];
                break;
            case '2':
                $pending_data = CareCorpVisitIssue::select(
                    'corporate.corporate',
                    DB::raw("FORMAT(care_corp_visit.visit_date, 'yyyy-mm-dd') as visit_date"),
                    DB::raw("FORMAT(care_corp_visit_issue.completed_on, 'yyyy-mm-dd') as completed_on"),
                    DB::raw("FORMAT(care_corp_visit_issue.complete_by, 'yyyy-mm-dd') as complete_by"),
                    'care_corp_visit.visited_by',
                    't_corp_visit_issue.comments',
                    'care_corp_visit_issue.assign_to'
                )
                    ->leftJoin('care_corp_visit', 'care_corp_visit.visit_no', '=', 'care_corp_visit_issue.visit_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'care_corp_visit.corp_id')
                    ->leftJoin('t_corp_visit_issue', 't_corp_visit_issue.code', '=', 'care_corp_visit_issue.issue')
                    ->whereNull('care_corp_visit_issue.completed_on')
                    ->get();

                return ['response' => $pending_data];
                break;
            default:
                return ['response' => []];
                break;
        }
    }
}
