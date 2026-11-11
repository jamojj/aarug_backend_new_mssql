<?php

namespace App\Http\Controllers\Registration\MemberManagement;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\FamilySize;
use App\Models\HaisSmartTriggers;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use App\Models\Registration\MemberAcceptance;
use App\Models\Registration\MemberAllergy;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberCancellation;
use App\Models\Registration\MemberExclusions;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\MemberMedical;
use App\Models\Registration\ReorderedPhotocards;
use Exception;
use Illuminate\Support\Facades\DB;
use PDOException;

class UnderwriteMemberController extends Controller
{
    public function fetchCorporate($corp_id)
    {
        $max_anniv = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');
        $corp = Corporate::select('corporate.corp_id', 'corporate.corporate', 't_client_type.code', 't_client_type.client_type', 'corporate.individual', 'corp_anniversary.anniv', 'corp_anniversary.start_date', 'corp_anniversary.end_date', 'corp_anniversary.renewal_date', 'corporate.agent_id')
            ->leftJoin('t_client_type', 'corporate.individual', '=', 't_client_type.code')
            ->leftJoin('corp_anniversary', 'corporate.corp_id', '=', 'corp_anniversary.corp_id')
            ->where('corporate.corp_id', $corp_id)
            ->where('anniv', $max_anniv)
            ->get();

        $category = CorpGroups::select('category')->where('corp_id', $corp_id)->distinct('category')->get();
        $product_name = CorpGroups::select('t_product_name.code', 't_product_name.product_name')
            ->leftJoin('t_product_name', 't_product_name.code', '=', 'corp_groups.product_name')
            ->where('corp_id', $corp_id)
            ->distinct('product_name')
            ->get();
        $data = [
            "corp" => $corp,
            "category" => $category,
            "product_name" => $product_name
        ];
        return $data;
    }

