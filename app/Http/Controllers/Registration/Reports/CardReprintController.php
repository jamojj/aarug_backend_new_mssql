<?php

namespace App\Http\Controllers\Registration\Reports;

use App\Http\Controllers\Controller;
use App\Models\HaisSmartTriggers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardReprintController extends Controller
{
    public function fetchCardReprintData(Request $request)
    {
        ini_set('max_execution_time', '0');
        $data = [];
        if (isset($request->options)) {
            switch ($request->options) {
                    //member no
                case '1':
                    $data = HaisSmartTriggers::select(
                        DB::raw("FORMAT(hais_smart_triggers.date_added , 'yyyy-mm-dd') as date_added"),
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                        'member_info.member_no',
                        'corporate.corporate',
                        't_card_reprint_reason.reprint_reason',
                        'hais_smart_triggers.user_id',
                        't_hais_triggers.descr'
                    )
                        ->join('member_info', 'member_info.member_no', '=', 'hais_smart_triggers.trigger_ref')
                        ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                        ->leftJoin('t_card_reprint_reason', 't_card_reprint_reason.code', '=', 'hais_smart_triggers.reprint_reason')
                        ->leftJoin('t_hais_triggers', 't_hais_triggers.idx', '=', 'hais_smart_triggers.trigger_id')
                        ->orderBy('hais_smart_triggers.date_added', 'DESC')
                        ->where('hais_smart_triggers.trigger_id', 2)
                        ->where('hais_smart_triggers.trigger_ref', $request->member_no)
                        ->get();

                    break;
                    //dates
                case '2':
                    $from = date("Ymd", strtotime($request->from));
                    $to = date("Ymd", strtotime($request->to));

                    $data = HaisSmartTriggers::select(
                        DB::raw("FORMAT(hais_smart_triggers.date_added , 'yyyy-mm-dd') as date_added"),
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                        'member_info.member_no',
                        'corporate.corporate',
                        't_card_reprint_reason.reprint_reason',
                        'hais_smart_triggers.user_id',
                        't_hais_triggers.descr'
                    )
                        ->join('member_info', 'member_info.member_no', '=', 'hais_smart_triggers.trigger_ref')
                        ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                        ->leftJoin('t_card_reprint_reason', 't_card_reprint_reason.code', '=', 'hais_smart_triggers.reprint_reason')
                        ->leftJoin('t_hais_triggers', 't_hais_triggers.idx', '=', 'hais_smart_triggers.trigger_id')
                        ->orderBy('hais_smart_triggers.date_added', 'DESC')
                        ->where('hais_smart_triggers.trigger_id', 2)
                        ->whereBetween(DB::raw("FORMAT(hais_smart_triggers.date_added, 'yyyyMMdd')"), [$from, $to])
                        ->get();
                    break;
                    //all
                case '3':
                    $data = HaisSmartTriggers::select(
                        DB::raw("FORMAT(hais_smart_triggers.date_added , 'yyyy-mm-dd') as date_added"),
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                        'member_info.member_no',
                        'corporate.corporate',
                        't_card_reprint_reason.reprint_reason',
                        'hais_smart_triggers.user_id',
                        't_hais_triggers.descr'
                    )
                        ->join('member_info', 'member_info.member_no', '=', 'hais_smart_triggers.trigger_ref')
                        ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                        ->leftJoin('t_card_reprint_reason', 't_card_reprint_reason.code', '=', 'hais_smart_triggers.reprint_reason')
                        ->leftJoin('t_hais_triggers', 't_hais_triggers.idx', '=', 'hais_smart_triggers.trigger_id')
                        ->orderBy('hais_smart_triggers.date_added', 'DESC')
                        ->where('hais_smart_triggers.trigger_id', 2)
                        ->get();
                    break;
            }
            return json_encode($data, JSON_INVALID_UTF8_IGNORE);
        }
    }
}
