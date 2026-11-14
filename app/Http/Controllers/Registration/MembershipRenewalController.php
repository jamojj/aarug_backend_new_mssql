<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\BenefitSharing;
use App\Models\Category;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpGroups;
use App\Models\ProductName;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use http\Env\Response;
use Illuminate\Http\Request;
use Mockery\Matcher\Closure;
use Symfony\Component\Console\Input\Input;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;
use function Symfony\Component\String\s;
use DateTime;
use DatePeriod;
use DateInterval;
use Exception;

class MembershipRenewalController extends Controller
{

    //validate imported data. push errors
    public function validateMembershipRenewalImportedData(Request $request)
    {

        // return $request;
        // ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', 0);
        //return (int)$request->corp_id;

        $response_arr = $errors_arr = $member_info = $member_anniversary_arr = $member_benefits_arr = array();
        $member_no = '';
        //$results = $request->all();

        $max_anniv = CorpAnniversary::select(DB::raw("max(anniv) as anniv"))
            ->where('corp_id', $request->corp_id)
            ->get();
        $max_anniv = (int)$max_anniv[0]['anniv'];

        //check dublicate member nos
        $elementCounts = array_count_values($request->member_no);
        $duplicateElements = array_filter($elementCounts, function ($count) {
            return $count > 1;
        });
        if (!empty($duplicateElements)) {
            return ["Dublicate member(s): " . implode(', ', array_keys($duplicateElements))];
        }

        if (isset($request->member_no)) {
            for ($i = 0; $i < count($request->member_no); $i++) {
                //check if member exists
                $ls_mem_no_count = MemberInfo::select(DB::raw('count(member_no) as count'))
                    ->where('member_no', $request->member_no[$i])
                    ->get();

                if ($ls_mem_no_count[0]['count'] > 0) {
                    $ls_corp = PrincipalApplicant::select('principal_applicant.corp_id')
                        ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                        ->where('member_info.member_no', $request->member_no[$i])
                        ->get();
                    $ls_corp = (int)$ls_corp[0]['corp_id'];

                    if ($ls_corp == (int)$request->corp_id) {
                        $corp_max_anniv = CorpAnniversary::select(DB::raw("max(isnull(anniv, 0)) as max_anniv"))
                            ->where('corp_id', $request->corp_id)
                            ->get();
                        $corp_max_anniv = $corp_max_anniv[0]['max_anniv'];

                        $member_max_anniv = MemberAnniversary::select(DB::raw("max(isnull(anniv, 0)) as max_anniv"))
                            ->where('member_no', $request->member_no[$i])
                            ->get();
                        $member_max_anniv = $member_max_anniv[0]['max_anniv'];

                        if ($member_max_anniv >= $corp_max_anniv) {
                            array_push($errors_arr, 'MEMBER HAS ALREADY BEEN RENEWED TO CORPORATE ANNIVERSARY');
                        } else {
                            //array_push($response_arr,'SAME CORP_ID');
                            $ll_plan = ProductName::select('code')
                                ->where('product_name', $request->option[$i])
                                ->get();
                            if (count($ll_plan) > 0) {
                                //array_push($response_arr, $ll_plan);
                                $ll_plan_code = (int)$ll_plan[0]['code'];

                                //array_push($response_arr, $ll_plan);
                                $ll_bene_count = CorpGroups::select(DB::raw("count(benefit) as count"))
                                    ->where('product_name', $ll_plan_code)
                                    ->where('category', $request->health_plan[$i])
                                    ->where('anniv', $max_anniv)
                                    ->where('corp_id', $request->corp_id)
                                    ->get();
                                $ll_bene_count = (int)$ll_bene_count[0]['count'];
                                //array_push($response_arr, $ll_bene_count);
                                if ($ll_bene_count > 0) {
                                    $member_details = MemberInfo::select(
                                        DB::raw("CONCAT(member_info.surname,' ', member_info.first_name, ' ', member_info.other_names) as member_name"),
                                        'member_info.family_title as family_title_code',
                                        'family_relation.relation as family_relation',
                                        'member_info.relation_to_principal as relation_to_principal_code',
                                        'family_relation.relation as relation_to_principal',
                                        'member_info.family_no',
                                        'member_info.member_no',
                                    )
                                        ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.family_title')
                                        ->where('member_info.member_no', $request->member_no[$i])
                                        ->get();

                                    foreach ($member_details as $data) {
                                        $data->category = $request->health_plan[$i];
                                        $data->health_option = $request->option[$i];
                                        $data->product_name = $ll_plan_code;
                                        array_push($member_info, $member_details[0]);
                                    }

                                    array_push($errors_arr, null);
                                } else {
                                    array_push($errors_arr, 'WRONG COMBINATION OF HEALTH PLAN AND OPTION');
                                }
                            } else {
                                array_push($errors_arr, 'HEALTH OPTION DOES NOT EXIST');
                            }
                        }
                    } else {
                        array_push($errors_arr, 'MEMBER DOES NOT BELONG TO SELECTED CORPORATE');
                    }
                } else {
                    array_push($errors_arr, 'MEMBER NOT IN SYSTEM');
                }


                $request_data = [
                    'member_no' => $request->member_no[$i],
                    'health_plan' => $request->health_plan[$i],
                    'option' => $request->option[$i],
                    'errors' => $errors_arr[$i],
                ];
                array_push($response_arr, $request_data);
            }
        }

        $member_anniversary = CorpAnniversary::select(
            'corp_anniversary.start_date',
            'corp_anniversary.end_date',
            'corp_anniversary.renewal_date',
            'corp_anniversary.anniv',
            'corp_anniversary.smart_sync',
            'corp_anniversary.sync',
        )
            ->distinct()
            ->where('corp_anniversary.anniv', $max_anniv)
            ->where('corp_anniversary.corp_id', $request->corp_id)
            ->get();


        array_push($member_anniversary_arr, $member_anniversary);

        return [
            'response' => json_encode($response_arr, JSON_INVALID_UTF8_IGNORE),
            'member_info' => json_encode($member_info, JSON_INVALID_UTF8_IGNORE),
            'member_anniversary' => json_encode($member_anniversary_arr, JSON_INVALID_UTF8_IGNORE),
            'errors' => $errors_arr
        ];
    }

