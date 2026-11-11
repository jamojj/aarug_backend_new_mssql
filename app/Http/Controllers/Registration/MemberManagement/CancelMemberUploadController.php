<?php

namespace App\Http\Controllers\Registration\MemberManagement;

use App\Http\Controllers\Controller;
use App\Models\CancelReason;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Registration\MemberAcceptance;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberCancellation;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CancelMemberUploadController extends Controller
{
    public function cancelMembersUpload(Request $request)
    {
        // return $request;
        $today = date("Y-m-d");
        $res = [];
        try {
            //check if member no exist
            if (isset($request->member_no) && isset($request->corp_id) && isset($request->reason)) {
                for ($i = 0; $i < count($request->member_no); $i++) {
                    //check if member exists
                    $member_exists = MemberInfo::where('member_no', $request->member_no[$i])->get();
                    if (count($member_exists) != 0) {
                        //explode to get family no
                        $family_no = explode('-', $request->member_no[$i]);
                        $family_no = $family_no[0];
                        //check whether cancellation reason is legit
                        $cancel_reason = CancelReason::where('code', $request->reason[$i])->get();
                        if (count($cancel_reason) !== 0) {
                            //check whether member is principal or dependant
                            $relation_to_principal = MemberInfo::select(DB::raw("isnull(relation_to_principal, 0) as relation_to_principal"))
                                ->where('member_no', $request->member_no[$i])
                                ->get();
                            $relation_to_principal = $relation_to_principal[0]->relation_to_principal;
                            //principal
                            if ($relation_to_principal == 24) {
                                //fetch all dependants in family to cancel
                                $dependants = MemberInfo::select('member_no')->where('family_no', $family_no)->get();
                                foreach ($dependants as $dependant) {
                                    //check corporate of member
                                    $member_corporate = PrincipalApplicant::select('principal_applicant.corp_id')
                                        ->join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                                        ->where('principal_applicant.corp_id', $request->corp_id[$i])
                                        ->where('member_info.member_no', $dependant->member_no)
                                        ->get();

                                    //check if member belongs to corporate
                                    if (count($member_corporate) !== 0) {
                                        //check if member is accepted
                                        $member = MemberAcceptance::where('status', 1)
                                            ->where('member_no', $dependant->member_no)
                                            ->get();
                                        if (count($member) !== 0) {

                                            //check if member is already cancelled
                                            $ll_cancelled = MemberInfo::where('member_no', $dependant->member_no)->where('cancelled', 1)->get();
                                            if (count($ll_cancelled) === 0) {
                                                //check if member was cancelled earlier the same day
                                                $cancelled_same_day = MemberCancellation::where('member_no', $dependant->member_no)->where('cancelled', 1)->where('date_can', $today)->get();
                                                if (count($cancelled_same_day) === 0) {
                                                    //check if member is retail or corporate
                                                    if ($request->corp_id[$i] == 14 || $request->corp_id[$i] == 639) {
                                                        //get max member
                                                        $max_anniv_member = MemberAnniversary::where('member_no', $dependant->member_no)->max('anniv');
                                                        DB::transaction(function () use ($request, $i, $today, $max_anniv_member, $family_no, $dependant) {
                                                            //do cancellations
                                                            MemberCancellation::create([
                                                                'MEMBER_NO' => $dependant->member_no,
                                                                'CANCELLED' => 1,
                                                                'DATE_CAN' => $today,
                                                                'ANNIV' => $max_anniv_member,
                                                                'REASON'=> $request->reason[$i],
                                                                'user_id' => $request->user,
                                                                'date_entered' => $today,
                                                                'smart_sync' => 1,
                                                                'sync' => 0,
                                                            ]);

                                                            //update / add a cancelled flag into member info
                                                            MemberInfo::where('member_no', $dependant->member_no)
                                                                ->update([
                                                                    'cancelled' => 1
                                                                ]);

                                                            //get updated family size
                                                            $ll_active = MemberInfo::where('family_no', $family_no)
                                                                ->where(DB::raw("isnull(cancelled, 0)"), 0)
                                                                ->count();

                                                            //update principal applicant
                                                            PrincipalApplicant::where([
                                                                'family_no' => $family_no,
                                                            ])->update([
                                                                'family_size' => $ll_active,
                                                                'smart_sync' => 1,
                                                                'sync' => 0,
                                                            ]);
                                                        });
                                                        $dt = [
                                                            'index' => $request->index[$i],
                                                            'res' => "Member cancelled successfully"
                                                        ];
                                                        array_push($res, $dt);
                                                    } else {
                                                        //get max member and corp anniversary
                                                        $max_anniv_member = MemberAnniversary::where('member_no', $dependant->member_no)->max('anniv');
                                                        $max_anniv_corporate = CorpAnniversary::where('corp_id', $request->corp_id[$i])->max('anniv');
                                                        //check if member anniv is same corp anniv
                                                        if ($max_anniv_member == $max_anniv_corporate) {
                                                            DB::transaction(function () use ($request, $i, $today, $max_anniv_member, $family_no, $dependant) {
                                                                //do cancellations
                                                                MemberCancellation::create([
                                                                    'MEMBER_NO' => $dependant->member_no,
                                                                    'CANCELLED' => 1,
                                                                    'DATE_CAN' => $today,
                                                                    'ANNIV' => $max_anniv_member,
                                                                    'REASON' => $request->reason[$i],
                                                                    'user_id' => $request->user,
                                                                    'date_entered' => $today,
                                                                    'smart_sync' => 1,
                                                                    'sync' => 0,
                                                                ]);

                                                                //update / add a cancelled flag into member info
                                                                MemberInfo::where('member_no', $dependant->member_no)
                                                                    ->update([
                                                                        'cancelled' => 1
                                                                    ]);

                                                                //get updated family size
                                                                $ll_active = MemberInfo::where('family_no', $family_no)
                                                                    ->where(DB::raw("isnull(cancelled, 0)"), 0)
                                                                    ->count();

                                                                //update principal applicant
                                                                PrincipalApplicant::where([
                                                                    'family_no' => $family_no,
                                                                ])->update([
                                                                    'family_size' => $ll_active,
                                                                    'smart_sync' => 1,
                                                                    'sync' => 0,
                                                                ]);
                                                            });
                                                            $dt = [
                                                                'index' => $request->index[$i],
                                                                'res' => "Member cancelled successfully"
                                                            ];
                                                            array_push($res, $dt);
                                                        } else {
                                                            $dt = [
                                                                'index' => $request->index[$i],
                                                                'res' => "Notice, Member is not in current anniversary of corporate!"
                                                            ];
                                                            array_push($res, $dt);
                                                        }
                                                    }
                                                } else {
                                                    $dt = [
                                                        'index' => $request->index[$i],
                                                        'res' => "Notice, Member cannot be cancelled twice the same day!"
                                                    ];
                                                    array_push($res, $dt);
                                                }
                                            } else {
                                                $dt = [
                                                    'index' => $request->index[$i],
                                                    'res' => "Notice, Member is already cancelled!"
                                                ];
                                                array_push($res, $dt);
                                            }
                                        } else {
                                            $dt = [
                                                'index' => $request->index[$i],
                                                'res' => "Notice, Member is not accepted!"
                                            ];
                                            array_push($res, $dt);
                                        }
                                    } else {
                                        $dt = [
                                            'index' => $request->index[$i],
                                            'res' => "Notice, Member does not belong to corporate!"
                                        ];
                                        array_push($res, $dt);
                                    }
                                }
                                //dependant
                            } else if ($relation_to_principal == 25) {
                                //check corporate of member
                                $member_corporate = PrincipalApplicant::select('principal_applicant.corp_id')
                                    ->join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                                    ->where('principal_applicant.corp_id', $request->corp_id[$i])
                                    ->where('member_info.member_no', $request->member_no[$i])
                                    ->get();

                                //check if member belongs to corporate
                                if (count($member_corporate) !== 0) {
                                    //check if member is accepted
                                    $member = MemberAcceptance::where('status', 1)
                                        ->where('member_no', $request->member_no[$i])
                                        ->get();
                                    if (count($member) !== 0) {

                                        //check if member is already cancelled
                                        $ll_cancelled = MemberInfo::where('member_no', $request->member_no[$i])->where('cancelled', 1)->get();
                                        if (count($ll_cancelled) === 0) {
                                            //check if member was cancelled earlier the same day
                                            $cancelled_same_day = MemberCancellation::where('member_no', $request->member_no[$i])->where('cancelled', 1)->where('date_can', $today)->get();
                                            if (count($cancelled_same_day) === 0) {
                                                //check if member is retail or corporate
                                                if ($request->corp_id[$i] == 14 || $request->corp_id[$i] == 639) {
                                                    //get max member
                                                    $max_anniv_member = MemberAnniversary::where('member_no', $request->member_no[$i])->max('anniv');
                                                    DB::transaction(function () use ($request, $today, $i, $max_anniv_member, $family_no) {
                                                        //do cancellations
                                                        MemberCancellation::create([
                                                            'MEMBER_NO' => $request->member_no[$i],
                                                            'CANCELLED' => 1,
                                                            'DATE_CAN' => $today,
                                                            'ANNIV' => $max_anniv_member,
                                                            'REASON' => $request->reason[$i],
                                                            'user_id' => $request->user,
                                                            'date_entered' => $today,
                                                            'smart_sync' => 1,
                                                            'sync' => 0,
                                                        ]);

                                                        //update / add a cancelled flag into member info
                                                        MemberInfo::where('member_no', $request->member_no[$i])
                                                            ->update([
                                                                'cancelled' => 1
                                                            ]);

                                                        //get updated family size
                                                        $ll_active = MemberInfo::where('family_no', $family_no)
                                                            ->where(DB::raw("isnull(cancelled, 0)"), 0)
                                                            ->count('member_no');

                                                        //update principal applicant
                                                        PrincipalApplicant::where([
                                                            'family_no' => $family_no,
                                                        ])->update([
                                                            'family_size' => $ll_active,
                                                            'smart_sync' => 1,
                                                            'sync' => 0,
                                                        ]);
                                                    });
                                                    $dt = [
                                                        'index' => $request->index[$i],
                                                        'res' => "Member cancelled successfully"
                                                    ];
                                                    array_push($res, $dt);
                                                } else {
                                                    //get max member and corp anniversary
                                                    $max_anniv_member = MemberAnniversary::where('member_no', $request->member_no[$i])->max('anniv');
                                                    $max_anniv_corporate = CorpAnniversary::where('corp_id', $request->corp_id[$i])->max('anniv');
                                                    //check if member anniv is same corp anniv
                                                    if ($max_anniv_member == $max_anniv_corporate) {
                                                        DB::transaction(function () use ($request, $today, $i, $max_anniv_member, $family_no) {
                                                            //do cancellations
                                                            MemberCancellation::create([
                                                                'MEMBER_NO' => $request->member_no[$i],
                                                                'CANCELLED' => 1,
                                                                'DATE_CAN' => $today,
                                                                'ANNIV' => $max_anniv_member,
                                                                'REASON' => $request->reason[$i],
                                                                'user_id' => $request->user,
                                                                'date_entered' => $today,
                                                                'smart_sync' => 1,
                                                                'sync' => 0,
                                                            ]);

                                                            //update / add a cancelled flag into member info
                                                            MemberInfo::where('member_no', $request->member_no[$i])
                                                                ->update([
                                                                    'cancelled' => 1
                                                                ]);

                                                            //get updated family size
                                                            $ll_active = MemberInfo::where('family_no', $family_no)
                                                                ->where(DB::raw("isnull(cancelled, 0)"), 0)
                                                                ->count('member_no');

                                                            //update principal applicant
                                                            PrincipalApplicant::where([
                                                                'family_no' => $family_no,
                                                            ])->update([
                                                                'family_size' => $ll_active,
                                                                'smart_sync' => 1,
                                                                'sync' => 0,
                                                            ]);
                                                        });
                                                        $dt = [
                                                            'index' => $request->index[$i],
                                                            'res' => "Member cancelled successfully"
                                                        ];
                                                        array_push($res, $dt);
                                                    } else {
                                                        $dt = [
                                                            'index' => $request->index[$i],
                                                            'res' => "Notice, Member is not in current anniversary of corporate!"
                                                        ];
                                                        array_push($res, $dt);
                                                    }
                                                }
                                            } else {
                                                $dt = [
                                                    'index' => $request->index[$i],
                                                    'res' => "Notice, Member cannot be cancelled twice the same day!"
                                                ];
                                                array_push($res, $dt);
                                            }
                                        } else {
                                            $dt = [
                                                'index' => $request->index[$i],
                                                'res' => "Notice, Member is already cancelled!"
                                            ];
                                            array_push($res, $dt);
                                        }
                                    } else {
                                        $dt = [
                                            'index' => $request->index[$i],
                                            'res' => "Notice, Member is not accepted!"
                                        ];
                                        array_push($res, $dt);
                                    }
                                } else {
                                    $dt = [
                                        'index' => $request->index[$i],
                                        'res' => "Notice, Member does not belong to corporate!"
                                    ];
                                    array_push($res, $dt);
                                }
                            } else {
                                $dt = [
                                    'index' => $request->index[$i],
                                    'res' => "Notice, Member does not have relation to principal!"
                                ];
                                array_push($res, $dt);
                            }
                        } else {
                            $dt = [
                                'index' => $request->index[$i],
                                'res' => "Notice, cancellation reason does not exist!"
                            ];
                            array_push($res, $dt);
                        }
                    } else {
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => "Notice, Member does not exist!"
                        ];
                        array_push($res, $dt);
                    }
                }
            }
        } catch (Exception $e) {
            $dt = [
                'index' => $request->index[$i],
                'res' => "Error " . $e->getMessage()
            ];
            array_push($res, $dt);
        }

        return $res;
    }
}
