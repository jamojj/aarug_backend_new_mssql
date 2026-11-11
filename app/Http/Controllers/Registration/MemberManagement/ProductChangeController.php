<?php

namespace App\Http\Controllers\Registration\MemberManagement;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpGroups;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\Registration\ProductChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductChangeController extends Controller
{
    public function productChange($member_no)
    {
        $response = [];
        //max anniv of member
        $max_anniv = MemberAnniversary::where('member_no', $member_no)->max('anniv');
        //check if member exists
        $member_exists = MemberInfo::where('member_no', $member_no)->get();
        if (count($member_exists) != 0) {
            //check if member is cancelled
            $member_status = MemberInfo::select(DB::raw("isnull(cancelled, 0) as cancelled"))
                ->where('member_no', $member_no)
                ->get();

            if ($member_status[0]->cancelled == 0) {
                $corp_id = PrincipalApplicant::select('principal_applicant.corp_id')
                    ->join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->where('member_info.member_no', $member_no)
                    ->get();

                $corp_id = count($corp_id) !== 0 ? $corp_id[0]->corp_id : '';
                $max_anniv_corp = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');

                $product_change = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as full_name"),
                    'principal_applicant.corp_id',
                    'corporate.corporate',
                    'member_anniversary.health_plan',
                    'member_anniversary.h_option',
                    't_product_name.product_name',
                    'member_info.member_no'
                )

                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->join('member_anniversary', 'member_info.member_no', '=', 'member_anniversary.member_no')
                    ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
                    ->join('t_product_name', 'member_anniversary.h_option', '=', 't_product_name.code')
                    ->where('member_info.member_no', $member_no)
                    ->where('member_anniversary.anniv', $max_anniv)
                    ->get();

                $new_health_plan = CorpGroups::select('category as health_plan')
                    ->where('corp_id', $corp_id)
                    ->where('anniv', $max_anniv_corp)
                    ->distinct()
                    ->get();

                $data = [
                    'product_change' => $product_change,
                    'new_health_plan' => $new_health_plan,
                ];
                array_push($response, $data);
            } else {
                array_push($response, 'Member is cancelled!');
            }
        } else {
            array_push($response, 'Member does not exist!');
        }

