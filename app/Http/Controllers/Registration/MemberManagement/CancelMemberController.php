<?php

namespace App\Http\Controllers\Registration\MemberManagement;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberCancellation;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use App\Models\CancelReason;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class CancelMemberController extends Controller
{
    //fetch all member cancellation reasons
    public function fetchCancellationReasons()
    {
        $cancellation_reason = CancelReason::all();

        return $cancellation_reason;
    }

    //fetch data of member to be cancelled
    public function fetchCancelMemberData($sel_value_rd, $input_value)
    {
        //$sel_value_rd = $request->selected_radio;
        //$input_value = $request->input_value;

        //return [$sel_value_rd, $input_value];
        $data = [];
        switch ($sel_value_rd) {
            case '1':
                $max_anniv = CorpAnniversary::where('corp_id', $input_value)->max('anniv');
                $data = Corporate::select('CORPORATE.corp_id', 'CORPORATE.corporate', 'CORP_ANNIVERSARY.anniv', 'CORPORATE.cancelled', 'CORPORATE.cancellation_date', 'CORPORATE.cancellation_reason')
                    ->where('CORPORATE.corp_id', $input_value)
                    ->where('CORP_ANNIVERSARY.anniv', $max_anniv)
                    ->leftJoin('CORP_ANNIVERSARY', 'CORP_ANNIVERSARY.corp_id', '=', 'CORPORATE.corp_id')
                    ->get();

            case '2':
                $members = MemberInfo::select(
                    DB::raw("CONCAT(member_INFO.surname,' ',MEMBER_INFO.first_name,' ', MEMBER_INFO.other_names) as full_name"),
                    'member_info.family_no',
                    'member_info.member_no',
                    'FAMILY_RELATION.relation',
                    'CORPORATE.corporate',
                    'CORPORATE.corp_id',
                    'MEMBER_INFO.cancelled'
                )
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('FAMILY_RELATION', 'FAMILY_RELATION.code', '=', 'MEMBER_INFO.relation_to_principal')
                    ->leftJoin('CORPORATE', 'CORPORATE.corp_id', '=', 'principal_applicant.corp_id')
                    ->where('member_info.MEMBER_NO', $input_value)
                    ->get();
                foreach ($members as $member) {
                    $max_date_cancelled = MemberCancellation::where('member_no', $member->member_no)->max('DATE_CAN');
                    $cancel_data = MemberCancellation::select('date_can', 'reason')->where('member_no', $member->member_no)->where('date_can', $max_date_cancelled)->get();
                    $cancellation_date = count($cancel_data) !== 0 ?  $cancel_data[0]->date_can : '';
                    $cancellation_reason = count($cancel_data) !== 0 ?  $cancel_data[0]->reason : '';

                    $dt = [
                        'full_name' => $member->full_name,
                        'family_no' => $member->family_no,
                        'member_no' => $member->member_no,
                        'relation' => $member->relation,
                        'corporate' => $member->corporate,
                        'corp_id' => $member->corp_id,
                        'cancelled' => $member->cancelled,
                        'cancellation_date' => $cancellation_date,
                        'cancellation_reason' => $cancellation_reason,
                    ];

                    array_push($data, $dt);
                }
            case '3':
                $members = MemberInfo::select(
                    DB::raw("CONCAT(member_INFO.surname,' ',MEMBER_INFO.first_name,' ', MEMBER_INFO.other_names) as full_name"),
                    'member_info.family_no',
                    'member_info.member_no',
                    'FAMILY_RELATION.relation',
                    'CORPORATE.corporate',
                    'CORPORATE.corp_id',
                    'MEMBER_INFO.cancelled'
                )
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('FAMILY_RELATION', 'FAMILY_RELATION.code', '=', 'MEMBER_INFO.relation_to_principal')
                    ->leftJoin('CORPORATE', 'CORPORATE.corp_id', '=', 'principal_applicant.corp_id')
                    ->where('member_info.FAMILY_NO', $input_value)
                    ->get();
                foreach ($members as $member) {
                    $max_date_cancelled = MemberCancellation::where('member_no', $member->member_no)->max('DATE_CAN');
                    $cancel_data = MemberCancellation::select('date_can', 'reason')->where('member_no', $member->member_no)->where('date_can', $max_date_cancelled)->get();
                    $cancellation_date = count($cancel_data) !== 0 ?  $cancel_data[0]->date_can : '';
                    $cancellation_reason = count($cancel_data) !== 0 ?  $cancel_data[0]->reason : '';

                    $dt = [
                        'full_name' => $member->full_name,
                        'family_no' => $member->family_no,
                        'member_no' => $member->member_no,
                        'relation' => $member->relation,
                        'corporate' => $member->corporate,
                        'corp_id' => $member->corp_id,
                        'cancelled' => $member->cancelled,
                        'cancellation_date' => $cancellation_date,
                        'cancellation_reason' => $cancellation_reason,
                    ];

                    array_push($data, $dt);
                }
        }
        return $data;
    }

