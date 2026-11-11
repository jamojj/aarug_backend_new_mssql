<?php

namespace App\Http\Controllers\Care;

use App\Http\Controllers\Controller;
use App\Models\CareAdmission;
use App\Models\CareVisit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionVisitController extends Controller
{
    public function fetchMemberCare($memno)
    {
        $care_admission = CareAdmission::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            'care_admission.member_no',
            'care_admission.pre_auth_no',
            'provider.provider',
            'care_admission.date_admitted',
            'doctors.doctor',
            'care_admission.admission_no',
            'care_admission.ward',
            'care_admission.room_no',
            'care_admission.bed_no',
        )
            ->leftJoin('member_info', 'care_admission.member_no', '=', 'member_info.member_no')
            ->leftJoin('provider', 'care_admission.provider', '=', 'provider.code')
            ->leftJoin('doctors', 'care_admission.admitting_doc', '=', 'doctors.code')
            ->where('care_admission.member_no', $memno)
            ->get();
        $care_visit = CareVisit::select(
            DB::raw(" FORMAT(visit_date, 'yyyy-mm-dd') as visit_date"),
            'visited_by',
            'incurred_amt',
        )
            ->where('member_no', $memno)->get();
        $data = [
            'care_admission' => $care_admission,
            'care_visit' => $care_visit
        ];
        return $data;
    }

    public function saveCareVisit(Request $request)
    {
        $response = [];
        try {
            
            $max_care_visit = CareVisit::max('visit_no') + 1;

            //set inserted, updated, and deleted items
            $inserted_item = "visit_no : $max_care_visit";
            $updated_item = null;
            $deleted_item = null;

            $save_care_visit = CareVisit::create([
                'visit_no' => $max_care_visit,
                'member_no' => $request->member_no,
                'pre_auth_no' => $request->preauth_no,
                'visit_date' => $request->visit_date,
                'visited_by' => $request->visited_by,
                'presented' => $request->presented,
                'incurred_amt' => $request->incurred_amt,
                'exp_discharge_date' => $request->exp_disch_date,
                'care_nurse_comments' => $request->care_nurse_comments,
                'doc_comments' => $request->doc_comments,
            ]);

            if ($save_care_visit->wasRecentlyCreated === true) {
                array_push($response, "Care visit saved successfully");

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);

            } else {
                array_push($response, "Error saving care visit");
            }
        } catch (Exception $e) {
            array_push($response, $e->getMessage());
        }

        return $response;
    }
}
