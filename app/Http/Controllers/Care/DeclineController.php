<?php

namespace App\Http\Controllers\Care;

use App\Http\Controllers\Controller;
use App\Models\DeclineLetter;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDOException;

class DeclineController extends Controller
{
    //
    public function fetchMember(Request $request)
    {
        // return $request;
        try {
            if ($request->search_option == 1) {
                $member = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as member_name"),
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
                    'member_info.member_no',
                    'member_info.family_no',
                    't_gender.gender',
                )
                    ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
                    ->where(function ($q) use ($request) {
                        $q->where('member_info.surname', 'like', '%' . $request->memberName . '%')
                            ->orWhere('member_info.first_name', 'like', '%' . $request->memberName . '%')
                            ->orWhere('member_info.other_names', 'like', '%' . $request->memberName . '%');
                    })
                    ->where('principal_applicant.corp_id', $request->corp_id)
                    ->get();
                return $member;
            } elseif ($request->search_option == 2) {
                $member = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as member_name"),
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
                    'member_info.member_no',
                    'member_info.family_no',
                    't_gender.gender',
                )
                    ->leftJoin('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
                    ->where(function ($q) use ($request) {
                        $q->where('member_info.surname', 'like', '%' . $request->memberName . '%')
                            ->orWhere('member_info.first_name', 'like', '%' . $request->memberName . '%')
                            ->orWhere('member_info.other_names', 'like', '%' . $request->memberName . '%');
                    })
                    ->where('member_info.member_no', $request->memberNo)
                    ->where('principal_applicant.corp_id', $request->corp_id)
                    ->get();
                return $member;
            }
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function fetchMemberDetails($member_no)
    {
        $member_details = MemberInfo::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as full_name"),
            'member_info.member_no'
        )
            ->where('member_info.member_no', $member_no)
            ->get();
        return $member_details;
    }
    //fetch max decline code
    public function fetchMaxDeclineCode()
    {
        $code = DeclineLetter::max('code');
        $code = $code + 1;
        return $code;
    }
    //fetch all decline code
    public function fetchDeclineCodePerProvider($provider)
    {
        $code = DeclineLetter::select('code')
            ->where('provider', $provider)
            ->get();
        return $code;
    }

    public function fetchDeclineData(Request $request)
    {
        // return $request;
        $decline_data = DeclineLetter::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as full_name"),
            'decline_letter.code',
            'decline_letter.member_no',
            'decline_letter.provider as provider_code',
            'decline_letter.date_entered',
            'decline_letter.date_admitted',
            'decline_letter.decline_date',
            'decline_letter.decline_reason as decline_code',
            'decline_letter.user_id',
            'decline_letter.notes',
            'decline_reason.decline_reason',
            'provider.provider'
        )

            ->leftJoin('member_info', 'decline_letter.member_no', '=', 'member_info.member_no')
            ->leftJoin('provider', 'decline_letter.provider', '=', 'provider.code')
            ->leftJoin('decline_reason', 'decline_letter.decline_reason', '=', 'decline_reason.code')
            ->where('decline_letter.provider', $request->provider_selected)
            ->where('decline_letter.code', $request->search_code)
            ->get();
        return $decline_data;
    }

    public function saveDeclineLetter(Request $request)
    {
        // return $request;
        $today = date('Y-m-d');
        try {
            //set inserted, updated, and deleted items
            $inserted_item = "code : $request->code";
            $updated_item = null;
            $deleted_item = null;

            DeclineLetter::create([
                'code' => $request->code,
                'member_no' => $request->member_no,
                'provider' => $request->provider,
                'decline_date' => $request->decline_date,
                'date_admitted' => $request->date_admitted,
                'decline_reason' => $request->decline_reason,
                'user_id' => $request->user,
                'date_entered' => $today,
                'notes' => $request->notes,

            ]);

            //save access log
            app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);

            return ['message' => 'Save Successfully'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
