<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Registration\MemberAcceptance;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberCancellation;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberApprovalController extends Controller
{

    public function fetchMembersApprovalOverall()
    {
        ini_set('max_execution_time', '0');
        $new = [];
        $rows = MemberAcceptance::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_anniversary.anniv,0)) as anniv"),
            'corporate.corporate',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
            'member_acceptance.status_after_approval'
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_acceptance.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->orderBy('member_info.member_no')
            ->groupBy([
                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                'dob',
                'corporate.corporate',
                'member_info.member_no',
                'member_info.family_no',
                'member_info.relation_to_principal',
                'member_info.family_title',
                't_gender.gender',
                'member_acceptance.status_after_approval'
            ])
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->where(DB::raw("isnull(member_acceptance.status, 0)"), 4)
            ->get();

        foreach ($rows as $row) {

            $max_anniv = MemberAnniversary::where('member_no', $row->member_no)->max('anniv');
            $member_cover = MemberAnniversary::select(
                DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                'anniv',
                'health_plan',
                't_product_name.product_name'
            )
                ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                ->where('anniv', $max_anniv)
                ->where('member_no', $row->member_no)
                ->get();

            $dt = [
                'principal_name' => $row->principal_name,
                'member_name' => $row->member_name,
                'corporate' => $row->corporate,
                'family_no' => $row->family_no,
                'member_no' => $row->member_no,
                'dob' => $row->dob,
                'relation_to_principal' => $row->relation_to_principal,
                'family_title' => $row->family_title,
                'gender' => $row->gender,
                'category' => $member_cover[0]->health_plan,
                'product_name' => $member_cover[0]->product_name,
                'start_date' => $member_cover[0]->start_date,
                'end_date' => $member_cover[0]->end_date,
                'renewal_date' => $member_cover[0]->renewal_date,
                'anniv' => $member_cover[0]->anniv,
                'status_after_approval' => $row->status_after_approval
            ];

            array_push($new, $dt);
        }
        return $new;
    }

    public function fetchMembersApprovalCorporate(Request $request)
    {
        ini_set('max_execution_time', '0');
        $new = [];
        $renew = [];
        $cancel_reinstate = [];
        //new members without approval
        $rows = MemberAnniversary::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_anniversary.anniv,0)) as anniv"),
            'corporate.corporate',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->orderBy('member_info.member_no')
            ->groupBy(['member_name', 'principal_name', 'dob', 'corporate.corporate', 'member_info.member_no', 'member_info.family_no', 'member_info.relation_to_principal', 'member_info.family_title', 't_gender.gender'])
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->where('principal_applicant.corp_id', $request->corp_id)
            ->get();

        foreach ($rows as $row) {
            //check new member
            // if ($row->anniv == 1) {
            $sync = MemberAnniversary::select('sync')->where('anniv', $row->anniv)->where('member_no', $row->member_no)->get();
            $sync =  count($sync) !== 0 ? $sync[0]->sync : 0;

            if ($sync == '5') {
                $max_anniv = MemberAnniversary::where('member_no', $row->member_no)->max('anniv');
                $member_cover = MemberAnniversary::select(
                    DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                    DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                    'anniv',
                    'health_plan',
                    't_product_name.product_name'
                )
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                    ->where('anniv', $max_anniv)
                    ->where('member_no', $row->member_no)
                    ->get();

                $dt = [
                    'principal_name' => $row->principal_name,
                    'member_name' => $row->member_name,
                    'corporate' => $row->corporate,
                    'family_no' => $row->family_no,
                    'member_no' => $row->member_no,
                    'dob' => $row->dob,
                    'relation_to_principal' => $row->relation_to_principal,
                    'family_title' => $row->family_title,
                    'gender' => $row->gender,
                    'category' => $member_cover[0]->health_plan,
                    'product_name' => $member_cover[0]->product_name,
                    'start_date' => $member_cover[0]->start_date,
                    'end_date' => $member_cover[0]->end_date,
                    'renewal_date' => $member_cover[0]->renewal_date,
                    'anniv' => $member_cover[0]->anniv,
                ];

                array_push($new, $dt);
            }
            // }
        }


        //renew members approval
        $rows = MemberAnniversary::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_anniversary.anniv,0)) as anniv"),
            'corporate.corporate',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->where('principal_applicant.corp_id', $request->corp_id)
            ->groupBy(['member_name', 'principal_name', 'dob', 'corporate.corporate', 'member_info.member_no', 'member_info.family_no', 'member_info.relation_to_principal', 'member_info.family_title', 't_gender.gender'])
            ->orderBy('member_info.member_no')
            ->get();

        foreach ($rows as $row) {
            $corp_anniv = CorpAnniversary::select(DB::raw("max(anniv) as anniv"))->where('corp_id', $request->corp_id)->get();
            $corp_anniv = count($corp_anniv) != 0 ? $corp_anniv[0]->anniv : 0;
            if ($corp_anniv > $row->anniv) {
                if ($row->anniv > 1) {
                    $sync = MemberAnniversary::select('sync')->where('anniv', $row->anniv)->where('member_no', $row->member_no)->get();
                    $sync =  count($sync) !== 0 ? $sync[0]->sync : 0;
                    if ($sync == '5') {
                        $member_cover = MemberAnniversary::select(
                            DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                            DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                            DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                            'anniv',
                            'health_plan',
                            't_product_name.product_name'
                        )
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->where('anniv', $row->anniv)
                            ->where('member_no', $row->member_no)
                            ->get();

                        $dt = [
                            'principal_name' => $row->principal_name,
                            'member_name' => $row->member_name,
                            'corporate' => $row->corporate,
                            'family_no' => $row->family_no,
                            'member_no' => $row->member_no,
                            'dob' => $row->dob,
                            'relation_to_principal' => $row->relation_to_principal,
                            'family_title' => $row->family_title,
                            'gender' => $row->gender,
                            'category' => $member_cover[0]->health_plan,
                            'product_name' => $member_cover[0]->product_name,
                            'start_date' => $member_cover[0]->start_date,
                            'end_date' => $member_cover[0]->end_date,
                            'renewal_date' => $member_cover[0]->renewal_date,
                            'anniv' => $row->anniv,
                        ];

                        array_push($renew, $dt);
                    }
                }
            }
        }
        //cancel reinstate members approval
        $rows = MemberCancellation::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_cancellation.date_entered,'')) as date_entered"),
            'corporate.corporate',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_cancellation.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where('principal_applicant.corp_id', $request->corp_id)
            ->groupBy(['member_name', 'principal_name', 'dob', 'corporate.corporate', 'member_info.member_no', 'member_info.family_no', 'member_info.relation_to_principal', 'member_info.family_title', 't_gender.gender'])
            ->orderBy('member_info.member_no')
            ->get();

        foreach ($rows as $row) {
            $can = MemberInfo::select(DB::raw("isnull(cancelled, 0) as cancelled"))->where('member_no', $row->member_no)->get();
            $can =  count($can) !== 0 ? $can[0]->cancelled : '';
            if ($can !== '') {

                $sync = MemberCancellation::select('sync')->where('cancelled', $can)->where('date_entered', $row->date_entered)->where('member_no', $row->member_no)->get();
                $sync =  count($sync) !== 0 ? $sync[0]->sync : 0;
                if ($sync == '5') {
                    $max_anniv = MemberAnniversary::where('member_no', $row->member_no)->max('anniv');
                    $member_cover = MemberAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                        DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                        'anniv',
                        'health_plan',
                        't_product_name.product_name'
                    )
                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                        ->where('anniv', $max_anniv)
                        ->where('member_no', $row->member_no)
                        ->get();

                    $mem_cancellation = MemberCancellation::select(
                        DB::raw("case when member_cancellation.cancelled = 1 then 'CANCELLED' else 'ACTIVE' END as status"),
                        'member_cancellation.date_can',
                        'cancel_reason.cancel_reason',
                        'member_cancellation.cancelled'
                    )
                        ->leftJoin('cancel_reason', 'cancel_reason.code', '=', 'member_cancellation.reason')
                        ->where('member_cancellation.cancelled', $can)
                        ->where('member_cancellation.date_entered', $row->date_entered)
                        ->where('member_cancellation.member_no', $row->member_no)
                        ->get();

                    $dt = [
                        'principal_name' => $row->principal_name,
                        'member_name' => $row->member_name,
                        'corporate' => $row->corporate,
                        'family_no' => $row->family_no,
                        'member_no' => $row->member_no,
                        'dob' => $row->dob,
                        'relation_to_principal' => $row->relation_to_principal,
                        'family_title' => $row->family_title,
                        'gender' => $row->gender,
                        'date_entered' => $row->date_entered,
                        'category' => $member_cover[0]->health_plan,
                        'product_name' => $member_cover[0]->product_name,
                        'start_date' => $member_cover[0]->start_date,
                        'end_date' => $member_cover[0]->end_date,
                        'renewal_date' => $member_cover[0]->renewal_date,
                        'anniv' => $member_cover[0]->anniv,
                        'status' => $mem_cancellation[0]->status,
                        'cancel_reason' => $mem_cancellation[0]->cancel_reason,
                        'cancelled' => $mem_cancellation[0]->cancelled,
                        'date_cancelled' => $mem_cancellation[0]->date_can,
                    ];

                    array_push($cancel_reinstate, $dt);
                }
            }
        }


        return [
            'new' => $new,
            'renew' => $renew,
            'cancel' => $cancel_reinstate,
        ];
    }

    public function fetchMembersApprovalRetail(Request $request)
    {
        ini_set('max_execution_time', '0');
        $new = [];
        $renew = [];
        $cancel_reinstate = [];
        //new members without approval
        $rows = MemberAnniversary::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_anniversary.anniv,0)) as anniv"),
            'corporate.corporate',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->orderBy('member_info.member_no')
            ->groupBy(['member_name', 'principal_name', 'dob', 'corporate.corporate', 'member_info.member_no', 'member_info.family_no', 'member_info.relation_to_principal', 'member_info.family_title', 't_gender.gender'])
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->where('principal_applicant.family_no', $request->fam_no)
            ->get();

        foreach ($rows as $row) {
            //check new member
            // if ($row->anniv == 1) {
            $sync = MemberAnniversary::select('sync')->where('anniv', $row->anniv)->where('member_no', $row->member_no)->get();
            $sync =  count($sync) !== 0 ? $sync[0]->sync : 0;

            if ($sync == '5') {
                $max_anniv = MemberAnniversary::where('member_no', $row->member_no)->max('anniv');
                $member_cover = MemberAnniversary::select(
                    DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                    DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                    'anniv',
                    'health_plan',
                    't_product_name.product_name'
                )
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                    ->where('anniv', $max_anniv)
                    ->where('member_no', $row->member_no)
                    ->get();

                $dt = [
                    'principal_name' => $row->principal_name,
                    'member_name' => $row->member_name,
                    'corporate' => $row->corporate,
                    'family_no' => $row->family_no,
                    'member_no' => $row->member_no,
                    'dob' => $row->dob,
                    'relation_to_principal' => $row->relation_to_principal,
                    'family_title' => $row->family_title,
                    'gender' => $row->gender,
                    'category' => $member_cover[0]->health_plan,
                    'product_name' => $member_cover[0]->product_name,
                    'start_date' => $member_cover[0]->start_date,
                    'end_date' => $member_cover[0]->end_date,
                    'renewal_date' => $member_cover[0]->renewal_date,
                    'anniv' => $member_cover[0]->anniv,
                ];

                array_push($new, $dt);
                // }
            }
        }


        //renew members approval
        $rows = MemberAnniversary::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_anniversary.anniv,0)) as anniv"),
            'corporate.corporate',
            'corporate.corp_id',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->where('principal_applicant.family_no', $request->fam_no)
            ->groupBy(['member_name', 'principal_name', 'dob', 'corporate.corporate', 'corporate.corp_id', 'member_info.member_no', 'member_info.family_no', 'member_info.relation_to_principal', 'member_info.family_title', 't_gender.gender'])
            ->orderBy('member_info.member_no')
            ->get();

        foreach ($rows as $row) {
            $corp_anniv = CorpAnniversary::select(DB::raw("max(anniv) as anniv"))->where('corp_id', $row->corp_id)->get();
            $corp_anniv = count($corp_anniv) != 0 ? $corp_anniv[0]->anniv : 0;
            if ($corp_anniv > $row->anniv) {
                if ($row->anniv > 1) {
                    $sync = MemberAnniversary::select('sync')->where('anniv', $row->anniv)->where('member_no', $row->member_no)->get();
                    $sync =  count($sync) !== 0 ? $sync[0]->sync : 0;
                    if ($sync == '5') {
                        $member_cover = MemberAnniversary::select(
                            DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                            DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                            DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                            'anniv',
                            'health_plan',
                            't_product_name.product_name'
                        )
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->where('anniv', $row->anniv)
                            ->where('member_no', $row->member_no)
                            ->get();

                        $dt = [
                            'principal_name' => $row->principal_name,
                            'member_name' => $row->member_name,
                            'corporate' => $row->corporate,
                            'family_no' => $row->family_no,
                            'member_no' => $row->member_no,
                            'dob' => $row->dob,
                            'relation_to_principal' => $row->relation_to_principal,
                            'family_title' => $row->family_title,
                            'gender' => $row->gender,
                            'category' => $member_cover[0]->health_plan,
                            'product_name' => $member_cover[0]->product_name,
                            'start_date' => $member_cover[0]->start_date,
                            'end_date' => $member_cover[0]->end_date,
                            'renewal_date' => $member_cover[0]->renewal_date,
                            'anniv' => $row->anniv,
                        ];

                        array_push($renew, $dt);
                    }
                }
            }
        }
        //cancel reinstate members approval
        $rows = MemberCancellation::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_cancellation.date_entered,'')) as date_entered"),
            'corporate.corporate',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_cancellation.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where('principal_applicant.family_no', $request->fam_no)
            ->groupBy(['member_name', 'principal_name', 'dob', 'corporate.corporate', 'member_info.member_no', 'member_info.family_no', 'member_info.relation_to_principal', 'member_info.family_title', 't_gender.gender'])
            ->orderBy('member_info.member_no')
            ->get();

        foreach ($rows as $row) {
            $can = MemberInfo::select(DB::raw("isnull(cancelled, 0) as cancelled"))->where('member_no', $row->member_no)->get();
            $can =  count($can) !== 0 ? $can[0]->cancelled : '';
            if ($can !== '') {

                $sync = MemberCancellation::select('sync')->where('cancelled', $can)->where('date_entered', $row->date_entered)->where('member_no', $row->member_no)->get();
                $sync =  count($sync) !== 0 ? $sync[0]->sync : 0;
                if ($sync == '5') {
                    $max_anniv = MemberAnniversary::where('member_no', $row->member_no)->max('anniv');
                    $member_cover = MemberAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                        DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                        'anniv',
                        'health_plan',
                        't_product_name.product_name'
                    )
                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                        ->where('anniv', $max_anniv)
                        ->where('member_no', $row->member_no)
                        ->get();

                    $mem_cancellation = MemberCancellation::select(
                        DB::raw("case when member_cancellation.cancelled = 1 then 'CANCELLED' else 'ACTIVE' END as status"),
                        'member_cancellation.date_can',
                        'cancel_reason.cancel_reason',
                        'member_cancellation.cancelled'
                    )
                        ->leftJoin('cancel_reason', 'cancel_reason.code', '=', 'member_cancellation.reason')
                        ->where('member_cancellation.cancelled', $can)
                        ->where('member_cancellation.date_entered', $row->date_entered)
                        ->where('member_cancellation.member_no', $row->member_no)
                        ->get();

                    $dt = [
                        'principal_name' => $row->principal_name,
                        'member_name' => $row->member_name,
                        'corporate' => $row->corporate,
                        'family_no' => $row->family_no,
                        'member_no' => $row->member_no,
                        'dob' => $row->dob,
                        'relation_to_principal' => $row->relation_to_principal,
                        'family_title' => $row->family_title,
                        'gender' => $row->gender,
                        'date_entered' => $row->date_entered,
                        'category' => $member_cover[0]->health_plan,
                        'product_name' => $member_cover[0]->product_name,
                        'start_date' => $member_cover[0]->start_date,
                        'end_date' => $member_cover[0]->end_date,
                        'renewal_date' => $member_cover[0]->renewal_date,
                        'anniv' => $member_cover[0]->anniv,
                        'status' => $mem_cancellation[0]->status,
                        'cancel_reason' => $mem_cancellation[0]->cancel_reason,
                        'cancelled' => $mem_cancellation[0]->cancelled,
                        'date_cancelled' => $mem_cancellation[0]->date_can,
                    ];

                    array_push($cancel_reinstate, $dt);
                }
            }
        }

        return [
            'new' => $new,
            'renew' => $renew,
            'cancel' => $cancel_reinstate,
        ];
    }
    public function fetchMembersApprovalAll()
    {
        ini_set('max_execution_time', '0');
        $new = [];
        $renew = [];
        $cancel_reinstate = [];
        //new members without approval
        $rows = MemberAnniversary::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_anniversary.anniv,0)) as anniv"),
            'corporate.corporate',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->orderBy('member_info.member_no')
            ->groupBy(['member_name', 'principal_name', 'dob', 'corporate.corporate', 'member_info.member_no', 'member_info.family_no', 'member_info.relation_to_principal', 'member_info.family_title', 't_gender.gender'])
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->get();

        foreach ($rows as $row) {
            //check new member
            // if ($row->anniv == 1) {
            $sync = MemberAnniversary::select('sync')->where('anniv', $row->anniv)->where('member_no', $row->member_no)->get();
            $sync =  count($sync) !== 0 ? $sync[0]->sync : 0;

            if ($sync == '5') {
                $max_anniv = MemberAnniversary::where('member_no', $row->member_no)->max('anniv');
                $member_cover = MemberAnniversary::select(
                    DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                    DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                    'anniv',
                    'health_plan',
                    't_product_name.product_name'
                )
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                    ->where('anniv', $max_anniv)
                    ->where('member_no', $row->member_no)
                    ->get();

                $dt = [
                    'principal_name' => $row->principal_name,
                    'member_name' => $row->member_name,
                    'corporate' => $row->corporate,
                    'family_no' => $row->family_no,
                    'member_no' => $row->member_no,
                    'dob' => $row->dob,
                    'relation_to_principal' => $row->relation_to_principal,
                    'family_title' => $row->family_title,
                    'gender' => $row->gender,
                    'category' => $member_cover[0]->health_plan,
                    'product_name' => $member_cover[0]->product_name,
                    'start_date' => $member_cover[0]->start_date,
                    'end_date' => $member_cover[0]->end_date,
                    'renewal_date' => $member_cover[0]->renewal_date,
                    'anniv' => $member_cover[0]->anniv,
                ];

                array_push($new, $dt);
                // }
            }
        }


        //renew members approval
        $rows = MemberAnniversary::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_anniversary.anniv,0)) as anniv"),
            'corporate.corporate',
            'corporate.corp_id',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->groupBy(['member_name', 'principal_name', 'dob', 'corporate.corporate', 'corporate.corp_id', 'member_info.member_no', 'member_info.family_no', 'member_info.relation_to_principal', 'member_info.family_title', 't_gender.gender'])
            ->orderBy('member_info.member_no')
            ->get();

        foreach ($rows as $row) {
            $corp_anniv = CorpAnniversary::select(DB::raw("max(anniv) as anniv"))->where('corp_id', $row->corp_id)->get();
            $corp_anniv = count($corp_anniv) != 0 ? $corp_anniv[0]->anniv : 0;
            if ($corp_anniv > $row->anniv) {
                if ($row->anniv > 1) {
                    $sync = MemberAnniversary::select('sync')->where('anniv', $row->anniv)->where('member_no', $row->member_no)->get();
                    $sync =  count($sync) !== 0 ? $sync[0]->sync : 0;
                    if ($sync == '5') {
                        $member_cover = MemberAnniversary::select(
                            DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                            DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                            DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                            'anniv',
                            'health_plan',
                            't_product_name.product_name'
                        )
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->where('anniv', $row->anniv)
                            ->where('member_no', $row->member_no)
                            ->get();

                        $dt = [
                            'principal_name' => $row->principal_name,
                            'member_name' => $row->member_name,
                            'corporate' => $row->corporate,
                            'family_no' => $row->family_no,
                            'member_no' => $row->member_no,
                            'dob' => $row->dob,
                            'relation_to_principal' => $row->relation_to_principal,
                            'family_title' => $row->family_title,
                            'gender' => $row->gender,
                            'category' => $member_cover[0]->health_plan,
                            'product_name' => $member_cover[0]->product_name,
                            'start_date' => $member_cover[0]->start_date,
                            'end_date' => $member_cover[0]->end_date,
                            'renewal_date' => $member_cover[0]->renewal_date,
                            'anniv' => $row->anniv,
                        ];

                        array_push($renew, $dt);
                    }
                }
            }
        }
        //cancel reinstate members approval
        $rows = MemberCancellation::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("max(isnull(member_cancellation.date_entered,'1900-01-01')) as date_entered"),
            'corporate.corporate',
            'member_info.member_no',
            'member_info.family_no',
            'member_info.relation_to_principal',
            'member_info.family_title',
            't_gender.gender',
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'member_cancellation.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->groupBy(['member_name', 'principal_name', 'dob', 'corporate.corporate', 'member_info.member_no', 'member_info.family_no', 'member_info.relation_to_principal', 'member_info.family_title', 't_gender.gender'])
            ->orderBy('member_info.member_no')
            ->get();

        foreach ($rows as $row) {
            $can = MemberInfo::select(DB::raw("isnull(cancelled, 0) as cancelled"))->where('member_no', $row->member_no)->get();
            $can =  count($can) !== 0 ? $can[0]->cancelled : '';
            if ($can !== '') {

                $sync = MemberCancellation::select('sync')->where('cancelled', $can)->where('date_entered', $row->date_entered)->where('member_no', $row->member_no)->get();
                $sync =  count($sync) !== 0 ? $sync[0]->sync : 0;
                if ($sync == '5') {
                    $max_anniv = MemberAnniversary::where('member_no', $row->member_no)->max('anniv');
                    $member_cover = MemberAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                        DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                        'anniv',
                        'health_plan',
                        't_product_name.product_name'
                    )
                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                        ->where('anniv', $max_anniv)
                        ->where('member_no', $row->member_no)
                        ->get();

                    $mem_cancellation = MemberCancellation::select(
                        DB::raw("case when member_cancellation.cancelled = 1 then 'CANCELLED' else 'ACTIVE' END as status"),
                        'member_cancellation.date_can',
                        'cancel_reason.cancel_reason',
                        'member_cancellation.cancelled'
                    )
                        ->leftJoin('cancel_reason', 'cancel_reason.code', '=', 'member_cancellation.reason')
                        ->where('member_cancellation.cancelled', $can)
                        ->where('member_cancellation.date_entered', $row->date_entered)
                        ->where('member_cancellation.member_no', $row->member_no)
                        ->get();

                    $dt = [
                        'principal_name' => $row->principal_name,
                        'member_name' => $row->member_name,
                        'corporate' => $row->corporate,
                        'family_no' => $row->family_no,
                        'member_no' => $row->member_no,
                        'dob' => $row->dob,
                        'relation_to_principal' => $row->relation_to_principal,
                        'family_title' => $row->family_title,
                        'gender' => $row->gender,
                        'date_entered' => $row->date_entered,
                        'category' => $member_cover[0]->health_plan,
                        'product_name' => $member_cover[0]->product_name,
                        'start_date' => $member_cover[0]->start_date,
                        'end_date' => $member_cover[0]->end_date,
                        'renewal_date' => $member_cover[0]->renewal_date,
                        'anniv' => $member_cover[0]->anniv,
                        'status' => $mem_cancellation[0]->status,
                        'cancel_reason' => $mem_cancellation[0]->cancel_reason,
                        'cancelled' => $mem_cancellation[0]->cancelled,
                        'date_cancelled' => $mem_cancellation[0]->date_can,
                    ];

                    array_push($cancel_reinstate, $dt);
                }
            }
        }


        return [
            'new' => $new,
            'renew' => $renew,
            'cancel' => $cancel_reinstate,
        ];
    }

    public function saveMemberApproval(Request $request)
    {
        ini_set('max_execution_time', '0');
        // return $request;
        try {
            DB::transaction(function () use ($request) {
                //new members approval
                if (isset($request->member_no_new)) {
                    for ($i = 0; $i < count($request->member_no_new); $i++) {
                        if ($request->sync_new[$i] === '1') {
                            //UPDATE PRINCIPAL
                            PrincipalApplicant::where('family_no', $request->family_no_new[$i])
                                ->update(['sync' => 0]);
                            //UPDATE MEMBER ANNIVERSARY
                            MemberAnniversary::where([
                                'member_no' => $request->member_no_new[$i],
                                'anniv' => $request->anniv_new[$i],
                            ])
                                ->update(['sync' => 0]);
                            //UPDATE MEMBER BENEFITS
                            MemberBenefits::where([
                                'member_no' => $request->member_no_new[$i],
                                'anniv' => $request->anniv_new[$i],
                            ])
                                ->update(['sync' => 0]);
                        }
                    }
                }
                //renew members approval
                if (isset($request->member_no_renew)) {
                    for ($i = 0; $i < count($request->member_no_renew); $i++) {
                        if ($request->sync_renew[$i] === '1') {
                            //UPDATE MEMBER ANNIVERSARY
                            MemberAnniversary::where([
                                'member_no' => $request->member_no_renew[$i],
                                'anniv' => $request->anniv_renew[$i],
                            ])
                                ->update(['sync' => 0]);
                            //UPDATE MEMBER BENEFITS
                            MemberBenefits::where([
                                'member_no' => $request->member_no_renew[$i],
                                'anniv' => $request->anniv_renew[$i],
                            ])
                                ->update(['sync' => 0]);
                        }
                    }
                }
                //cancel reinstate approval
                if (isset($request->member_no_cancel_reinstate)) {
                    for ($i = 0; $i < count($request->member_no_cancel_reinstate); $i++) {
                        if ($request->sync_cancel_reinstate[$i] === '1') {
                            //UPDATE MEMBER CANCELLATION
                            MemberCancellation::where([
                                'member_no' => $request->member_no_cancel_reinstate[$i],
                                'cancelled' => $request->cancelled_cancel_reinstate[$i],
                                'date_can' => $request->date_can_cancel_reinstate[$i],
                            ])
                                ->update(['sync' => 0]);
                            //UPDATE MEMBER ANNIVERSARY
                            MemberAnniversary::where([
                                'member_no' => $request->member_no_cancel_reinstate[$i],
                                'anniv' => $request->anniv_cancel_reinstate[$i],
                            ])
                                ->update(['sync' => 0]);
                            //UPDATE PRINCIPAL
                            PrincipalApplicant::where('family_no', $request->family_no_cancel_reinstate[$i])
                                ->update(['sync' => 0]);
                        }
                    }
                }
            });
            return ['Members approved successfully.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function saveMemberApprovalOverall(Request $request)
    {
        // return $request;
        ini_set('max_execution_time', '0');
        try {
            DB::transaction(function () use ($request) {
                //new members approval
                if (isset($request->member_no_new)) {
                    for ($i = 0; $i < count($request->member_no_new); $i++) {
                        if ($request->sync_new[$i] === '1') {
                            //UPDATE MEMBER ACCEPTANCE
                            MemberAcceptance::where([
                                'member_no' => $request->member_no_new[$i],
                            ])
                                ->update(['status' => $request->status_after_approval[$i]]);
                        }
                    }
                }
            });
            return ['Members approved successfully.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