    //fetch members from selected corp id
    public function fetchCorporateData($corp_id)
    {
        $data = MemberInfo::select(
            'MEMBER_INFO.family_no',
            'MEMBER_INFO.member_no',
            DB::raw("CONCAT(member_INFO.surname,' ', MEMBER_INFO.first_name, ' ', MEMBER_INFO.other_names) as full_name"),
            'MEMBER_INFO.relation_to_principal',
            'MEMBER_INFO.family_title',
            'PRINCIPAL_APPLICANT.category',
            'PRINCIPAL_APPLICANT.plan'
        )
            ->leftJoin('PRINCIPAL_APPLICANT', 'PRINCIPAL_APPLICANT.family_no', '=', 'MEMBER_INFO.family_no')
            ->where(function ($cancel_status) {
                $cancel_status->where('MEMBER_INFO.cancelled', 0)
                    ->orWhereNull('MEMBER_INFO.cancelled');
            })
            ->where('PRINCIPAL_APPLICANT.corp_id', $corp_id)
            ->get();
        return $data;
    }

    //fetch family details of individual selected
    public function fetchIndividualData($individual_member_no)
    {
        $individual_family_no = explode("-", $individual_member_no);
        $individual_family_no = $individual_family_no[0];
        $max_anniv = MemberAnniversary::select(DB::raw("max(anniv) as anniv"))->where('member_no', $individual_member_no)->get();
        $max_anniv = count($max_anniv) !== 0 ? $max_anniv[0]->anniv : "";
        $end_date = MemberAnniversary::select('end_date')->where('anniv', $max_anniv)->where('member_no', $individual_member_no)->get();
        $end_date = count($end_date) !== 0 ? $end_date[0]->end_date : "";

        // $data = MemberAnniversary::select(
        //     DB::raw('(select relation from family_relation where member_info.family_title = family_relation.code) as family_title'),
        //     DB::raw("CONCAT(member_INFO.surname,' ', MEMBER_INFO.first_name, ' ', MEMBER_INFO.other_names) as full_name"),
        //     'MEMBER_INFO.family_no',
        //     'MEMBER_INFO.member_no',
        //     'MEMBER_INFO.relation_to_principal',
        //     'MEMBER_INFO.family_title as family_title_code',
        //     'MEMBER_ANNIVERSARY.health_plan as category',
        //     'MEMBER_ANNIVERSARY.h_option as product_code',
        //     'T_PRODUCT_NAME.product_name',
        //     'FAMILY_RELATION.relation',
        // )
        //     ->leftJoin('MEMBER_INFO', 'MEMBER_ANNIVERSARY.member_no', '=', 'MEMBER_INFO.member_no')
        //     ->leftJoin('MEMBER_ACCEPTANCE', 'MEMBER_ACCEPTANCE.member_no', '=', 'MEMBER_INFO.member_no')
        //     ->leftJoin('FAMILY_RELATION', 'FAMILY_RELATION.code', '=', 'MEMBER_INFO.relation_to_principal')
        //     ->leftJoin('T_PRODUCT_NAME', 'T_PRODUCT_NAME.code', '=', 'MEMBER_ANNIVERSARY.h_option')
        //     // ->where('member_anniversary.anniv', $max_anniv)
        //     ->whereIn('MEMBER_ACCEPTANCE.status', [0, 1])
        //     ->where(DB::raw("isnull(MEMBER_INFO.cancelled, 0)"), 0)
        //     ->where('MEMBER_INFO.family_no', $individual_family_no)
        //     ->get();

        $anniv_data = MemberAnniversary::select(
            DB::raw('(select relation from family_relation where member_info.family_title = family_relation.code) as family_title'),
            DB::raw("CONCAT(member_INFO.surname,' ', MEMBER_INFO.first_name, ' ', MEMBER_INFO.other_names) as full_name"),
            DB::raw("MAX(member_anniversary.anniv) as max_anniv"),
            'member_anniversary.start_date',
            'member_anniversary.end_date',
            'member_anniversary.renewal_date',
            'MEMBER_INFO.family_no',
            'MEMBER_INFO.member_no',
            'MEMBER_INFO.relation_to_principal',
            'MEMBER_INFO.family_title as family_title_code',
            'MEMBER_ANNIVERSARY.health_plan as category',
            'MEMBER_ANNIVERSARY.h_option as product_code',
            'T_PRODUCT_NAME.product_name',
            'FAMILY_RELATION.relation',
        )
            ->leftJoin('MEMBER_INFO', 'MEMBER_ANNIVERSARY.member_no', '=', 'MEMBER_INFO.member_no')
            ->leftJoin('MEMBER_ACCEPTANCE', 'MEMBER_ACCEPTANCE.member_no', '=', 'MEMBER_INFO.member_no')
            ->leftJoin('FAMILY_RELATION', 'FAMILY_RELATION.code', '=', 'MEMBER_INFO.relation_to_principal')
            ->leftJoin('T_PRODUCT_NAME', 'T_PRODUCT_NAME.code', '=', 'MEMBER_ANNIVERSARY.h_option')
            ->orderBy('MEMBER_INFO.member_no', 'asc')
            ->whereIn('MEMBER_ACCEPTANCE.status', [0, 1])
            ->where(DB::raw("isnull(MEMBER_INFO.cancelled, 0)"), 0)
            ->where('MEMBER_INFO.family_no', $individual_family_no)
            ->groupBy(
                'family_title',
                DB::raw("CONCAT(member_INFO.surname,' ', MEMBER_INFO.first_name, ' ', MEMBER_INFO.other_names)"),
                'MEMBER_INFO.family_no',
                'MEMBER_INFO.member_no',
                'relation_to_principal',
                'MEMBER_INFO.family_title',
                'MEMBER_ANNIVERSARY.health_plan',
                'MEMBER_ANNIVERSARY.h_option',
                'T_PRODUCT_NAME.product_name',
                'FAMILY_RELATION.relation',
                'member_anniversary.start_date',
                'member_anniversary.end_date',
                'member_anniversary.renewal_date'
            )
            ->havingRaw('MAX(member_anniversary.anniv) = (SELECT MAX(anniv) FROM member_anniversary WHERE member_no = MEMBER_INFO.member_no)')
            ->get();


        return ['anniv_data' => json_encode($anniv_data, JSON_INVALID_UTF8_IGNORE), 'max_anniv' => $max_anniv, 'end_date' => $end_date];
    }

