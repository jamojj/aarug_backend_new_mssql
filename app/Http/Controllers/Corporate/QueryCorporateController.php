<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\AdminFeeType;
use App\Models\Agent;
use App\Models\Benefit;
use App\Models\BenefitSharing;
use App\Models\Category;
use App\Models\ContactRelation;
use App\Models\Corporate\CorpAdminFee;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpContactPerson;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\Corporate\CorpProvider;
use App\Models\ProductName;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\PrincipalApplicant;
use App\Models\Title;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDOException;

class QueryCorporateController extends Controller
{
    public function queryCorporate($corp_id)
    {
        $refined_data = array();

        $corporate =  Corporate::select('corporate.*', 'agent.agent_name', 't_client_type.client_type')
            ->leftJoin('agent', 'corporate.agent_id', '=', 'agent.agent_id')
            ->leftJoin('t_client_type', 'corporate.individual', '=', 't_client_type.code')
            ->where('corp_id', $corp_id)->get();
        $prev_annivs = CorpAnniversary::select('corp_anniversary.anniv', 'agent.agent_name', 'corp_anniversary.agent_id', 'corp_anniversary.start_date', 'corp_anniversary.end_date', 'corp_anniversary.renewal_date', 'corp_anniversary.smart_sync')
            ->leftJoin('agent', 'corp_anniversary.agent_id', '=', 'agent.agent_id')
            ->orderBy('corp_anniversary.anniv', 'DESC')
            ->where('corp_anniversary.corp_id', $corp_id)
            ->get();
        $corp_contact_person = CorpContactPerson::select('corp_contact_person.*', 'title.title as title_name', 'contact_relation.relation')
            ->leftJoin('title', 'corp_contact_person.title', '=', 'title.code')
            ->leftJoin('contact_relation', 'corp_contact_person.job_title', '=', 'contact_relation.code')
            ->where('corp_contact_person.corp_id', $corp_id)
            ->get();
        $max_anniv = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');
        $admin_fee = CorpAdminFee::select('corp_admin_fee.*',  't_admin_fee_type.code', 't_admin_fee_type.admin_fee_type')
            ->leftJoin('t_admin_fee_type', 'corp_admin_fee.admin_fee_type', '=', 't_admin_fee_type.code')
            ->where('corp_id', $corp_id)->get();
        $corp_groups = CorpGroups::select('corp_groups.*', 'benefit_sharing.sharing as sharing_name')
            ->leftJoin('benefit_sharing', 'corp_groups.sharing', '=', 'benefit_sharing.code')
            ->orderBy('corp_groups.anniv', 'DESC')
            ->orderBy('corp_groups.category', 'ASC')
            ->where('corp_groups.corp_id', $corp_id)
            ->get();
        $corp_provider =  CorpProvider::select('corp_provider.provider', 'corp_provider.copay_amt', 'corp_provider.anniv', 'provider.provider', 'provider.code as provider_code')
            ->Join('provider', 'corp_provider.provider', '=', 'provider.code')
            ->where('anniv', $max_anniv)
            ->where('corp_id', $corp_id)
            ->get();

        $health_plan = Category::all();
        $product_name = ProductName::all();
        $benefit = Benefit::all();
        $sharing = BenefitSharing::all();
        $agent = Agent::all();
        $title = Title::all();
        $contact_relation = ContactRelation::all();
        $admin_fee_types = AdminFeeType::all();

        // foreach ($prev_annivs as $annivs) {
        //     $start_date = ($annivs->start_date);
        //     $end_date = ($annivs->end_date);
        //     $renewal_date = ($annivs->renewal_date);
        //     $smart_sync = ($annivs->smart_sync);

        //     $anniv_data = [
        //         'anniv' => $annivs->anniv,
        //         'agent_name' => $annivs->agent_name,
        //         'agent_id' => $annivs->agent_id,
        //         'start_date' => $start_date,
        //         'end_date' => $end_date,
        //         'renewal_date' => $renewal_date,
        //         'smart_sync' => $smart_sync,
        //     ];

        //     array_push($refined_data, $anniv_data);
        // }

        $data = [
            // 'prev_annivs' => $refined_data,
            'prev_annivs' => $prev_annivs,
            'corporate' => $corporate,
            'corp_provider' => $corp_provider,
            'corp_contact_person' => $corp_contact_person,
            'max_anniv' => $max_anniv,
            'admin_fee' => $admin_fee,
            'corp_groups' => $corp_groups,
            'health_plan' => $health_plan,
            'product_name' => $product_name,
            'benefit' => $benefit,
            'sharing' => $sharing,
            'agent' => $agent,
            'title' => $title,
            'contact_relation' => $contact_relation,
            'admin_fee_types' => $admin_fee_types
        ];

        // return $data;
        return response()->json(
            $data,
            200,
            [],
            JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    //update or save new corporate data
    public function saveQueryCorporateData(Request $request)
    {
        // return $request;
        $response = [];
        $corp_id = $request->input('corp_id');
        $anniv = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');

        try {
            DB::transaction(function () use ($request, $corp_id, $anniv) {
                //set inserted, updated, and deleted items
                $inserted_item = null;
                $updated_item = "corp_id : $corp_id";
                $deleted_item = null;


                //update current corp anniv
                CorpAnniversary::where([
                    'CORP_ID' => $corp_id,
                    'ANNIV' => $anniv
                ])->update(
                    [
                        'START_DATE' => $request->start_date[0],
                        'END_DATE' => $request->end_date[0],
                        'RENEWAL_DATE' => $request->renewal_date[0],
                        'AGENT_ID' => $request->agent_id,
                        'smart_sync' => $request->smart_sync[0] == 1 ? 1 : null,
                        'sync' => $request->smart_sync[0] == 1 ? null : 1,
                        // 'smart_sync' => $request->smart_sync[$i]
                    ]
                );
                //update corp anniversary
                // for ($i = 0; $i < count($request->start_date); $i++) {

                //     $save_corp_annivs =  CorpAnniversary::where([
                //         'CORP_ID' => $corp_id,
                //         'ANNIV' => $request->corp_anniv[$i]
                //     ])->update(
                //         [
                //             'START_DATE' => $request->start_date[$i],
                //             'END_DATE' => $request->end_date[$i],
                //             'RENEWAL_DATE' => $request->renewal_date[$i],
                //             'AGENT_ID' => $request->intermediary[$i],
                //             'smart_sync' => $request->smart_sync[$i] == 1 ? 1 : null,
                //             'sync' => $request->smart_sync[$i] == 1 ? null : 1,
                //             // 'smart_sync' => $request->smart_sync[$i]
                //         ]
                //     );
                // }

                // if ($save_corp_annivs == 1) {
                $smart_sync = CorpAnniversary::where('anniv', $anniv)->where('corp_id', $corp_id)->pluck('smart_sync');
                //check smart sync status
                if (count($smart_sync) !== 0) {
                    if ($smart_sync[0] != null) {
                        $smart_sync = 1;
                    } else {
                        $smart_sync = null;
                    }
                } else {
                    $smart_sync = null;
                }
                //update corporate data
                Corporate::where('corp_id', $corp_id)->update([
                    'corp_code' => $request->corp_code,
                    'CORPORATE' => $request->corporate,
                    'TEL_NO' => $request->corp_tel_no,
                    'FAX_NO' => $request->fax_no,
                    'MOBILE_NO' => $request->corp_mobile_no,
                    'POSTAL_ADD' => $request->postal_add,
                    'product_category' => $request->product_category,
                    'TOWN' => $request->town,
                    'EMAIL' => $request->corp_email,
                    'PHY_LOC' => $request->phy_loc,
                    'AGENT_ID' => (int)$request->agent_id,
                    'USER_ID' => $request->user,
                    'DATE_ENTERED' => $request->date_entered,
                    'individual' => $request->class,
                    'smart_sync' => $smart_sync == 1 ? 1 : null,
                    'sync' => $smart_sync == 1 ? null : 1,
                ]);

                //update principald based on corporate type whether retail, corporate, or sme
                PrincipalApplicant::where('corp_id', $corp_id)->update([
                    'individual' => $request->class
                ]);


                //update or save corp groups
                $max_idx = CorpGroups::whereNotNull('idx')->max('idx');
                if (isset($request->benefit_added)) {
                    for ($i = 0; $i < count($request->benefit_added); $i++) {
                        $max_idx++;
                        //check if exists
                        $exists = CorpGroups::where('benefit', $request->benefit_added[$i])
                            ->where('category', $request->health_plan_added[$i])
                            ->where('product_name', $request->product_name_added[$i])
                            ->where('anniv', $anniv)
                            ->where('corp_id', $corp_id)
                            ->get();
                        if (count($exists) == 0) {
                            CorpGroups::create([
                                'idx' => $max_idx,
                                'corp_id' => $corp_id,
                                'anniv' => $anniv,
                                'category' => $request->health_plan_added[$i],
                                'benefit' => $request->benefit_added[$i],
                                'fund' => $request->fund_added[$i],
                                'limit' => $request->limit_added[$i],
                                'sharing' => $request->sharing_added[$i],
                                'sub_limit_of' => $request->sub_limit_of_added[$i],
                                'capitated' => $request->capitated_added[$i],
                                'quantity' => $request->quantity_added[$i],
                                'product_name' => $request->product_name_added[$i],
                                'smart_sync' => $smart_sync == 1 ? 1 : null,
                                'sync' => $smart_sync == 1 ? null : 1,
                            ]);
                        }
                    }
                }

                if (isset($request->benefit)) {
                    for ($i = 0; $i < count($request->benefit); $i++) {
                        CorpGroups::where('idx', $request->idx[$i])->update([
                            'idx' => $request->idx[$i],
                            'corp_id' => $corp_id,
                            'anniv' => $request->anniv[$i],
                            'category' => $request->health_plan[$i],
                            'benefit' => $request->benefit[$i],
                            'fund' => $request->fund[$i],
                            'limit' => $request->limit[$i],
                            'sharing' => $request->sharing[$i],
                            'sub_limit_of' => $request->sub_limit_of[$i],
                            'capitated' => $request->capitated[$i],
                            'quantity' => $request->quantity[$i],
                            'product_name' => $request->product_name[$i],
                            'smart_sync' => $smart_sync == 1 ? 1 : null,
                            'sync' => $smart_sync == 1 ? null : 1,
                        ]);
                    }
                }
                // }
                //update or create corp contact person(s)
                if (isset($request->title_added)) {
                    for ($i = 0; $i < count($request->title_added); $i++) {
                        CorpContactPerson::create(
                            [
                                'CORP_ID' => $corp_id,
                                'TITLE' => $request->title_added[$i],
                                'SURNAME' => $request->surname_added[$i],
                                'FIRST_NAME' => $request->first_name_added[$i],
                                'OTHER_NAMES' => $request->other_names_added[$i],
                                'JOB_TITLE' => $request->contact_relation_added[$i],
                                'MOBILE_NO' => $request->contact_mobile_no_added[$i],
                                'TEL_NO' => $request->contact_tel_no_added[$i],
                                'EMAIL' => $request->contact_email_added[$i]
                            ]
                        );
                    }
                }
                if (isset($request->title)) {
                    for ($i = 0; $i < count($request->title); $i++) {
                        CorpContactPerson::where(
                            [
                                'CORP_ID' => $corp_id,
                                'JOB_TITLE' => $request->contact_relation[$i],
                            ]
                        )
                            ->update([
                                'CORP_ID' => $corp_id,
                                'TITLE' => $request->title[$i],
                                'SURNAME' => $request->surname[$i],
                                'FIRST_NAME' => $request->first_name[$i],
                                'OTHER_NAMES' => $request->other_names[$i],
                                'JOB_TITLE' => $request->contact_relation[$i],
                                'MOBILE_NO' => $request->contact_mobile_no[$i],
                                'TEL_NO' => $request->contact_tel_no[$i],
                                'EMAIL' => $request->contact_email[$i]
                            ]);
                    }
                }
                //update corp admin fee
                if (isset($request->admin_fee_type)) {
                    for ($i = 0; $i < count($request->admin_fee_type); $i++) {
                        CorpAdminFee::where(
                            [
                                'corp_id' => $corp_id,
                                'anniv' => $request->admin_fee_anniv[$i]
                            ]
                        )
                            ->update([
                                'admin_fee_type' => $request->admin_fee_type[$i],
                                'admin_fee_rate' => $request->admin_fee_rate[$i],
                                'upfront_copay' => $request->upfront_copay[$i],
                                'agent_commis_rate' => $request->agent_commis_rate[$i],
                                'commis_whtax_rate' => $request->commis_whtax_rate[$i],
                                'unit_manager_rate' => $request->unit_manager_rate[$i],
                                'bdm_rate' => $request->bdm_rate[$i]
                            ]);
                    }
                }

                //delete provider copay if delete is checked or update the copay amount
                if (isset($request->delete_provider)) {
                    for ($i = 0; $i < count($request->delete_provider); $i++) {
                        if ($request->delete_provider[$i] == 1) {
                            CorpProvider::where([
                                'provider' => $request->provider[$i],
                                'corp_id' => $corp_id,
                                'anniv' => $request->provider_anniv[$i],
                            ])->delete();
                        } else {
                            CorpProvider::where([
                                'provider' => $request->provider[$i],
                                'corp_id' => $corp_id,
                                'anniv' => $request->provider_anniv[$i],
                            ])->update(
                                [
                                    'copay_amt' => $request->copay_amount[$i],
                                ]
                            );
                        }
                    }
                }

                //add new provider copay if it doesn't exist
                if (isset($request->appended_provider)) {
                    for ($i = 0; $i < count($request->appended_provider); $i++) {
                        //check for dublicate provider copay
                        $existing = CorpProvider::where('provider', $request->appended_provider[$i])
                            ->where('corp_id', $corp_id)
                            ->where('anniv', $request->appended_provider_anniv[$i])
                            ->get();
                        if (count($existing) == 0) {
                            CorpProvider::create([
                                'provider' => $request->appended_provider[$i],
                                'corp_id' => $corp_id,
                                'copay_amt' => $request->appended_copay_amount[$i],
                                'anniv' => $request->appended_provider_anniv[$i]
                            ]);
                        }
                    }
                }

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });
            return  ['Corporate updated successfully'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    //normalize benefits
    public function normalizeMemberBenefits(Request $request)
    {
        // return $request;
        ini_set('max_execution_time', '0');
        $today = date('Y');
        $ll_mem_dur = $lc_limit = 0;
        try {
            //check if corporate is airtel retail which should not be normalized
            if ($request->corp_id != 758) {

                //transaction
                DB::transaction(function () use ($request, $today, $ll_mem_dur, $lc_limit) {

                    $anniv = CorpAnniversary::where('corp_id', $request->corp_id)->max('anniv');

                    $days = CorpAnniversary::select(
                        DB::raw("datediff(day, corp_anniversary.start_date, corp_anniversary.end_date) +1 as il_corp_dur"),
                        'corp_anniversary.start_date',
                        'corp_anniversary.end_date',
                        'corporate.individual'
                    )
                        ->join('corporate', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                        ->where('corp_anniversary.corp_id',  $request->corp_id)
                        ->where('corp_anniversary.anniv', $anniv)
                        ->get();

                    $il_corp_dur =  $days[0]->il_corp_dur;
                    $il_individual =  $days[0]->individual;
                    //fetching corp categories
                    $ls_cat = CorpGroups::select('category')->where('anniv', $anniv)
                        ->where('corp_id',  $request->corp_id)
                        ->distinct('category')
                        ->get();
                    //fetching corp products name
                    foreach ($ls_cat as $categories) {
                        $ll_product = CorpGroups::select('product_name')->where('anniv', $anniv)
                            ->where('corp_id',  $request->corp_id)
                            ->where('category',  $categories->category)
                            ->distinct('product_name')
                            ->get();

                        //fetching benefits
                        foreach ($ll_product as $product_names) {
                            $benefit_data = CorpGroups::select('benefit', 'limit', 'sharing', 'sub_limit_of', 'quantity', 'product_name', 'fund', 'waiting_period')
                                ->where('product_name', $product_names->product_name)
                                ->where('category', $categories->category)
                                ->where('anniv', $anniv)
                                ->where('corp_id',  $request->corp_id)
                                ->get();

                            //fetching member info
                            foreach ($benefit_data as $benefits) {
                                $member_no = PrincipalApplicant::leftJoin('member_info', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                    ->where('principal_applicant.corp_id', $request->corp_id)
                                    ->pluck('member_info.member_no');
                                $member_data = MemberAnniversary::select(
                                    'member_anniversary.member_no',
                                    'member_info.relation_to_principal',
                                    'member_info.family_title',
                                    'member_info.dob',
                                    'member_info.gender',
                                    'member_anniversary.start_date',
                                    'member_anniversary.end_date',
                                    DB::raw("datediff(day,member_anniversary.start_date, member_anniversary.end_date)+1 as ll_mem_dur")
                                )
                                    ->join('member_info', 'member_anniversary.member_no', '=', 'member_info.member_no')
                                    ->where('member_anniversary.h_option', $product_names->product_name)
                                    ->where('member_anniversary.health_plan',  $categories->category)
                                    ->where('member_anniversary.anniv',  $anniv)
                                    ->whereIn('member_anniversary.member_no',  $member_no)
                                    ->get();
                                foreach ($member_data as $data) {
                                    $ld_dob = date('Y', strtotime($data->dob));
                                    $ll_age = $today - $ld_dob;
                                    if ($il_corp_dur <= $data->ll_mem_dur) {
                                        $ll_mem_dur = $il_corp_dur;
                                    }
                                    if ($benefits->limit > 0) {
                                        $lc_limit = (($benefits->limit / $il_corp_dur) * $ll_mem_dur);
                                    } else {
                                        $lc_limit = $benefits->limit;
                                    }

                                    //check if corporate is corp or sme and if sme force limit to   (1 is corp, 2 is retail, 3 is sme)
                                    // if ($il_individual == 3) {
                                    //     $lc_limit = 0;
                                    // }
                                    //check if member has the benefit
                                    $ll_cnt = MemberBenefits::where('anniv', $anniv)
                                        ->where('benefit', $benefits->benefit)
                                        ->where('member_no', $data->member_no)
                                        ->count('*');
                                    //corp group sharing
                                    $ll_share = $benefits->sharing;
                                    $ll_rela = $data->relation_to_principal;
                                    $ll_fam_rela = $data->family_title;
                                    $ll_gender = $data->gender;
                                    $maternity_related_benefits = [59, 24, 35, 43, 68, 5];
                                    $memberBenefitNewData = [
                                        'member_no' => $data->member_no,
                                        'benefit' => $benefits->benefit,
                                        'limit' => $lc_limit,
                                        'sharing' => $ll_share,
                                        'anniv' => $anniv,
                                        'sub_limit_of' => $benefits->sub_limit_of,
                                        'fund' => $benefits->fund,
                                        'quantity' => $benefits->quantity,
                                        'product_name' => $benefits->product_name,
                                        'category' => $categories->category,
                                        'waiting_period' => $benefits->waiting_period,
                                    ];

                                    $memberBenefitUpdateData = [
                                        'limit' => $lc_limit,
                                        'sharing' => $ll_share,
                                        'sub_limit_of' => $benefits->sub_limit_of,
                                        'fund' => $benefits->fund,
                                        'quantity' => $benefits->quantity,
                                        'product_name' => $benefits->product_name,
                                        'category' => $categories->category,
                                        'waiting_period' => $benefits->waiting_period,
                                    ];
                                    //if member doesn't have add new benefit
                                    if ($ll_cnt == 0) {
                                        //per family and per person
                                        if ($ll_share == 1 || $ll_share == 2) {
                                            //check if benefit is maternity related 
                                            if (in_array($benefits->benefit, $maternity_related_benefits)) {
                                                //check if member is principal female or dependant is spouse and female 
                                                if (($ll_rela == 24 && $ll_gender == 2) || ($ll_fam_rela == 4 && $ll_gender == 2)) {
                                                    MemberBenefits::create($memberBenefitNewData);
                                                }
                                            } else {
                                                MemberBenefits::create($memberBenefitNewData);
                                            }
                                        } else {
                                            //per principal
                                            if ($ll_share == 3) {
                                                //per principal
                                                if ($ll_rela == 24) {
                                                    //check if benefit is maternity related 
                                                    if (in_array($benefits->benefit, $maternity_related_benefits)) {
                                                        //check if member is principal female or dependant is spouse and female 
                                                        if (($ll_rela == 24 && $ll_gender == 2) || ($ll_fam_rela == 4 && $ll_gender == 2)) {
                                                            MemberBenefits::create($memberBenefitNewData);
                                                        }
                                                    } else {
                                                        MemberBenefits::create($memberBenefitNewData);
                                                    }
                                                }
                                                //per dependant
                                            } elseif ($ll_share == 4) {
                                                //dependant
                                                if ($ll_rela == 25) {
                                                    //check if benefit is maternity related 
                                                    if (in_array($benefits->benefit, $maternity_related_benefits)) {
                                                        //check if member is principal female or dependant is spouse and female 
                                                        if (($ll_rela == 24 && $ll_gender == 2) || ($ll_fam_rela == 4 && $ll_gender == 2)) {
                                                            MemberBenefits::create($memberBenefitNewData);
                                                        }
                                                    } else {
                                                        MemberBenefits::create($memberBenefitNewData);
                                                    }
                                                }
                                                //per spouse
                                            } elseif ($ll_share == 5) {
                                                //spouse
                                                if ($ll_fam_rela == 4) {
                                                    //check if benefit is maternity related 
                                                    if (in_array($benefits->benefit, $maternity_related_benefits)) {
                                                        //check if member is principal female or dependant is spouse and female 
                                                        if (($ll_rela == 24 && $ll_gender == 2) || ($ll_fam_rela == 4 && $ll_gender == 2)) {
                                                            MemberBenefits::create($memberBenefitNewData);
                                                        }
                                                    } else {
                                                        MemberBenefits::create($memberBenefitNewData);
                                                    }
                                                }
                                                //per child
                                            } elseif ($ll_share == 6) {
                                                if ($ll_age < 18) {
                                                    //check if benefit is maternity related 
                                                    if (in_array($benefits->benefit, $maternity_related_benefits)) {
                                                        //check if member is principal female or dependant is spouse and female 
                                                        if (($ll_rela == 24 && $ll_gender == 2) || ($ll_fam_rela == 4 && $ll_gender == 2)) {
                                                            MemberBenefits::create($memberBenefitNewData);
                                                        }
                                                    } else {
                                                        MemberBenefits::create($memberBenefitNewData);
                                                    }
                                                }
                                                //per adult
                                            } elseif ($ll_share == 7) {
                                                if ($ll_age > 17) {
                                                    //check if benefit is maternity related 
                                                    if (in_array($benefits->benefit, $maternity_related_benefits)) {
                                                        //check if member is principal female or dependant is spouse and female 
                                                        if (($ll_rela == 24 && $ll_gender == 2) || ($ll_fam_rela == 4 && $ll_gender == 2)) {
                                                            MemberBenefits::create($memberBenefitNewData);
                                                        }
                                                    } else {
                                                        MemberBenefits::create($memberBenefitNewData);
                                                    }
                                                }
                                                //per principal/spouse
                                            } elseif ($ll_share == 8) {
                                                //(4 is spouse, 3 is principal, 24 is principal)
                                                if ($ll_fam_rela == 4 || $ll_fam_rela == 3 || $ll_fam_rela == 24) {
                                                    //check if benefit is maternity related 
                                                    if (in_array($benefits->benefit, $maternity_related_benefits)) {
                                                        //check if member is principal female or dependant is spouse and female 
                                                        if (($ll_rela == 24 && $ll_gender == 2) || ($ll_fam_rela == 4 && $ll_gender == 2)) {
                                                            MemberBenefits::create($memberBenefitNewData);
                                                        }
                                                    } else {
                                                        MemberBenefits::create($memberBenefitNewData);
                                                    }
                                                }
                                            }
                                        }
                                        //if member has benefit update it
                                    } else {
                                        //per family and per person
                                        if ($ll_share == 1 || $ll_share == 2) {
                                            MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                ->update($memberBenefitUpdateData);
                                        } else {
                                            //per principal
                                            if ($ll_share == 3) {
                                                //principal 
                                                if ($ll_rela == 24) {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->update($memberBenefitUpdateData);
                                                } else {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->delete();
                                                }
                                                //per dependant
                                            } elseif ($ll_share == 4) {
                                                //dependant
                                                if ($ll_rela == 25) {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->update($memberBenefitUpdateData);
                                                } else {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->delete();
                                                }
                                                //per spouse
                                            } elseif ($ll_share == 5) {
                                                //spouse
                                                if ($ll_fam_rela == 4) {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->update($memberBenefitUpdateData);
                                                } else {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->delete();
                                                }
                                                //per child
                                            } elseif ($ll_share == 6) {
                                                if ($ll_age < 18) {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->update($memberBenefitUpdateData);
                                                } else {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->delete();
                                                }
                                                //per adult
                                            } elseif ($ll_share == 7) {
                                                //over 18
                                                if ($ll_age > 17) {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->update($memberBenefitUpdateData);
                                                } else {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->delete();
                                                }
                                                //per principal/spouse
                                            } elseif ($ll_share == 8) {
                                                //(4 is spouse, 3 is principal, 24 is principal)
                                                if ($ll_fam_rela == 4 || $ll_fam_rela == 3 || $ll_fam_rela == 24) {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->update($memberBenefitUpdateData);
                                                } else {
                                                    MemberBenefits::where(['anniv' => $anniv, 'benefit' => $benefits->benefit, 'member_no' => $data->member_no,])
                                                        ->delete();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //updating nov in member anniversary
                    $member_no = PrincipalApplicant::leftJoin('member_info', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->where('principal_applicant.corp_id', $request->corp_id)
                        ->pluck('member_info.member_no');
                    MemberAnniversary::where('anniv', $anniv)
                        ->whereIn('member_no', $member_no)
                        ->update(['nov' => null]);
                });
                return  ['Benefits Normalized Successfully'];
            } else {
                return ['Airtel Retail scheme cannot be normalized!'];
            }
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function deleteCorporateProductLine(Request $request)
    {
        ini_set('max_execution_time', 0);

        try {
            DB::transaction(function () use ($request) {
                //get all members in a corporate
                $member_nos = PrincipalApplicant::leftJoin('member_info', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->where('principal_applicant.corp_id', $request->corp_id)
                    ->pluck('member_info.member_no');

                //delete from member level
                MemberBenefits::where(['anniv' => $request->anniv, 'benefit' => $request->benefit, 'product_name' => $request->product_name, 'category' => $request->health_plan])->whereIn('member_no', $member_nos)
                    ->delete();

                //delete from corporate level 
                CorpGroups::where('idx', $request->idx)->delete();
            });
            return ['Benefit with idx ' . $request->idx . ' deleted successfully.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