        return $response;
    }

    public function newProductChange($member_no, $health_plan)
    {
        $max_anniv = MemberAnniversary::where('member_no', $member_no)->max('anniv');
        $corp_id = PrincipalApplicant::select('principal_applicant.corp_id')
            ->join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->where('member_info.member_no', $member_no)
            ->get();

        $corp_id = count($corp_id) !== 0 ? $corp_id[0]->corp_id : '';


        if ($corp_id == 14) {
            $new_product = DB::table("corp_groups")
                ->select("corp_groups.product_name as code", 't_product_name.product_name')
                ->join('t_product_name', 'corp_groups.product_name', '=', 't_product_name.code')
                ->where("corp_groups.category", $health_plan)
                ->where("corp_groups.corp_id", $corp_id)
                ->distinct()
                ->get();
        } else {
            $new_product = DB::table("corp_groups")
                ->select("corp_groups.product_name as code", 't_product_name.product_name')
                ->join('t_product_name', 'corp_groups.product_name', '=', 't_product_name.code')
                ->where("corp_groups.anniv", $max_anniv)
                ->where("corp_groups.category", $health_plan)
                ->where("corp_groups.corp_id", $corp_id)
                ->distinct()
                ->get();
        }

        return $new_product;
    }
    public function saveProductChange(Request $request)
    {
        $response = array();
        $member_no = $request->input('member_no');
        $corp_id = MemberInfo::select('principal_applicant.corp_id')
            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->where('member_info.member_no', $member_no)
            ->get();
        $corp_id = count($corp_id) != 0 ? $corp_id[0]->corp_id : '';
        $anniv = MemberAnniversary::where('member_no', $member_no)->max('anniv');
        $today = today();
        $max_rec_id = ProductChange::max('rec_id');
        $max_rec_id++;
        //delete member benefits
        MemberBenefits::where('anniv', $anniv)->where('member_no', $member_no)->delete();

        //fetch member data

        $mem_data = MemberInfo::select('gender', 'family_title', 'relation_to_principal', 'dob')
            ->where('member_no', $member_no)
            ->get();

        $ld_dob = date('Y', strtotime($mem_data[0]->dob));
        $ll_age = date('Y') - $ld_dob;

        $ll_famrela = count($mem_data) != 0 ? $mem_data[0]->family_title : '';
        $ll_relapri = count($mem_data) != 0 ? $mem_data[0]->relation_to_principal : '';

        //check if  principal 
        if ($ll_relapri == 24) {
            PrincipalApplicant::where('member_no', $member_no)->update([
                'plan' => $request->new_product,
                'category' => $request->new_health_plan,
                'smart_sync' => 1,
                'sync' => 0
            ]);
        }
        //mem anniv data
        $mem_anniv_data = MemberAnniversary::select(
            DB::raw("datediff(day, start_date, end_date) +1 as ll_memdays"),
            DB::raw("isnull(sync, 0) as ll_sync"),
            'start_date'
        )
            ->where('anniv', $anniv)->where('member_no', $member_no)
            ->get();

        $ll_memdays = count($mem_anniv_data) != 0 ? $mem_anniv_data[0]->ll_memdays : '';
        $ll_sync = count($mem_anniv_data) != 0 ? $mem_anniv_data[0]->ll_sync : '';


        //corp anniv data
        if ($corp_id == 14) {
            $ll_corpdays = $ll_memdays;
            $start_date =  $mem_anniv_data[0]->start_date;
        } else {
            $corp_anniv_data = CorpAnniversary::select(
                DB::raw("datediff(day, start_date, end_date) +1 as ll_corpdays"),
                'start_date'
            )
                ->where('anniv', $anniv)->where('corp_id', $corp_id)
                ->get();
            $ll_corpdays = count($corp_anniv_data) != 0 ? $corp_anniv_data[0]->ll_corpdays : '';
            $start_date = count($corp_anniv_data) != 0 ? $corp_anniv_data[0]->start_date : '';
        }


        $ld_ldate = date('Y-m-d', strtotime('2020-03-24'));


        //fetch benefits from corp groups
        if ($corp_id == 14) {

            $corp_benefits = CorpGroups::select('benefit', 'limit', 'sharing', 'sub_limit_of', 'quantity', 'category', 'fund', 'product_name')
                ->where('product_name', $request->new_product)
                ->where('category', $request->new_health_plan)
                ->where('corp_id', $corp_id)
                ->get();
        } else {
            $corp_benefits = CorpGroups::select('benefit', 'limit', 'sharing', 'sub_limit_of', 'quantity', 'category', 'fund', 'product_name')
                ->where('product_name', $request->new_product)
                ->where('category', $request->new_health_plan)
                ->where('anniv', $anniv)
                ->where('corp_id', $corp_id)
                ->get();
        }


        foreach ($corp_benefits as $benefits) {
            $lc_limits =  $benefits->limit;
            $ll_share =  $benefits->sharing;

            if ($ll_corpdays <= $ll_memdays) {
                $ll_memdays = $ll_corpdays;
            }

            if ($start_date < $ld_ldate) {
                if ($lc_limits > 0) {
                    $lc_limits = ($lc_limits / $ll_corpdays) * $ll_memdays;
                }
            }
            //save member benefits 

            //check if benefit exists
            $benefit_exists = MemberBenefits::where('member_no', $member_no)->where('benefit', $benefits->benefit)->where('anniv', $anniv)->get();
            if (count($benefit_exists) == 0) {

                //benefits applies to all members
                if ($ll_share == 1 || $ll_share == 2) {
                    MemberBenefits::create([
                        'member_no' => $member_no,
                        'benefit' => $benefits->benefit,
                        'limit' => $lc_limits,
                        'sharing' => $benefits->sharing,
                        'anniv' => $anniv,
                        'sub_limit_of' => $benefits->sub_limit_of,
                        'fund' => $benefits->fund,
                        'quantity' => $benefits->quantity,
                        'product_name' => $benefits->product_name,
                        'category' => $benefits->category,
                        'smart_sync' => 1,
                        'sync' => 0
                    ]);
                } else {

                    if ($ll_share == 3) {
                        //per principal
                        if ($ll_relapri == 24) {
                            MemberBenefits::create([
                                'member_no' => $member_no,
                                'benefit' => $benefits->benefit,
                                'limit' => $lc_limits,
                                'sharing' => $benefits->sharing,
                                'anniv' => $anniv,
                                'sub_limit_of' => $benefits->sub_limit_of,
                                'fund' => $benefits->fund,
                                'quantity' => $benefits->quantity,
                                'product_name' => $benefits->product_name,
                                'category' => $benefits->category,
                                'smart_sync' => 1,
                                'sync' => 0
                            ]);
                        }
                    } elseif ($ll_share == 4) {
                        //per dependant
                        if ($ll_relapri == 25) {
                            MemberBenefits::create([
                                'member_no' => $member_no,
                                'benefit' => $benefits->benefit,
                                'limit' => $lc_limits,
                                'sharing' => $benefits->sharing,
                                'anniv' => $anniv,
                                'sub_limit_of' => $benefits->sub_limit_of,
                                'fund' => $benefits->fund,
                                'quantity' => $benefits->quantity,
                                'product_name' => $benefits->product_name,
                                'category' => $benefits->category,
                                'smart_sync' => 1,
                                'sync' => 0
                            ]);
                        }
                    } elseif ($ll_share == 5) {
                        //per spouse
                        if ($ll_famrela == 4) {
                            MemberBenefits::create([
                                'member_no' => $member_no,
                                'benefit' => $benefits->benefit,
                                'limit' => $lc_limits,
                                'sharing' => $benefits->sharing,
                                'anniv' => $anniv,
                                'sub_limit_of' => $benefits->sub_limit_of,
                                'fund' => $benefits->fund,
                                'quantity' => $benefits->quantity,
                                'product_name' => $benefits->product_name,
                                'category' => $benefits->category,
                                'smart_sync' => 1,
                                'sync' => 0
                            ]);
                        }
                    } elseif ($ll_share == 6) {
                        //per child
                        if ($ll_age < 18) {
                            MemberBenefits::create([
                                'member_no' => $member_no,
                                'benefit' => $benefits->benefit,
                                'limit' => $lc_limits,
                                'sharing' => $benefits->sharing,
                                'anniv' => $anniv,
                                'sub_limit_of' => $benefits->sub_limit_of,
                                'fund' => $benefits->fund,
                                'quantity' => $benefits->quantity,
                                'product_name' => $benefits->product_name,
                                'category' => $benefits->category,
                                'smart_sync' => 1,
                                'sync' => 0
                            ]);
                        }
                    } elseif ($ll_share == 7) {
                        //per adult
                        if ($ll_age > 17) {
                            MemberBenefits::create([
                                'member_no' => $member_no,
                                'benefit' => $benefits->benefit,
                                'limit' => $lc_limits,
                                'sharing' => $benefits->sharing,
                                'anniv' => $anniv,
                                'sub_limit_of' => $benefits->sub_limit_of,
                                'fund' => $benefits->fund,
                                'quantity' => $benefits->quantity,
                                'product_name' => $benefits->product_name,
                                'category' => $benefits->category,
                                'smart_sync' => 1,
                                'sync' => 0
                            ]);
                        }
                    } elseif ($ll_share == 8) {
                        //per principal/spouse
                        if ($ll_famrela == 4 || $ll_famrela == 3) {
                            MemberBenefits::create([
                                'member_no' => $member_no,
                                'benefit' => $benefits->benefit,
                                'limit' => $lc_limits,
                                'sharing' => $benefits->sharing,
                                'anniv' => $anniv,
                                'sub_limit_of' => $benefits->sub_limit_of,
                                'fund' => $benefits->fund,
                                'quantity' => $benefits->quantity,
                                'product_name' => $benefits->product_name,
                                'category' => $benefits->category,
                                'smart_sync' => 1,
                                'sync' => 0
                            ]);
                        }
                    }
                }
            }
        }
        //save in product change
        if ($ll_sync == 1) {
            ProductChange::create([
                'rec_id' => $max_rec_id,
                'member_no' => $member_no,
                'corp_id' => $corp_id,
                'anniv' => $anniv,
                'prev_hplan' => $request->input('current_health_plan'),
                'prev_poption' => $request->input('h_option'),
                'cur_hplan' => $request->input('new_health_plan'),
                'cur_poption' => $request->input('new_product'),
                'create_date' => $today,
                'user_id' => $request->user,
                'smart_sync' => 1,
                'sync' => 0
            ]);
        } else {
            ProductChange::create([
                'rec_id' => $max_rec_id,
                'member_no' => $member_no,
                'corp_id' => $corp_id,
                'anniv' => $anniv,
                'prev_hplan' => $request->input('current_health_plan'),
                'prev_poption' => $request->input('h_option'),
                'cur_hplan' => $request->input('new_health_plan'),
                'cur_poption' => $request->input('new_product'),
                'create_date' => $today,
                'user_id' => $request->user,
                'smart_sync' => 1,
                'sync' => 0
            ]);
        }
        //update member anniversary
        MemberAnniversary::where('member_no', $member_no)
            ->where('anniv', $anniv)
            ->update([
                'health_plan' => $request->input('new_health_plan'),
                'h_option' => $request->input('new_product'),
                'smart_sync' => 1,
                'sync' => 0
            ]);


        array_push($response, "Product Change Successfully");

        return $response;
    }
}
