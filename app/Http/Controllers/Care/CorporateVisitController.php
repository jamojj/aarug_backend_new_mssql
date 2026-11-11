<?php

namespace App\Http\Controllers\Care;

use App\Http\Controllers\Controller;
use App\Models\CareCorpVisit;
use App\Models\CareCorpVisitIssue;
use Illuminate\Http\Request;

class CorporateVisitController extends Controller
{
    //fetch max visit no
    public function fetchMaxVisitNo(){
        $visit_no = CareCorpVisit::max('visit_no');
        $visit_no = $visit_no+1;
        return $visit_no;
    }
     //fetch max issue no
     public function fetchMaxIssueNo(){
        $issue_no = CareCorpVisitIssue::max('issue_no');
        $issue_no = $issue_no+1;
        return $issue_no;
    }

    
    public function saveCorporateVisit(Request $request){
        // return $request;
        try{
            //set inserted, updated, and deleted items
            $inserted_item = "visit_no : $request->visit_no";
            $updated_item = null;
            $deleted_item = null;

            CareCorpVisit::create([
                'visit_no' =>$request->visit_no,
                'corp_id' =>$request->corp_id,
                'visit_date' =>$request->visit_date,
                'visited_by' =>$request->visited_by,
                'met_with' =>$request->met_with,
                'next_visit_date' =>$request->next_visit_date,
                'met_with_title' =>$request->met_with_title,
                
            ]);

            //save access log
            app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);


            return ['message' => 'Save Successfully.'];
        }catch (\Exception $e){
            return ['error' => $e->getMessage()];
        }
    }
}