    //fetch member benfits for renewal
    public function fetchMemberBenefitsForRenewal(Request $request)
    {
        // return $request;
        ini_set('max_execution_time', 0);
        $member_benefits = [];
        $member_anniversary = [];
        //toggle between corporate and family
        switch ($request->selected_option) {
            //corporate
            case '1':
                $max_anniv = CorpAnniversary::where('corp_id', $request->corp_id)->max('anniv');
                //fetch latest corp anniversary
                $corp_anniversary = CorpAnniversary::select('start_date', 'renewal_date', 'end_date', 'anniv')
                    ->where('corp_id', $request->corp_id)
                    ->where('anniv', $max_anniv)
                    ->get();
                for ($i = 0; $i < count($request->member_no); $i++) {
                    $member_max_anniv = MemberAnniversary::where('member_no', $request->member_no[$i])->max('anniv');

                    $member_anniv_data = MemberAnniversary::select(
                        'member_anniversary.anniv',
                        'member_anniversary.health_plan',
                        'member_anniversary.h_option as h_option_code',
                        't_product_name.product_name',
                        'member_anniversary.smart_sync',
                        'member_anniversary.sync',
                        'member_info.member_no',
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    )
                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
                        ->where('member_anniversary.member_no', $request->member_no[$i])
                        ->where('member_anniversary.anniv', $member_max_anniv)
                        ->get();
                    foreach ($member_anniv_data as $member_anniv_datum) {
                        $product_name = ProductName::where('code', $request->product_name[$i])->pluck('product_name');
                        $product_name = count($product_name) != 0 ? $product_name[0] : '';

                        $member_anniv_datum->product_name = $product_name;
                        $member_anniv_datum->health_plan = $request->category[$i];
                        $member_anniv_datum->start_date = $corp_anniversary[0]['start_date'];
                        $member_anniv_datum->end_date = $corp_anniversary[0]['end_date'];
                        $member_anniv_datum->renewal_date = $corp_anniversary[0]['renewal_date'];
                        $member_anniv_datum->anniv = $corp_anniversary[0]['anniv'];
                    }

                    array_push($member_anniversary, $member_anniv_data[0]);

                    $corp_benefits = CorpGroups::select(
                        'corp_groups.category',
                        't_product_name.code as product_code',
                        't_product_name.product_name',
                        'corp_groups.benefit',
                        'corp_groups.sub_limit_of',
                        'corp_groups.limit',
                        'benefit_sharing.code as sharing_code',
                        'benefit_sharing.sharing',
                        'corp_groups.fund',
                        'corp_groups.capitated',
                        'corp_groups.suspend_at',
                        'corp_groups.quantity',
                        'corp_groups.waiting_period',
                        'corp_groups.anniv',
                    )
                        ->leftJoin('benefit_sharing', 'benefit_sharing.code', '=', 'corp_groups.sharing')
                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'corp_groups.product_name')
                        ->where('corp_groups.corp_id', $request->corp_id)
                        ->where('corp_groups.category', $request->category[$i])
                        ->where('corp_groups.product_name', $request->product_name[$i])
                        ->where('corp_groups.anniv', $max_anniv)
                        ->get();

                    foreach ($corp_benefits as $corp_ben) {
                        $member_data = MemberInfo::select(
                            DB::raw("CONCAT(surname,' ',first_name,' ',other_names) as member_name"),
                            'member_no'
                        )
                            ->where('member_no', $request->member_no[$i])
                            ->get();

                        $member_name = count($member_data) != 0 ? $member_data[0]->member_name : '';
                        $member_no = count($member_data) != 0 ? $member_data[0]->member_no : '';

                        $dt = [
                            'category' => $corp_ben->category,
                            'product_code' => $corp_ben->product_code,
                            'product_name' => $corp_ben->product_name,
                            'benefit' => $corp_ben->benefit,
                            'sub_limit_of' => $corp_ben->sub_limit_of,
                            'limit' => $corp_ben->limit,
                            'sharing_code' => $corp_ben->sharing_code,
                            'sharing' => $corp_ben->sharing,
                            'fund' => $corp_ben->fund,
                            'capitated' => $corp_ben->capitated,
                            'suspend_at' => $corp_ben->suspend_at,
                            'quantity' => $corp_ben->quantity,
                            'waiting_period' => $corp_ben->waiting_period,
                            'anniv' => $corp_ben->anniv,
                            'member_name' => $member_name,
                            'member_no' => $member_no,
                        ];
                        array_push($member_benefits, $dt);
                    }
                }
                break;
            //family
            case '2':
                //fetch latest family anniversary
                $global_anniv = 0;
                $today = date("Y-m-d");
                $fam_max_anniv = MemberAnniversary::where('member_no', $request->principal_member_no)->max('anniv');
                $family_anniversary = MemberAnniversary::select('start_date', 'renewal_date', 'end_date', 'anniv')
                    ->where('member_no', $request->principal_member_no)
                    ->where('anniv', $fam_max_anniv)
                    ->get();
                if (isset($request->member_no)) {
                    for ($i = 0; $i < count($request->member_no); $i++) {
                        if ($request->renew[$i] == '1') {
                            $member_max_anniv = MemberAnniversary::where('member_no', $request->member_no[$i])->max('anniv');

                            $member_anniv_data = MemberAnniversary::select(
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                                'member_anniversary.health_plan',
                                'member_anniversary.h_option as h_option_code',
                                't_product_name.product_name',
                                'member_anniversary.smart_sync',
                                'member_anniversary.sync',
                                'member_info.member_no',
                                'member_anniversary.start_date',
                                'member_anniversary.end_date',
                                'member_anniversary.renewal_date',
                                'member_anniversary.anniv'
                            )
                                ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
                                ->where('member_anniversary.anniv', $member_max_anniv)
                                ->where('member_anniversary.member_no', $request->member_no[$i])
                                ->get();
                            foreach ($member_anniv_data as $member_anniv_datum) {
                                //assign start date depending on whether the member is a principal or dependant
                                if ($request->principal_member_no != $request->member_no[$i]) {
                                    //check if principal is on cover and they have a higher anniv and if they are not being renewed
                                    if ($family_anniversary[0]->end_date >= $today  && $family_anniversary[0]['anniv'] > $member_anniv_datum->anniv && $request->renew[0] != '1') {
                                        $member_anniv_datum->start_date = $today;
                                        $member_anniv_datum->end_date = $family_anniversary[0]->end_date;
                                        $member_anniv_datum->renewal_date = $family_anniversary[0]->renewal_date;
                                        $member_anniv_datum->anniv = $global_anniv = intval($family_anniversary[0]['anniv']);
                                        //check if principal is on cover and they have a higher anniv and if they are  being renewed
                                    } else if ($family_anniversary[0]->end_date >= $today  && $request->renew[0] == '1') {
                                        if ($family_anniversary[0]->renewal_date < $today) {
                                            $member_anniv_datum->start_date =  $today;
                                        } else {
                                            $member_anniv_datum->start_date = $family_anniversary[0]->renewal_date;
                                        }
                                        $member_anniv_datum->end_date = date('Y-m-d', strtotime($family_anniversary[0]->renewal_date . '+1 year -1 day'));
                                        $member_anniv_datum->renewal_date = date('Y-m-d', strtotime($family_anniversary[0]->renewal_date . '+1 year'));
                                        $member_anniv_datum->anniv = $global_anniv = intval($family_anniversary[0]['anniv']) + 1;
                                        //principal not on cover or expired and is being renewed 
                                    } else if ($family_anniversary[0]->end_date < $today  && $request->renew[0] == '1') {
                                        $member_anniv_datum->start_date =  $today;
                                        $member_anniv_datum->end_date = date('Y-m-d', strtotime($member_anniv_datum->start_date . '+1 year -1 day'));
                                        $member_anniv_datum->renewal_date = date('Y-m-d', strtotime($member_anniv_datum->start_date . '+1 year'));
                                        $member_anniv_datum->anniv =  $global_anniv = intval($family_anniversary[0]['anniv']) + 1;
                                    } else if ($family_anniversary[0]['anniv'] == $member_anniv_datum->anniv &&  $request->renew[0] != '1') {
                                        return ['Cannot renew dependant alone without principal'];
                                    }
                                } else {
                                    if ($family_anniversary[0]->renewal_date < $today) {
                                        $member_anniv_datum->start_date =  $today;
                                    } else {
                                        $member_anniv_datum->start_date = $member_anniv_datum->renewal_date;
                                    }
                                    $member_anniv_datum->end_date = date('Y-m-d', strtotime($member_anniv_datum->start_date . '+1 year -1 day'));
                                    $member_anniv_datum->renewal_date = date('Y-m-d', strtotime($member_anniv_datum->start_date . '+1 year'));
                                    $member_anniv_datum->anniv =  $global_anniv = intval($family_anniversary[0]['anniv']) + 1;
                                }
                            }

                            array_push($member_anniversary, $member_anniv_data[0]);

                            $member_benefits_data = MemberBenefits::select(
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                                'member_benefits.category',
                                'member_benefits.product_name as product_code',
                                't_product_name.product_name',
                                'member_benefits.benefit',
                                'member_benefits.sub_limit_of',
                                'member_benefits.limit',
                                'member_benefits.sharing as sharing_code',
                                'benefit_sharing.sharing',
                                'member_benefits.fund',
                                'member_benefits.capitated',
                                'member_benefits.suspended_date as suspend_at',
                                'member_benefits.member_no',
                                'member_benefits.quantity',
                                'member_benefits.waiting_period',
                            )
                                ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_benefits.product_name')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'member_benefits.member_no')
                                ->leftJoin('benefit_sharing', 'benefit_sharing.code', '=', 'member_benefits.sharing')
                                ->where('member_benefits.member_no', $request->member_no[$i])
                                ->where('member_benefits.anniv', $member_max_anniv)
                                ->get();

                            foreach ($member_benefits_data as $member_benefits_datum) {
                                $member_benefits_datum->anniv = intval($global_anniv);
                            }
                            array_push($member_benefits, $member_benefits_data);
                        }
                    }
                }
                break;
            default:
                break;
        }
        //return for corporate and retail
        return [
            'member_anniversary' => json_encode(
                $member_anniversary,
                JSON_INVALID_UTF8_IGNORE
            ),
            'member_benefits' => json_encode($member_benefits, JSON_INVALID_UTF8_IGNORE)
        ];
    }

    //renew members
    public function saveImportedMembershipRenewal(Request $request)
    {
        // return $request;
        // ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', 0);

        $today = date('Y-m-d');
        try {
            DB::transaction(function () use ($request, $today) {
                //save into member anniversary add new row
                if (isset($request->renew_member_no)) {
                    for ($i = 0; $i < count($request->renew_member_no); $i++) {
                        MemberAnniversary::create([
                            'member_no' => $request->renew_member_no[$i],
                            'start_date' => $request->renew_start_date[$i],
                            'end_date' => $request->renew_end_date[$i],
                            'renewal_date' => $request->renew_renewal_date[$i],
                            'anniv' => $request->renew_anniv[$i],
                            'user_id' => $request->user_id,
                            'date_entered' => $today,
                            'sync' => $request->renew_sync[$i],
                            'smart_sync' => $request->renew_smart_sync[$i],
                            'health_plan' => $request->renew_health_plan[$i],
                            'h_option' => $request->renew_product_name[$i],
                            'smart_sync' => 1,
                            'sync' => 0
                        ]);
                    }
                }
                //save into member benefits add new rows
                if (isset($request->renew_benefit_member_no)) {
                    for ($i = 0; $i < count($request->renew_benefit_member_no); $i++) {
                        //check if benefit already exists in member benefits
                        $existing_benefit = MemberBenefits::where('member_no', $request->renew_benefit_member_no[$i])
                            ->where('benefit', $request->renew_benefit_benefit[$i])
                            ->where('anniv', $request->renew_benefit_anniv[$i])
                            ->count();
                        if ($existing_benefit == 0) {
                            MemberBenefits::create([
                                'member_no' => $request->renew_benefit_member_no[$i],
                                'benefit' => $request->renew_benefit_benefit[$i],
                                'limit' => $request->renew_benefit_limit[$i],
                                'sharing' => $request->renew_benefit_sharing[$i],
                                'anniv' => $request->renew_benefit_anniv[$i],
                                'sub_limit_of' => $request->renew_benefit_sub_limit_of[$i],
                                'suspended_date' => $request->suspend_at[$i],
                                'capitated' => $request->renew_benefit_capitated[$i],
                                'fund' => $request->renew_benefit_fund[$i],
                                'quantity' => $request->renew_benefit_quantity[$i],
                                'product_name' => $request->renew_benefit_product_name[$i],
                                'category' => $request->renew_benefit_category[$i],
                                'waiting_period' => $request->renew_benefit_waiting_period[$i],
                                // 'smart_sync' => 1,
                                // 'sync' => 0
                            ]);
                        }
                    }
                }
            });
            return ['Members renewed successfully'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    //check renewal annivs for client
    public function checkRenewalAnnivs(Request $request)
    {
        //return $request;
        switch ($request->option) {
            case '1':
                $ll_max_anniv = CorpAnniversary::select(DB::raw("max(isnull(anniv, 0)) as max_anniv"))
                    ->where('corp_id', $request->corp_id)
                    ->get();
                $ll_max_anniv = (int)$ll_max_anniv[0]['max_anniv'];

                $ll_min_anniv = CorpAnniversary::select(DB::raw("min(anniv) as min_anniv"))
                    ->where('corp_id', $request->corp_id)
                    ->get();
                $ll_min_anniv = (int)$ll_min_anniv[0]['max_anniv'];

                if ($ll_max_anniv == $ll_min_anniv) {
                    return ['message' => 'Notice ! This corporate is not yet renewed, Renew it first to proceed'];
                } else {
                    return [];
                }
                break;
            case '2':
                return [];
                break;
            default:
                break;
        }
    }

    //renew enmass
    public function renewEnmass(Request $request)
    {

        // ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', 0);

        $ll_age = 0;
        $today = date("Y-m-d");
        switch ($request->option) {
            case "1":
                $ll_max_anniv = CorpAnniversary::select(DB::raw("max(isnull(anniv, 0)) as max_anniv"))
                    ->where('corp_id', $request->corp_id)
                    ->get();
                $ll_max_anniv = (int)$ll_max_anniv[0]['max_anniv'];

                $ll_min_anniv = CorpAnniversary::select(DB::raw("min(isnull(anniv, 0)) as min_anniv"))
                    ->where('corp_id', $request->corp_id)
                    ->get();
                $ll_min_anniv = (int)$ll_min_anniv[0]['max_anniv'];

                //fetch corporate anniversaries
                $corp_anniv_dates = CorpAnniversary::select('start_date', 'end_date', 'renewal_date', 'smart_sync')
                    ->where('corp_id', $request->corp_id)
                    ->where('anniv', $ll_max_anniv)
                    ->get();
                $corp_anniv_dates = $corp_anniv_dates[0];

                //fetch corporate members and the benefits
                $corp_members = MemberInfo::select(
                    'member_info.member_no',
                    'member_anniversary.health_plan',
                    'member_anniversary.h_option',
                    'member_info.relation_to_principal',
                    'member_info.family_title'
                )
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                    ->where('corp_id', $request->corp_id)
                    ->orderBy('member_info.member_no')
                    ->get();

                foreach ($corp_members as $corp_member) {
                    //fetch dob for each member
                    $ld_dob = MemberInfo::select('dob')
                        ->where('member_no', $corp_member->member_no)
                        ->get();
                    $ld_dob = $ld_dob[0]['dob'];

                    //calculate age....
                    $ll_age = date_diff(date_create($ld_dob), date_create($today))->y;

                    //check if member has been renewed prior and has anniversary in member anniversary
                    $ll_mem_anniv = MemberAnniversary::select(DB::raw("max(isnull(anniv, 0)) as member_max_anniv"))
                        ->where('member_no', $corp_member->member_no)
                        ->get();
                    $ll_mem_anniv = (int)$ll_mem_anniv[0]['member_max_anniv'];

                    if ($ll_mem_anniv == $ll_max_anniv) {
                        DB::transaction(function () use ($corp_member, $ll_max_anniv) {
                            MemberAnniversary::where([
                                'member_no' => $corp_member->member_no,
                                'anniv' => $ll_max_anniv
                            ])->delete();
                        });
                    }

                    //save new anniversaries into member anniversaries
                    DB::transaction(function () use ($request, $corp_member, $corp_anniv_dates, $ll_max_anniv, $today) {
                        MemberAnniversary::create([
                            'member_no' => $corp_member->member_no,
                            'start_date' => $corp_anniv_dates->start_date,
                            'end_date' => $corp_anniv_dates->end_date,
                            'renewal_date' => $corp_anniv_dates->renewal_date,
                            'anniv' => $ll_max_anniv,
                            'user_id' => $request->user,
                            'date_entered' => $today,
                            'health_plan' => $corp_member->health_plan,
                            'h_option' => $corp_member->h_option,
                            'smart_sync' => 1,
                            'sync' => 0
                        ]);
                    });

                    //benefits table
                    $ll_gender = MemberInfo::select('gender')
                        ->where('member_no', $corp_member->member_no)
                        ->get();

                    $ll_gender = $ll_gender[0]['gender'];

                    //check if member has been renewed prior and has benefits in member benefits
                    $ll_mem_anniv = MemberBenefits::select(DB::raw("max(isnull(anniv, 0)) as member_max_anniv"))
                        ->where('member_no', $corp_member->member_no)
                        ->get();
                    $ll_mem_anniv = (int)$ll_mem_anniv[0]['member_max_anniv'];
                    // return $ll_mem_anniv;
                    if ($ll_mem_anniv == $ll_max_anniv) {
                        DB::transaction(function () use ($corp_member, $ll_max_anniv) {
                            MemberBenefits::where([
                                'member_no' => $corp_member->member_no,
                                'anniv' => $ll_max_anniv
                            ])->delete();
                        });
                    }

                    $benefit_details = CorpGroups::select(
                        'benefit',
                        'limit',
                        'sharing',
                        'sub_limit_of',
                        'capitated',
                        'fund',
                        'quantity'
                    )
                        ->where('product_name', $corp_member->h_option)
                        ->where('category', $corp_member->health_plan)
                        ->where('anniv', $ll_max_anniv)
                        ->where('corp_id', $request->corp_id)
                        ->get();

                    foreach ($benefit_details as $benefit_detail) {
                        $ll_sharing = $benefit_detail->sharing;

                        if ($ll_sharing == 1 || $ll_sharing == 2) {
                            //benefit applies to all members...save into member benefits
                            DB::transaction(function () use ($corp_member, $ll_max_anniv, $benefit_detail) {
                                MemberBenefits::create([
                                    'member_no' => $corp_member->member_no,
                                    'anniv' => $ll_max_anniv,
                                    'sharing' => $benefit_detail->sharing,
                                    'sub_limit_of' => $benefit_detail->sub_limit_of,
                                    'benefit' => $benefit_detail->benefit,
                                    'limit' => $benefit_detail->limit,
                                    'capitated' => $benefit_detail->capitated,
                                    'fund' => $benefit_detail->fund,
                                    'quantity' => $benefit_detail->quantity,
                                    'product_name' => $corp_member->h_option,
                                    'category' => $corp_member->health_plan,
                                    'smart_sync' => 1,
                                    'sync' => 0
                                ]);
                            });
                        } else {
                            //benefit does not apply to everyone
                            if ($ll_sharing == 3) {
                                if ($corp_member->relation_to_principal == 24) {
                                    //per principal ...
                                    DB::transaction(function () use ($corp_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $corp_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $corp_member->h_option,
                                            'category' => $corp_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 4) {
                                if ($corp_member->relation_to_principal == 25) {
                                    //per dependant ...
                                    DB::transaction(function () use ($corp_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $corp_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $corp_member->h_option,
                                            'category' => $corp_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 5) {
                                if ($corp_member->family_title == 4) {
                                    //per spouse ...
                                    DB::transaction(function () use ($corp_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $corp_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $corp_member->h_option,
                                            'category' => $corp_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 6) {
                                if ($ll_age < 18) {
                                    //per child ...
                                    DB::transaction(function () use ($corp_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $corp_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $corp_member->h_option,
                                            'category' => $corp_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 7) {
                                if ($ll_age > 17) {
                                    //per adult ...
                                    DB::transaction(function () use ($corp_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $corp_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $corp_member->h_option,
                                            'category' => $corp_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 8) {
                                if ($corp_member->family_title == 4 || $corp_member->family_title == 3) {
                                    //per principal/spouse
                                    DB::transaction(function () use ($corp_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $corp_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $corp_member->h_option,
                                            'category' => $corp_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            }
                        }
                    }
                }
                return ['message' => 'Corporate Renewal Successful'];
                break;
            case "2":
                $ll_mem_anniv = MemberAnniversary::select(DB::raw("max(isnull(anniv, 0)) as max_anniv"))
                    ->where('member_no', $request->member_no)
                    ->get();
                $ll_mem_anniv = $ll_mem_anniv[0]['max_anniv'];

                $ld_ren = MemberAnniversary::select('renewal_date')
                    ->where('anniv', $ll_mem_anniv)
                    ->where('member_no', $request->member_no)
                    ->get();

                $ld_str = $ld_ren[0]['renewal_date'];
                $ld_end = date('Y-m-d', strtotime($ld_str . " + 364 days"));
                $ld_ren = date('Y-m-d', strtotime($ld_str . " + 365 days"));
                //increment anniv to next ...add 1
                $ll_max_anniv = $ll_mem_anniv + 1;

                //extract family member from principal member no selected
                $family_no = explode('-', $request->member_no);
                $family_no = $family_no[0];

                //fetch family members
                $family_members = MemberInfo::select(
                    'member_info.member_no',
                    'member_anniversary.health_plan',
                    'member_anniversary.h_option',
                    'member_info.relation_to_principal',
                    'member_info.family_title'
                )
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                    ->where('family_no', $family_no)
                    ->orderBy('member_info.member_no')
                    ->get();

                foreach ($family_members as $family_member) {
                    //fetch dob for each member
                    $ld_dob = MemberInfo::select('dob')
                        ->where('member_no', $family_member->member_no)
                        ->get();
                    $ld_dob = $ld_dob[0]['dob'];

                    //calculate age...
                    $ll_age = date_diff(date_create($ld_dob), date_create($today))->y;

                    //check if member has been renewed prior and has anniversary in member anniversary
                    $ll_mem_anniv = MemberAnniversary::select(DB::raw("max(isnull(anniv, 0)) as member_max_anniv"))
                        ->where('member_no', $family_member->member_no)
                        ->get();
                    $ll_mem_anniv = (int)$ll_mem_anniv[0]['member_max_anniv'];

                    if ($ll_mem_anniv == $ll_max_anniv) {
                        DB::transaction(function () use ($family_member, $ll_max_anniv) {
                            MemberAnniversary::where([
                                'member_no' => $family_member->member_no,
                                'anniv' => $ll_max_anniv
                            ])->delete();
                        });
                    }
                    //save new anniversaries into member anniversaries
                    DB::transaction(function () use ($request, $family_member, $ld_str, $ld_end, $ld_ren, $ll_max_anniv, $today) {
                        MemberAnniversary::create([
                            'member_no' => $family_member->member_no,
                            'start_date' => $ld_str,
                            'end_date' => $ld_end,
                            'renewal_date' => $ld_ren,
                            'anniv' => $ll_max_anniv,
                            'user_id' => $request->user,
                            'date_entered' => $today,
                            'health_plan' => $family_member->health_plan,
                            'h_option' => $family_member->h_option,
                            'smart_sync' => 1,
                            'sync' => 0
                        ]);
                    });

                    //check if member has been renewed prior and has benefits in member benefits
                    $ll_mem_anniv = MemberBenefits::select(DB::raw("max(isnull(anniv, 0)) as member_max_anniv"))
                        ->where('member_no', $family_member->member_no)
                        ->get();
                    $ll_mem_anniv = (int)$ll_mem_anniv[0]['member_max_anniv'];

                    if ($ll_mem_anniv == $ll_max_anniv) {
                        DB::transaction(function () use ($family_member, $ll_max_anniv) {
                            MemberBenefits::where([
                                'member_no' => $family_member->member_no,
                                'anniv' => $ll_max_anniv
                            ])->delete();
                        });
                    }
                    $benefit_details = MemberBenefits::select(
                        'benefit',
                        'limit',
                        'sharing',
                        'sub_limit_of',
                        'capitated',
                        'fund',
                        'quantity',
                        'product_name',
                        'category'
                    )
                        ->where('anniv', $ll_mem_anniv)
                        ->where('member_no', $family_member->member_no)
                        ->get();

                    foreach ($benefit_details as $benefit_detail) {
                        $ll_sharing = $benefit_detail->sharing;

                        if ($ll_sharing == 1 || $ll_sharing == 2) {
                            //benefit applies to all members...save into member benefits
                            DB::transaction(function () use ($family_member, $ll_max_anniv, $benefit_detail) {
                                MemberBenefits::create([
                                    'member_no' => $family_member->member_no,
                                    'anniv' => $ll_max_anniv,
                                    'sharing' => $benefit_detail->sharing,
                                    'sub_limit_of' => $benefit_detail->sub_limit_of,
                                    'benefit' => $benefit_detail->benefit,
                                    'limit' => $benefit_detail->limit,
                                    'capitated' => $benefit_detail->capitated,
                                    'fund' => $benefit_detail->fund,
                                    'quantity' => $benefit_detail->quantity,
                                    'product_name' => $family_member->h_option,
                                    'category' => $family_member->health_plan,
                                    'smart_sync' => 1,
                                    'sync' => 0
                                ]);
                            });
                        } else {
                            //benefit does not apply to everyone
                            if ($ll_sharing == 3) {
                                if ($family_member->relation_to_principal == 24) {
                                    //per principal ...
                                    DB::transaction(function () use ($family_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $family_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $family_member->h_option,
                                            'category' => $family_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 4) {
                                if ($family_member->relation_to_principal == 25) {
                                    //per principal ...
                                    DB::transaction(function () use ($family_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $family_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $family_member->h_option,
                                            'category' => $family_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 5) {
                                if ($family_member->family_title == 4) {
                                    //per principal ...
                                    DB::transaction(function () use ($family_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $family_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $family_member->h_option,
                                            'category' => $family_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 6) {
                                if ($ll_age < 18) {
                                    //per child ...
                                    DB::transaction(function () use ($family_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $family_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $family_member->h_option,
                                            'category' => $family_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 7) {
                                if ($ll_age > 17) {
                                    //per adult ...
                                    DB::transaction(function () use ($family_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $family_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $family_member->h_option,
                                            'category' => $family_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            } elseif ($ll_sharing == 8) {
                                if ($family_member->family_title == 4 || $family_member->family_title == 3) {
                                    //per principal/spouse
                                    DB::transaction(function () use ($family_member, $ll_max_anniv, $benefit_detail) {
                                        MemberBenefits::create([
                                            'member_no' => $family_member->member_no,
                                            'anniv' => $ll_max_anniv,
                                            'sharing' => $benefit_detail->sharing,
                                            'sub_limit_of' => $benefit_detail->sub_limit_of,
                                            'benefit' => $benefit_detail->benefit,
                                            'limit' => $benefit_detail->limit,
                                            'capitated' => $benefit_detail->capitated,
                                            'fund' => $benefit_detail->fund,
                                            'quantity' => $benefit_detail->quantity,
                                            'product_name' => $family_member->h_option,
                                            'category' => $family_member->health_plan,
                                            'smart_sync' => 1,
                                            'sync' => 0
                                        ]);
                                    });
                                }
                            }
                        }
                    }
                }
                return ['message' => 'Family Renewal Successful'];
                break;
            default:
                break;
        }
    }
}