    public function saveCancelReinstateMember(Request $request)
    {
        //return $request;
        $message = array();
        $today = date('Y-m-d');
        $date_cancelled = $request->date;
        $corp_id = $request->corp_id;

        switch ($request->module) {
            case "cancel":
                try {
                    //update tables for cancel
                    if (isset($request->selected_radio)) {
                        //if corporate selected
                        if ($request->selected_radio == "1") {                          
                            $corp_max_anniv = CorpAnniversary::where('CORP_ID', $corp_id)->max('anniv');
                            $corp_members = MemberInfo::select('MEMBER_INFO.member_no', 'MEMBER_INFO.cancelled')
                                ->distinct()
                                ->leftJoin('PRINCIPAL_APPLICANT', 'PRINCIPAL_APPLICANT.family_no', '=', 'MEMBER_INFO.family_no')
                                ->leftJoin('MEMBER_ACCEPTANCE', 'MEMBER_ACCEPTANCE.member_no', '=', 'MEMBER_INFO.member_no')
                                ->leftJoin('MEMBER_ANNIVERSARY', 'MEMBER_ANNIVERSARY.member_no', '=', 'MEMBER_INFO.member_no')
                                ->leftJoin('CORPORATE', 'CORPORATE.corp_id', '=', 'principal_applicant.corp_id')
                                ->orderBy('MEMBER_INFO.member_no')
                                ->where('MEMBER_ACCEPTANCE.status', 1)
                                ->where('PRINCIPAL_APPLICANT.corp_id', $corp_id)
                                ->get();

                            foreach ($corp_members as $corp_member) {
                                //ensure not to insert into member cancellation if another member cancellation already exists
                                $ll_cancelled = MemberInfo::select('cancelled')
                                    ->where('member_no', $corp_member->member_no)
                                    ->get();
                                $ll_cancelled = $ll_cancelled[0]['cancelled'];

                                if ($ll_cancelled == null || $ll_cancelled == 0) {
                                    //latest entry is not cancellation
                                    $ll_smrt_sync = MemberAnniversary::select(DB::raw("isnull(sync, 0) as sync"))
                                        ->where('member_no', $corp_member->member_no)
                                        ->where('anniv', $corp_max_anniv)
                                        ->get();
                                    if (isEmpty($ll_smrt_sync)) {
                                        $ll_smrt_sync = 0;
                                    } else {
                                        $ll_smrt_sync = $ll_smrt_sync[0]['sync'];
                                    }
                                    //insert into cancellation table
                                    DB::transaction(function ()
                                    use ($request, $corp_member, $date_cancelled, $corp_max_anniv, $today, $corp_id, $ll_smrt_sync) {
                                        //create entries of members in corporate to Member cancellation
                                        MemberCancellation::create([
                                            'MEMBER_NO' => $corp_member->member_no,
                                            'CANCELLED' => 1,
                                            'DATE_CAN' => $date_cancelled,
                                            'ANNIV' => $corp_max_anniv,
                                            'REASON' => $request->reason,
                                            'user_id' => $request->user,
                                            'date_entered' => $today,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                            // 'sync' => null,
                                            // 'smart_sync' => $ll_smrt_sync,
                                        ]);

                                        //update / add a cancelled flag into member info
                                        MemberInfo::where('member_no', $corp_member->member_no)
                                            ->update([
                                                'cancelled' => 1
                                            ]);

                                        //update member_anniversary
                                        MemberAnniversary::where([
                                            'member_no' => $corp_member->member_no,
                                            'anniv' => $corp_max_anniv,
                                        ])->update([
                                            'nov' => null,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                        ]);
                                    });
                                }
                            }
                            //cancel the corporate
                            DB::transaction(
                                function () use ($corp_id, $today, $request) {
                                    Corporate::where('corp_id', $corp_id)->update([
                                        'cancelled' => 1,
                                        'cancellation_date' => $today,
                                        'cancellation_reason' => $request->reason,
                                        'smart_sync' => 1,
                                        'sync' => 0,
                                    ]);
                                }
                            );
                        
                            array_push($message, "Corporate and its Family member(s) cancelled");
                            //return ['message' => "Corporate and its Family member(s) cancelled"];
                            return ['message' => $message[0]];
                        } //if member selected
                        else if ($request->selected_radio == "2") {
                            $member_count = $request->table_row_count;
                            for ($i = 0; $i < $member_count; $i++) {
                                $member_no = $request->member_no[$i];
                                //find and cancel this member who must be accepted, activated and not previously cancelled
                                $ll_cnt = MemberInfo::select(DB::raw("count(*) as count"))
                                    ->leftJoin('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
                                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_acceptance.status', 1)
                                    ->where('member_info.member_no', $member_no)
                                    ->get();
                                $ll_cnt = $ll_cnt[0]['count'];

                                if ($ll_cnt == 0) {
                                    //member not accepted and or activated
                                    array_push($message, 'Member not Accepted and or Activated...so cannot be Cancelled');
                                    return ['message' => $message[0]];
                                    //return ['message' => 'Member not Accepted and or Activated...so cannot be Cancelled'];
                                } else {
                                    //get the family number of this member
                                    $ls_family_no = MemberInfo::select('family_no')
                                        ->where('member_no', $member_no)
                                        ->get();
                                    $ls_family_no = $ls_family_no[0]['family_no'];

                                    //get the latest member anniversary because one can only cancel a member in the latest anniv
                                    $ll_anniv = MemberAnniversary::where('member_no', $member_no)
                                        ->max('anniv');

                                    //check if member is already cancelled - done from frontend search request
                                    $ll_cancelled = MemberInfo::select('cancelled')
                                        ->where('member_no', $member_no)
                                        ->get();
                                    $ll_cancelled_value = $ll_cancelled[0]['cancelled'];

                                    if ($ll_cancelled_value == 1) {
                                        array_push($message, 'Member has already been Cancelled !');
                                        return ['message' => $message[0]];
                                        //return ['message' => 'Member has already been Cancelled !'];
                                    } else if (isNull($ll_cancelled) || $ll_cancelled_value == 0) {
                                        //latest entry is not cancellation
                                        $ll_smrt_sync = MemberAnniversary::select(DB::raw("isnull(sync, 0) as sync"))
                                            ->where('member_no', $member_no)
                                            ->where('anniv', $ll_anniv)
                                            ->get();
                                        if (isEmpty($ll_smrt_sync)) {
                                            $ll_smrt_sync = 0;
                                        } else {
                                            $ll_smrt_sync = $ll_smrt_sync[0]['sync'];
                                        }
                                        //insert into cancellation table
                                        DB::transaction(function ()
                                        use ($request, $member_no, $date_cancelled, $ll_anniv, $today, $ls_family_no, $ll_smrt_sync) {
                                            MemberCancellation::create([
                                                'MEMBER_NO' => $member_no,
                                                'CANCELLED' => 1,
                                                'DATE_CAN' => $date_cancelled,
                                                'ANNIV' => $ll_anniv,
                                                'REASON' => $request->reason,
                                                'user_id' => $request->user,
                                                'date_entered' => $today,
                                                'smart_sync' => 1,
                                                'sync' => 0,
                                                // 'sync' => null,
                                                // 'smart_sync' => $ll_smrt_sync,
                                            ]);

                                            //update / add a cancelled flag into member info
                                            MemberInfo::where('member_no', $member_no)
                                                ->update([
                                                    'cancelled' => 1
                                                ]);

                                            //update member_anniversary
                                            MemberAnniversary::where([
                                                'member_no' => $member_no,
                                                'anniv' => $ll_anniv
                                            ])->update([
                                                'nov' => null,
                                                'smart_sync' => 1,
                                                'sync' => 0,
                                            ]);
                                        });

                                        //get updated family size
                                        $ll_active = MemberInfo::select(DB::raw("count(member_no) as count"))
                                            ->where('family_no', $ls_family_no)
                                            ->where(DB::raw("isnull(cancelled, 0)"), 0)
                                            ->get();
                                        $ll_active = $ll_active[0]['count'];
                                        //update principal applicant
                                        DB::transaction(function () use ($ls_family_no, $ll_active) {
                                            PrincipalApplicant::where([
                                                'family_no' => $ls_family_no,
                                            ])->update([
                                                'family_size' => $ll_active,
                                                'smart_sync' => 1,
                                                'sync' => 0,
                                            ]);
                                        });
                                    }
                                }
                            }
                            array_push($message, 'Member Cancelled');
                            return ['message' => $message[0]];
                            //return ['message' => "Member Cancelled"];
                        } // family selected
                        else if ($request->selected_radio == "3") {
                            //count the number of members in the family
                            $member_count = $request->table_row_count;

                            $family_no = $request->family_no;
                            for ($i = 0; $i < $member_count; $i++) {
                                //get the latest member anniversary because one can only cancel a member in the latest anniv
                                $ll_anniv = MemberAnniversary::where('member_no', $request->member_no[$i])
                                    ->max('anniv');

                                //ensure no insert into member cancellation if another member cancellation already exists
                                $ll_cancelled = MemberInfo::select('cancelled')
                                    ->where('member_no', $request->member_no[$i])
                                    ->get();
                                $ll_cancelled_value = $ll_cancelled[0]['cancelled'];

                                if ($ll_cancelled_value == 1) {
                                    array_push($message, 'Member has already been Cancelled !');
                                    return ['message' => $message[0]];
                                    //return ['message' => 'Member has already been Cancelled !'];
                                } else if (isNull($ll_cancelled) || $ll_cancelled_value == 0) {
                                    //latest entry is not cancellation
                                    $ll_smrt_sync = MemberAnniversary::select(DB::raw("isnull(sync, 0) as sync"))
                                        ->where('member_no', $request->member_no[$i])
                                        ->where('anniv', $ll_anniv)
                                        ->get();
                                    if (isEmpty($ll_smrt_sync)) {
                                        $ll_smrt_sync = 0;
                                    } else {
                                        $ll_smrt_sync = $ll_smrt_sync[0]['sync'];
                                    }
                                    //insert into cancellation table
                                    DB::transaction(function ()
                                    use ($request, $date_cancelled, $ll_anniv, $today, $i, $ll_smrt_sync) {
                                        MemberCancellation::create([
                                            'MEMBER_NO' => $request->member_no[$i],
                                            'CANCELLED' => 1,
                                            'DATE_CAN' => $date_cancelled,
                                            'ANNIV' => $ll_anniv,
                                            'REASON' => $request->reason,
                                            'user_id' => $request->user,
                                            'date_entered' => $today,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                            // 'sync' => null,
                                            // 'smart_sync' => $ll_smrt_sync,
                                        ]);

                                        //update / add a cancelled flag into member info
                                        MemberInfo::where('member_no', $request->member_no[$i])
                                            ->update([
                                                'cancelled' => 1
                                            ]);

                                        //update member_anniversary
                                        MemberAnniversary::where([
                                            'member_no' => $request->member_no[$i],
                                            'anniv' => $ll_anniv
                                        ])->update([
                                            'nov' => null,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                        ]);
                                    });
                                }
                            }
                            array_push($message, 'Member Cancelled');
                            return ['message' => $message[0]];
                            //return ['message' => "Member Cancelled"];
                        }
                    }
                } catch (\Exception $e) {
                    array_push($message, "Client Cancellation Failed");
                    //array_push($message, $e->getMessage());
                    return ['message' => $message[0]];
                }
                break;
            case "reinstate":
                try {
                    if (isset($request->selected_radio)) {
                        //If corporate selected
                        if ($request->selected_radio == "1") {
                            $corp_max_anniv = CorpAnniversary::where('CORP_ID', $corp_id)->max('anniv');
                            //find and reinstate this corp members
                            $corp_members = MemberInfo::select('MEMBER_INFO.member_no', 'MEMBER_INFO.cancelled')
                                ->distinct()
                                ->leftJoin('PRINCIPAL_APPLICANT', 'PRINCIPAL_APPLICANT.family_no', '=', 'MEMBER_INFO.family_no')
                                ->where('PRINCIPAL_APPLICANT.corp_id', $corp_id)
                                ->orderBy('MEMBER_INFO.member_no')
                                ->get();
                            $count = count($corp_members);

                            foreach ($corp_members as $corp_member) {
                                //ensure that the member was cancelled for the stated reason above
                                if ($corp_member->cancelled == 1) {
                                    //latest entry is a cancellation
                                    //compare latest cancellation with reason for reinstatement (MUST BE SAME)
                                    $ldt_date = MemberCancellation::select(
                                        DB::raw("max(member_cancellation.date_can) as max_date_can"),
                                        'member_cancellation.reason',
                                        'cancel_reason.cancel_reason'
                                    )
                                        ->leftJoin('cancel_reason', 'cancel_reason.code', '=', 'member_cancellation.reason')
                                        ->where('member_no', $corp_member->member_no)
                                        ->groupBy('reason', 'cancel_reason')
                                        ->orderBy('max_date_can', 'DESC')
                                        ->get();
                                    if (isEmpty($ldt_date)) {
                                        $ll_reason = 0;
                                    } else {
                                        $ll_reason = $ldt_date[0]['reason'];
                                    }
                                    //                                if ($ll_reason == (int)$request->reason) {
                                    $ll_smrt_sync = MemberAnniversary::select(DB::raw("isnull(sync, 0) as sync"))
                                        ->where('member_no', $corp_member->member_no)
                                        ->where('anniv', $corp_max_anniv)
                                        ->get();
                                    if (isEmpty($ll_smrt_sync)) {
                                        $ll_smrt_sync = 0;
                                    } else {
                                        $ll_smrt_sync = $ll_smrt_sync[0]['sync'];
                                    }
                                    //update and/or insert into cancellation table a reinstation
                                    DB::transaction(function ()
                                    use ($request, $corp_member, $date_cancelled, $corp_max_anniv, $today, $corp_id, $ll_smrt_sync) {
                                        //create entries of members in corporate to Member cancellation
                                        MemberCancellation::create([
                                            'MEMBER_NO' => $corp_member->member_no,
                                            'CANCELLED' => 0,
                                            'DATE_CAN' => $date_cancelled,
                                            'ANNIV' => $corp_max_anniv,
                                            'REASON' => $request->reason,
                                            'user_id' => $request->user,
                                            'date_entered' => $today,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                            // 'sync' => null,
                                            // 'smart_sync' => $ll_smrt_sync,
                                        ]);

                                        //update / add a cancelled flag into member info
                                        MemberInfo::where('member_no', $corp_member->member_no)
                                            ->update([
                                                'cancelled' => 0
                                            ]);

                                        //update member_anniversary
                                        MemberAnniversary::where([
                                            'member_no' => $corp_member->member_no,
                                            'anniv' => $corp_max_anniv
                                        ])->update([
                                            'nov' => null,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                        ]);
                                    });                                    

                                }
                            }
                            //reinstate the corporate
                            DB::transaction(
                                function () use ($corp_id) {
                                    Corporate::where('corp_id', $corp_id)->update([
                                        'cancelled' => 0,
                                        'cancellation_date' => null,
                                        'cancellation_reason' => null,
                                        'smart_sync' => 1,
                                        'sync' => 0,
                                    ]);
                                }
                            );
                          
                            array_push($message, 'Corporate and its Family member(s) Reinstated');
                            return ['message' => $message[0]];
                        } //if member selected
                        else if ($request->selected_radio == "2") {
                            $member_count = $request->table_row_count;
                            for ($i = 0; $i < $member_count; $i++) {
                                $member_no = $request->member_no[$i];
                                //ensure that the member is already cancelled
                                $ll_cancelled = MemberInfo::select('cancelled')
                                    ->where('member_no', $member_no)
                                    ->get();
                                $ll_cancelled_value = $ll_cancelled[0]['cancelled'];

                                //get the latest member anniversary because one can only cancel a member in the latest anniv
                                $ll_anniv = MemberAnniversary::where('member_no', $member_no)
                                    ->max('anniv');

                                //compare latest cancellation with reason for reinstatement (MUST BE SAME)
                                $ldt_date = MemberCancellation::select(
                                    DB::raw("max(member_cancellation.date_can) as max_date_can"),
                                    'member_cancellation.reason',
                                    'cancel_reason.cancel_reason'
                                )
                                    ->leftJoin('cancel_reason', 'cancel_reason.code', '=', 'member_cancellation.reason')
                                    ->where('member_no', $member_no)
                                    ->groupBy('reason', 'cancel_reason')
                                    ->orderBy('max_date_can', 'DESC')
                                    ->get();
                                $ll_reason = $ldt_date[0]['reason'];

                                if ($ll_reason == (int)$request->reason) {
                                    $ll_smrt_sync = MemberAnniversary::select(DB::raw("isnull(sync, 0) as sync"))
                                        ->where('member_no', $member_no)
                                        ->where('anniv', $ll_anniv)
                                        ->get();
                                    if (isEmpty($ll_smrt_sync)) {
                                        $ll_smrt_sync = 0;
                                    } else {
                                        $ll_smrt_sync = $ll_smrt_sync[0]['sync'];
                                    }
                                    //update and/or insert into cancellation table a reinstation
                                    DB::transaction(function ()
                                    use ($request, $member_no, $ll_anniv, $date_cancelled, $today, $corp_id, $ll_smrt_sync) {
                                        //create entries of members in corporate to Member cancellation
                                        MemberCancellation::create([
                                            'MEMBER_NO' => $member_no,
                                            'CANCELLED' => 0,
                                            'DATE_CAN' => $date_cancelled,
                                            'ANNIV' => $ll_anniv,
                                            'REASON' => $request->reason,
                                            'user_id' => $request->user,
                                            'date_entered' => $today,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                            // 'sync' => null,
                                            // 'smart_sync' => $ll_smrt_sync,
                                        ]);

                                        //update / add a reinstatement flag into member info
                                        MemberInfo::where('member_no', $member_no)
                                            ->update([
                                                'cancelled' => 0
                                            ]);

                                        //update member_anniversary
                                        MemberAnniversary::where([
                                            'member_no' => $member_no,
                                            'anniv' => $ll_anniv
                                        ])->update([
                                            'nov' => null,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                        ]);
                                    });

                                    //get the family number of this member
                                    $ls_family_no = MemberInfo::select('family_no')
                                        ->where('member_no', $member_no)
                                        ->get();
                                    $ls_family_no = $ls_family_no[0]['family_no'];

                                    //get updated family size
                                    $ll_active = MemberInfo::select(DB::raw("count(member_no) as count"))
                                        ->where('family_no', $ls_family_no)
                                        ->where(DB::raw("isnull(cancelled, 0)"), 0)
                                        ->get();
                                    $ll_active = $ll_active[0]['count'];
                                    //update principal applicant
                                    DB::transaction(function () use ($ls_family_no, $ll_active) {
                                        PrincipalApplicant::where([
                                            'family_no' => $ls_family_no,
                                        ])->update([
                                            'family_size' => $ll_active
                                        ]);
                                    });
                                } else {
                                    array_push($message, 'Reinstatement reason should be same as Cancellation Reason.
                                    Previous Cancellation Reason was ' . $ldt_date[0]['cancel_reason']);
                                    return ['message' => $message[0]];
                                    //return ['message' => 'Reinstatement reason should be same as Cancellation Reason.
                                    //Previous Cancellation Reason was ' . $ldt_date[0]['cancel_reason']];
                                }
                            }
                            array_push($message, 'Member Reinstated');
                            return ['message' => $message[0]];
                            //return ['message' => "Member Reinstated"];
                        } //if family no selected
                        else if ($request->selected_radio == "3") {
                            //count the number of members in the family
                            $member_count = $request->table_row_count;
                            $family_no = $request->family_no[0];

                            //count the number of members in the family
                            $member_count = $request->table_row_count;
                            $fam_members = MemberInfo::select('member_no')
                                ->distinct('member_no')
                                ->where('family_no', $family_no)
                                ->get();

                            foreach ($fam_members as $member) {
                                $member_no = $member->member_no;

                                //ensure that the member is already cancelled
                                $ll_cancelled = MemberInfo::select('cancelled')
                                    ->where('member_no', $member_no)
                                    ->get();
                                $ll_cancelled_value = $ll_cancelled[0]['cancelled'];

                                //get the latest member anniversary because one can only cancel a member in the latest anniv
                                $ll_anniv = MemberAnniversary::where('member_no', $member_no)->max('anniv');

                                //get the latest member anniversary because one can only cancel a member in the latest anniv
                                $ll_anniv = MemberAnniversary::where('member_no', $member_no)
                                    ->max('anniv');

                                //compare latest cancellation with reason for reinstatement (MUST BE SAME)
                                $ldt_date = MemberCancellation::select(
                                    DB::raw("max(member_cancellation.date_can) as max_date_can"),
                                    'member_cancellation.reason',
                                    'cancel_reason.cancel_reason'
                                )
                                    ->leftJoin('cancel_reason', 'cancel_reason.code', '=', 'member_cancellation.reason')
                                    ->where('member_no', $member_no)
                                    ->groupBy('reason', 'cancel_reason')
                                    ->orderBy('max_date_can', 'DESC')
                                    ->get();
                                $ll_reason = $ldt_date[0]['reason'];

                                if ($ll_reason == (int)$request->reason) {
                                    $ll_smrt_sync = MemberAnniversary::select(DB::raw("isnull(sync, 0) as sync"))
                                        ->where('member_no', $member_no)
                                        ->where('anniv', $ll_anniv)
                                        ->get();
                                    if (isEmpty($ll_smrt_sync)) {
                                        $ll_smrt_sync = 0;
                                    } else {
                                        $ll_smrt_sync = $ll_smrt_sync[0]['sync'];
                                    }
                                    //update and/or insert into cancellation table a reinstation
                                    DB::transaction(function ()
                                    use ($request, $member_no, $ll_anniv, $date_cancelled, $today, $corp_id, $ll_smrt_sync) {
                                        //create entries of members in corporate to Member cancellation
                                        MemberCancellation::create([
                                            'MEMBER_NO' => $member_no,
                                            'CANCELLED' => 0,
                                            'DATE_CAN' => $date_cancelled,
                                            'ANNIV' => $ll_anniv,
                                            'REASON' => $request->reason,
                                            'user_id' => $request->user,
                                            'date_entered' => $today,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                            // 'sync' => null,
                                            // 'smart_sync' => $ll_smrt_sync,
                                        ]);

                                        //update / add a reinstatement flag into member info
                                        MemberInfo::where('member_no', $member_no)
                                            ->update([
                                                'cancelled' => 0
                                            ]);

                                        //update member_anniversary
                                        MemberAnniversary::where([
                                            'member_no' => $member_no,
                                            'anniv' => $ll_anniv
                                        ])->update([
                                            'nov' => null,
                                            'smart_sync' => 1,
                                            'sync' => 0,
                                        ]);
                                    });
                                } else {
                                    array_push($message, 'Reinstatement reason should be same as Cancellation Reason.
                                    Previous Cancellation Reason was ' . $ldt_date[0]['cancel_reason']);
                                    return ['message' => $message[0]];
                                    //return ['message' => 'Reinstatement reason should be same as Cancellation Reason.
                                    //    Previous Cancellation Reason was ' . $ldt_date[0]['cancel_reason']];
                                }
                            }
                            array_push($message, 'Family Reinstated');
                            return ['message' => $message[0]];
                            //return ['message' => "Family Reinstated"];
                        }
                    }
                } catch (\Exception $e) {
                    array_push($message, "Client Reinstating Failed");
                    return ['message' => $message[0]];
                }
                break;
            default:
                array_push($message, 'Error: Contact the I.T Admin');
                return ['message' => $message[0]];
                //return['message' => "Error: Contact the I.T Admin"];
                break;
        }
    }
}
