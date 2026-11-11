<?php

namespace App\Http\Controllers;

use App\Models\Corporate\Corporate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataAnalysisUtilizationReportController extends Controller
{
   public function fetchDataAnalysisUtilizationReport(Request $request){
     ini_set('max_execution_time', '0');
        $data =  [];
        switch ($request->option) {
            //corporate
            case '1':
                $data = DB::select("CALL corporate_utilization(?,?,?)", [$request->corporate, $request->anniv, 1]);
                // $data = $this->commonData("WHERE member_benefits.anniv = $request->anniv AND principal_applicant.corp_id = $request->corporate");
                break;
            //family
            case '2':
                $data = DB::select("CALL family_utilization(?,?,?)", [$request->family_no, $request->family_anniv, 1]);
                // $data = $this->commonData("WHERE member_benefits.anniv = $request->family_anniv AND principal_applicant.family_no = '$request->family_no'");
                break;
            //member
            case '3':
                $data = DB::select("CALL member_utilization(?,?,?)", [$request->member_no, $request->member_anniv, 1]);
                // $data = $this->commonData("WHERE member_benefits.anniv = $request->member_anniv AND member_info.member_no = '$request->member_no'");
                break;
            //all corporates
            case '4':
                $corp_data = Corporate::select(
                    DB::raw("max(corp_anniversary.anniv) as max_anniv"),
                    'corporate.corp_id',
                )
                    ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                    ->groupBy('corporate.corp_id')
                    ->get();

                foreach ($corp_data as $cd) {
                    $dt = DB::select("CALL corporate_utilization(?,?,?)", [$cd->max_anniv, $cd->corp_id, 1]);
                    // $dt = $this->commonData("WHERE member_benefits.anniv = $cd->max_anniv AND principal_applicant.corp_id = $cd->corp_id");
                    array_push($data, $dt);
                }

                break;
        }
        return $data;
   }
}
