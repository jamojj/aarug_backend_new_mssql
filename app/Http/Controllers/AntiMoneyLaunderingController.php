<?php

namespace App\Http\Controllers;

use App\Models\Corporate\Corporate;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AntiMoneyLaunderingController extends Controller
{


    public function fetchNonSanctionedCorporates()
    {
        $report = Corporate::select('corp_id', 'corporate')
        ->where(DB::raw("isnull(sanctioned, 0)"), 0)
        ->where(DB::raw("isnull(cancelled, 0)"), 0)
        ->whereIn('individual', [1, 3])
        ->orderBy('corporate', 'asc')
        ->get();
        return json_encode($report, JSON_INVALID_UTF8_IGNORE);
    }
    public function fetchNonSanctionedPrincipals()
    {
        $report = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname, ' ', principal_applicant.first_name, ' ', principal_applicant.other_names) as name"),
            'principal_applicant.member_no'
        )
            ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where(DB::raw("isnull(principal_applicant.sanctioned, 0)"), 0)
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->where('corporate.individual', 2)
            ->orderBy('name', 'asc')
            ->get();
        return json_encode($report, JSON_INVALID_UTF8_IGNORE);
    }
    public function fetchAntiMoneyLaunderingSanctioned()
    {
        $corporate = Corporate::select(
            'idx',
            'corporate as name',
            'sanctioned',
            'sanctioned_status',
            DB::raw("'CORPORATE' as type"),
            DB::raw("CASE WHEN sanctioned = 1 THEN 'sanctioned' ELSE 'not-sanctioned' END as sanctioned_text"),
            DB::raw("CASE WHEN sanctioned_status = 1 then 'politically-exposed' ELSE 'not-exposed' END as sanctioned_status_text")
        )
            ->where(DB::raw("isnull(sanctioned, 0)"), 1)
            ->where(DB::raw("isnull(cancelled, 0)"), 0)
            ->whereIn('individual', [1, 3]);
        $report = PrincipalApplicant::select(
            'principal_applicant.idx',
            DB::raw("CONCAT(principal_applicant.surname, ' ', principal_applicant.first_name, ' ', principal_applicant.other_names) as name"),
            'principal_applicant.sanctioned',
            'principal_applicant.sanctioned_status',
            DB::raw("'RETAIL' as type"),
            DB::raw("CASE WHEN principal_applicant.sanctioned = 1 THEN 'sanctioned' ELSE 'not-sanctioned' END as sanctioned_text"),
            DB::raw("CASE WHEN principal_applicant.sanctioned_status = 1 then 'politically-exposed' ELSE 'not exposed' END as sanctioned_status_text")
        )
            ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where(DB::raw("isnull(principal_applicant.sanctioned, 0)"), 1)
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->where('corporate.individual', 2)
            ->union($corporate)
            ->orderBy('name')
            ->get();

        return json_encode($report, JSON_INVALID_UTF8_IGNORE);
    }


    public function saveSanctionChoosen(Request $request)
    {
        try {
            if ($request->type == 'CORPORATE') {
                DB::transaction(
                    function () use ($request) {
                        Corporate::where('idx', $request->id)->update([
                            'sanctioned' => $request->sanction_choosen
                        ]);
                    }
                );
            } else if ($request->type == 'RETAIL') {
                DB::transaction(
                    function () use ($request) {
                        PrincipalApplicant::where('idx', $request->id)->update([
                            'sanctioned' => $request->sanction_choosen
                        ]);
                    }
                );
            }
            return ['success'];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function saveSanctionStatus(Request $request)
    {
        try {
            if ($request->type == 'CORPORATE') {
                DB::transaction(
                    function () use ($request) {
                        Corporate::where('idx', $request->id)->update([
                            'sanctioned_status' => $request->sanction_status
                        ]);
                    }
                );
            } else if ($request->type == 'RETAIL') {
                DB::transaction(
                    function () use ($request) {
                        PrincipalApplicant::where('idx', $request->id)->update([
                            'sanctioned_status' => $request->sanction_status
                        ]);
                    }
                );
            }
            return ['success'];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function saveSanctionMain(Request $request)
    {
        try {
            if ($request->chooseOption == 1) {
                DB::transaction(
                    function () use ($request) {
                        Corporate::where('corp_id', $request->corp)->update([
                            'sanctioned' => $request->sanctioned_main,
                            'sanctioned_status' => $request->status_main
                        ]);
                    }
                );
            } else if ($request->chooseOption == 2) {
                DB::transaction(
                    function () use ($request) {
                        PrincipalApplicant::where('member_no', $request->principal)->update([
                            'sanctioned' => $request->sanctioned_main,
                            'sanctioned_status' => $request->status_main
                        ]);
                    }
                );
            }

            return ['success'];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
