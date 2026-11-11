<?php

namespace App\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Allergy;
use App\Models\Benefit;
use App\Models\BenefitSharing;
use App\Models\BloodGroup;
use App\Models\CardReprintReason;
use App\Models\Category;
use App\Models\ClinicalDiagnosis;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\FamilyRelation;
use App\Models\Illness;
use App\Models\MemberDifferReject;
use App\Models\Occupation;
use App\Models\ProductName;
use App\Models\Products;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberCancellation;
use App\Models\Registration\PrincipalApplicant;
use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberDropdownsController extends Controller
{
    public function memberDropdowns()
    {
        $clinical_diag = array();

        $corp = Corporate::select('corp_id', 'corporate')
            // ->whereNull('cancelled')
            // ->orWhere('cancelled', 0)
            ->orderBy('corporate')
            ->get();
        $card_reprint_reason = CardReprintReason::all();
        $benefit_sharing = BenefitSharing::all();
        $benefits = Benefit::select('benefit', 'code')->get();
        $max_idx = PrincipalApplicant::whereNotNull('idx')->max('idx');
        $max_idx =  $max_idx == null || $max_idx == 0 ? 1 : $max_idx;
        $healthPlan = Category::all();
        $product_name = ProductName::select('code', 'product_name')->get();
        $agent = Agent::select('agent_id', 'agent_name')->get();
        $occupation = Occupation::all();
        $family_title = FamilyRelation::select('code', 'relation')->whereIn('code', [3, 4, 5, 6, 7, 26])->get();
        $title = Title::all();
        $blood_group = BloodGroup::all();
        $allergy = Allergy::all();
        $reason = MemberDifferReject::orderBy('REASON')->get();
        $illness = Illness::orderBy('illnes')->whereNotNull('illnes')->get();
        $clinical_diagnosis = ClinicalDiagnosis::select('clinical_diagnosis', 'idx', 'diag_code')
            ->where('clinical_diagnosis', '!=', '')
            ->where('diag_code', '!=', '')
            ->orderBy('clinical_diagnosis', 'ASC')
            ->get();
        foreach ($clinical_diagnosis as $cd) {
            $arr = [
                'idx' => $cd->idx,
                'diag_code' => $cd->diag_code,
                'clinical_diagnosis' => mb_convert_encoding($cd->clinical_diagnosis, 'UTF-8')
            ];
            array_push($clinical_diag, $arr);
        }
        $data = [
            'max_id' => $max_idx,
            'benefit_sharing' => $benefit_sharing,
            'health_plan' => $healthPlan,
            'product_name' => $product_name,
            'corporate' => $corp,
            'agent' => $agent,
            'occupation' => $occupation,
            'family_title' => $family_title,
            'title' => $title,
            'blood_group' => $blood_group,
            'allergy' => $allergy,
            'reason' => $reason,
            'illness' => $illness,
            'clinical_diagnosis' => $clinical_diag,
            'benefits' => $benefits,
            'card_reprint_reason' => $card_reprint_reason
        ];
        return $data;
    }

    public function searchDiagnosis($searchText)
    {
        $diagnosis = ClinicalDiagnosis::select(
            'clinical_diagnosis',
            'idx',
            'diag_code'
        )
            ->where('diag_code', $searchText)
            ->orWhere('clinical_diagnosis', 'like', '%' . $searchText . '%')
            ->get();
        $diagnosis = json_encode($diagnosis, JSON_INVALID_UTF8_IGNORE);
        return $diagnosis;
    }

    public function getOptions($healthPlan)
    {
        $options = Products::select('t_products.product_name as code', 't_product_name.product_name')
            ->leftJoin('t_product_name', 't_products.product_name', '=', 't_product_name.code')
            ->distinct('t_products.product_name')
            ->where('t_products.health_plan', $healthPlan)->get();
        return $options;
    }

    public function getOptionsForCorporate(Request $request)
    {
        // return $request;
        $max_anniv = CorpAnniversary::where('corp_id', $request->corp_id)->max('anniv');

        $options = CorpGroups::select('t_product_name.code', 't_product_name.product_name')
            ->join('t_product_name', 'corp_groups.product_name', '=', 't_product_name.code')
            ->where('corp_groups.category', $request->healthPlan)
            ->where('corp_groups.anniv', $max_anniv)
            ->where('corp_groups.corp_id', $request->corp_id)
            ->distinct('product_name')
            ->get();

        return $options;
    }


    public function getBenefits(Request $request)
    {
        $benefits = [];
        //for retail members
        if ($request->corp_id == 14) {
            if ($request->same_benefits == "true") {
                $benefits = MemberBenefits::select('member_benefits.category', 'member_benefits.product_name as product_code', 'member_benefits.benefit as benefit_code', 'member_benefits.sub_limit_of', 'member_benefits.limit', 'member_benefits.sharing as sharing_code', 'member_benefits.anniv', 'member_benefits.fund', 'member_benefits.capitated', 'member_benefits.suspend_at', 'member_benefits.quantity', 'member_benefits.waiting_period', 't_product_name.product_name', 'benefit.benefit', 'benefit_sharing.sharing')
                    ->leftJoin('t_product_name', 'member_benefits.product_name', '=', 't_product_name.code')
                    ->leftJoin('benefit', 'member_benefits.benefit', '=', 'benefit.code')
                    ->leftJoin('benefit_sharing', 'member_benefits.sharing', '=', 'benefit_sharing.code')
                    ->where('member_benefits.category', $request->health_plan)
                    ->where('member_benefits.product_name', $request->options)
                    ->where('member_benefits.member_no', $request->member_no)
                    ->where('member_benefits.anniv', $request->anniv)
                    ->get();               
            }else{
                $benefits = CorpGroups::select('corp_groups.category', 'corp_groups.product_name as product_code', 'corp_groups.benefit as benefit_code', 'corp_groups.sub_limit_of', 'corp_groups.limit', 'corp_groups.sharing as sharing_code', 'corp_groups.anniv', 'corp_groups.fund', 'corp_groups.capitated', 'corp_groups.suspend_at', 'corp_groups.quantity', 'corp_groups.waiting_period', 't_product_name.product_name', 'benefit.benefit', 'benefit_sharing.sharing')
                    ->leftJoin('t_product_name', 'corp_groups.product_name', '=', 't_product_name.code')
                    ->leftJoin('benefit', 'corp_groups.benefit', '=', 'benefit.code')
                    ->leftJoin('benefit_sharing', 'corp_groups.sharing', '=', 'benefit_sharing.code')
                    ->where('corp_groups.category', $request->health_plan)
                    ->where('corp_groups.product_name', $request->options)
                    ->where('corp_groups.corp_id', $request->corp_id)
                    ->get();
                    foreach ($benefits as $bene) {
                       $bene->anniv = $request->anniv;
                    }
            }
            //for corporate
        } else {
            $benefits = CorpGroups::select('corp_groups.category', 'corp_groups.product_name as product_code', 'corp_groups.benefit as benefit_code', 'corp_groups.sub_limit_of', 'corp_groups.limit', 'corp_groups.sharing as sharing_code', 'corp_groups.anniv', 'corp_groups.fund', 'corp_groups.capitated', 'corp_groups.suspend_at', 'corp_groups.quantity', 'corp_groups.waiting_period', 't_product_name.product_name', 'benefit.benefit', 'benefit_sharing.sharing')
                ->leftJoin('t_product_name', 'corp_groups.product_name', '=', 't_product_name.code')
                ->leftJoin('benefit', 'corp_groups.benefit', '=', 'benefit.code')
                ->leftJoin('benefit_sharing', 'corp_groups.sharing', '=', 'benefit_sharing.code')
                ->where('corp_groups.category', $request->health_plan)
                ->where('corp_groups.product_name', $request->options)
                ->where('corp_groups.corp_id', $request->corp_id)
                ->where('corp_groups.anniv', $request->anniv)
                ->get();
        }

        return $benefits;
    }

    public function fetchAnnivsForMember($member_no)
    {
        $annivs = MemberAnniversary::select('anniv')->where('member_no', $member_no)->orderBy('anniv', 'ASC')->get();
        return $annivs;
    }
}