    public function fetchPrincipalData(Request $request)
    {

        $principal = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ', principal_applicant.other_names) as full_name"),
            'principal_applicant.family_no',
            'principal_applicant.member_no',
            'corporate.corporate',
        )
            ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where('principal_applicant.corp_id', $request->corporate_main)
            ->where(function ($q) use ($request) {
                $q->where('principal_applicant.surname', 'like', '%' . $request->principal_name . '%')
                    ->orWhere('principal_applicant.first_name', 'like', '%' . $request->principal_name . '%')
                    ->orWhere('principal_applicant.other_names', 'like', '%' . $request->principal_name . '%');
            })->get();
        return  json_encode($principal, JSON_INVALID_UTF8_IGNORE);
    }

    public function fetchMemberData(Request $request)
    {
        //with member name and corporate
        if ($request->options === "1") {
            $member = MemberInfo::select(
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as full_name_member"),
                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ', principal_applicant.other_names) as full_name_principal"),
                'member_info.family_no',
                'member_info.member_no',
                'corporate.corporate',
            )
                ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->orderBy('corporate.corporate', 'ASC')
                ->orderBy('full_name_member', 'ASC')
                ->where(function ($q) use ($request) {
                    $q->where('member_info.surname', 'like', '%' . $request->member_name . '%')
                        ->orWhere('member_info.first_name', 'like', '%' . $request->member_name . '%')
                        ->orWhere('member_info.other_names', 'like', '%' . $request->member_name . '%');
                })
                ->where('principal_applicant.corp_id', $request->corporate_main)
                ->get();
            return  json_encode($member, JSON_INVALID_UTF8_IGNORE);
            //with member name alone
        } else {
            $member = MemberInfo::select(
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ', member_info.other_names) as full_name_member"),
                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ', principal_applicant.other_names) as full_name_principal"),
                'member_info.family_no',
                'member_info.member_no',
                'corporate.corporate',
            )
                ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->orderBy('corporate.corporate', 'ASC')
                ->orderBy('full_name_member', 'ASC')
                ->where(function ($q) use ($request) {
                    $q->where('member_info.surname', 'like', '%' . $request->member_name . '%')
                        ->orWhere('member_info.first_name', 'like', '%' . $request->member_name . '%')
                        ->orWhere('member_info.other_names', 'like', '%' . $request->member_name . '%');
                })->get();
            return  json_encode($member, JSON_INVALID_UTF8_IGNORE);
        }
    }


    public function fetchFamily($fam_no)
    {
        $today = date('Ymd');
        $family = MemberInfo::select(
            DB::raw("CONCAT(member_info.surname, ' ', member_info.first_name, ' ', member_info.other_names) as full_names"),
            DB::raw("isnull(member_info.cancelled, 0) as status"),
            DB::raw("max(isnull(member_anniversary.anniv, 0)) as mem_anniv"),
            DB::raw("max(isnull(corp_anniversary.anniv, 0)) as corp_anniv"),
            'member_info.family_no',
            'member_info.member_no',
            'principal_applicant.corp_id',
            'family_relation.relation',
            'member_info.family_title',
            'member_info.tel_no',
            'member_info.email',

        )
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
            ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', '=', 'principal_applicant.corp_id')
            ->leftJoin('family_relation', 'member_info.relation_to_principal', '=', 'family_relation.code')
            ->where('member_info.family_no', $fam_no)
            ->orderBy('member_info.member_no')
            ->groupBy([
                'member_info.member_no',
                'member_info.surname',
                'member_info.first_name',
                'member_info.other_names',
                'member_info.cancelled',
                'member_info.family_no',
                'member_info.member_no',
                'family_relation.relation',
                'member_info.family_title',
                'member_info.tel_no',
                'member_info.email',
                'principal_applicant.corp_id'
            ])
            ->get();

        foreach ($family as $fam) {
            $corp_end_date = CorpAnniversary::select(DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date"))->where('anniv', $fam->corp_anniv)->where('corp_id', $fam->corp_id)->get();
            $corp_end_date = count($corp_end_date) != 0 ? $corp_end_date[0]->end_date : '';
            $memb_dates = MemberAnniversary::select(
                DB::raw("FORMAT(start_date, 'yyyyMMdd') as start_date"),
                DB::raw("FORMAT(end_date, 'yyyyMMdd') as end_date")
            )->where('anniv', $fam->mem_anniv)->where('member_no', $fam->member_no)->get();
            $memb_start_date = count($memb_dates) != 0 ? $memb_dates[0]->start_date : '';
            $memb_end_date = count($memb_dates) != 0 ? $memb_dates[0]->end_date : '';
            $memb_status = MemberAcceptance::select('status')->where('member_no', $fam->member_no)->get();
            $memb_status = count($memb_status) != 0 ? $memb_status[0]->status : '';


            //check member status
            if ($memb_status == 2) {
                $fam->status = 'SUSPENDED';
            } elseif ($memb_status == 3) {
                $fam->status = 'REJECTED';
            } elseif ($memb_status == 4) {
                $fam->status = 'PENDING';
            } else {
                //check if member is cancelled
                if ($fam->status == 0) {                    
                    if ($memb_end_date < $today) {
                        $fam->status = 'EXPIRED';
                    } else {
                        if ($memb_start_date > $today) {
                            $fam->status = 'FUTURE START (' . date("d/m/Y", strtotime($memb_start_date)) . ')';
                        } else {
                            $fam->status = 'ACTIVE';
                        }
                    }
                } else {
                    $fam->status = 'CANCELLED';
                }
            }
        }
        return  json_encode($family, JSON_INVALID_UTF8_IGNORE);
    }

    public function fetchOnePrincipalData($member_no)
    {
        $arr = explode('-', $member_no);
        $family_no = $arr[0];
        $member_no = $arr[0] . '-00';
        $member_no_dependant = '';
        $start_date = $end_date = $renewal_date = "";
        $principal = PrincipalApplicant::select(
            'principal_applicant.FAMILY_NO',
            'principal_applicant.MEMBER_NO',
            'principal_applicant.SURNAME',
            'principal_applicant.FIRST_NAME',
            'principal_applicant.OTHER_NAMES',
            'principal_applicant.AGENT_ID',
            'principal_applicant.corp_id',
            'corporate.corporate',
            'principal_applicant.employer',
            'principal_applicant.TEL_NO',
            'principal_applicant.MOBILE_NO',
            'principal_applicant.POSTAL_ADD',
            'principal_applicant.town',
            'principal_applicant.EMAIL',
            'principal_applicant.PHY_LOC',
            'principal_applicant.relation_to_family',
            'principal_applicant.individual',
            'principal_applicant.date_form_received',
            'principal_applicant.witnessed',
            'principal_applicant.date_witnessed',
            'principal_applicant.user_id',
            'principal_applicant.date_entered',
            'principal_applicant.province',
            'principal_applicant.form_filled',
            'principal_applicant.department',
            'principal_applicant.idx',
            'principal_applicant.category',
            'principal_applicant.marital_status',
            'principal_applicant.date_employed',
            'principal_applicant.previous_insurer',
            'principal_applicant.period_insured',
            'principal_applicant.beneficiary',
            'principal_applicant.beneficiary_id',
            'principal_applicant.beneficiary_relation',
            'principal_applicant.FAMILY_SIZE',
            'principal_applicant.plan',
            'principal_applicant.country',
            'principal_applicant.sanctioned',
            't_product_name.product_name'
        )
            ->leftJoin('t_product_name', 't_product_name.code', '=', 'principal_applicant.plan')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where('principal_applicant.member_no', $member_no)
            ->get();

        $family_size = MemberInfo::where('family_no', $family_no)->count();
        //create member no
        if ($family_size < 10) {
            $member_no_dependant = $family_no . '-0' . $family_size;
        } else {
            $member_no_dependant = $family_no . '-' . $family_size;
        }
        //check if member no already exists and increment if exists
        $member_exists = MemberInfo::where('member_no', $member_no_dependant)->get();

        if (count($member_exists) !== 0) {
            $family_size += 1;
            if ($family_size < 10) {
                $member_no_dependant = $family_no . '-0' . $family_size;
            } else {
                $member_no_dependant = $family_no . '-' . $family_size;
            }
        }
        //check if member no exists a second time and assign max mem no + 1
        $member_exists = MemberInfo::where('member_no', $member_no_dependant)->get();
        if (count($member_exists) !== 0) {
            $max_member_no = MemberInfo::where('family_no', $family_no)->max('member_no');
            $max_no = explode("-", $max_member_no);
            $max_no = $max_no[1];
            $max_no += 1;

            if ($max_no < 10) {
                $member_no_dependant = $family_no . '-0' . $max_no;
            } else {
                $member_no_dependant = $family_no . '-' . $max_no;
            }
        }


        $family_size_label = FamilySize::select('fam_size')->where('fam_lives', $family_size)->get();
        $family_size_label = count($family_size_label) !== 0 ?  $family_size_label[0]->fam_size : '';
        $max_anniv_for_principal = MemberAnniversary::where('member_no', $member_no)->max('anniv');
        $principal_anniversary_details_for_retail_members = MemberAnniversary::select(
            'start_date',
            'end_date',
            'renewal_date'
        )
            ->where('member_no', $member_no)
            ->where('anniv', $max_anniv_for_principal)
            ->get();
        $start_date = count($principal_anniversary_details_for_retail_members) !== 0 ? $principal_anniversary_details_for_retail_members[0]->start_date : "";
        $end_date = count($principal_anniversary_details_for_retail_members) !== 0 ? $principal_anniversary_details_for_retail_members[0]->end_date : "";
        $renewal_date = count($principal_anniversary_details_for_retail_members) !== 0 ? $principal_anniversary_details_for_retail_members[0]->renewal_date : "";

        return ['principal' => $principal, 'member_no_dependant' => $member_no_dependant, 'family_size' => $family_size, 'family_size_label' => $family_size_label, 'start_date' => $start_date, 'end_date' => $end_date, 'renewal_date' => $renewal_date, 'anniv' => $max_anniv_for_principal];
    }

    public function fetchOneMemberData($member_no)
    {
        $principal_data = PrincipalApplicant::select('principal_applicant.corp_id', 'corporate.individual')
            ->join('member_info', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->join('corporate', 'principal_applicant.corp_id', '=', 'corporate.corp_id')
            ->where('member_info.member_no', $member_no)
            ->get();
        $corp_id = count($principal_data) != 0 ?  $principal_data[0]->corp_id  : '';
        $individual = count($principal_data) != 0 ?  $principal_data[0]->individual  : '';
        $member_info = MemberInfo::where('member_no', $member_no)->get();
        $member_medical = MemberMedical::select('member_medical_new.member_no', 'member_medical_new.conditiondt', 'illness.code', 'illness.illnes', 'member_medical_new.anniv')
            ->leftJoin('illness', 'member_medical_new.illnes', '=', 'illness.code')
            ->where('member_no', $member_no)->get();
        $member_acceptance = MemberAcceptance::where('member_no', $member_no)->get();
        $member_allergy = MemberAllergy::select('member_allergy.member_no', 'allergy.code', 'allergy.allergy')
            ->leftJoin('allergy', 'member_allergy.allergy', '=', 'allergy.code')
            ->where('member_no', $member_no)->get();
        $member_benefits = MemberBenefits::where('member_no', $member_no)->orderBy('anniv', 'DESC')->get();
        $member_exclusions = MemberExclusions::select('member_exclusions.member_no', 'clinical_diagnosis.clinical_diagnosis', 'member_exclusions.diagnosisdt', 'member_exclusions.exclfromdt', 'member_exclusions.excltodt', 'member_exclusions.permanent', 'member_exclusions.nov')
            ->leftJoin('clinical_diagnosis', 'member_exclusions.diag_code', 'clinical_diagnosis.diag_code')
            ->where('member_no', $member_no)->get();
        $max_anniv = MemberAnniversary::where('member_no', $member_no)->max('anniv');
        $member_anniversary = MemberAnniversary::where('member_no', $member_no)
            ->where('anniv', $max_anniv)
            ->get();
        $start_date = count($member_anniversary) !== 0 ? $member_anniversary[0]->start_date : "";
        $end_date = count($member_anniversary) !== 0 ? $member_anniversary[0]->end_date : "";
        $renewal_date = count($member_anniversary) !== 0 ? $member_anniversary[0]->renewal_date : "";
        //get previous anniversaries
        $member_prev_annivs = MemberAnniversary::where('member_no', $member_no)
            ->orderBy('anniv', 'DESC')
            ->whereNotIn('anniv', [$max_anniv])
            ->get();

        $smart_card_reprint = HaisSmartTriggers::where('trigger_ref', $member_no)->get();

        $cancellation = MemberCancellation::select(
            DB::raw("FORMAT(member_cancellation.date_can, 'yyyy-mm-dd') as date_can"),
            DB::raw("CASE WHEN  isnull(member_cancellation.cancelled, 0) = 1 
                THEN 'CANCELLED'
            WHEN isnull(member_cancellation.cancelled, 0) = 0 
                THEN 'ACTIVE'
            END as status"),
            'member_cancellation.member_no',
            'member_cancellation.anniv',
            'cancel_reason.cancel_reason'
        )
            ->leftJoin('cancel_reason', 'member_cancellation.reason', '=', 'cancel_reason.code')
            ->where('member_no', $member_no)
            ->orderBy('member_cancellation.date_can', 'DESC')
            ->orderBy('anniv', 'DESC')
            ->get();

        $data = [
            'member_info' => json_encode($member_info, JSON_INVALID_UTF8_IGNORE),
            'member_medical' => json_encode($member_medical, JSON_INVALID_UTF8_IGNORE),
            'member_acceptance' => json_encode($member_acceptance, JSON_INVALID_UTF8_IGNORE),
            'member_allergy' => json_encode($member_allergy, JSON_INVALID_UTF8_IGNORE),
            'member_benefits' => json_encode($member_benefits, JSON_INVALID_UTF8_IGNORE),
            'member_exclusions' => json_encode($member_exclusions, JSON_INVALID_UTF8_IGNORE),
            'smart_card_reprint' => json_encode($smart_card_reprint, JSON_INVALID_UTF8_IGNORE),
            'member_anniversary' => json_encode($member_anniversary, JSON_INVALID_UTF8_IGNORE),
            'member_prev_annivs' => json_encode($member_prev_annivs, JSON_INVALID_UTF8_IGNORE),
            'cancellation' => json_encode($cancellation, JSON_INVALID_UTF8_IGNORE),
            'corp_id' => $corp_id,
            'individual' => $individual,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'renewal_date' => $renewal_date,
            'anniv' => $max_anniv
        ];

        return $data;
    }

    public function saveMember(Request $request)
    {
        // return $request;
        $date_entered = date("Y-m-d");
        $max_idx = PrincipalApplicant::whereNotNull('idx')->max('idx');
        //CHECK IF MEMBER EXISTS
        $dublicate_member = MemberInfo::where('surname', $request->surname)
            ->where('first_name', $request->first_name)
            ->where('other_names', $request->other_names)
            ->where('dob', $request->dob)
            ->get();
        if (count($dublicate_member) === 0 || $request->save_anyway == 1) {
            switch ($request->module) {
                case 'principal':
                    //principal applicant
                    try {
                        DB::transaction(function () use ($date_entered, $max_idx, $request) {
                            PrincipalApplicant::create([
                                'family_no' => $request->family_no,
                                'member_no' => $request->member_no,
                                'surname' => $request->surname,
                                'first_name' => $request->first_name,
                                'other_names' => $request->other_names,
                                'agent_id' => $request->agent_id,
                                'corp_id' => $request->corp_id,
                                'tel_no' => $request->tel_no,
                                'mobile_no' => $request->mobile_no,
                                'postal_add' => $request->postal_add,
                                'email' => $request->email,
                                'phy_loc' => $request->phy_loc,
                                'relation_to_family' => (int)$request->relation_to_principal,
                                'user_id' => $request->underwriter,
                                'date_entered' => $date_entered,
                                'idx' => $max_idx + 1,
                                'category' => $request->health_plan,
                                'marital_status' => (int)$request->marital_status,
                                'family_size' => 1,
                                'plan' => (int)$request->option,
                                'individual' => (int)$request->individual,
                                // 'smart_sync' => 1,
                                // 'sync' => 0
                            ]);
                            $this->saveFunc($request, $date_entered);
                        });
                        return ['Principal saved successfully'];
                    } catch (Exception $e) {
                        return ['Error ' . $e->getMessage()];
                    }
                    break;
                case 'dependant':
                    try {
                        DB::transaction(function () use ($date_entered, $request) {
                            $fam_size = MemberInfo::where('family_no', $request->family_no)->count();
                            PrincipalApplicant::where('family_no', $request->family_no)
                                ->update([
                                    'family_size' => $fam_size + 1,
                                    'smart_sync' => 1,
                                    'sync' => 0
                                ]);
                            $this->saveFunc($request, $date_entered);
                        });
                        return ['Dependant saved successfully'];
                    } catch (Exception $e) {
                        return ['Error ' . $e->getMessage()];
                    }
                    break;
                case 'reorder_photocards':
                    try {
                        DB::transaction(function () use ($date_entered, $request) {
                            ReorderedPhotocards::create([
                                'member_no' => $request->member_no_reorder,
                                'info_to_printer' => $request->info_to_printer_reorder,
                                'card_from_printer' => $request->card_from_printer_reorder,
                                'card_to_member' => $request->card_to_member_reorder,
                                'anniv' => (int)$request->anniv_reorder,
                                'user_id' => $request->user_reorder,
                                'date_entered' => $date_entered,
                                'reorder_reason' => (int)$request->reason_reorder,
                                'serial_no' => $request->serial_no_reorder,
                                'print_date' => $request->member_no_reorder,
                                'smart_sync' => 1,
                                'sync' => 0
                                // 'smart_sync' => (int)$request->smart_sync_reorder,
                                // 'sync' => (int)$request->sync_reorder
                            ]);
                        });
                        return ['Photocards reordered successfully'];
                    } catch (Exception $e) {
                        return ['Error ' . $e->getMessage()];
                    }
                    break;
            }
        } else {
            return ['Dublicate. Member with same names and dob already exists!'];
        }
    }

    public function saveFunc($request, $date_entered)
    {
        //member info
        MemberInfo::create([
            'family_no' => $request->family_no_personal,
            'member_no' => $request->member_no_personal,
            'surname' => $request->surname_personal,
            'first_name' => $request->first_name_personal,
            'other_names' => $request->other_names_personal,
            'dob' => $request->dob,
            'occupation' => (int)$request->occupation,
            'relation_to_principal' => (int)$request->relation_to_principal,
            'user_id' => $request->underwriter,
            'date_entered' => $date_entered,
            'family_title' => (int)$request->family_title,
            'employment_no' => $request->employment_no,
            'gender' => (int)$request->gender,
            'card_to_member' => $request->card_to_member,
            'blood_group' => (int)$request->blood_group,
            'height' => $request->height,
            'weight' => $request->weight,
            'passport_no' => $request->passport_no,
            'info_to_printer' => $request->info_to_printer,
            'card_from_printer' => $request->card_from_printer,
            'app_form_date' => $request->application_form_date,
            'card_serial_no' => $request->card_serial_no,
            'corp_id' => $request->corp_id,
            'family_size' => (int)$request->family_size,
            'join_date' => $request->start_date, //changed from join_date since they're the same
            'title' => (int)$request->title,
            'is_vip' => (int)$request->is_vip,
            'marital_status' => (int)$request->marital_status,
            'id_pp_no' => $request->idno,
            'email' => $request->email_personal,
            'tel_no' => $request->tel_no_personal,
            'hc_status' => $request->member_type
        ]);

        // member allergy
        if (isset($request->allergy)) {
            $allergies = array_unique($request->allergy);

            foreach ($allergies as $allergy) {
                MemberAllergy::create([
                    'member_no' => $request->member_no_personal,
                    'allergy' => (int)$allergy
                ]);
            }
        }
        //member benefits
        if ($request->corp_id == "14") {
            if (isset($request->benefit_retail)) {
                for ($i = 0; $i < count($request->member_no_benefit_retail); $i++) {
                    $benefit_exists = MemberBenefits::where('anniv', $request->anniv_retail[$i])
                        ->where('member_no', $request->member_no_benefit_retail[$i])
                        ->where('benefit', $request->benefit_retail[$i])
                        ->get();
                    if (count($benefit_exists) === 0) {
                        MemberBenefits::create([
                            'member_no' => $request->member_no_benefit_retail[$i],
                            'benefit' => $request->benefit_retail[$i],
                            'limit' => $request->limit_retail[$i],
                            'sharing' => $request->sharing_retail[$i],
                            'anniv' => $request->anniv_retail[$i],
                            'sub_limit_of' => $request->sub_limit_of_retail[$i],
                            'suspended' => $request->suspend_at_retail[$i],
                            'suspended_date' => $request->suspend_date_retail[$i],
                            'capitated' => $request->capitated_retail[$i],
                            'fund' => $request->fund_retail[$i],
                            'quantity' => $request->quantity_retail[$i],
                            'product_name' => $request->product_name_benefit_retail[$i],
                            'prorata_days' => $request->prorata_days_retail[$i],
                            'category' => $request->category_benefit_retail[$i],
                            'waiting_period' => $request->waiting_period_retail[$i],
                            // 'smart_sync' => 1,
                            // 'sync' => 0
                        ]);
                    }
                }
            }
        } else {
            if (isset($request->benefit)) {
                for ($i = 0; $i < count($request->member_no_benefit); $i++) {
                    $benefit_exists = MemberBenefits::where('anniv', $request->anniv[$i])
                        ->where('member_no', $request->member_no_benefit[$i])
                        ->where('benefit', $request->benefit[$i])
                        ->get();
                    if (count($benefit_exists) === 0) {
                        MemberBenefits::create([
                            'member_no' => $request->member_no_benefit[$i],
                            'benefit' => $request->benefit[$i],
                            'limit' => $request->limit[$i],
                            'sharing' => $request->sharing[$i],
                            'anniv' => $request->anniv[$i],
                            'sub_limit_of' => $request->sub_limit_of[$i],
                            'suspended' => $request->suspend_at[$i],
                            'suspended_date' => $request->suspend_date[$i],
                            'capitated' => $request->capitated[$i],
                            'fund' => $request->fund[$i],
                            'quantity' => $request->quantity[$i],
                            'product_name' => $request->product_name_benefit[$i],
                            'prorata_days' => $request->prorata_days[$i],
                            'category' => $request->category_benefit[$i],
                            'waiting_period' => $request->waiting_period[$i],
                            // 'smart_sync' => 1,
                            // 'sync' => 0
                        ]);
                    }
                }
            }
        }

        // member medical
        if (isset($request->illness)) {
            for ($i = 0; $i < count($request->illness); $i++) {

                $exists = MemberMedical::where('member_no', $request->member_no_illness[$i])
                    ->where('illnes', (int)$request->illness[$i])
                    ->exists();

                if (!$exists) {
                    MemberMedical::create([
                        'member_no' => $request->member_no_illness[$i],
                        'anniv' => (int)$request->anniv_illness[$i],
                        'illnes' => (int)$request->illness[$i],
                        'conditiondt' => $request->condition_date[$i]
                    ]);
                }
            }
        }
        // member exclusion
        if (isset($request->diagnosis)) {
            for ($i = 0; $i < count($request->member_no_diagnosis); $i++) {

                $exists = MemberExclusions::where('member_no', $request->member_no_diagnosis[$i])
                    ->where('diag_code', $request->diagnosis[$i])
                    ->exists();

                if (!$exists) {
                    MemberExclusions::create([
                        'member_no' => $request->member_no_diagnosis[$i],
                        'diag_code' => $request->diagnosis[$i],
                        'diagnosisdt' => $request->diagnosis_date[$i],
                        'exclfromdt' => $request->excl_from[$i],
                        'excltodt' => $request->excl_to[$i],
                        'permanent' => (int)$request->permanent[$i]
                    ]);
                }
            }
        }
        //member acceptance 
        MemberAcceptance::create([
            'MEMBER_NO' => $request->member_no_vetting,
            // 'STATUS' => (int)$request->vetting_status,
            'STATUS' => 4, // 4 is pending 
            'STATUS_DATE' => $request->status_date,
            'USER_ID' => $request->underwriter,
            'DATE_ENTERED' => $date_entered,
            'COMMENTS' => $request->comment,
            'DEF_REJ' => (int)$request->reason,
            'STATUS_AFTER_APPROVAL' => (int)$request->vetting_status
        ]);
        //member activation
        MemberAnniversary::create([
            'member_no' => $request->member_no_activation,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'renewal_date' => $request->renewal_date,
            'anniv' => $request->activation_anniv,
            'user_id' => $request->underwriter,
            'date_entered' => $date_entered,
            'sync' => $request->sync,
            'smart_sync' => $request->smart_sync,
            'health_plan' => $request->activation_health_plan,
            'h_option' => $request->activation_option,
            'smart_sync' => 1,
            'sync' => 0
        ]);
    }
}
