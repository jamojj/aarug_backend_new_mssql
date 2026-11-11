<?php

namespace App\Http\Controllers\Corporate;

use App\Http\Controllers\Controller;
use App\Models\ClientRates;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\Corporate\PolicyDocument;
use App\Models\ProductName;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\User;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PolicyDocumentController extends Controller
{
    //fetch policy document number for corporate
    public function fetchPolicyDocs($client, $type)
    {
        $policy_docs = [];

        if ($type === "retail" || $type === "hospital_cash" || $type === "kitanda" || $type === "munomukabi_retail" || $type === "bancasurance_retail") {
            $policy_docs = PolicyDocument::select('policy_no')
                ->where('member_no', $client)
                ->where('deleted', 0)
                ->get();
        } else if ($type === "corporate" || $type === "sme" || $type === "munomukabi_corporate" || $type === "bancasurance_corporate") {
            $policy_docs = PolicyDocument::select('policy_no')
                ->where('corp_id', $client)
                ->where('deleted', 0)
                ->get();
        } else if ($type == 'kitanda_corporate') {
            $policy_docs = PolicyDocument::select('policy_no')
                ->where('policy_no', 'like', 'KITANDA%')
                ->where('corp_id', $client)
                ->where('deleted', 0)
                ->get();
        } else if ($type == 'liberty_blue') {
            $policy_docs = PolicyDocument::select('policy_no')
                ->where(function ($q) {
                    $q->where('policy_no', 'like', 'LIBERTYBLUE%')
                        ->orWhere('policy_no', 'like', '%AARBLUE%');
                })
                ->where('corp_id', $client)
                ->where('deleted', 0)
                ->get();
        }

        return $policy_docs;
    }
    //fetch specific policy document data
    public function fetchPolicyDocData($policy_no)
    {
        $data = PolicyDocument::where('policy_no', $policy_no)->get();
        return $data[0];
    }
    //fetch specific policy schedule hospital cash data
    public function fetchPolicyDocDataHospitalCash($policy_no)
    {
        $policy_data = [];
        $data = PolicyDocument::where('policy_no', $policy_no)->get();
        foreach ($data as $dt) {
            //start and end date
            $cover_dates = MemberAnniversary::select(
                DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
            )
                ->where('anniv', $dt->anniv)
                ->where('member_no', $dt->member_no)
                ->get();

            $start_date = count($cover_dates) != 0 ? $cover_dates[0]->start_date : '';
            $end_date = count($cover_dates) != 0 ? $cover_dates[0]->end_date : '';

            //client address
            $client_address = PrincipalApplicant::select('postal_add', 'tel_no', 'email')
                ->where('member_no', $dt->member_no)
                ->get();
            $address = count($client_address) !== 0 ? $client_address[0]->postal_add : '';
            $tel_no = count($client_address) !== 0 ? $client_address[0]->tel_no : '';
            $email = count($client_address) !== 0 ? $client_address[0]->email : '';

            $policy_data = [
                'policy_no' => $dt->policy_no,
                'health_plan' => $dt->health_plan,
                'member_no' => $dt->member_no,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'address' => $address,
                'tel_no' => $tel_no,
                'email' => $email,
                'zone' => $dt->zone,
                'overall_annual_limit' => $dt->overall_annual_limit,
                'lives' => $dt->lives,
                'client_premium' => $dt->client_premium,
                'hospital_cash_limit_per_night' => ($dt->overall_annual_limit / 10),
                'max_benefit_nights_per_year' => '10',
                'hospital_cash_pay_out' => '15 days',
                'maternity_cover' => $dt->maternity_cover,
                'enroll_one' => $dt->enroll_one,
                'enroll_two' => $dt->enroll_two,
                'enroll_three' => $dt->enroll_three,
                'enroll_four' => $dt->enroll_four,
                'enroll_five' => $dt->enroll_five

            ];
        }


        return $policy_data;
    }
    //fetch specific policy schedule kitanda
    public function fetchPolicyDocDataKitanda($policy_no)
    {
        $policy_data = [];
        $data = PolicyDocument::where('policy_no', $policy_no)->get();
        foreach ($data as $dt) {
            //start and end date
            $cover_dates = MemberAnniversary::select(
                DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
            )
                ->where('anniv', $dt->anniv)
                ->where('member_no', $dt->member_no)
                ->get();

            $start_date = count($cover_dates) != 0 ? $cover_dates[0]->start_date : '';
            $end_date = count($cover_dates) != 0 ? $cover_dates[0]->end_date : '';

            //client address
            $client_address = PrincipalApplicant::select('postal_add', 'tel_no', 'email')
                ->where('member_no', $dt->member_no)
                ->get();
            $address = count($client_address) !== 0 ? $client_address[0]->postal_add : '';
            $tel_no = count($client_address) !== 0 ? $client_address[0]->tel_no : '';
            $email = count($client_address) !== 0 ? $client_address[0]->email : '';

            $policy_data = [
                'policy_no' => $dt->policy_no,
                'health_plan' => $dt->health_plan,
                'member_no' => $dt->member_no,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'address' => $address,
                'tel_no' => $tel_no,
                'email' => $email,
                'zone' => $dt->zone,
                'overall_annual_limit' => $dt->overall_annual_limit,
                'lives' => $dt->lives,
                'client_premium' => $dt->client_premium,
                'hospitalization_benefit' => $dt->hospitalization_benefit,
                'funeral' => $dt->funeral,
                'total_permanent_disability' => $dt->total_permanent_disability,
                'waiting_period' => $dt->waiting_period,
                'enhanced_corporate_annual_overall_limit' => $dt->enhanced_corporate_annual_overall_limit,
                'enroll_one' => $dt->enroll_one,
                'enroll_two' => $dt->enroll_two,
                'enroll_three' => $dt->enroll_three,
                'enroll_four' => $dt->enroll_four,
                'enroll_five' => $dt->enroll_five,
                'hospital_accommodation_and_nursing_care' => $dt->hospital_accommodation_and_nursing_care,
                'diagnostic_tests_mri_ct_and_blood_tests_during_admission' => $dt->diagnostic_tests_mri_ct_and_blood_tests_during_admission,
                'consultation_fees_for_surgeons_or_physicians_during_admission' => $dt->consultation_fees_for_surgeons_or_physicians_during_admission

            ];
        }


        return $policy_data;
    }
    //fetch specific policy schedule kitanda
    public function fetchPolicyDocDataKitandaCorporate($policy_no)
    {
        $policy_data = [];
        $data = PolicyDocument::where('policy_no', $policy_no)->get();
        foreach ($data as $dt) {
            //start and end date
            $cover_dates = CorpAnniversary::select(
                DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
            )
                ->where('anniv', $dt->anniv)
                ->where('corp_id', $dt->corp_id)
                ->get();

            $start_date = count($cover_dates) != 0 ? $cover_dates[0]->start_date : '';
            $end_date = count($cover_dates) != 0 ? $cover_dates[0]->end_date : '';

            //client address
            $client_address = Corporate::select('postal_add', 'tel_no', 'email')
                ->where('corp_id', $dt->corp_id)
                ->get();
            $address = count($client_address) !== 0 ? $client_address[0]->postal_add : '';
            $tel_no = count($client_address) !== 0 ? $client_address[0]->tel_no : '';
            $email = count($client_address) !== 0 ? $client_address[0]->email : '';

            $policy_data = [
                'policy_no' => $dt->policy_no,
                'health_plan' => $dt->health_plan,
                'member_no' => $dt->member_no,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'address' => $address,
                'tel_no' => $tel_no,
                'email' => $email,
                'zone' => $dt->zone,
                'overall_annual_limit' => $dt->overall_annual_limit,
                'lives' => $dt->lives,
                'client_premium' => $dt->client_premium,
                'hospitalization_benefit' => $dt->hospitalization_benefit,
                'funeral' => $dt->funeral,
                'total_permanent_disability' => $dt->total_permanent_disability,
                'waiting_period' => $dt->waiting_period,
                'enhanced_corporate_annual_overall_limit' => $dt->enhanced_corporate_annual_overall_limit,
                'enroll_one' => $dt->enroll_one,
                'enroll_two' => $dt->enroll_two,
                'enroll_three' => $dt->enroll_three,
                'enroll_four' => $dt->enroll_four,
                'enroll_five' => $dt->enroll_five,
                'hospital_accommodation_and_nursing_care' => $dt->hospital_accommodation_and_nursing_care,
                'diagnostic_tests_mri_ct_and_blood_tests_during_admission' => $dt->diagnostic_tests_mri_ct_and_blood_tests_during_admission,
                'consultation_fees_for_surgeons_or_physicians_during_admission' => $dt->consultation_fees_for_surgeons_or_physicians_during_admission

            ];
        }


        return $policy_data;
    }
    //fetch specific policy schedule munamukabi
    public function fetchPolicyDocDataMunamukabi($policy_no)
    {
        $policy_data = [];
        $data = PolicyDocument::where('policy_no', $policy_no)->get();
        foreach ($data as $dt) {
            $client_name = "";
            if (!is_null($dt->corp_id)) {
                $data = Corporate::select('corporate')->where('corp_id', $dt->corp_id)->get();
                $client_name = count($data) !== 0 ? $data[0]->corporate : "";
            } else if (!is_null($dt->member_no)) {
                $data = MemberInfo::select(
                    DB::raw("CONCAT(member_INFO.surname, ' ',MEMBER_INFO.first_name,' ',MEMBER_INFO.other_names) as names"),
                )
                    ->where('member_no', $dt->member_no)
                    ->orderBy('names', 'asc')
                    ->get();

                $client_name = count($data) !== 0 ? $data[0]->names : "";
            }
            $policy_data = [
                'policy_no' => $dt->policy_no,
                'health_plan' => $dt->health_plan,
                'client_name' => $client_name,
                'corp_id' => $dt->corp_id,
                'member_no' => $dt->member_no,
                'zone' => $dt->zone,
                'lives' => $dt->lives,
                'overall_annual_limit' => $dt->overall_annual_limit,
                'antenatal' => $dt->antenatal,
                'chronic_conditions_developed_on_cover' => $dt->chronic_conditions_developed_on_cove,
                'congenital_conditions' => $dt->congenital_and_genetic,
                'general_eye_examinations' => $dt->general_eye_examinations,
                'consultation_and_eye_treatment' => $dt->consultation_and_eye_treatment,
                'covid19_ip' => $dt->covid19_i,
                'covid19_op' => $dt->covid19_o,
                'dental_limit' => $dt->dental_op,
                'emergency_and_road' => $dt->emergency_and_road,
                'eye_treatment' => $dt->eye_treatment,
                'funeral' => $dt->funeral,
                'general_practitioners' => $dt->general_practitioners,
                'health_plan' => $dt->health_plan,
                'health_promotions' => $dt->health_promotions,
                'hiv_and_aids' => $dt->hiv_and_aids,
                'illness_accident_hospitalization' => $dt->illness_accident_hospitalization,
                'illness_hospitalization' => $dt->illness_hospitalization,
                'internal_prostheses' => $dt->internal_prostheses,
                'international_emergency_medical' => $dt->international_emergency_medical,
                'international_emergency_rescue' => $dt->international_emergency_rescue,
                'lens_frames_glasses' => $dt->lens_frames_glasses,
                'maternity' => $dt->maternity,
                'minor_surgeries' => $dt->minor_surgeries,
                'non_surgical_extractions' => $dt->non_surgical_extractions,
                'nursing_fees' => $dt->nursing_fees,
                'oncology_tests_drugs_and_consultation_op' => $dt->oncology_tests_drugs_and_consultation_op,
                'oncology_tests_drugs_and_consultation_ip' => $dt->oncology_tests_drugs_and_consultation_ip,
                'preexisting_conditions' => $dt->preexisting_conditions,
                'reconstructive_surgery' => $dt->reconstructive_surgery,
                'prescribed_medicines' => $dt->prescribed_medicines,
                'xray' => $dt->xray,
                'pathology' => $dt->pathology,
                'psychiatric_treatment' => $dt->psychiatric_treatmen,
                'physiotherapy' => $dt->physiotherapy,
                'parent_accomodation' => $dt->parent_accomodation,
                'congenital_and_genetics' => $dt->congenital_and_genetics,
                'inpatient_treatment_hiv' => $dt->inpatient_treatment_hiv,
                'outpatient_treatment_hiv' => $dt->outpatient_treatment_hiv,
                'evacuation' => $dt->evacuation,
                'maternity' => $dt->maternity,
                'delivery_postnatal_treatment' => $dt->delivery_postnatal_treatment,
                'premature_cover' => $dt->premature_cover,
                'new_born_baby_care' => $dt->new_born_baby_care,
                'complications_pregnancy' => $dt->complications_pregnancy,
                'overall_annual_outpatient' => $dt->overall_annual_outpatient,
                'antenatal_postnatal' => $dt->antenatal_postnatal,
                'preexisting_chronic_conditions' => $dt->preexisting_chronic_conditions,
                'post_hospitalization_treatment' => $dt->post_hospitalization_treatment,
                'outpatient_surgical_operations' => $dt->outpatient_surgical_operations,
                'optometry_limit' => $dt->optometry_limit,
                'simple_outer_surgeries' => $dt->simple_outer_surgeries,
                'visual_acuity_tests' => $dt->visual_acuity_tests,
                'lens_frames_glasses' => $dt->lens_frames_glasses,
                'dental_limit' => $dt->dental_limit,
                'consultation_and_treatment' => $dt->consultation_and_treatment,
                'extractions' => $dt->extractions,
                'infections' => $dt->infections,
                'fillings' => $dt->fillings,
                'root_canal' => $dt->root_canal,
                'xray_mri_petscan_ctscan' => $dt->xray_mri_petscan_ctscan,
                'non_surgical_extractions' => $dt->non_surgical_extractions,
                'scaling_polishing' => $dt->scaling_polishing,
                'annual_health_checks' => $dt->annual_health_check,
                'personal_accident_child' => $dt->personal_accident_chil,
                'personal_accident' => $dt->personal_accident,
                'funeral' => $dt->funeral,
                'maternity_cover' => $dt->maternity_cover,
                'chronics_development_on_cover' => $dt->chronics_development_on_cover,
                'admissions_icu' => $dt->admissions_icu,
                'room_limit' => $dt->room_limit,
                'general_surgery' => $dt->general_surgery,
                'pathology_diagnostic_tests' => $dt->general_surgery,
                'x_ray' => $dt->x_ray
            ];
        }


        return $policy_data;
    }
    //fetch specific policy schedule bancasurance
    public function fetchPolicyDocDataBancasurance($policy_no)
    {
        $policy_data = [];
        $data = PolicyDocument::where('policy_no', $policy_no)->get();
        foreach ($data as $dt) {
            $client_name = "";
            if (!is_null($dt->corp_id)) {
                $data = Corporate::select('corporate')->where('corp_id', $dt->corp_id)->get();
                $client_name = count($data) !== 0 ? $data[0]->corporate : "";
            } else if (!is_null($dt->member_no)) {
                $data = MemberInfo::select(
                    DB::raw("CONCAT(member_INFO.surname, ' ',MEMBER_INFO.first_name,' ',MEMBER_INFO.other_names) as names"),
                )
                    ->where('member_no', $dt->member_no)
                    ->orderBy('names', 'asc')
                    ->get();

                $client_name = count($data) !== 0 ? $data[0]->names : "";
            }
            $policy_data = [
                'policy_no' => $dt->policy_no,
                'health_plan' => $dt->health_plan,
                'client_name' => $client_name,
                'corp_id' => $dt->corp_id,
                'member_no' => $dt->member_no,
                'zone' => $dt->zone,
                'lives' => $dt->lives,
                'overall_annual_limit' => $dt->overall_annual_limit,
                'antenatal' => $dt->antenatal,
                'chronic_conditions_developed_on_cover' => $dt->chronic_conditions_developed_on_cove,
                'congenital_conditions' => $dt->congenital_and_genetic,
                'general_eye_examinations' => $dt->general_eye_examinations,
                'consultation_and_eye_treatment' => $dt->consultation_and_eye_treatment,
                'covid19_ip' => $dt->covid19_i,
                'covid19_op' => $dt->covid19_o,
                'dental_limit' => $dt->dental_op,
                'emergency_and_road' => $dt->emergency_and_road,
                'eye_treatment' => $dt->eye_treatment,
                'funeral' => $dt->funeral,
                'general_practitioners' => $dt->general_practitioners,
                'health_plan' => $dt->health_plan,
                'health_promotions' => $dt->health_promotions,
                'hiv_and_aids' => $dt->hiv_and_aids,
                'illness_accident_hospitalization' => $dt->illness_accident_hospitalization,
                'illness_hospitalization' => $dt->illness_hospitalization,
                'internal_prostheses' => $dt->internal_prostheses,
                'international_emergency_medical' => $dt->international_emergency_medical,
                'international_emergency_rescue' => $dt->international_emergency_rescue,
                'lens_frames_glasses' => $dt->lens_frames_glasses,
                'maternity' => $dt->maternity,
                'minor_surgeries' => $dt->minor_surgeries,
                'non_surgical_extractions' => $dt->non_surgical_extractions,
                'nursing_fees' => $dt->nursing_fees,
                'oncology_tests_drugs_and_consultation_op' => $dt->oncology_tests_drugs_and_consultation_op,
                'oncology_tests_drugs_and_consultation_ip' => $dt->oncology_tests_drugs_and_consultation_ip,
                'preexisting_conditions' => $dt->preexisting_conditions,
                'reconstructive_surgery' => $dt->reconstructive_surgery,
                'prescribed_medicines' => $dt->prescribed_medicines,
                'xray' => $dt->xray,
                'pathology' => $dt->pathology,
                'psychiatric_treatment' => $dt->psychiatric_treatmen,
                'physiotherapy' => $dt->physiotherapy,
                'parent_accomodation' => $dt->parent_accomodation,
                'congenital_and_genetics' => $dt->congenital_and_genetics,
                'inpatient_treatment_hiv' => $dt->inpatient_treatment_hiv,
                'outpatient_treatment_hiv' => $dt->outpatient_treatment_hiv,
                'evacuation' => $dt->evacuation,
                'maternity' => $dt->maternity,
                'delivery_postnatal_treatment' => $dt->delivery_postnatal_treatment,
                'premature_cover' => $dt->premature_cover,
                'new_born_baby_care' => $dt->new_born_baby_care,
                'complications_pregnancy' => $dt->complications_pregnancy,
                'overall_annual_outpatient' => $dt->overall_annual_outpatient,
                'antenatal_postnatal' => $dt->antenatal_postnatal,
                'preexisting_chronic_conditions' => $dt->preexisting_chronic_conditions,
                'post_hospitalization_treatment' => $dt->post_hospitalization_treatment,
                'outpatient_surgical_operations' => $dt->outpatient_surgical_operations,
                'optometry_limit' => $dt->optometry_limit,
                'simple_outer_surgeries' => $dt->simple_outer_surgeries,
                'visual_acuity_tests' => $dt->visual_acuity_tests,
                'lens_frames_glasses' => $dt->lens_frames_glasses,
                'dental_limit' => $dt->dental_limit,
                'consultation_and_treatment' => $dt->consultation_and_treatment,
                'extractions' => $dt->extractions,
                'infections' => $dt->infections,
                'fillings' => $dt->fillings,
                'root_canal' => $dt->root_canal,
                'xray_mri_petscan_ctscan' => $dt->xray_mri_petscan_ctscan,
                'non_surgical_extractions' => $dt->non_surgical_extractions,
                'scaling_polishing' => $dt->scaling_polishing,
                'annual_health_checks' => $dt->annual_health_check,
                'personal_accident_child' => $dt->personal_accident_chil,
                'personal_accident' => $dt->personal_accident,
                'funeral' => $dt->funeral,
                'maternity_cover' => $dt->maternity_cover,
                'chronics_development_on_cover' => $dt->chronics_development_on_cover,
                'admissions_icu' => $dt->admissions_icu,
                'room_limit' => $dt->room_limit,
                'general_surgery' => $dt->general_surgery,
                'pathology_diagnostic_tests' => $dt->general_surgery,
                'x_ray' => $dt->x_ray
            ];
        }


        return $policy_data;
    }

    //fetch corp or family categories 
    public function fetchCorpOrFamilyCategories(Request $request)
    {
        $categories = [];
        switch ($request->client_type) {
            case 'corporate':
                $categories = CorpGroups::select("category")
                    ->where('corp_id', $request->corporate)
                    ->where('anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
            case 'retail':
                $fam_no_arr = explode('-', $request->retail);
                $mem_nos = MemberInfo::where('family_no', $fam_no_arr[0])->pluck('member_no');
                $mem_nos = count($mem_nos) != 0 ? $mem_nos : [];

                $categories = MemberAnniversary::select("health_plan")
                    // ->where('member_no', $request->retail)
                    ->whereIn('member_no', $mem_nos)
                    ->where('anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
            case 'sme':
                $categories = CorpGroups::select("category")
                    ->where('corp_id', $request->sme)
                    ->where('anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
            case 'hospital_cash':
                $categories = MemberAnniversary::select("health_plan")
                    ->where('member_no', $request->hospital_cash_members)
                    ->where('anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
            case 'munomukabi':
                switch ($request->scheme) {
                    case '1':
                        $categories = CorpGroups::select("category as health_plan")
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->where('anniv', $request->annivs)
                            ->distinct()
                            ->get();
                        break;
                    case '2':
                        $categories = MemberAnniversary::select("health_plan")
                            ->where('member_no', $request->munomukabi_member)
                            ->where('anniv', $request->annivs)
                            ->distinct()
                            ->get();

                        break;
                }
                break;
            case 'bancasurance':
                switch ($request->scheme) {
                    case '1':
                        $categories = CorpGroups::select("category as health_plan")
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->where('anniv', $request->annivs)
                            ->distinct()
                            ->get();
                        break;
                    case '2':
                        $categories = MemberAnniversary::select("health_plan")
                            ->where('member_no', $request->bancasurance_member)
                            ->where('anniv', $request->annivs)
                            ->distinct()
                            ->get();

                        break;
                }
                break;
            case 'liberty_blue':
                $categories = CorpGroups::select("category")
                    ->where('corp_id', $request->scheme)
                    ->where('anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
        }

        return $categories;
    }

    public function fetchCorpOrFamilyProductNames(Request $request)
    {
        $product_names = [];
        switch ($request->client_type) {
            case 'corporate':
                $product_names = CorpGroups::select("t_product_name.code", "t_product_name.product_name")
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'corp_groups.product_name')
                    ->where('corp_groups.corp_id', $request->corporate)
                    ->where('corp_groups.category', $request->category)
                    ->where('corp_groups.anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
            case 'retail':
                $fam_no_arr = explode('-', $request->retail);
                $mem_nos = MemberInfo::where('family_no', $fam_no_arr[0])->pluck('member_no');
                $mem_nos = count($mem_nos) != 0 ? $mem_nos : [];

                $product_names = MemberAnniversary::select("t_product_name.code", "t_product_name.product_name")
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                    // ->where('member_anniversary.member_no', $request->retail)
                    ->whereIn('member_anniversary.member_no', $mem_nos)
                    ->where('member_anniversary.health_plan', $request->category)
                    ->where('member_anniversary.anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
            case 'sme':
                $product_names = CorpGroups::select("t_product_name.code", "t_product_name.product_name")
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'corp_groups.product_name')
                    ->where('corp_groups.corp_id', $request->sme)
                    ->where('corp_groups.category', $request->category)
                    ->where('corp_groups.anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
            case 'hospital_cash':
                $product_names = MemberAnniversary::select("t_product_name.code", "t_product_name.product_name")
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                    ->where('member_anniversary.member_no', $request->hospital_cash_members)
                    ->where('member_anniversary.health_plan', $request->category)
                    ->where('member_anniversary.anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
            case 'hospital_cash':
                $product_names = MemberAnniversary::select("t_product_name.code", "t_product_name.product_name")
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                    ->where('member_anniversary.member_no', $request->hospital_cash)
                    ->where('member_anniversary.health_plan', $request->category)
                    ->where('member_anniversary.anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
            case 'munomukabi':
                switch ($request->scheme) {
                    case '1':

                        $product_names = CorpGroups::select("t_product_name.code", "t_product_name.product_name")
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'corp_groups.product_name')
                            ->where('corp_groups.corp_id', $request->munomukabi_corporate)
                            ->where('corp_groups.category', $request->category)
                            ->where('corp_groups.anniv', $request->annivs)
                            ->distinct()
                            ->get();
                        break;
                    case '2':

                        $product_names = MemberAnniversary::select("t_product_name.code", "t_product_name.product_name")
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->where('member_anniversary.member_no', $request->munomukabi_member)
                            ->where('member_anniversary.health_plan', $request->category)
                            ->where('member_anniversary.anniv', $request->annivs)
                            ->distinct()
                            ->get();

                        break;
                }


                break;
            case 'bancasurance':
                switch ($request->scheme) {
                    case '1':

                        $product_names = CorpGroups::select("t_product_name.code", "t_product_name.product_name")
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'corp_groups.product_name')
                            ->where('corp_groups.corp_id', $request->bancasurance_corporate)
                            ->where('corp_groups.category', $request->category)
                            ->where('corp_groups.anniv', $request->annivs)
                            ->distinct()
                            ->get();
                        break;
                    case '2':

                        $product_names = MemberAnniversary::select("t_product_name.code", "t_product_name.product_name")
                            ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                            ->where('member_anniversary.member_no', $request->bancasurance_member)
                            ->where('member_anniversary.health_plan', $request->category)
                            ->where('member_anniversary.anniv', $request->annivs)
                            ->distinct()
                            ->get();

                        break;
                }


                break;
            case 'liberty_blue':
                $product_names = CorpGroups::select("t_product_name.code", "t_product_name.product_name")
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'corp_groups.product_name')
                    ->where('corp_groups.corp_id', $request->scheme)
                    ->where('corp_groups.category', $request->category)
                    ->where('corp_groups.anniv', $request->annivs)
                    ->distinct()
                    ->get();
                break;
        }

        return $product_names;
    }

    public function fetchPolicyDataCreate(Request $request)
    {
        // return $request;
        $data = [];
        switch ($request->client_type) {
            case 'corporate':
                //product name
                $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                $product_name = $product_name[0];
                $ls_hpname = $request->category . ' - ' . $product_name;

                //lives
                $mem_nos = PrincipalApplicant::join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->pluck('member_info.member_no');

                $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                    ->where('health_plan', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->count('member_no');

                //premium per life
                $lc_prem = ClientRates::where('health_plan', $request->category)
                 ->where('product_name', $request->product_name)
                    ->where('corp_id', $request->corporate)
                    ->pluck('premium');
                $lc_prem = count($lc_prem) != 0 ? $lc_prem[0] : 0;
                //overall limit
                $lc_overall_limit = CorpGroups::where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->max(DB::raw("isnull(limit,0)"));
                //accident hospitalization
                $lc_acc_hosp = CorpGroups::where('benefit', 1)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_acc_hosp = count($lc_acc_hosp) != 0 ? $lc_acc_hosp[0] : 0;
                //illness hospitalization
                $lc_ill_hosp = CorpGroups::where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->whereIn('benefit', [27, 28])
                    ->max(DB::raw("isnull(limit,0)"));
                //admissions chronic
                $lc_adm_care = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->where('benefit', 29)
                    ->max(DB::raw("isnull(limit,0)"));
                //admissions accident
                $lc_adm_care_acc = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->where('benefit', 30)
                    ->max(DB::raw("isnull(limit,0)"));
                //admissions illness
                $lc_adm_care_ill = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->where('benefit', 31)
                    ->max(DB::raw("isnull(limit,0)"));
                //room limit
                $lc_room_limit = CorpGroups::where('benefit', 49)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_room_limit = count($lc_room_limit) != 0 ? $lc_room_limit[0] : 0;
                //chronic
                $lc_chronic = CorpGroups::where('benefit', 7)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_chronic = count($lc_chronic) != 0 ? $lc_chronic[0] : 0;
                //recon surgery accident
                $lc_recon_surg = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->where('benefit', 45)
                    ->max(DB::raw("isnull(limit,0)"));
                //recon surgery illness
                $lc_recon_surg_illness = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->where('benefit', 46)
                    ->max(DB::raw("isnull(limit,0)"));
                //acc_dmg_eyes
                $lc_acc_eyes = CorpGroups::where('benefit', 2)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_acc_eyes = count($lc_acc_eyes) != 0 ? $lc_acc_eyes[0] : 0;
                //acc_dmg_teeth
                $lc_acc_teeth = CorpGroups::where('benefit', 3)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_acc_teeth = count($lc_acc_teeth) != 0 ? $lc_acc_teeth[0] : 0;
                //psych treat
                $lc_psych_treat = CorpGroups::where('benefit', 44)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_psych_treat = count($lc_psych_treat) != 0 ? $lc_psych_treat[0] : 0;
                //psych treat ip
                $lc_psych_treat_ip = CorpGroups::where('benefit', 53)
                    ->whereNotNull('limit')
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_psych_treat_ip = count($lc_psych_treat_ip) != 0 ? $lc_psych_treat_ip[0] : 0;
                //congenital
                $lc_cong = CorpGroups::where(
                    'benefit',
                    9
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_cong = count($lc_cong) != 0 ? $lc_cong[0] : 0;
                //congenital ip
                $lc_cong_ip = CorpGroups::where(
                    'benefit',
                    54
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->whereNotNull('limit')
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_cong_ip = count($lc_cong_ip) != 0 ? $lc_cong_ip[0] : 0;
                //hiv ip
                $lc_hiv_ip = CorpGroups::where(
                    'benefit',
                    25
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_hiv_ip = count($lc_hiv_ip) != 0 ? $lc_hiv_ip[0] : 0;
                //delivery and post natal before maternity c section benefit is 68
                $lc_csection = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->where('benefit', 68)
                    ->max(DB::raw("isnull(limit,0)"));

                //maternity normal delivery, before benefit = 34
                $lc_norm_deliv = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->where('benefit', 59)
                    ->max(DB::raw("isnull(limit,0)"));

                //premature
                $lc_premat = CorpGroups::where(
                    'benefit',
                    43
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_premat = count($lc_premat) != 0 ? $lc_premat[0] : 0;
                //op
                $lc_op = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->whereIn('benefit', [38, 39, 40])
                    ->max(DB::raw("isnull(limit,0)"));

                //chronic op
                $lc_op_chrnc = CorpGroups::where(
                    'benefit',
                    8
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_op_chrnc = count($lc_op_chrnc) != 0 ? $lc_op_chrnc[0] : 0;
                //antenatal op
                $lc_op_antenatal = CorpGroups::where(
                    'benefit',
                    5
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_op_antenatal = count($lc_op_antenatal) != 0 ? $lc_op_antenatal[0] : 0;
                //hiv op
                $lc_op_hiv = CorpGroups::where(
                    'benefit',
                    26
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_op_hiv = count($lc_op_hiv) != 0 ? $lc_op_hiv[0] : 0;

                //frames and lenses combined
                $lc_optical = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->whereIn('benefit', [36, 37])
                    ->max(DB::raw("isnull(limit,0)"));
                //frames only
                $lc_frames = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->whereIn('benefit', [88, 90])
                    ->max(DB::raw("isnull(limit,0)"));
                //lenses only
                $lc_lenses = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->whereIn('benefit', [89, 91])
                    ->max(DB::raw("isnull(limit,0)"));

                //dental
                $lc_dental = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->whereIn('benefit', [11, 12])
                    ->max(DB::raw("isnull(limit,0)"));
                //annual health check up
                $lc_checkup = CorpGroups::where(
                    'benefit',
                    4
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_checkup = count($lc_checkup) != 0 ? $lc_checkup[0] : 0;
                //Personal Accident with Permanent Total Disability
                $lc_ptd = CorpGroups::where(
                    'benefit',
                    42
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->corporate
                    )
                    ->pluck('limit');
                $lc_ptd = count($lc_ptd) != 0 ? $lc_ptd[0] : 0;
                //Personal Accident with Permanent Total Disability - child
                $lc_ptd_child = CorpGroups::where(
                    'benefit',
                    52
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->corporate
                    )
                    ->pluck('limit');
                $lc_ptd_child = count($lc_ptd_child) != 0 ? $lc_ptd_child[0] : 0;
                //last expense
                $lc_last_expense = CorpGroups::whereIn(
                    'benefit',
                    [20, 21]
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->corporate
                    )
                    ->pluck('limit');
                $lc_last_expense = count($lc_last_expense) != 0 ? $lc_last_expense[0] : 0;
                //covid19 ip
                $lc_covid19_ip = CorpGroups::where(
                    'benefit',
                    61
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->corporate
                    )
                    ->pluck('limit');
                $lc_covid19_ip = count($lc_covid19_ip) != 0 ? $lc_covid19_ip[0] : 0;
                //covid19 op
                $lc_covid19_op = CorpGroups::where(
                    'benefit',
                    60
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->corporate
                    )
                    ->pluck('limit');
                $lc_covid19_op = count($lc_covid19_op) != 0 ? $lc_covid19_op[0] : 0;
                //chronic acquired on cover
                $lc_chronic_cdl = CorpGroups::where('benefit', 55)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->corporate
                    )
                    ->pluck('limit');
                $lc_chronic_cdl = count($lc_chronic_cdl) != 0 ? $lc_chronic_cdl[0] : 0;
                //chronic acquired on cover
                $lc_op_chrnc_cdl = CorpGroups::where(
                    'benefit',
                    56
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $lc_op_chrnc_cdl = count($lc_op_chrnc_cdl) != 0 ? $lc_op_chrnc_cdl[0] : 0;

                //optical overall
                $optical_overall = CorpGroups::where(
                    'benefit',
                    83
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $optical_overall = count($optical_overall) != 0 ? $optical_overall[0] : 0;
                //consultation and eye treatment
                $consultation_and_eye_treatment = CorpGroups::where(
                    'benefit',
                    84
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $consultation_and_eye_treatment = count($consultation_and_eye_treatment) != 0 ? $consultation_and_eye_treatment[0] : 0;
                //Ophthalmology
                $ophthalmology  = CorpGroups::where(
                    'benefit',
                    57
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $ophthalmology = count($ophthalmology) != 0 ? $ophthalmology[0] : 0;
                //CONGENITAL, PREMATURE AND GENETIC CONDITION DEFECTS  
                 $congenital_premature_and_genetic_condition_defects  = CorpGroups::where(
                    'benefit',
                    141
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->pluck('limit');
                $congenital_premature_and_genetic_condition_defects = count($congenital_premature_and_genetic_condition_defects) != 0 ? $congenital_premature_and_genetic_condition_defects[0] : 0;
                //enrollment notes
                $ls_enrollone = '*All clients aged above 60 years will be subject to atleast 30% risk premium increment upto 65 yrs. The premium increment is subject to change depending on the clients health status and corporates medical scheme.';
                $ls_enrolltwo = '* The cost of the medex of those aged above 45 years will be borne by the client at a pre-negotiated cost of Ugx150,000 in a facility recommended by the AAR medical evaluator';
                $ls_enrollthree = '* Any extra tests required by AAR will be borne by the clients upon request from the Underwriter.';
                $ls_enrollfour = '*All benefits cannot exceed the overall annual limit.';
                $ls_enrollfive = 'All claims incurred in providers where AAR has no affiliate will be covered if pre-authourized and if reported with genuine receipts within 48 hrs.';
                $ls_enrollsix = 'Disclaimer: The benefits described in the table above are subject to the Policy Conditions in the AAR General Insurance Limited Cover Policy Document.';
                $ls_enrollseven = 'Every attempt has been made to ensure complete accuracy of this document.However, in the event of a conflict between this document and the AAR Health Cover Policy Conditions, the Policy Conditions will prevail.';
                $ls_enrollelleven = 'The respective benefit limit is the maximum limit of cover that will be considered based on the specific cause of the claim.';

                $data = [
                    'optometry_limit' => $lc_optical,
                    'anniv' => $request->annivs,
                    'lives' => $ll_lives,
                    'health_plan' => $ls_hpname,
                    'overall_annual_limit' => $lc_overall_limit,
                    'accident_hosp' => $lc_acc_hosp,
                    'illness_hosp' => $lc_ill_hosp,
                    'admissions_icu' => $lc_adm_care_ill,
                    'room_limit' => $lc_room_limit,
                    'preexisting_cond' => $lc_chronic,
                    'reconstructive_surgery' => $lc_recon_surg_illness,
                    'acc_damage_natural_eyes' => $lc_acc_eyes,
                    'acc_damage_natural_teeth' => $lc_acc_teeth,
                    'psychiatric_treatment' => $lc_psych_treat_ip,
                    'congenital_conditions' => $lc_cong_ip,
                    'inpatient_treatment_hiv' => $lc_hiv_ip,
                    'normal_delivery' => $lc_norm_deliv,
                    'caesarian_section' => $lc_csection,
                    'premature_cover' => $lc_premat,
                    'op_annual_limit' => $lc_op,
                    'preexisting_conditions' => $lc_op_chrnc,
                    'antenatal_postnatal' => $lc_op_antenatal,
                    'op_psychiatric_treatment' => $lc_psych_treat,
                    'op_congenital_conditions' => $lc_cong,
                    'outpatient_treatment_of_hiv' => $lc_op_hiv,
                    'dental_limit' => $lc_dental,
                    'annual_health_checks' => $lc_checkup,
                    'personal_accident' => $lc_ptd,
                    'last_expenses' => $lc_last_expense,
                    'enroll_one' => $ls_enrollone,
                    'enroll_two' => $ls_enrolltwo,
                    'enroll_three' => $ls_enrollthree,
                    'enroll_four' => $ls_enrollfour,
                    'enroll_five' => $ls_enrollfive,
                    'enroll_six' => $ls_enrollsix,
                    'enroll_seven' => $ls_enrollseven,
                    'enroll_elleven' => $ls_enrollelleven,
                    'personal_accident_child' => $lc_ptd_child,
                    'admissions_icu_chronic' => $lc_adm_care,
                    'admissions_icu_accident' => $lc_adm_care_acc,
                    'reconstructive_surgery_acc' => $lc_recon_surg,
                    'covid19_op' => $lc_covid19_op,
                    'covid19_ip' => $lc_covid19_ip,
                    'ip_chronic_conditions' => $lc_chronic_cdl,
                    'op_chronic_conditions' => $lc_op_chrnc_cdl,
                    'optical_overall' => $optical_overall,
                    'consultation_and_eye_treatment' => $consultation_and_eye_treatment,
                    'ophthalmology' => $ophthalmology,
                    'congenital_premature_and_genetic_condition_defects' => $congenital_premature_and_genetic_condition_defects,
                    'lenses' => $lc_lenses,
                    'frames' => $lc_frames

                ];

                break;

            case 'sme':
                //product name
                $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                $product_name = $product_name[0];
                $ls_hpname = $request->category . ' - ' . $product_name;

                //lives
                $mem_nos = PrincipalApplicant::join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->where('principal_applicant.corp_id', $request->sme)
                    ->pluck('member_info.member_no');

                $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                    ->where('health_plan', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->count('member_no');

                //premium per life
                $lc_prem = ClientRates::where('health_plan', $request->category)
                 ->where('product_name', $request->product_name)
                    ->where('corp_id', $request->sme)
                    ->pluck('premium');
                $lc_prem = count($lc_prem) != 0 ? $lc_prem[0] : 0;

                //overall limit
                $lc_overall_limit = CorpGroups::where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->max(DB::raw("isnull(limit,0)"));
                //accident hospitalization
                $lc_acc_hosp = CorpGroups::where('benefit', 1)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_acc_hosp = count($lc_acc_hosp) != 0 ? $lc_acc_hosp[0] : 0;
                //illness hospitalization
                $lc_ill_hosp = CorpGroups::where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->whereIn('benefit', [27, 28])
                    ->max(DB::raw("isnull(limit,0)"));
                //admissions chronic
                $lc_adm_care = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->where('benefit', 29)
                    ->max(DB::raw("isnull(limit,0)"));
                //admissions accident
                $lc_adm_care_acc = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->where('benefit', 30)
                    ->max(DB::raw("isnull(limit,0)"));
                //admissions illness
                $lc_adm_care_ill = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->where('benefit', 31)
                    ->max(DB::raw("isnull(limit,0)"));
                //room limit
                $lc_room_limit = CorpGroups::where('benefit', 49)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_room_limit = count($lc_room_limit) != 0 ? $lc_room_limit[0] : 0;
                //chronic
                $lc_chronic = CorpGroups::where('benefit', 7)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_chronic = count($lc_chronic) != 0 ? $lc_chronic[0] : 0;
                //recon surgery accident
                $lc_recon_surg = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->where('benefit', 45)
                    ->max(DB::raw("isnull(limit,0)"));
                //recon surgery illness
                $lc_recon_surg_illness = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->where('benefit', 46)
                    ->max(DB::raw("isnull(limit,0)"));
                //acc_dmg_eyes
                $lc_acc_eyes = CorpGroups::where('benefit', 2)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_acc_eyes = count($lc_acc_eyes) != 0 ? $lc_acc_eyes[0] : 0;

                //acc_dmg_teeth
                $lc_acc_teeth = CorpGroups::where('benefit', 3)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_acc_teeth = count($lc_acc_teeth) != 0 ? $lc_acc_teeth[0] : 0;

                //psych treat
                $lc_psych_treat = CorpGroups::where('benefit', 44)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_psych_treat = count($lc_psych_treat) != 0 ? $lc_psych_treat[0] : 0;

                //psych treat ip
                $lc_psych_treat_ip = CorpGroups::where('benefit', 53)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->whereNotNull('limit')
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_psych_treat_ip = count($lc_psych_treat_ip) != 0 ? $lc_psych_treat_ip[0] : 0;
                //congenital
                $lc_cong = CorpGroups::where(
                    'benefit',
                    9
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_cong = count($lc_cong) != 0 ? $lc_cong[0] : 0;

                //congenital ip
                $lc_cong_ip = CorpGroups::where(
                    'benefit',
                    54
                )
                    ->whereNotNull('limit')
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_cong_ip = count($lc_cong_ip) != 0 ? $lc_cong_ip[0] : 0;

                //hiv ip
                $lc_hiv_ip = CorpGroups::where(
                    'benefit',
                    25
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_hiv_ip = count($lc_hiv_ip) != 0 ? $lc_hiv_ip[0] : 0;
                //delivery, antenatal and post natal before maternity c section benefit 68
                $lc_csection = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->where('benefit', 68)
                    ->max(DB::raw("isnull(limit,0)"));
                //maternity normal delivery, before benefit = 34
                $lc_norm_deliv = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->where('benefit', 59)
                    ->max(DB::raw("isnull(limit,0)"));

                //premature
                $lc_premat = CorpGroups::where(
                    'benefit',
                    43
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_premat = count($lc_premat) != 0 ? $lc_premat[0] : 0;
                //op
                $lc_op = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->whereIn('benefit', [38, 39, 40])
                    ->max(DB::raw("isnull(limit,0)"));

                //chronic op
                $lc_op_chrnc = CorpGroups::where(
                    'benefit',
                    8
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_op_chrnc = count($lc_op_chrnc) != 0 ? $lc_op_chrnc[0] : 0;

                //antenatal op
                $lc_op_antenatal = CorpGroups::where(
                    'benefit',
                    5
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_op_antenatal = count($lc_op_antenatal) != 0 ? $lc_op_antenatal[0] : 0;
                //hiv op
                $lc_op_hiv = CorpGroups::where(
                    'benefit',
                    26
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_op_hiv = count($lc_op_hiv) != 0 ? $lc_op_hiv[0] : 0;

                //optical
                $lc_optical = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->whereIn('benefit', [36, 37])
                    ->max(DB::raw("isnull(limit,0)"));

                //frames only
                $lc_frames = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->whereIn('benefit', [88, 90])
                    ->max(DB::raw("isnull(limit,0)"));
                //lenses only
                $lc_lenses = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->whereIn('benefit', [89, 91])
                    ->max(DB::raw("isnull(limit,0)"));

                //dental
                $lc_dental = CorpGroups::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->whereIn('benefit', [11, 12])
                    ->max(DB::raw("isnull(limit,0)"));
                //annual health check up
                $lc_checkup = CorpGroups::where(
                    'benefit',
                    4
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_checkup = count($lc_checkup) != 0 ? $lc_checkup[0] : 0;
                //chronic acquired on cover
                $lc_chronic_cdl = CorpGroups::where('benefit', 55)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->sme
                    )
                    ->pluck('limit');
                $lc_chronic_cdl = count($lc_chronic_cdl) != 0 ? $lc_chronic_cdl[0] : 0;
                //chronic acquired on cover
                $lc_op_chrnc_cdl = CorpGroups::where(
                    'benefit',
                    56
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $lc_op_chrnc_cdl = count($lc_op_chrnc_cdl) != 0 ? $lc_op_chrnc_cdl[0] : 0;
                //Personal Accident with Permanent Total Disability
                $lc_ptd = CorpGroups::where(
                    'benefit',
                    42
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->sme
                    )
                    ->pluck('limit');
                $lc_ptd = count($lc_ptd) != 0 ? $lc_ptd[0] : 0;
                //Personal Accident with Permanent Total Disability - child
                $lc_ptd_child = CorpGroups::where(
                    'benefit',
                    52
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->sme
                    )
                    ->pluck('limit');
                $lc_ptd_child = count($lc_ptd_child) != 0 ? $lc_ptd_child[0] : 0;

                //last expense
                $lc_last_expense = CorpGroups::whereIn(
                    'benefit',
                    [20, 21]
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->sme
                    )
                    ->pluck('limit');
                $lc_last_expense = count($lc_last_expense) != 0 ? $lc_last_expense[0] : 0;
                //covid19 ip
                $lc_covid19_ip = CorpGroups::where(
                    'benefit',
                    61
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->sme
                    )
                    ->pluck('limit');
                $lc_covid19_ip = count($lc_covid19_ip) != 0 ? $lc_covid19_ip[0] : 0;

                //covid19 op
                $lc_covid19_op = CorpGroups::where(
                    'benefit',
                    60
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where(
                        'corp_id',
                        $request->sme
                    )
                    ->pluck('limit');
                $lc_covid19_op = count($lc_covid19_op) != 0 ? $lc_covid19_op[0] : 0;

                //optical overall
                $optical_overall = CorpGroups::where(
                    'benefit',
                    83
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $optical_overall = count($optical_overall) != 0 ? $optical_overall[0] : 0;
                //consultation and eye treatment
                $consultation_and_eye_treatment = CorpGroups::where(
                    'benefit',
                    84
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $consultation_and_eye_treatment = count($consultation_and_eye_treatment) != 0 ? $consultation_and_eye_treatment[0] : 0;

                //Ophthalmology
                $ophthalmology  = CorpGroups::where(
                    'benefit',
                    57
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $ophthalmology = count($ophthalmology) != 0 ? $ophthalmology[0] : 0;
                 //CONGENITAL, PREMATURE AND GENETIC CONDITION DEFECTS  
                 $congenital_premature_and_genetic_condition_defects  = CorpGroups::where(
                    'benefit',
                    141
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->sme)
                    ->pluck('limit');
                $congenital_premature_and_genetic_condition_defects = count($congenital_premature_and_genetic_condition_defects) != 0 ? $congenital_premature_and_genetic_condition_defects[0] : 0;
                //enrollment notes
                $ls_enrollone = '*Pre-Chronic conditions will not be covered';
                $ls_enrolltwo = '*The Quote and Provider network is subject to further tailoring based on clients budget, number of lives and Health Insurance Benefit needs.';
                $ls_enrollthree = '*All Clients with less than 10 principals will be subject to a waiting period of 12 months on Maternity , chronic conditions developed on cover and 3 months on Illness Hospitalisation';
                $ls_enrollfour = '*All benefits cannot exceed the overall annual limit.';
                $ls_enrollfive = '*All claims incurred in providers where AAR has no affiliate will be covered if pre-authorized and if reported with genuine receipts within 48 hrs.';
                $ls_enrollsix = '*All Hospitalisations must be pre-authorized through the case management arm of AAR Insurance';
                $ls_enrollseven = '*0.5% of premium will be paid for Insurance Training Levy.';
                $ls_enrolleight = '*All clients aged above 60 years will be subject to atleast 30% risk premium increment upto 65 yrs. The premium increment is subject to change depending on the clients health status and corporate medical scheme.';
                $ls_enrollnine = '* The cost of the medex of those aged above 45 years will be borne by the client at a pre-negotiated cost of Ugx150,000 in a facility recommended by the AAR medical evaluator.';
                $ls_enrollten = '*Any extra tests required by AAR will be borne by the clients upon request from the Medical Underwriter.';
                $ls_enrollelleven = 'The respective benefit limit is the maximum limit of cover that will be considered based on the specific cause of the claim.';
                $data = [
                    'optometry_limit' => $lc_optical,
                    'ip_chronic_conditions' => $lc_chronic_cdl,
                    'op_chronic_conditions' => $lc_op_chrnc_cdl,
                    'anniv' => $request->annivs,
                    'lives' => $ll_lives,
                    'health_plan' => $ls_hpname,
                    'overall_annual_limit' => $lc_overall_limit,
                    'accident_hosp' => $lc_acc_hosp,
                    'illness_hosp' => $lc_ill_hosp,
                    'admissions_icu' => $lc_adm_care_ill,
                    'room_limit' => $lc_room_limit,
                    'preexisting_cond' => $lc_chronic,
                    'reconstructive_surgery' => $lc_recon_surg_illness,
                    'acc_damage_natural_eyes' => $lc_acc_eyes,
                    'acc_damage_natural_teeth' => $lc_acc_teeth,
                    'psychiatric_treatment' => $lc_psych_treat_ip,
                    'congenital_conditions' => $lc_cong_ip,
                    'inpatient_treatment_hiv' => $lc_hiv_ip,
                    'normal_delivery' => $lc_norm_deliv,
                    'caesarian_section' => $lc_csection,
                    'premature_cover' => $lc_premat,
                    'op_annual_limit' => $lc_op,
                    'preexisting_conditions' => $lc_op_chrnc,
                    'antenatal_postnatal' => $lc_op_antenatal,
                    'op_psychiatric_treatment' => $lc_psych_treat,
                    'op_congenital_conditions' => $lc_cong,
                    'outpatient_treatment_of_hiv' => $lc_op_hiv,
                    'dental_limit' => $lc_dental,
                    'annual_health_checks' => $lc_checkup,
                    'personal_accident' => $lc_ptd,
                    'last_expenses' => $lc_last_expense,
                    'enroll_one' => $ls_enrollone,
                    'enroll_two' => $ls_enrolltwo,
                    'enroll_three' => $ls_enrollthree,
                    'enroll_four' => $ls_enrollfour,
                    'enroll_five' => $ls_enrollfive,
                    'enroll_six' => $ls_enrollsix,
                    'enroll_seven' => $ls_enrollseven,
                    'enroll_eight' => $ls_enrolleight,
                    'enroll_nine' => $ls_enrollnine,
                    'enroll_ten' => $ls_enrollten,
                    'enroll_elleven' => $ls_enrollelleven,
                    'personal_accident_child' => $lc_ptd_child,
                    'admissions_icu_chronic' => $lc_adm_care,
                    'admissions_icu_accident' => $lc_adm_care_acc,
                    'reconstructive_surgery_acc' => $lc_recon_surg,
                    'covid19_op' => $lc_covid19_op,
                    'covid19_ip' => $lc_covid19_ip,
                    'optical_overall' => $optical_overall,
                    'consultation_and_eye_treatment' => $consultation_and_eye_treatment,
                    'ophthalmology' => $ophthalmology,
                    'congenital_premature_and_genetic_condition_defects'=>$congenital_premature_and_genetic_condition_defects,
                    'lenses' => $lc_lenses,
                    'frames' => $lc_frames
                ];

                break;
            case 'retail':
                //product name
                $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                $product_name = $product_name[0];
                $ls_hpname = $request->category . ' - ' . $product_name;

                //client name explode
                $name = explode("-", $request->client_name);
                $client_name = $name[0];

                //member no explode
                $arr = explode("-", $request->retail);
                $family_no = $arr[0];
                //lives
                $mem_nos = MemberInfo::where(DB::raw("isnull(cancelled, 0)"), 0)
                    ->where('family_no', $family_no)
                    ->pluck('member_no');

                $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                    ->where('health_plan', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->count('member_no');

                //premium per life
                $lc_prem = ClientRates::where('health_plan', $request->category)
                 ->where('product_name', $request->product_name)
                    ->where('family_no', $family_no)
                    ->pluck('premium');
                $lc_prem = count($lc_prem) != 0 ? $lc_prem[0] : 0;
                //overall limit
                $lc_overall_limit = MemberBenefits::where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->max(DB::raw("isnull(limit,0)"));
                //accident hospitalization
                $lc_acc_hosp = MemberBenefits::where('benefit', 1)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_acc_hosp = count($lc_acc_hosp) != 0 ? $lc_acc_hosp[0] : 0;
                //illness hospitalization
                $lc_ill_hosp = MemberBenefits::where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->whereIn('benefit', [27, 28])
                    ->max(DB::raw("isnull(limit,0)"));
                //admissions chronic
                $lc_adm_care = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->where('benefit', 29)
                    ->max(DB::raw("isnull(limit,0)"));
                //admissions accident
                $lc_adm_care_acc = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->where('benefit', 30)
                    ->max(DB::raw("isnull(limit,0)"));
                //admissions illness
                $lc_adm_care_ill = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->where('benefit', 31)
                    ->max(DB::raw("isnull(limit,0)"));
                //room limit
                $lc_room_limit = MemberBenefits::where('benefit', 49)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_room_limit = count($lc_room_limit) != 0 ? $lc_room_limit[0] : 0;
                //chronic
                $lc_chronic = MemberBenefits::where('benefit', 7)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_chronic = count($lc_chronic) != 0 ? $lc_chronic[0] : 0;
                //chronic acquired on cover
                $lc_chronic_cdl = MemberBenefits::where('benefit', 55)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_chronic_cdl = count($lc_chronic_cdl) != 0 ? $lc_chronic_cdl[0] : 0;
                //recon surgery accident
                $lc_recon_surg = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->where('benefit', 45)
                    ->max(DB::raw("isnull(limit,0)"));
                //recon surgery illness
                $lc_recon_surg_illness = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->where('benefit', 46)
                    ->max(DB::raw("isnull(limit,0)"));
                //acc_dmg_eyes
                $lc_acc_eyes = MemberBenefits::where('benefit', 2)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_acc_eyes = count($lc_acc_eyes) != 0 ? $lc_acc_eyes[0] : 0;
                //acc_dmg_teeth
                $lc_acc_teeth = MemberBenefits::where('benefit', 3)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_acc_teeth = count($lc_acc_teeth) != 0 ? $lc_acc_teeth[0] : 0;
                //psych treat
                $lc_psych_treat = MemberBenefits::where('benefit', 44)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_psych_treat = count($lc_psych_treat) != 0 ? $lc_psych_treat[0] : 0;
                //psych treat ip
                $lc_psych_treat_ip = MemberBenefits::where('benefit', 53)
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_psych_treat_ip = count($lc_psych_treat_ip) != 0 ? $lc_psych_treat_ip[0] : 0;
                //congenital
                $lc_cong = MemberBenefits::where(
                    'benefit',
                    9
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_cong = count($lc_cong) != 0 ? $lc_cong[0] : 0;
                //congenital ip
                $lc_cong_ip = MemberBenefits::where(
                    'benefit',
                    54
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_cong_ip = count($lc_cong_ip) != 0 ? $lc_cong_ip[0] : 0;
                //hiv ip
                $lc_hiv_ip = MemberBenefits::where(
                    'benefit',
                    25
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_hiv_ip = count($lc_hiv_ip) != 0 ? $lc_hiv_ip[0] : 0;
                //maternity c section
                $lc_csection = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->whereIn('benefit', [32, 33])
                    ->max(DB::raw("isnull(limit,0)"));
                //maternity normal delivery
                $lc_norm_deliv = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->where('benefit', 34)
                    ->max(DB::raw("isnull(limit,0)"));

                //premature
                $lc_premat = MemberBenefits::where(
                    'benefit',
                    43
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_premat = count($lc_premat) != 0 ? $lc_premat[0] : 0;
                //op
                $lc_op = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->whereIn('benefit', [38, 39, 40])
                    ->max(DB::raw("isnull(limit,0)"));

                //chronic op
                $lc_op_chrnc = MemberBenefits::where(
                    'benefit',
                    8
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_op_chrnc = count($lc_op_chrnc) != 0 ? $lc_op_chrnc[0] : 0;
                //chronic acquired on cover
                $lc_op_chrnc_cdl = MemberBenefits::where(
                    'benefit',
                    56
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_op_chrnc_cdl = count($lc_op_chrnc_cdl) != 0 ? $lc_op_chrnc_cdl[0] : 0;
                //antenatal op
                $lc_op_antenatal = MemberBenefits::where(
                    'benefit',
                    5
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_op_antenatal = count($lc_op_antenatal) != 0 ? $lc_op_antenatal[0] : 0;
                //hiv op
                $lc_op_hiv = MemberBenefits::where(
                    'benefit',
                    26
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_op_hiv = count($lc_op_hiv) != 0 ? $lc_op_hiv[0] : 0;

                //optical
                $lc_optical = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->whereIn('benefit', [36, 37])
                    ->max(DB::raw("isnull(limit,0)"));

                //frames only
                $lc_frames = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->whereIn('benefit', [88, 90])
                    ->max(DB::raw("isnull(limit,0)"));
                //lenses only
                $lc_lenses = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->whereIn('benefit', [89, 91])
                    ->max(DB::raw("isnull(limit,0)"));

                //dental
                $lc_dental = MemberBenefits::where(
                    'product_name',
                    $request->product_name
                )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->whereIn('benefit', [11, 12])
                    ->max(DB::raw("isnull(limit,0)"));
                //annual health check up
                $lc_checkup = MemberBenefits::where(
                    'benefit',
                    4
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_checkup = count($lc_checkup) != 0 ? $lc_checkup[0] : 0;
                //Personal Accident with Permanent Total Disability
                $lc_ptd = MemberBenefits::where(
                    'benefit',
                    42
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_ptd = count($lc_ptd) != 0 ? $lc_ptd[0] : 0;
                //Personal Accident with Permanent Total Disability - child
                $lc_ptd_child = MemberBenefits::where(
                    'benefit',
                    52
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_ptd_child = count($lc_ptd_child) != 0 ? $lc_ptd_child[0] : 0;
                //last expense
                $lc_last_expense = MemberBenefits::whereIn(
                    'benefit',
                    [20, 21]
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_last_expense = count($lc_last_expense) != 0 ? $lc_last_expense[0] : 0;
                //covid19 ip
                $lc_covid19_ip = MemberBenefits::where(
                    'benefit',
                    61
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_covid19_ip = count($lc_covid19_ip) != 0 ? $lc_covid19_ip[0] : 0;
                //covid19 op
                $lc_covid19_op = MemberBenefits::where(
                    'benefit',
                    60
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $lc_covid19_op = count($lc_covid19_op) != 0 ? $lc_covid19_op[0] : 0;
                //optical overall
                $optical_overall = MemberBenefits::where(
                    'benefit',
                    83
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $optical_overall = count($optical_overall) != 0 ? $optical_overall[0] : 0;
                //consultation and eye treatment
                $consultation_and_eye_treatment = MemberBenefits::where(
                    'benefit',
                    84
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $consultation_and_eye_treatment = count($consultation_and_eye_treatment) != 0 ? $consultation_and_eye_treatment[0] : 0;

                //Ophthalmology
                $ophthalmology  = MemberBenefits::where(
                    'benefit',
                    57
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $ophthalmology = count($ophthalmology) != 0 ? $ophthalmology[0] : 0;
                //Ophthalmology
                $congenital_premature_and_genetic_condition_defects  = MemberBenefits::where(
                    'benefit',
                    141
                )
                    ->where(
                        'product_name',
                        $request->product_name
                    )
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->pluck('limit');
                $congenital_premature_and_genetic_condition_defects = count($congenital_premature_and_genetic_condition_defects) != 0 ? $congenital_premature_and_genetic_condition_defects[0] : 0;

                //enrollment notes
                $ls_enrollone = '*Pre-Chronic conditions will not be covered';
                $ls_enrolltwo = '*The Quote and Provider network is subject to further tailoring based on clients budget, number of lives and Health Insurance Benefit needs.';
                $ls_enrollthree = '*All Clients with less than 10 principals will be subject to a waiting period of 12 months on Maternity , chronic conditions developed on cover and 3 months on Illness Hospitalisation';
                $ls_enrollfour = '*All benefits cannot exceed the overall annual limit.';
                $ls_enrollfive = '*All claims incurred in providers where AAR has no affiliate will be covered if pre-authorized and if reported with genuine receipts within 48 hrs.';
                $ls_enrollsix = '*All Hospitalisations must be pre-authorized through the case management arm of AAR Insurance';
                $ls_enrollseven = '*0.5% of premium will be paid for Insurance Training Levy.';
                $ls_enrolleight = '*All clients aged above 60 years will be subject to atleast 30% risk premium increment upto 65 yrs. The premium increment is subject to change depending on the clients health status and corporate medical scheme.';
                $ls_enrollnine = '* The cost of the medex of those aged above 45 years will be borne by the client at a pre-negotiated cost of Ugx150,000 in a facility recommended by the AAR medical evaluator.';
                $ls_enrollten = '*Any extra tests required by AAR will be borne by the clients upon request from the Medical Underwriter.';
                $ls_enrollelleven = 'The respective benefit limit is the maximum limit of cover that will be considered based on the specific cause of the claim.';
                $data = [
                    'client_name' => $client_name,
                    'optometry_limit' => $lc_optical,
                    'anniv' => $request->annivs,
                    'lives' => $ll_lives,
                    'health_plan' => $ls_hpname,
                    'overall_annual_limit' => $lc_overall_limit,
                    'accident_hosp' => $lc_acc_hosp,
                    'illness_hosp' => $lc_ill_hosp,
                    'admissions_icu' => $lc_adm_care_ill,
                    'room_limit' => $lc_room_limit,
                    'preexisting_cond' => $lc_chronic,
                    'ip_chronic_conditions' => $lc_chronic_cdl,
                    'reconstructive_surgery' => $lc_recon_surg_illness,
                    'acc_damage_natural_eyes' => $lc_acc_eyes,
                    'acc_damage_natural_teeth' => $lc_acc_teeth,
                    'psychiatric_treatment' => $lc_psych_treat_ip,
                    'congenital_conditions' => $lc_cong_ip,
                    'inpatient_treatment_hiv' => $lc_hiv_ip,
                    'normal_delivery' => $lc_norm_deliv,
                    'caesarian_section' => $lc_csection,
                    'premature_cover' => $lc_premat,
                    'op_annual_limit' => $lc_op,
                    'preexisting_conditions' => $lc_op_chrnc,
                    'op_chronic_conditions' => $lc_op_chrnc_cdl,
                    'antenatal_postnatal' => $lc_op_antenatal,
                    'op_psychiatric_treatment' => $lc_psych_treat,
                    'op_congenital_conditions' => $lc_cong,
                    'outpatient_treatment_of_hiv' => $lc_op_hiv,
                    'dental_limit' => $lc_dental,
                    'annual_health_checks' => $lc_checkup,
                    'personal_accident' => $lc_ptd,
                    'last_expenses' => $lc_last_expense,
                    'enroll_one' => $ls_enrollone,
                    'enroll_two' => $ls_enrolltwo,
                    'enroll_three' => $ls_enrollthree,
                    'enroll_four' => $ls_enrollfour,
                    'enroll_five' => $ls_enrollfive,
                    'enroll_six' => $ls_enrollsix,
                    'enroll_seven' => $ls_enrollseven,
                    'enroll_eight' => $ls_enrolleight,
                    'enroll_nine' => $ls_enrollnine,
                    'enroll_ten' => $ls_enrollten,
                    'enroll_elleven' => $ls_enrollelleven,
                    'personal_accident_child' => $lc_ptd_child,
                    'admissions_icu_chronic' => $lc_adm_care,
                    'admissions_icu_accident' => $lc_adm_care_acc,
                    'reconstructive_surgery_acc' => $lc_recon_surg,
                    'covid19_op' => $lc_covid19_op,
                    'covid19_ip' => $lc_covid19_ip,
                    'optical_overall' => $optical_overall,
                    'consultation_and_eye_treatment' => $consultation_and_eye_treatment,
                    'ophthalmology' => $ophthalmology,
                    'congenital_premature_and_genetic_condition_defects'=>$congenital_premature_and_genetic_condition_defects,
                    'lenses' => $lc_lenses,
                    'frames' => $lc_frames
                ];
                break;
            case 'hospital_cash':
                //product name
                $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                $product_name = $product_name[0];
                $ls_hpname = $request->category . ' - ' . $product_name;

                //client name explode
                $name = explode("-", $request->client_name);
                $client_name = $name[0];


                //start and end date
                $cover_dates = MemberAnniversary::select(
                    DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                )
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();

                $start_date = count($cover_dates) != 0 ? $cover_dates[0]->start_date : '';
                $end_date = count($cover_dates) != 0 ? $cover_dates[0]->end_date : '';

                //client address
                $client_address = PrincipalApplicant::select('postal_add', 'tel_no', 'email')
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();
                $address = count($client_address) !== 0 ? $client_address[0]->postal_add : '';
                $tel_no = count($client_address) !== 0 ? $client_address[0]->tel_no : '';
                $email = count($client_address) !== 0 ? $client_address[0]->email : '';

                $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                    ->where('health_plan', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->count('member_no');

                $product_option = MemberAnniversary::select(
                    't_product_name.product_name',
                    'member_anniversary.health_plan'
                )
                    ->join('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();

                $product_option = count($product_option) !== 0 ? $product_option[0]->health_plan . '-' . $product_option[0]->product_name : '';

                //overall limit
                $lc_overall_limit = MemberBenefits::where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->max(DB::raw("isnull(limit,0)"));

                //maternity cover
                $maternity_cover = MemberBenefits::where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 126)
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->max(DB::raw("isnull(limit,0)"));

                //enrollment notes
                $ls_enrollone = '*All claims should be incurred in a hospital registered and licensed under the ministry of health.';
                $ls_enrolltwo = '*Hospital cash is payable upon spending 2 nights in the hospital.';
                $ls_enrollthree = '*AAR should be notified within 24hours of admission.';
                $ls_enrollfour = '*The maximum number of night’s payable in a year is 10.';
                $ls_enrollfive = '*Premiums are payable in advance.';
                $data = [
                    'policy_no' => '',
                    'health_plan' => $product_option,
                    'member_no' => $request->hospital_cash_members,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'address' => $address,
                    'tel_no' => $tel_no,
                    'email' => $email,
                    'client_name' => $client_name,
                    'zone' => 'Uganda',
                    'overall_annual_limit' => $lc_overall_limit,
                    'lives' => $ll_lives,
                    'hospital_cash_limit_per_night' => ($lc_overall_limit / 10),
                    'max_benefit_nights_per_year' => '10',
                    'hospital_cash_pay_out' => '15 days',
                    'maternity_cover' => $maternity_cover,
                    'enroll_one' => $ls_enrollone,
                    'enroll_two' => $ls_enrolltwo,
                    'enroll_three' => $ls_enrollthree,
                    'enroll_four' => $ls_enrollfour,
                    'enroll_five' => $ls_enrollfive,
                ];
                break;
            case 'kitanda':
                //product name
                $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                $product_name = $product_name[0];
                $ls_hpname = $request->category . ' - ' . $product_name;

                //client name explode
                $name = explode("-", $request->client_name);
                $client_name = $name[0];


                //start and end date
                $cover_dates = MemberAnniversary::select(
                    DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                )
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();

                $start_date = count($cover_dates) != 0 ? $cover_dates[0]->start_date : '';
                $end_date = count($cover_dates) != 0 ? $cover_dates[0]->end_date : '';

                //client address
                $client_address = PrincipalApplicant::select('postal_add', 'tel_no', 'email')
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();
                $address = count($client_address) !== 0 ? $client_address[0]->postal_add : '';
                $tel_no = count($client_address) !== 0 ? $client_address[0]->tel_no : '';
                $email = count($client_address) !== 0 ? $client_address[0]->email : '';

                $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                    ->where('health_plan', $request->category)
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->count('member_no');

                $product_option = MemberAnniversary::select(
                    't_product_name.product_name',
                    'member_anniversary.health_plan'
                )
                    ->join('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();

                $product_option = count($product_option) !== 0 ? $product_option[0]->health_plan . '-' . $product_option[0]->product_name : '';

                //hospitalization benefit
                $hospitalization_benefit = MemberBenefits::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 78)
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();

                $hospitalization_benefit = count($hospitalization_benefit) !== 0 ? $hospitalization_benefit[0]->limit : 0;
                //Funeral
                $funeral = MemberBenefits::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 20)
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();

                $funeral = count($funeral) !== 0 ? $funeral[0]->limit : 0;
                //Total permanent disability
                $total_permanent_disability = MemberBenefits::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 77)
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();

                $total_permanent_disability = count($total_permanent_disability) !== 0 ? $total_permanent_disability[0]->limit : 0;
                //Enhanced Corporate Annual Overall Limit
                $enhanced_corporate_annual_overall_limit = MemberBenefits::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 14)
                    ->where('anniv', $request->annivs)
                    ->where('member_no', $request->hospital_cash_members)
                    ->get();

                $enhanced_corporate_annual_overall_limit = count($enhanced_corporate_annual_overall_limit) !== 0 ? $enhanced_corporate_annual_overall_limit[0]->limit : 0;


                //enrollment notes
                $ls_enrollone = '*All claims should be incurred in a hospital registered and licensed under the ministry of health.';
                $ls_enrolltwo = '*Hospital cash is payable upon spending 2 nights in the hospital.';
                $ls_enrollthree = '*AAR should be notified within 24hours of admission.';
                $ls_enrollfour = '*The maximum number of night’s payable in a year is 10.';
                $ls_enrollfive = '*Premiums are payable in advance.';
                $data = [
                    'policy_no' => '',
                    'health_plan' => $product_option,
                    'member_no' => $request->hospital_cash_members,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'address' => $address,
                    'tel_no' => $tel_no,
                    'email' => $email,
                    'client_name' => $client_name,
                    'zone' => 'Uganda',
                    'lives' => $ll_lives,
                    'hospitalization_benefit' => $hospitalization_benefit,
                    'funeral' => $funeral,
                    'total_permanent_disability' => $total_permanent_disability,
                    'enhanced_corporate_annual_overall_limit' => $enhanced_corporate_annual_overall_limit,
                    'waiting_period' => '10',
                    'enroll_one' => $ls_enrollone,
                    'enroll_two' => $ls_enrolltwo,
                    'enroll_three' => $ls_enrollthree,
                    'enroll_four' => $ls_enrollfour,
                    'enroll_five' => $ls_enrollfive,
                ];
                break;
            case 'liberty_blue':
                //product name
                $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                $product_name = $product_name[0];
                $ls_hpname = $product_name;

                //client  or scheme
                $scheme_data = Corporate::select('corporate')->where('corp_id', $request->scheme)->get();
                $scheme_name = $scheme_data[0]->corporate;

                //lives
                $mem_nos = PrincipalApplicant::join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->where('principal_applicant.corp_id', $request->scheme)
                    ->pluck('member_info.member_no');

                $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                    ->where('health_plan', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->count('member_no');

                //premium per life
                $lc_prem = ClientRates::where('health_plan', $request->category)
                    ->where('product_name', $request->product_name)
                    ->where('corp_id', $request->scheme)
                    ->pluck('premium');
                $lc_prem = count($lc_prem) != 0 ? $lc_prem[0] : 0;


                //overall limit benefit
                $overall_annual_limit = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 92)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $overall_annual_limit = count($overall_annual_limit) !== 0 ? $overall_annual_limit[0]->limit : 0;
               
                //day to day one 
                $day_to_day_one = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 93)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $day_to_day_one = count($day_to_day_one) !== 0 ? $day_to_day_one[0]->limit : 0;
                //day to day two 
                $day_to_day_two = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 131)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $day_to_day_two = count($day_to_day_two) !== 0 ? $day_to_day_two[0]->limit : 0;
                //day to day three 
                $day_to_day_three = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 130)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $day_to_day_three = count($day_to_day_three) !== 0 ? $day_to_day_three[0]->limit : 0;
                //chronic condition bene
                $chronic_condition_bene = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 121)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $chronic_condition_bene = count($chronic_condition_bene) !== 0 ? $chronic_condition_bene[0]->limit : 0;
                //out patient maternity care
                $out_patient_maternity_care = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 95)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $out_patient_maternity_care = count($out_patient_maternity_care) !== 0 ? $out_patient_maternity_care[0]->limit : 0;
                //dental limit
                $dental_limit = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 11)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $dental_limit = count($dental_limit) !== 0 ? $dental_limit[0]->limit : 0;
                //prescribed acute benefit
                $prescribed_acute = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 125)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $prescribed_acute = count($prescribed_acute) !== 0 ? $prescribed_acute[0]->limit : 0;
                //optical benefit
                $optical = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 124)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $optical = count($optical) !== 0 ? $optical[0]->limit : 0;
                //optical benefit 2 with copay 5000
                $optical_two = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 129)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $optical_two = count($optical_two) !== 0 ? $optical_two[0]->limit : 0;
                //basic dentistry
                $basic_dentistry = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 127)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $basic_dentistry = count($basic_dentistry) !== 0 ? $basic_dentistry[0]->limit : 0;
                //basic dentistry two with copay 5000
                $basic_dentistry_two = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 132)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $basic_dentistry_two = count($basic_dentistry_two) !== 0 ? $basic_dentistry_two[0]->limit : 0;
                //specialised dentistry
                $specialised_dentistry = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 128)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $specialised_dentistry = count($specialised_dentistry) !== 0 ? $specialised_dentistry[0]->limit : 0;
                //neonatal benefit
                $neonatal = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 96)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $neonatal = count($neonatal) !== 0 ? $neonatal[0]->limit : 0;
                //rehabilitation benefit for 30 days
                $rehabilitation_thirty = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 97)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $rehabilitation_thirty = count($rehabilitation_thirty) !== 0 ? $rehabilitation_thirty[0]->limit : 0;
                //rehabilitation benefit for 10 days
                $rehabilitation_ten = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 137)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $rehabilitation_ten = count($rehabilitation_ten) !== 0 ? $rehabilitation_ten[0]->limit : 0;
                //specialised radiology benefit
                $specialised_radiology = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 98)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $specialised_radiology = count($specialised_radiology) !== 0 ? $specialised_radiology[0]->limit : 0;
                //Prosthesis and devices cochlear benefit
                $prosthesis_devices_cochlear = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 99)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $prosthesis_devices_cochlear = count($prosthesis_devices_cochlear) !== 0 ? $prosthesis_devices_cochlear[0]->limit : 0;
                //Prosthesis and devices all benefit
                $prosthesis_devices_all = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 100)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $prosthesis_devices_all = count($prosthesis_devices_all) !== 0 ? $prosthesis_devices_all[0]->limit : 0;
                //external medical appliances hearing benefit
                $external_medical_appliances_hearing = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 101)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $external_medical_appliances_hearing = count($external_medical_appliances_hearing) !== 0 ? $external_medical_appliances_hearing[0]->limit : 0;
                //external medical appliances others benefit
                $external_medical_appliances_others = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 102)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $external_medical_appliances_others = count($external_medical_appliances_others) !== 0 ? $external_medical_appliances_others[0]->limit : 0;
                //major diseases benefits limit benefit
                $major_diseases_benefits_limit = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 105)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $major_diseases_benefits_limit = count($major_diseases_benefits_limit) !== 0 ? $major_diseases_benefits_limit[0]->limit : 0;
                //repatriation of mortal remains benefit
                $repatriation_of_mortal_remains = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 112)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $repatriation_of_mortal_remains = count($repatriation_of_mortal_remains) !== 0 ? $repatriation_of_mortal_remains[0]->limit : 0;
                //funeral benefit
                $funeral = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 114)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $funeral = count($funeral) !== 0 ? $funeral[0]->limit : 0;
                //accomodation benefit
                $accommodation = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 119)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $accommodation = count($accommodation) !== 0 ? $accommodation[0]->limit : 0;
                //repatriation_of_mortal_remains
                $repatriation_of_mortal_remains = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 112)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->scheme)
                    ->get();

                $repatriation_of_mortal_remains = count($repatriation_of_mortal_remains) !== 0 ? $repatriation_of_mortal_remains[0]->limit : 0;



                $data = [
                    'policy_no' => '',
                    'client' => $scheme_name,
                    'lives'=>$ll_lives,
                    'premium_per_life'=>$lc_prem,
                    'health_plan' => $ls_hpname,
                    'zone' => 'Worldwide (excluding North America) Evacuation and critical care: Africa and India',
                    'network_provider' => 'Premier Network',
                    'overall_annual_limit' => $overall_annual_limit,
                    'prescribed_acute' => $prescribed_acute,
                    'optical' => $optical,
                    'optical_two' => $optical_two,
                    'basic_dentistry' => $basic_dentistry,
                    'basic_dentistry_two' => $basic_dentistry_two,
                    'specialised_dentistry' => $specialised_dentistry,
                    'neonatal' => $neonatal,
                    'rehabilitation_ten' => $rehabilitation_ten,
                    'rehabilitation_thirty' => $rehabilitation_thirty,
                    'specialised_radiology' => $specialised_radiology,
                    'prosthesis_devices_cochlear' => $prosthesis_devices_cochlear,
                    'prosthesis_devices_all' => $prosthesis_devices_all,
                    'prosthesis_devices' => number_format($prosthesis_devices_cochlear) . " for Cochlear implants " . number_format($prosthesis_devices_all) . " for all other prosthesis",
                    'external_medical_appliances_hearing' => $external_medical_appliances_hearing,
                    'external_medical_appliances_others' => $external_medical_appliances_others,
                    'external_medical_appliances' => number_format($external_medical_appliances_hearing) . " for Hearing Aids " . number_format($external_medical_appliances_others) . " for Other appliances",
                    'major_diseases_benefits_limit' => $major_diseases_benefits_limit,
                    'repatriation_of_mortal_remains' => $repatriation_of_mortal_remains,
                    'funeral' => $funeral,
                    'day_to_day_one' => $day_to_day_one,
                    'day_to_day_two' => $day_to_day_two,
                    'day_to_day_three' => $day_to_day_three,
                    'chronic_condition_bene' => $chronic_condition_bene,
                    'dental_limit' => $dental_limit,
                    'out_patient_maternity_care' => $out_patient_maternity_care,
                    'accommodation' => $accommodation

                ];
                break;
            case 'kitanda_corporate':
                //product name
                // return $request;
                $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                $product_name = $product_name[0];
                $ls_hpname = $request->category . ' - ' . $product_name;

                //client name explode
                $name = explode("-", $request->client_name);
                $client_name = $name[0];


                //start and end date
                $cover_dates = CorpAnniversary::select(
                    DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                )
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->get();

                $start_date = count($cover_dates) != 0 ? $cover_dates[0]->start_date : '';
                $end_date = count($cover_dates) != 0 ? $cover_dates[0]->end_date : '';

                //client address
                $client_address = Corporate::select('postal_add', 'tel_no', 'email')
                    ->where('corp_id', $request->corporate)
                    ->get();
                $address = count($client_address) !== 0 ? $client_address[0]->postal_add : '';
                $tel_no = count($client_address) !== 0 ? $client_address[0]->tel_no : '';
                $email = count($client_address) !== 0 ? $client_address[0]->email : '';

                //lives
                $mem_nos = PrincipalApplicant::join('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->pluck('member_info.member_no');

                $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                    ->where('health_plan', $request->category)
                    ->where('anniv', $request->annivs)
                    ->whereIn('member_no', $mem_nos)
                    ->count('member_no');



                //hospitalization benefit
                $hospitalization_benefit = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 78)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->get();

                $hospitalization_benefit = count($hospitalization_benefit) !== 0 ? $hospitalization_benefit[0]->limit : 0;


                //Funeral
                $funeral = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 20)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->get();

                $funeral = count($funeral) !== 0 ? $funeral[0]->limit : 0;
                //Total permanent disability
                $total_permanent_disability = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 77)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->get();

                $total_permanent_disability = count($total_permanent_disability) !== 0 ? $total_permanent_disability[0]->limit : 0;
                //Enhanced Corporate Annual Overall Limit
                $enhanced_corporate_annual_overall_limit = CorpGroups::select('limit')
                    ->where('product_name', $request->product_name)
                    ->where('category', $request->category)
                    ->where('benefit', 14)
                    ->where('anniv', $request->annivs)
                    ->where('corp_id', $request->corporate)
                    ->get();

                $enhanced_corporate_annual_overall_limit = count($enhanced_corporate_annual_overall_limit) !== 0 ? $enhanced_corporate_annual_overall_limit[0]->limit : 0;


                //enrollment notes
                $ls_enrollone = '*All claims should be incurred in a hospital registered and licensed under the ministry of health.';
                $ls_enrolltwo = '*Hospital cash is payable upon spending 2 nights in the hospital.';
                $ls_enrollthree = '*AAR should be notified within 24hours of admission.';
                $ls_enrollfour = '*The maximum number of night’s payable in a year is 10.';
                $ls_enrollfive = '*Premiums are payable in advance.';
                $data = [
                    'policy_no' => '',
                    'health_plan' => $ls_hpname,
                    'member_no' => $request->hospital_cash_members,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'address' => $address,
                    'tel_no' => $tel_no,
                    'email' => $email,
                    'client_name' => $client_name,
                    'zone' => 'Uganda',
                    'lives' => $ll_lives,
                    'hospitalization_benefit' => $hospitalization_benefit,
                    'funeral' => $funeral,
                    'total_permanent_disability' => $total_permanent_disability,
                    'enhanced_corporate_annual_overall_limit' => $enhanced_corporate_annual_overall_limit,
                    'waiting_period' => '10',
                    'enroll_one' => $ls_enrollone,
                    'enroll_two' => $ls_enrolltwo,
                    'enroll_three' => $ls_enrollthree,
                    'enroll_four' => $ls_enrollfour,
                    'enroll_five' => $ls_enrollfive,
                ];
                break;
            case 'munomukabi':
                switch ($request->scheme) {
                    case '1':
                        $client_no = $request->munomukabi_corporate;
                        //client name explode
                        $client_name = Corporate::select('corporate')->where('corp_id', $request->munomukabi_corporate)->get();
                        $client_name = count($client_name) !== 0 ? $client_name[0]->corporate : '';
                        //product name
                        $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                        $product_name = $product_name[0];
                        $ls_hpname = $request->category . ' - ' . $product_name;
                        // $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                        //     ->where('health_plan', $request->category)
                        //     ->where('anniv', $request->annivs)
                        //     ->where('member_no', $request->munomukabi_member)
                        //     ->count('member_no');
                        $ll_lives = PrincipalApplicant::where('h_option', $request->product_name)
                            ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                            ->where('member_anniversary.health_plan', $request->category)
                            ->where('member_anniversary.anniv', $request->annivs)
                            ->where('principal_applicant.corp_id', $request->munomukabi_corporate)
                            ->count('member_info.member_no');

                        //overall annual limit  per person
                        $overall_annual_limit = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 10) //Corporate Card Annual Overall Limit
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $overall_annual_limit = count($overall_annual_limit) !== 0 ? $overall_annual_limit[0]->limit : 0;
                        //Hospital Room Limit Per Night
                        $hospital_room_limit_per_night = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 49)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $hospital_room_limit_per_night = count($hospital_room_limit_per_night) !== 0 ? $hospital_room_limit_per_night[0]->limit : 0;
                        //chronic conditions developed on cover
                        $chronic_conditions_developed_on_cover = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 55)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $chronic_conditions_developed_on_cover = count($chronic_conditions_developed_on_cover) !== 0 ? $chronic_conditions_developed_on_cover[0]->limit : 0;
                        //covid ip
                        $covid_ip = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 61)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $covid_ip = count($covid_ip) !== 0 ? $covid_ip[0]->limit : 0;
                        //reconstructive surgery 
                        $reconstructive_surgery = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 45)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $reconstructive_surgery = count($reconstructive_surgery) !== 0 ? $reconstructive_surgery[0]->limit : 0;
                        //psychiatric treatment  
                        $psychiatric_treatment = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 53)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $psychiatric_treatment = count($psychiatric_treatment) !== 0 ? $psychiatric_treatment[0]->limit : 0;
                        //congenital_and_genetics   
                        $congenital_and_genetics = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 54)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $congenital_and_genetics = count($congenital_and_genetics) !== 0 ? $congenital_and_genetics[0]->limit : 0;
                        //maternity limit  
                        $maternity_limit = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 59)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $maternity_limit = count($maternity_limit) !== 0 ? $maternity_limit[0]->limit : 0;
                        //delivery and postnatal treatment  
                        $delivery_postnatal_treatment = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 68)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $delivery_postnatal_treatment = count($delivery_postnatal_treatment) !== 0 ? $delivery_postnatal_treatment[0]->limit : 0;
                        //premature cover  
                        $premature_cover = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 43)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $premature_cover = count($premature_cover) !== 0 ? $premature_cover[0]->limit : 0;
                        //overall_annual_outpatient  
                        $overall_annual_outpatient = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 38)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $overall_annual_outpatient = count($overall_annual_outpatient) !== 0 ? $overall_annual_outpatient[0]->limit : 0;
                        //antenatal  
                        $antenatal_postnatal = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 5)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $antenatal_postnatal = count($antenatal_postnatal) !== 0 ? $antenatal_postnatal[0]->limit : 0;
                        //preexisting_chronic_conditions  
                        $preexisting_chronic_conditions = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 8)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $preexisting_chronic_conditions = count($preexisting_chronic_conditions) !== 0 ? $preexisting_chronic_conditions[0]->limit : 0;
                        //covid_op  
                        $covid_op = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 60)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $covid_op = count($covid_op) !== 0 ? $covid_op[0]->limit : 0;
                        //optometry_op  
                        $optometry_op = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 83)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $optometry_op = count($optometry_op) !== 0 ? $optometry_op[0]->limit : 0;
                        //dental_op  
                        $dental_op = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 11)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $dental_op = count($dental_op) !== 0 ? $dental_op[0]->limit : 0;
                        //annual_health_checks  
                        $annual_health_checks = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 4)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $annual_health_checks = count($annual_health_checks) !== 0 ? $annual_health_checks[0]->limit : 0;
                        //personal_accidents_children  
                        $personal_accidents_children = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 52)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $personal_accidents_children = count($personal_accidents_children) !== 0 ? $personal_accidents_children[0]->limit : 0;
                        //personal_accidents_adult 
                        $personal_accidents_adult = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 42)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $personal_accidents_adult = count($personal_accidents_adult) !== 0 ? $personal_accidents_adult[0]->limit : 0;

                        //Funeral
                        $funeral = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 20)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $funeral = count($funeral) !== 0 ? $funeral[0]->limit : 0;
                        //Total permanent disability
                        $total_permanent_disability = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 20)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $total_permanent_disability = count($total_permanent_disability) !== 0 ? $total_permanent_disability[0]->limit : 0;
                        //Enhanced Corporate Annual Overall Limit
                        $enhanced_corporate_annual_overall_limit = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 14)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $enhanced_corporate_annual_overall_limit = count($enhanced_corporate_annual_overall_limit) !== 0 ? $enhanced_corporate_annual_overall_limit[0]->limit : 0;
                        //outpatient fund
                        $outpatient_fund = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 38)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->munomukabi_corporate)
                            ->get();

                        $outpatient_fund = count($outpatient_fund) !== 0 ? $outpatient_fund[0]->limit : 0;


                        break;

                    case '2':
                        $client_no = $request->munomukabi_member;
                        //client name                     
                        $members = MemberInfo::select(
                            DB::raw("CONCAT(member_INFO.surname, ' ',MEMBER_INFO.first_name,' ',MEMBER_INFO.other_names) as names"),
                        )
                            ->where('member_no', $request->munomukabi_member)
                            ->orderBy('names', 'asc')
                            ->get();

                        $client_name = count($members) !== 0 ? $members[0]->names : '';
                        //product name
                        $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                        $product_name = $product_name[0];
                        $ls_hpname = $request->category . ' - ' . $product_name;
                        $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                            ->where('health_plan', $request->category)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->count('member_no');

                        //overall annual limit  per person
                        $overall_annual_limit = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 10) //Corporate Card Annual Overall Limit
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $overall_annual_limit = count($overall_annual_limit) !== 0 ? $overall_annual_limit[0]->limit : 0;
                        //Hospital Room Limit Per Night
                        $hospital_room_limit_per_night = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 49)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $hospital_room_limit_per_night = count($hospital_room_limit_per_night) !== 0 ? $hospital_room_limit_per_night[0]->limit : 0;
                        //chronic conditions developed on cover
                        $chronic_conditions_developed_on_cover = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 55)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $chronic_conditions_developed_on_cover = count($chronic_conditions_developed_on_cover) !== 0 ? $chronic_conditions_developed_on_cover[0]->limit : 0;
                        //covid ip
                        $covid_ip = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 61)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $covid_ip = count($covid_ip) !== 0 ? $covid_ip[0]->limit : 0;
                        //reconstructive surgery 
                        $reconstructive_surgery = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 45)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $reconstructive_surgery = count($reconstructive_surgery) !== 0 ? $reconstructive_surgery[0]->limit : 0;
                        //psychiatric treatment  
                        $psychiatric_treatment = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 53)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $psychiatric_treatment = count($psychiatric_treatment) !== 0 ? $psychiatric_treatment[0]->limit : 0;
                        //congenital_and_genetics   
                        $congenital_and_genetics = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 54)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $congenital_and_genetics = count($congenital_and_genetics) !== 0 ? $congenital_and_genetics[0]->limit : 0;
                        //maternity limit  
                        $maternity_limit = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 59)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $maternity_limit = count($maternity_limit) !== 0 ? $maternity_limit[0]->limit : 0;
                        //delivery and postnatal treatment  
                        $delivery_postnatal_treatment = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 68)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $delivery_postnatal_treatment = count($delivery_postnatal_treatment) !== 0 ? $delivery_postnatal_treatment[0]->limit : 0;
                        //premature cover  
                        $premature_cover = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 43)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $premature_cover = count($premature_cover) !== 0 ? $premature_cover[0]->limit : 0;
                        //overall_annual_outpatient  
                        $overall_annual_outpatient = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 38)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $overall_annual_outpatient = count($overall_annual_outpatient) !== 0 ? $overall_annual_outpatient[0]->limit : 0;
                        //antenatal  
                        $antenatal_postnatal = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 5)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $antenatal_postnatal = count($antenatal_postnatal) !== 0 ? $antenatal_postnatal[0]->limit : 0;
                        //preexisting_chronic_conditions  
                        $preexisting_chronic_conditions = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 8)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $preexisting_chronic_conditions = count($preexisting_chronic_conditions) !== 0 ? $preexisting_chronic_conditions[0]->limit : 0;
                        //covid_op  
                        $covid_op = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 60)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $covid_op = count($covid_op) !== 0 ? $covid_op[0]->limit : 0;
                        //optometry_op  
                        $optometry_op = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 83)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $optometry_op = count($optometry_op) !== 0 ? $optometry_op[0]->limit : 0;
                        //dental_op  
                        $dental_op = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 11)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $dental_op = count($dental_op) !== 0 ? $dental_op[0]->limit : 0;
                        //annual_health_checks  
                        $annual_health_checks = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 4)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $annual_health_checks = count($annual_health_checks) !== 0 ? $annual_health_checks[0]->limit : 0;
                        //personal_accidents_children  
                        $personal_accidents_children = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 52)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $personal_accidents_children = count($personal_accidents_children) !== 0 ? $personal_accidents_children[0]->limit : 0;
                        //personal_accidents_adult 
                        $personal_accidents_adult = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 42)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $personal_accidents_adult = count($personal_accidents_adult) !== 0 ? $personal_accidents_adult[0]->limit : 0;

                        //Funeral
                        $funeral = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 20)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $funeral = count($funeral) !== 0 ? $funeral[0]->limit : 0;
                        //Total permanent disability
                        $total_permanent_disability = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 20)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $total_permanent_disability = count($total_permanent_disability) !== 0 ? $total_permanent_disability[0]->limit : 0;
                        //Enhanced Corporate Annual Overall Limit
                        $enhanced_corporate_annual_overall_limit = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 14)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $enhanced_corporate_annual_overall_limit = count($enhanced_corporate_annual_overall_limit) !== 0 ? $enhanced_corporate_annual_overall_limit[0]->limit : 0;
                        //Enhanced Corporate Annual Overall Limit
                        $outpatient_fund = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 38)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->munomukabi_member)
                            ->get();

                        $outpatient_fund = count($outpatient_fund) !== 0 ? $outpatient_fund[0]->limit : 0;


                        break;
                }

                $data = [
                    'policy_no' => '',
                    'client_name' => $client_name,
                    'client_no' => $client_no,
                    'health_plan' => $ls_hpname,
                    'lives' => $ll_lives,
                    // 'zone' => 'East Africa Covered',
                    // 'health_promotions' => 'Upto Four health talks per year',
                    'overall_annual_limit' => $overall_annual_limit,
                    // 'illness_accident_hospitalization' => 'Covered upto OAL',
                    // 'admissions_icu' => 'Covered within the respective benefit Limit',
                    'room_limit' => $hospital_room_limit_per_night,
                    'chronic_conditions_developed_on_cover' => $chronic_conditions_developed_on_cover,
                    // 'preexisting_conditions' => 'Covered upto CDL Limit',
                    // 'oncology_tests_drugs_and_consultation_ip' => 'Not Covered',
                    // 'oncology_tests_drugs_and_consultation_op' => 'Not Covered',
                    'covid19_ip' => $covid_ip,
                    // 'nursing_fees' => 'Covered',
                    // 'general_surgery' => 'Covered',
                    'reconstructive_surgery' => $reconstructive_surgery,
                    // 'prescribed_medicines' => 'Covered',
                    // 'internal_prostheses' => 'Covered',
                    // 'xray_mri_petscan_ctscan' => 'Covered',
                    // 'pathology' => 'Covered',
                    'psychiatric_treatment' => $psychiatric_treatment,
                    // 'physiotherapy' => 'Covered within the Respective Benefit Limit',
                    // 'parent_accomodation' => 'Covered upto Room Limit',
                    'congenital_and_genetics' => $congenital_and_genetics,
                    // 'inpatient_treatment_hiv' => "Covered upto CDL Limit",
                    // 'outpatient_treatment_hiv' => "Covered upto CDL Limit",
                    // 'evacuation' => 'Covered',
                    // 'international_emergency_medical' => 'Covered',
                    // 'international_emergency_rescue' => 'Covered',
                    'maternity' => $maternity_limit,
                    'delivery_postnatal_treatment' => $delivery_postnatal_treatment,
                    'premature_cover' => $premature_cover,
                    // 'new_born_baby_care' => 'Covered within Delivery Limit',
                    // 'complications_pregnancy' => 'Covered within Delivery Limit',
                    'overall_annual_outpatient' => $overall_annual_outpatient,
                    'antenatal_postnatal' => $antenatal_postnatal,
                    'preexisting_chronic_conditions' => $preexisting_chronic_conditions,
                    // 'oncology_tests_drugs_and_consultation_op' => 'Not Covered',
                    // 'oncology_tests_drugs_and_consultation_ip' => 'Not Covered',
                    'covid19_op' => $covid_op,
                    // 'general_practitioners' => 'Covered',
                    // 'prescribed_medicines' => 'Covered',
                    // 'physiotherapy' => 'Covered within the Respective Benefit Limit',
                    // 'post_hospitalization_treatment' => 'Covered',
                    // 'outpatient_surgical_operations' => 'Covered',
                    // 'outpatient_treatment_hiv' => 'Covered  Upto  CDL Limit',
                    // 'inpatient_treatment_hiv_text' => 'Covered  Upto  CDL Limit',
                    'optometry_limit' => $optometry_op,
                    // 'general_eye_examinations' => 'Covered',
                    // 'consultation_and_eye_treatment' => 'Covered',
                    // 'simple_outer_surgeries' => 'Covered',
                    // 'visual_acuity_tests' => 'Covered',
                    // 'lens_frames_glasses' => 'Covered',
                    'dental_limit' => $dental_op,
                    // 'consultation_and_treatment' => 'Covered',
                    // 'extractions' => 'Covered',
                    // 'infections' => 'Covered',
                    // 'fillings' => 'Covered',
                    // 'minor_surgeries' => 'Covered',
                    // 'root_canal' => 'Covered',
                    // 'x_ray' => 'Covered',
                    // 'non_surgical_extractions' => 'Covered',
                    // 'scaling_and_polishing' => 'Covered',
                    'annual_health_checks' => $annual_health_checks,
                    'personal_accident_child' => $personal_accidents_children,
                    'personal_accident' => $personal_accidents_adult,
                    // 'health_promotions' => 'upto four health talks per year',
                    'funeral' => $funeral,
                    'outpatient_fund' => $outpatient_fund,
                    // 'illness_hospitalization' => '60 days',
                    // 'maternity_cover' => '12 Months',
                    // 'chronics_development_on_cover' => '6 Months'                  
                ];
                break;
            case 'bancasurance':
                switch ($request->scheme) {
                    case '1':
                        $client_no = $request->bancasurance_corporate;
                        //client name explode
                        $client_name = Corporate::select('corporate')->where('corp_id', $request->bancasurance_corporate)->get();
                        $client_name = count($client_name) !== 0 ? $client_name[0]->corporate : '';
                        //product name
                        $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                        $product_name = $product_name[0];
                        $ls_hpname = $request->category . ' - ' . $product_name;
                        // $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                        //     ->where('health_plan', $request->category)
                        //     ->where('anniv', $request->annivs)
                        //     ->where('member_no', $request->munomukabi_member)
                        //     ->count('member_no');
                        $ll_lives = PrincipalApplicant::where('h_option', $request->product_name)
                            ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                            ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                            ->where('member_anniversary.health_plan', $request->category)
                            ->where('member_anniversary.anniv', $request->annivs)
                            ->where('principal_applicant.corp_id', $request->bancasurance_corporate)
                            ->count('member_info.member_no');

                        //overall annual limit  per person
                        $overall_annual_limit = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 13)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $overall_annual_limit = count($overall_annual_limit) !== 0 ? $overall_annual_limit[0]->limit : 0;
                        //Hospital Room Limit Per Night
                        $hospital_room_limit_per_night = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 49)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $hospital_room_limit_per_night = count($hospital_room_limit_per_night) !== 0 ? $hospital_room_limit_per_night[0]->limit : 0;
                        //chronic conditions developed on cover
                        $chronic_conditions_developed_on_cover = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 55)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $chronic_conditions_developed_on_cover = count($chronic_conditions_developed_on_cover) !== 0 ? $chronic_conditions_developed_on_cover[0]->limit : 0;
                        //covid ip
                        $covid_ip = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 61)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $covid_ip = count($covid_ip) !== 0 ? $covid_ip[0]->limit : 0;
                        //reconstructive surgery 
                        $reconstructive_surgery = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 45)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $reconstructive_surgery = count($reconstructive_surgery) !== 0 ? $reconstructive_surgery[0]->limit : 0;
                        //psychiatric treatment  
                        $psychiatric_treatment = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 53)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $psychiatric_treatment = count($psychiatric_treatment) !== 0 ? $psychiatric_treatment[0]->limit : 0;
                        //congenital_and_genetics   
                        $congenital_and_genetics = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 54)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $congenital_and_genetics = count($congenital_and_genetics) !== 0 ? $congenital_and_genetics[0]->limit : 0;
                        //maternity limit  
                        $maternity_limit = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 59)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $maternity_limit = count($maternity_limit) !== 0 ? $maternity_limit[0]->limit : 0;
                        //delivery and postnatal treatment  
                        $delivery_postnatal_treatment = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 68)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $delivery_postnatal_treatment = count($delivery_postnatal_treatment) !== 0 ? $delivery_postnatal_treatment[0]->limit : 0;
                        //premature cover  
                        $premature_cover = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 43)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $premature_cover = count($premature_cover) !== 0 ? $premature_cover[0]->limit : 0;
                        //overall_annual_outpatient  
                        $overall_annual_outpatient = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 38)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $overall_annual_outpatient = count($overall_annual_outpatient) !== 0 ? $overall_annual_outpatient[0]->limit : 0;
                        //antenatal  
                        $antenatal_postnatal = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 5)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $antenatal_postnatal = count($antenatal_postnatal) !== 0 ? $antenatal_postnatal[0]->limit : 0;
                        //preexisting_chronic_conditions  
                        $preexisting_chronic_conditions = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 8)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $preexisting_chronic_conditions = count($preexisting_chronic_conditions) !== 0 ? $preexisting_chronic_conditions[0]->limit : 0;
                        //covid_op  
                        $covid_op = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 60)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $covid_op = count($covid_op) !== 0 ? $covid_op[0]->limit : 0;
                        //optometry_op  
                        $optometry_op = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 83)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $optometry_op = count($optometry_op) !== 0 ? $optometry_op[0]->limit : 0;
                        //dental_op  
                        $dental_op = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 11)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $dental_op = count($dental_op) !== 0 ? $dental_op[0]->limit : 0;
                        //annual_health_checks  
                        $annual_health_checks = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 4)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $annual_health_checks = count($annual_health_checks) !== 0 ? $annual_health_checks[0]->limit : 0;
                        //personal_accidents_children  
                        $personal_accidents_children = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 52)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $personal_accidents_children = count($personal_accidents_children) !== 0 ? $personal_accidents_children[0]->limit : 0;
                        //personal_accidents_adult 
                        $personal_accidents_adult = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 42)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $personal_accidents_adult = count($personal_accidents_adult) !== 0 ? $personal_accidents_adult[0]->limit : 0;

                        //Funeral
                        $funeral = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 20)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $funeral = count($funeral) !== 0 ? $funeral[0]->limit : 0;
                        //Total permanent disability
                        $total_permanent_disability = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 20)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $total_permanent_disability = count($total_permanent_disability) !== 0 ? $total_permanent_disability[0]->limit : 0;
                        //Enhanced Corporate Annual Overall Limit
                        $enhanced_corporate_annual_overall_limit = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 14)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $enhanced_corporate_annual_overall_limit = count($enhanced_corporate_annual_overall_limit) !== 0 ? $enhanced_corporate_annual_overall_limit[0]->limit : 0;
                        //outpatient fund
                        $outpatient_fund = CorpGroups::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 38)
                            ->where('anniv', $request->annivs)
                            ->where('corp_id', $request->bancasurance_corporate)
                            ->get();

                        $outpatient_fund = count($outpatient_fund) !== 0 ? $outpatient_fund[0]->limit : 0;


                        break;

                    case '2':
                        $client_no = $request->bancasurance_member;
                        //client name                     
                        $members = MemberInfo::select(
                            DB::raw("CONCAT(member_INFO.surname, ' ',MEMBER_INFO.first_name,' ',MEMBER_INFO.other_names) as names"),
                        )
                            ->where('member_no', $request->bancasurance_member)
                            ->orderBy('names', 'asc')
                            ->get();

                        $client_name = count($members) !== 0 ? $members[0]->names : '';
                        //product name
                        $product_name = ProductName::where('code', $request->product_name)->pluck('product_name');
                        $product_name = $product_name[0];
                        $ls_hpname = $request->category . ' - ' . $product_name;
                        $ll_lives = MemberAnniversary::where('h_option', $request->product_name)
                            ->where('health_plan', $request->category)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->count('member_no');

                        //overall annual limit  per person
                        $overall_annual_limit = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 10)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $overall_annual_limit = count($overall_annual_limit) !== 0 ? $overall_annual_limit[0]->limit : 0;
                        //Hospital Room Limit Per Night
                        $hospital_room_limit_per_night = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 49)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $hospital_room_limit_per_night = count($hospital_room_limit_per_night) !== 0 ? $hospital_room_limit_per_night[0]->limit : 0;
                        //chronic conditions developed on cover
                        $chronic_conditions_developed_on_cover = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 55)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $chronic_conditions_developed_on_cover = count($chronic_conditions_developed_on_cover) !== 0 ? $chronic_conditions_developed_on_cover[0]->limit : 0;
                        //covid ip
                        $covid_ip = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 61)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $covid_ip = count($covid_ip) !== 0 ? $covid_ip[0]->limit : 0;
                        //reconstructive surgery 
                        $reconstructive_surgery = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 45)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $reconstructive_surgery = count($reconstructive_surgery) !== 0 ? $reconstructive_surgery[0]->limit : 0;
                        //psychiatric treatment  
                        $psychiatric_treatment = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 53)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $psychiatric_treatment = count($psychiatric_treatment) !== 0 ? $psychiatric_treatment[0]->limit : 0;
                        //congenital_and_genetics   
                        $congenital_and_genetics = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 54)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $congenital_and_genetics = count($congenital_and_genetics) !== 0 ? $congenital_and_genetics[0]->limit : 0;
                        //maternity limit  
                        $maternity_limit = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 59)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $maternity_limit = count($maternity_limit) !== 0 ? $maternity_limit[0]->limit : 0;
                        //delivery and postnatal treatment  
                        $delivery_postnatal_treatment = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 68)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $delivery_postnatal_treatment = count($delivery_postnatal_treatment) !== 0 ? $delivery_postnatal_treatment[0]->limit : 0;
                        //premature cover  
                        $premature_cover = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 43)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $premature_cover = count($premature_cover) !== 0 ? $premature_cover[0]->limit : 0;
                        //overall_annual_outpatient  
                        $overall_annual_outpatient = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 38)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $overall_annual_outpatient = count($overall_annual_outpatient) !== 0 ? $overall_annual_outpatient[0]->limit : 0;
                        //antenatal  
                        $antenatal_postnatal = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 5)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $antenatal_postnatal = count($antenatal_postnatal) !== 0 ? $antenatal_postnatal[0]->limit : 0;
                        //preexisting_chronic_conditions  
                        $preexisting_chronic_conditions = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 8)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $preexisting_chronic_conditions = count($preexisting_chronic_conditions) !== 0 ? $preexisting_chronic_conditions[0]->limit : 0;
                        //covid_op  
                        $covid_op = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 60)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $covid_op = count($covid_op) !== 0 ? $covid_op[0]->limit : 0;
                        //optometry_op  
                        $optometry_op = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 83)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $optometry_op = count($optometry_op) !== 0 ? $optometry_op[0]->limit : 0;
                        //dental_op  
                        $dental_op = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 11)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $dental_op = count($dental_op) !== 0 ? $dental_op[0]->limit : 0;
                        //annual_health_checks  
                        $annual_health_checks = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 4)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $annual_health_checks = count($annual_health_checks) !== 0 ? $annual_health_checks[0]->limit : 0;
                        //personal_accidents_children  
                        $personal_accidents_children = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 52)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $personal_accidents_children = count($personal_accidents_children) !== 0 ? $personal_accidents_children[0]->limit : 0;
                        //personal_accidents_adult 
                        $personal_accidents_adult = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 42)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $personal_accidents_adult = count($personal_accidents_adult) !== 0 ? $personal_accidents_adult[0]->limit : 0;

                        //Funeral
                        $funeral = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 20)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $funeral = count($funeral) !== 0 ? $funeral[0]->limit : 0;
                        //Total permanent disability
                        $total_permanent_disability = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 20)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $total_permanent_disability = count($total_permanent_disability) !== 0 ? $total_permanent_disability[0]->limit : 0;
                        //Enhanced Corporate Annual Overall Limit
                        $enhanced_corporate_annual_overall_limit = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 14)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $enhanced_corporate_annual_overall_limit = count($enhanced_corporate_annual_overall_limit) !== 0 ? $enhanced_corporate_annual_overall_limit[0]->limit : 0;
                        //Enhanced Corporate Annual Overall Limit
                        $outpatient_fund = MemberBenefits::select('limit')
                            ->where('product_name', $request->product_name)
                            ->where('category', $request->category)
                            ->where('benefit', 38)
                            ->where('anniv', $request->annivs)
                            ->where('member_no', $request->bancasurance_member)
                            ->get();

                        $outpatient_fund = count($outpatient_fund) !== 0 ? $outpatient_fund[0]->limit : 0;


                        break;
                }

                $data = [
                    'policy_no' => '',
                    'client_name' => $client_name,
                    'client_no' => $client_no,
                    'health_plan' => $ls_hpname,
                    'lives' => $ll_lives,
                    // 'zone' => 'East Africa Covered',
                    // 'health_promotions' => 'Upto Four health talks per year',
                    'overall_annual_limit' => $overall_annual_limit,
                    // 'illness_accident_hospitalization' => 'Covered upto OAL',
                    // 'admissions_icu' => 'Covered within the respective benefit Limit',
                    'room_limit' => $hospital_room_limit_per_night,
                    'chronic_conditions_developed_on_cover' => $chronic_conditions_developed_on_cover,
                    // 'preexisting_conditions' => 'Covered upto CDL Limit',
                    // 'oncology_tests_drugs_and_consultation_ip' => 'Not Covered',
                    // 'oncology_tests_drugs_and_consultation_op' => 'Not Covered',
                    'covid19_ip' => $covid_ip,
                    // 'nursing_fees' => 'Covered',
                    // 'general_surgery' => 'Covered',
                    'reconstructive_surgery' => $reconstructive_surgery,
                    // 'prescribed_medicines' => 'Covered',
                    // 'internal_prostheses' => 'Covered',
                    // 'xray_mri_petscan_ctscan' => 'Covered',
                    // 'pathology' => 'Covered',
                    'psychiatric_treatment' => $psychiatric_treatment,
                    // 'physiotherapy' => 'Covered within the Respective Benefit Limit',
                    // 'parent_accomodation' => 'Covered upto Room Limit',
                    'congenital_and_genetics' => $congenital_and_genetics,
                    // 'inpatient_treatment_hiv' => "Covered upto CDL Limit",
                    // 'outpatient_treatment_hiv' => "Covered upto CDL Limit",
                    // 'evacuation' => 'Covered',
                    // 'international_emergency_medical' => 'Covered',
                    // 'international_emergency_rescue' => 'Covered',
                    'maternity' => $maternity_limit,
                    'delivery_postnatal_treatment' => $delivery_postnatal_treatment,
                    'premature_cover' => $premature_cover,
                    // 'new_born_baby_care' => 'Covered within Delivery Limit',
                    // 'complications_pregnancy' => 'Covered within Delivery Limit',
                    'overall_annual_outpatient' => $overall_annual_outpatient,
                    'antenatal_postnatal' => $antenatal_postnatal,
                    'preexisting_chronic_conditions' => $preexisting_chronic_conditions,
                    // 'oncology_tests_drugs_and_consultation_op' => 'Not Covered',
                    // 'oncology_tests_drugs_and_consultation_ip' => 'Not Covered',
                    'covid19_op' => $covid_op,
                    // 'general_practitioners' => 'Covered',
                    // 'prescribed_medicines' => 'Covered',
                    // 'physiotherapy' => 'Covered within the Respective Benefit Limit',
                    // 'post_hospitalization_treatment' => 'Covered',
                    // 'outpatient_surgical_operations' => 'Covered',
                    // 'outpatient_treatment_hiv' => 'Covered  Upto  CDL Limit',
                    // 'inpatient_treatment_hiv_text' => 'Covered  Upto  CDL Limit',
                    'optometry_limit' => $optometry_op,
                    // 'general_eye_examinations' => 'Covered',
                    // 'consultation_and_eye_treatment' => 'Covered',
                    // 'simple_outer_surgeries' => 'Covered',
                    // 'visual_acuity_tests' => 'Covered',
                    // 'lens_frames_glasses' => 'Covered',
                    'dental_limit' => $dental_op,
                    // 'consultation_and_treatment' => 'Covered',
                    // 'extractions' => 'Covered',
                    // 'infections' => 'Covered',
                    // 'fillings' => 'Covered',
                    // 'minor_surgeries' => 'Covered',
                    // 'root_canal' => 'Covered',
                    // 'x_ray' => 'Covered',
                    // 'non_surgical_extractions' => 'Covered',
                    // 'scaling_and_polishing' => 'Covered',
                    'annual_health_checks' => $annual_health_checks,
                    'personal_accident_child' => $personal_accidents_children,
                    'personal_accident' => $personal_accidents_adult,
                    // 'health_promotions' => 'upto four health talks per year',
                    'funeral' => $funeral,
                    'outpatient_fund' => $outpatient_fund,
                    // 'illness_hospitalization' => '60 days',
                    // 'maternity_cover' => '12 Months',
                    // 'chronics_development_on_cover' => '6 Months'                  
                ];
                break;
        }

        return $data;
    }

    //save policy document
    public function savePolicyDocument(Request $request)
    {
        // return $request;      
        $response = [];
        $create_date = date("Y-m-d");
        $today = date("Ym");
        $policy_no_count = PolicyDocument::where(DB::raw("FORMAT(create_date, 'yyyymm')"), $today)->count('policy_no');

        $policy_no_count = is_null($policy_no_count) ? 0 : $policy_no_count;
        $policy_no_count = $policy_no_count + 1;
        $policy_no = 'AGIU/' . $today . '/' .  $policy_no_count;

        //set inserted, updated, and deleted items
        $inserted_item = "policy_no : $policy_no";
        $updated_item = null;
        $deleted_item = null;


        switch ($request->client_type) {
            case 'corporate':
                $save_policy_corporate = PolicyDocument::create([
                    'policy_no' => $policy_no,
                    'corp_id' => $request->corp_id,
                    'anniv' => $request->anniv,
                    'zone' => $request->zone,
                    'health_plan' => $request->health_plan,
                    'lives' => $request->lives,
                    'promotions' => $request->promotions,
                    'overall_annual_limit' => $this->replaceCommas($request->overall_annual_limit),
                    'accident_hosp' => $this->replaceCommas(
                        $request->accident_hosp
                    ),
                    'illness_hosp' => $this->replaceCommas(
                        $request->illness_hosp
                    ),
                    'admissions_icu' => $this->replaceCommas(
                        $request->admissions_icu
                    ),
                    'room_limit' => $this->replaceCommas(
                        $request->room_limit
                    ),
                    'preexisting_cond' => $this->replaceCommas(
                        $request->preexisting_cond
                    ),
                    'oncology_tests' => $this->replaceCommas($request->oncology_tests),
                    'nursing_fees' => $this->replaceCommas($request->nursing_fees),
                    'general_surgery' => $this->replaceCommas($request->general_surgery),
                    'reconstructive_surgery' => $this->replaceCommas(
                        $request->reconstructive_surgery
                    ),
                    'prescribed_drugs' => $this->replaceCommas($request->prescribed_drugs),
                    'internal_prostheses' => $this->replaceCommas($request->internal_prostheses),
                    'xrays' => $this->replaceCommas($request->xrays),
                    'pathology' => $this->replaceCommas($request->pathology),
                    'acc_damage_natural_eyes' =>  $this->replaceCommas(
                        $request->acc_damage_natural_eyes
                    ),
                    'acc_damage_natural_teeth' =>  $this->replaceCommas(
                        $request->acc_damage_natural_teeth
                    ),
                    'psychiatric_treatment' => $this->replaceCommas(
                        $request->psychiatric_treatment
                    ),
                    'physiotherapy' =>  $this->replaceCommas($request->physiotherapy),
                    'parent_accommodation' => $this->replaceCommas($request->parent_accommodation),
                    'congenital_conditions' => $this->replaceCommas(
                        $request->congenital_conditions
                    ),
                    'inpatient_treatment_hiv' => $this->replaceCommas(
                        $request->inpatient_treatment_hiv
                    ),
                    'evacuation' =>  $this->replaceCommas($request->evacuation),
                    'international_emergency_medical' =>  $this->replaceCommas($request->international_emergency_medical),
                    // 'international_emergency_rescue' => $this->replaceCommas($request->international_emergency_rescue),
                    'maternity' => $this->replaceCommas($request->maternity),
                    'normal_delivery' => $this->replaceCommas(
                        $request->normal_delivery
                    ),
                    'caesarian_section' => $this->replaceCommas(
                        $request->caesarian_section
                    ),
                    'premature_cover' =>  $this->replaceCommas(
                        $request->premature_cover
                    ),
                    'complications_pregnancy' => $this->replaceCommas($request->complications_pregnancy),
                    'op_annual_limit' => $this->replaceCommas(
                        $request->op_annual_limit
                    ),
                    'preexisting_conditions' => $this->replaceCommas($request->preexisting_conditions),
                    'op_oncology_tests' =>  $this->replaceCommas($request->op_oncology_tests),
                    'general_practitioners' => $this->replaceCommas($request->general_practitioners),
                    'antenatal_postnatal' => $this->replaceCommas($request->antenatal_postnatal),
                    'prescribed_medicines' => $this->replaceCommas($request->prescribed_medicines),
                    'op_physiotherapy' => $this->replaceCommas($request->op_physiotherapy),
                    'op_psychiatric_treatment' => $this->replaceCommas(
                        $request->op_psychiatric_treatment
                    ),
                    'op_congenital_conditions' =>  $this->replaceCommas($request->op_congenital_conditions),
                    'post_hospitalisation_treatment' => $this->replaceCommas($request->post_hospitalisation_treatment),
                    'outpatient_surgical_operations' => $this->replaceCommas($request->outpatient_surgical_operations),
                    'outpatient_treatment_of_hiv' => $this->replaceCommas(
                        $request->outpatient_treatment_of_hiv
                    ),
                    'general_eye_examinations' => $this->replaceCommas($request->general_eye_examinations),
                    'frames_lenses' =>  $this->replaceCommas($request->frames_lenses),
                    'dental_limit' => $this->replaceCommas(
                        $request->dental_limit
                    ),
                    'dental_consultation_treatment' => $this->replaceCommas($request->dental_consultation_treatment),
                    'scaling_polishing' => $this->replaceCommas($request->scaling_polishing),
                    'annual_health_checks' => $this->replaceCommas(
                        $request->annual_health_checks
                    ),
                    'personal_accident' => $this->replaceCommas(
                        $request->personal_accident
                    ),
                    'last_expenses' => $this->replaceCommas(
                        $request->last_expenses
                    ),
                    'illness_hospitalization' =>  $this->replaceCommas($request->illness_hospitalization),
                    'maternity_wp' =>  $this->replaceCommas($request->maternity_wp),
                    'provider_network' => $this->replaceCommas($request->provider_network),
                    'create_date' => $create_date,
                    'date_entered' => $request->date_entered,
                    'user_id' => $request->user_id,
                    'op_option' => $this->replaceCommas($request->op_option),
                    'enroll_one' => $request->enroll_one,
                    'enroll_two' => $request->enroll_two,
                    'enroll_three' => $request->enroll_three,
                    'enroll_four' => $request->enroll_four,
                    'enroll_five' => $request->enroll_five,
                    'enroll_six' => $request->enroll_six,
                    'enroll_seven' => $request->enroll_seven,
                    'enroll_elleven' => $request->enroll_elleven,
                    'member_no' => $request->member_no,
                    'net_premium' => $this->replaceCommas($request->net_premium),
                    'ip_psychiatric_treatment' => $this->replaceCommas($request->ip_psychiatric_treatment),
                    'ip_congenital_conditions' => $this->replaceCommas(
                        $request->ip_congenital_conditions
                    ),
                    'personal_accident_child' => $this->replaceCommas(
                        $request->personal_accident_child
                    ),
                    'admissions_icu_chronic' => $this->replaceCommas(
                        $request->admissions_icu_chronic
                    ),
                    'admissions_icu_accident' => $this->replaceCommas(
                        $request->admissions_icu_accident
                    ),
                    'reconstructive_surgery_acc' => $this->replaceCommas(
                        $request->reconstructive_surgery_acc
                    ),
                    'deleted' => 0,
                    'client_premium' => $request->client_premium,
                    // 'ip_chronic_conditions' => $this->replaceCommas($request->ip_chronic_conditions),
                    // 'op_chronic_conditions' => $this->replaceCommas($request->op_chronic_conditions),
                    'chronic_wp' =>  $this->replaceCommas($request->chronic_wp),
                    'minimum_age' => (int)$request->minimum_age,
                    'medex' => (int)$request->medex,
                    'maximum_age' => (int)$request->maximum_age,
                    'enroll_eight' => $request->enroll_eight,
                    'enroll_nine' => $request->enroll_nine,
                    'enroll_ten' => $request->enroll_ten,
                    'nursery_care' => $this->replaceCommas(
                        $request->nursery_care
                    ),
                    'covid19_op' => $this->replaceCommas(
                        $request->covid19_op
                    ),
                    'covid19_ip' => $this->replaceCommas($request->covid19_ip),
                    'optometry_limit' => $this->replaceCommas($request->optometry_limit),
                    'optical_overall' => $this->replaceCommas($request->optical_overall),
                    'consultation_and_eye_treatment' =>  $this->replaceCommas($request->consultation_and_eye_treatment),
                    'ophthalmology' =>  $this->replaceCommas($request->ophthalmology),
                    'lenses' =>  $this->replaceCommas($request->lenses),
                    'frames' =>  $this->replaceCommas($request->frames),
                    'congenital_premature_and_genetic_condition_defects' =>  $this->replaceCommas($request->congenital_premature_and_genetic_condition_defects)
                ]);

                if ($save_policy_corporate->wasRecentlyCreated === true) {
                    array_push($response, "Corporate policy document saved successfully with policy no $policy_no");
                } else {
                    array_push($response, "Error saving corporate policy document");
                }
                break;
            case 'sme':
                $save_policy_retail = PolicyDocument::create([
                    'policy_no' => $policy_no,
                    'corp_id' => $request->corp_id,
                    'user_id' => $request->user_id,
                    'anniv' => $request->anniv,
                    'zone' => $request->zone,
                    'health_plan' => $request->health_plan,
                    'lives' => $request->lives,
                    'promotions' => $this->replaceCommas(
                        $request->promotions
                    ),
                    'overall_annual_limit' => $this->replaceCommas($request->overall_annual_limit),
                    'accident_hosp' => $this->replaceCommas(
                        $request->accident_hosp
                    ),
                    'illness_hosp' => $this->replaceCommas(
                        $request->illness_hosp
                    ),
                    'admissions_icu' => $this->replaceCommas(
                        $request->admissions_icu
                    ),
                    'room_limit' => $this->replaceCommas(
                        $request->room_limit
                    ),
                    'preexisting_cond' => $this->replaceCommas(
                        $request->preexisting_cond
                    ),
                    'oncology_tests' => $this->replaceCommas(
                        $request->oncology_tests
                    ),
                    'nursing_fees' => $this->replaceCommas(
                        $request->nursing_fees
                    ),
                    'general_surgery' => $this->replaceCommas(
                        $request->general_surgery
                    ),
                    'reconstructive_surgery' => $this->replaceCommas(
                        $request->reconstructive_surgery
                    ),
                    'prescribed_drugs' => $this->replaceCommas(
                        $request->prescribed_drugs
                    ),
                    'internal_prostheses' => $this->replaceCommas(
                        $request->internal_prostheses
                    ),
                    'xrays' => $this->replaceCommas(
                        $request->xrays
                    ),
                    'pathology' => $this->replaceCommas(
                        $request->pathology
                    ),
                    'acc_damage_natural_eyes' =>  $this->replaceCommas(
                        $request->acc_damage_natural_eyes
                    ),
                    'acc_damage_natural_teeth' =>  $this->replaceCommas(
                        $request->acc_damage_natural_teeth
                    ),
                    'psychiatric_treatment' => $this->replaceCommas(
                        $request->psychiatric_treatment
                    ),
                    'physiotherapy' => $this->replaceCommas(
                        $request->physiotherapy
                    ),
                    'parent_accommodation' => $this->replaceCommas(
                        $request->parent_accommodation
                    ),
                    'congenital_conditions' => $this->replaceCommas(
                        $request->congenital_conditions
                    ),
                    'inpatient_treatment_hiv' => $this->replaceCommas(
                        $request->inpatient_treatment_hiv
                    ),
                    'evacuation' => $this->replaceCommas(
                        $request->evacuation
                    ),
                    'international_emergency_medical' => $this->replaceCommas(
                        $request->international_emergency_medical
                    ),
                    // 'international_emergency_rescue' => $this->replaceCommas(
                    //     $request->international_emergency_rescue
                    // ),
                    'maternity' => $this->replaceCommas(
                        $request->maternity
                    ),
                    'normal_delivery' => $this->replaceCommas(
                        $request->normal_delivery
                    ),
                    'caesarian_section' => $this->replaceCommas($request->caesarian_section),
                    'premature_cover' => $this->replaceCommas(
                        $request->premature_cover
                    ),
                    'complications_pregnancy' => $request->complications_pregnancy,
                    'op_annual_limit' => $this->replaceCommas(
                        $request->op_annual_limit
                    ),
                    'preexisting_conditions' => $this->replaceCommas(
                        $request->preexisting_conditions
                    ),
                    'op_oncology_tests' => $this->replaceCommas(
                        $request->op_oncology_tests
                    ),
                    'general_practitioners' => $this->replaceCommas(
                        $request->general_practitioners
                    ),
                    'antenatal_postnatal' => $this->replaceCommas(
                        $request->antenatal_postnatal
                    ),
                    'prescribed_medicines' => $this->replaceCommas(
                        $request->prescribed_medicines
                    ),
                    'op_physiotherapy' => $this->replaceCommas(
                        $request->op_physiotherapy
                    ),
                    'op_psychiatric_treatment' => $this->replaceCommas(
                        $request->op_psychiatric_treatment
                    ),
                    'op_congenital_conditions' => $this->replaceCommas(
                        $request->op_congenital_conditions
                    ),
                    'post_hospitalisation_treatment' => $this->replaceCommas(
                        $request->post_hospitalisation_treatment
                    ),
                    'outpatient_surgical_operations' => $this->replaceCommas(
                        $request->outpatient_surgical_operations
                    ),
                    'outpatient_treatment_of_hiv' => $this->replaceCommas(
                        $request->outpatient_treatment_of_hiv
                    ),
                    'general_eye_examinations' => $this->replaceCommas(
                        $request->general_eye_examinations
                    ),
                    'frames_lenses' => $this->replaceCommas(
                        $request->frames_lenses
                    ),
                    'dental_limit' => $this->replaceCommas(
                        $request->dental_limit
                    ),
                    'dental_consultation_treatment' => $this->replaceCommas(
                        $request->dental_consultation_treatment
                    ),
                    'scaling_polishing' => $this->replaceCommas($request->scaling_polishing),
                    'annual_health_checks' => $this->replaceCommas(
                        $request->annual_health_checks
                    ),
                    'personal_accident' => $this->replaceCommas(
                        $request->personal_accident
                    ),
                    'last_expenses' => $this->replaceCommas(
                        $request->last_expenses
                    ),
                    'illness_hospitalization' => $this->replaceCommas(
                        $request->illness_hospitalization
                    ),
                    'maternity_wp' => $this->replaceCommas(
                        $request->maternity_wp
                    ),
                    'provider_network' => $this->replaceCommas(
                        $request->provider_network
                    ),
                    'create_date' => $create_date,
                    'date_entered' => $request->date_entered,
                    'user_id' => $request->user_id,
                    'op_option' => $this->replaceCommas(
                        $request->op_option
                    ),
                    'enroll_one' => $request->enroll_one,
                    'enroll_two' => $request->enroll_two,
                    'enroll_three' => $request->enroll_three,
                    'enroll_four' => $request->enroll_four,
                    'enroll_five' => $request->enroll_five,
                    'enroll_six' => $request->enroll_six,
                    'enroll_seven' => $request->enroll_seven,
                    'enroll_eight' => $request->enroll_eight,
                    'enroll_nine' => $request->enroll_nine,
                    'enroll_ten' => $request->enroll_ten,
                    'enroll_elleven' => $request->enroll_elleven,
                    'member_no' => $request->member_no,
                    'net_premium' => $this->replaceCommas(
                        $request->net_premium
                    ),
                    'ip_psychiatric_treatment' => $this->replaceCommas(
                        $request->ip_psychiatric_treatment
                    ),
                    'ip_congenital_conditions' => $this->replaceCommas(
                        $request->ip_congenital_conditions
                    ),
                    'personal_accident_child' => $this->replaceCommas(
                        $request->personal_accident_child
                    ),
                    'admissions_icu_chronic' => $this->replaceCommas(
                        $request->admissions_icu_chronic
                    ),
                    'admissions_icu_accident' => $this->replaceCommas(
                        $request->admissions_icu_accident
                    ),
                    'reconstructive_surgery_acc' => $this->replaceCommas(
                        $request->reconstructive_surgery_acc
                    ),
                    'deleted' => 0,
                    'client_premium' => $request->client_premium,
                    'ip_chronic_conditions' => $this->replaceCommas($request->ip_chronic_conditions),
                    'op_chronic_conditions' => $this->replaceCommas($request->op_chronic_conditions),
                    'chronic_wp' => $this->replaceCommas($request->chronic_wp),
                    'minimum_age' => (int)$request->minimum_age,
                    'medex' => (int)$request->medex,
                    'maximum_age' => (int)$request->maximum_age,
                    'enroll_eight' => $request->enroll_eight,
                    'enroll_nine' => $request->enroll_nine,
                    'enroll_ten' => $request->enroll_ten,
                    'nursery_care' => $this->replaceCommas(
                        $request->nursery_care
                    ),
                    'covid19_op' => $this->replaceCommas(
                        $request->covid19_op
                    ),
                    'covid19_ip' => $this->replaceCommas($request->covid19_ip),
                    'optometry_limit' => $this->replaceCommas($request->optometry_limit),
                    'optical_overall' => $this->replaceCommas($request->optical_overall),
                    'consultation_and_eye_treatment' =>  $this->replaceCommas($request->consultation_and_eye_treatment),
                    'ophthalmology' =>  $this->replaceCommas($request->ophthalmology),
                    'lenses' =>  $this->replaceCommas($request->lenses),
                    'frames' =>  $this->replaceCommas($request->frames),
                    'congenital_premature_and_genetic_condition_defects' =>  $this->replaceCommas($request->congenital_premature_and_genetic_condition_defects)
                ]);

                if ($save_policy_retail->wasRecentlyCreated === true) {
                    array_push($response, "SME policy document saved successfully with policy no $policy_no");
                } else {
                    array_push($response, "Error saving SME policy document");
                }
                break;
            case 'retail':
                $save_policy_retail = PolicyDocument::create([
                    'policy_no' => $policy_no,
                    'user_id' => $request->user_id,
                    'anniv' => $request->anniv,
                    'zone' => $request->zone,
                    'health_plan' => $request->health_plan,
                    'lives' => $request->lives,
                    'promotions' => $this->replaceCommas(
                        $request->promotions
                    ),
                    'overall_annual_limit' => $this->replaceCommas($request->overall_annual_limit),
                    'accident_hosp' => $this->replaceCommas(
                        $request->accident_hosp
                    ),
                    'illness_hosp' => $this->replaceCommas(
                        $request->illness_hosp
                    ),
                    'admissions_icu' => $this->replaceCommas(
                        $request->admissions_icu
                    ),
                    'room_limit' => $this->replaceCommas(
                        $request->room_limit
                    ),
                    'preexisting_cond' => $this->replaceCommas(
                        $request->preexisting_cond
                    ),
                    'oncology_tests' => $this->replaceCommas($request->oncology_tests),
                    'nursing_fees' => $this->replaceCommas($request->nursing_fees),
                    'general_surgery' => $this->replaceCommas($request->general_surgery),
                    'reconstructive_surgery' => $this->replaceCommas(
                        $request->reconstructive_surgery
                    ),
                    'prescribed_drugs' => $this->replaceCommas($request->prescribed_drugs),
                    'internal_prostheses' => $this->replaceCommas($request->internal_prostheses),
                    'xrays' => $this->replaceCommas($request->xrays),
                    'pathology' => $this->replaceCommas($request->pathology),
                    'acc_damage_natural_eyes' =>  $this->replaceCommas(
                        $request->acc_damage_natural_eyes
                    ),
                    'acc_damage_natural_teeth' =>  $this->replaceCommas(
                        $request->acc_damage_natural_teeth
                    ),
                    'psychiatric_treatment' => $this->replaceCommas(
                        $request->psychiatric_treatment
                    ),
                    'physiotherapy' => $this->replaceCommas($request->physiotherapy),
                    'parent_accommodation' => $this->replaceCommas($request->parent_accommodation),
                    'congenital_conditions' => $this->replaceCommas(
                        $request->congenital_conditions
                    ),
                    'inpatient_treatment_hiv' => $this->replaceCommas(
                        $request->inpatient_treatment_hiv
                    ),
                    'evacuation' => $this->replaceCommas($request->evacuation),
                    'international_emergency_medical' => $this->replaceCommas($request->international_emergency_medical),
                    // 'international_emergency_rescue' => $this->replaceCommas($request->international_emergency_rescue),
                    'maternity' => $this->replaceCommas($request->maternity),
                    'normal_delivery' => $this->replaceCommas(
                        $request->normal_delivery
                    ),
                    'caesarian_section' => $this->replaceCommas($request->caesarian_section),
                    'premature_cover' => $this->replaceCommas(
                        $request->premature_cover
                    ),
                    'complications_pregnancy' => $this->replaceCommas(
                        $request->complications_pregnancy
                    ),
                    'op_annual_limit' => $this->replaceCommas(
                        $request->op_annual_limit
                    ),
                    'preexisting_conditions' => $this->replaceCommas($request->preexisting_conditions),
                    'op_oncology_tests' => $this->replaceCommas($request->op_oncology_tests),
                    'general_practitioners' => $this->replaceCommas($request->general_practitioners),
                    'antenatal_postnatal' => $this->replaceCommas($request->antenatal_postnatal),
                    'prescribed_medicines' => $this->replaceCommas($request->prescribed_medicines),
                    'op_physiotherapy' => $this->replaceCommas($request->op_physiotherapy),
                    'op_psychiatric_treatment' => $this->replaceCommas(
                        $request->op_psychiatric_treatment
                    ),
                    'op_congenital_conditions' => $this->replaceCommas(
                        $request->op_congenital_conditions
                    ),
                    'post_hospitalisation_treatment' => $this->replaceCommas($request->post_hospitalisation_treatment),
                    'outpatient_surgical_operations' => $this->replaceCommas($request->outpatient_surgical_operations),
                    'outpatient_treatment_of_hiv' => $this->replaceCommas(
                        $request->outpatient_treatment_of_hiv
                    ),
                    'general_eye_examinations' => $this->replaceCommas($request->general_eye_examinations),
                    'frames_lenses' => $this->replaceCommas($request->frames_lenses),
                    'dental_limit' => $this->replaceCommas(
                        $request->dental_limit
                    ),
                    'dental_consultation_treatment' => $this->replaceCommas($request->dental_consultation_treatment),
                    'scaling_polishing' => $this->replaceCommas($request->scaling_polishing),
                    'annual_health_checks' => $this->replaceCommas(
                        $request->annual_health_checks
                    ),
                    'personal_accident' => $this->replaceCommas(
                        $request->personal_accident
                    ),
                    'last_expenses' => $this->replaceCommas(
                        $request->last_expenses
                    ),
                    'illness_hospitalization' => $this->replaceCommas($request->illness_hospitalization),
                    'maternity_wp' => $this->replaceCommas($request->maternity_wp),
                    'provider_network' => $this->replaceCommas($request->provider_network),
                    'create_date' => $create_date,
                    'date_entered' => $request->date_entered,
                    'user_id' => $request->user_id,
                    'op_option' => $this->replaceCommas(
                        $request->op_option
                    ),
                    'enroll_one' => $request->enroll_one,
                    'enroll_two' => $request->enroll_two,
                    'enroll_three' => $request->enroll_three,
                    'enroll_four' => $request->enroll_four,
                    'enroll_five' => $request->enroll_five,
                    'enroll_six' => $request->enroll_six,
                    'enroll_seven' => $request->enroll_seven,
                    'enroll_eight' => $request->enroll_eight,
                    'enroll_nine' => $request->enroll_nine,
                    'enroll_ten' => $request->enroll_ten,
                    'enroll_elleven' => $request->enroll_elleven,
                    'member_no' => $request->member_no,
                    'net_premium' => $this->replaceCommas($request->net_premium),
                    'ip_psychiatric_treatment' => $this->replaceCommas($request->ip_psychiatric_treatment),
                    'ip_congenital_conditions' => $this->replaceCommas(
                        $request->ip_congenital_conditions
                    ),
                    'personal_accident_child' => $this->replaceCommas(
                        $request->personal_accident_child
                    ),
                    'admissions_icu_chronic' => $this->replaceCommas(
                        $request->admissions_icu_chronic
                    ),
                    'admissions_icu_accident' => $this->replaceCommas(
                        $request->admissions_icu_accident
                    ),
                    'reconstructive_surgery_acc' => $this->replaceCommas(
                        $request->reconstructive_surgery_acc
                    ),
                    'deleted' => 0,
                    'client_premium' => $request->client_premium,
                    'ip_chronic_conditions' => $this->replaceCommas($request->ip_chronic_conditions),
                    'op_chronic_conditions' => $this->replaceCommas($request->op_chronic_conditions),
                    'chronic_wp' => $this->replaceCommas($request->chronic_wp),
                    'minimum_age' => (int)$request->minimum_age,
                    'medex' => (int)$request->medex,
                    'maximum_age' => (int)$request->maximum_age,
                    'enroll_eight' => $request->enroll_eight,
                    'enroll_nine' => $request->enroll_nine,
                    'enroll_ten' => $request->enroll_ten,
                    'nursery_care' => $this->replaceCommas(
                        $request->nursery_care
                    ),
                    'covid19_op' => $this->replaceCommas(
                        $request->covid19_op
                    ),
                    'covid19_ip' => $this->replaceCommas($request->covid19_ip),
                    'optometry_limit' => $this->replaceCommas($request->optometry_limit),
                    'optical_overall' => $this->replaceCommas($request->optical_overall),
                    'consultation_and_eye_treatment' =>  $this->replaceCommas($request->consultation_and_eye_treatment),
                    'ophthalmology' =>  $this->replaceCommas($request->ophthalmology),
                    'lenses' =>  $this->replaceCommas($request->lenses),
                    'frames' =>  $this->replaceCommas($request->frames),
                    'congenital_premature_and_genetic_condition_defects' =>  $this->replaceCommas($request->congenital_premature_and_genetic_condition_defects)
                ]);

                if ($save_policy_retail->wasRecentlyCreated === true) {
                    array_push($response, "Retail policy document saved successfully with policy no $policy_no");
                } else {
                    array_push($response, "Error saving retail policy document");
                }
                break;
            case 'hospital_cash':
                $policy_no_hospital_cash = $request->member_no . '/' . $today . '/' . $policy_no_count;
                $save_policy_retail = PolicyDocument::create([
                    'policy_no' => $policy_no_hospital_cash,
                    'user_id' => $request->user_id,
                    'anniv' => $request->anniv,
                    'zone' => $request->zone,
                    'health_plan' => $request->health_plan,
                    'lives' => $request->lives,
                    'create_date' => $create_date,
                    'date_entered' => $create_date,
                    'overall_annual_limit' => $this->replaceCommas($request->overall_annual_limit),
                    'member_no' => $request->member_no,
                    'deleted' => 0,
                    'client_premium' => $request->client_premium,
                    'maternity_cover' => $request->maternity_cover,
                    'enroll_one' => $request->enroll_one,
                    'enroll_two' => $request->enroll_two,
                    'enroll_three' => $request->enroll_three,
                    'enroll_four' => $request->enroll_four,
                    'enroll_five' => $request->enroll_five,
                ]);

                if ($save_policy_retail->wasRecentlyCreated === true) {
                    array_push($response, "Retail policy document saved successfully with policy no $policy_no_hospital_cash");
                } else {
                    array_push($response, "Error saving retail policy document");
                }
                break;
            case 'kitanda':
                $kitanda_policy_no = $request->member_no . '/' . $today . '/' . $policy_no_count;
                $save_policy_retail = PolicyDocument::create([
                    'policy_no' => $kitanda_policy_no,
                    'user_id' => $request->user_id,
                    'anniv' => $request->anniv,
                    'zone' => $request->zone,
                    'health_plan' => $request->health_plan,
                    'lives' => $request->lives,
                    'create_date' => $create_date,
                    'date_entered' => $create_date,
                    'overall_annual_limit' => $this->replaceCommas($request->overall_annual_limit),
                    'member_no' => $request->member_no,
                    'deleted' => 0,
                    'client_premium' => $request->client_premium,
                    'hospitalization_benefit' => $request->hospitalization_benefit,
                    'funeral' => $request->funeral,
                    'total_permanent_disability' => $request->total_permanent_disability,
                    'waiting_period' => $request->waiting_period,
                    'enhanced_corporate_annual_overall_limit' => $request->enhanced_corporate_annual_overall_limit,
                    'enroll_one' => $request->enroll_one,
                    'enroll_two' => $request->enroll_two,
                    'enroll_three' => $request->enroll_three,
                    'enroll_four' => $request->enroll_four,
                    'enroll_five' => $request->enroll_five,
                    'hospital_accommodation_and_nursing_care' => $request->hospital_accommodation_and_nursing_care,
                    'diagnostic_tests_mri_ct_and_blood_tests_during_admission' => $request->diagnostic_tests_mri_ct_and_blood_tests_during_admission,
                    'consultation_fees_for_surgeons_or_physicians_during_admission' => $request->consultation_fees_for_surgeons_or_physicians_during_admission
                ]);

                if ($save_policy_retail->wasRecentlyCreated === true) {
                    array_push($response, "Retail policy document saved successfully with policy no $kitanda_policy_no");
                } else {
                    array_push($response, "Error saving retail policy document");
                }
                break;
            case 'kitanda_corporate':
                $kitanda_policy_no = 'KITANDA' . '/' . $today . '/' . $policy_no_count;
                $save_policy_retail = PolicyDocument::create([
                    'policy_no' => $kitanda_policy_no,
                    'corp_id' => $request->corp_id,
                    'user_id' => $request->user_id,
                    'anniv' => $request->anniv,
                    'zone' => $request->zone,
                    'health_plan' => $request->health_plan,
                    'lives' => $request->lives,
                    'create_date' => $create_date,
                    'date_entered' => $create_date,
                    'overall_annual_limit' => $this->replaceCommas($request->overall_annual_limit),
                    'deleted' => 0,
                    'client_premium' => $request->client_premium,
                    'hospitalization_benefit' => $request->hospitalization_benefit,
                    'funeral' => $request->funeral,
                    'total_permanent_disability' => $request->total_permanent_disability,
                    'waiting_period' => $request->waiting_period,
                    'enhanced_corporate_annual_overall_limit' => $request->enhanced_corporate_annual_overall_limit,
                    'enroll_one' => $request->enroll_one,
                    'enroll_two' => $request->enroll_two,
                    'enroll_three' => $request->enroll_three,
                    'enroll_four' => $request->enroll_four,
                    'enroll_five' => $request->enroll_five,
                    'hospital_accommodation_and_nursing_care' => $request->hospital_accommodation_and_nursing_care,
                    'diagnostic_tests_mri_ct_and_blood_tests_during_admission' => $request->diagnostic_tests_mri_ct_and_blood_tests_during_admission,
                    'consultation_fees_for_surgeons_or_physicians_during_admission' => $request->consultation_fees_for_surgeons_or_physicians_during_admission
                ]);

                if ($save_policy_retail->wasRecentlyCreated === true) {
                    array_push($response, "Corporate policy document saved successfully with policy no $kitanda_policy_no");
                } else {
                    array_push($response, "Error saving corporate policy document");
                }
                break;
            case 'munomukabi':
                $munomukabi_policy_no = 'MUNOMUKABI' . '/' . $today . '/' . $policy_no_count;
                $save_policy_retail = PolicyDocument::create([
                    'policy_no' => $munomukabi_policy_no,
                    'user_id' => $request->user_id,
                    'anniv' => $request->anniv,
                    'zone' => $request->zone,
                    'health_plan' => $request->health_plan,
                    'lives' => $request->lives,
                    'create_date' => $create_date,
                    'date_entered' => $create_date,
                    'overall_annual_limit' => $this->replaceCommas($request->overall_annual_limit),
                    'member_no' => $request->scheme == 2 ?  $request->member : null,
                    'corp_id' => $request->scheme == 1 ?  $request->corporate : null,
                    'deleted' => 0,
                    'annual_health_checks' => $this->replaceCommas($request->annual_health_checks),
                    'antenatal' => $request->antenatal,
                    'chronic_conditions_developed_on_cover' => $this->replaceCommas($request->chronic_conditions_developed_on_cover),
                    'congenital_conditions' => $this->replaceCommas($request->congenital_and_genetics),
                    'general_eye_examinations' => $request->general_eye_examinations,
                    'consultation_and_eye_treatment' => $request->consultation_and_eye_treatment,
                    'covid19_ip' => $this->replaceCommas($request->covid19_ip),
                    'covid19_op' => $this->replaceCommas($request->covid19_op),
                    'dental_limit' =>  $this->replaceCommas($request->dental_op),
                    'emergency_and_road' => $request->emergency_and_road,
                    'eye_treatment' => $request->eye_treatment,
                    'funeral' => $this->replaceCommas($request->funeral),
                    'general_practitioners' => $request->general_practitioners,
                    'health_plan' => $request->health_plan,
                    'health_promotions' => $request->health_promotions,
                    'hiv_and_aids' => $request->hiv_and_aids,
                    'illness_accident_hospitalization' => $request->illness_accident_hospitalization,
                    'illness_hospitalization' => $request->illness_hospitalization,
                    'internal_prostheses' => $request->internal_prostheses,
                    'international_emergency_medical' => $request->international_emergency_medical,
                    'international_emergency_rescue' => $request->international_emergency_rescue,
                    'lens_frames_glasses' => $request->lens_frames_glasses, // new varchar
                    'maternity' => $this->replaceCommas($request->maternity),
                    'minor_surgeries' => $request->minor_surgeries, //new varchar
                    'non_surgical_extractions' => $request->non_surgical_extractions, //new varchar
                    'nursing_fees' => $request->nursing_fees,
                    'oncology_tests_drugs_and_consultation_op' => $request->oncology_tests_drugs_and_consultation_op, //new
                    'oncology_tests_drugs_and_consultation_ip' => $request->oncology_tests_drugs_and_consultation_ip, //new
                    'preexisting_conditions' => $request->preexisting_conditions,
                    'reconstructive_surgery' => $this->replaceCommas($request->reconstructive_surgery),
                    'prescribed_medicines' => $request->prescribed_medicines,
                    'xray' => $request->xray,
                    'pathology' => $request->pathology,
                    'psychiatric_treatment' => $this->replaceCommas($request->psychiatric_treatment),
                    'physiotherapy' => $request->physiotherapy,
                    'parent_accomodation' => $request->parent_accomodation,
                    'congenital_and_genetics' => $this->replaceCommas($request->congenital_and_genetics), //new
                    'inpatient_treatment_hiv' => $request->inpatient_treatment_hiv,
                    'outpatient_treatment_hiv' => $request->outpatient_treatment_hiv, //varchar
                    'evacuation' => $request->evacuation,
                    'maternity' => $this->replaceCommas($request->maternity),
                    'delivery_postnatal_treatment' => $this->replaceCommas($request->delivery_postnatal_treatment),   // new
                    'premature_cover' => $this->replaceCommas($request->premature_cover),
                    'new_born_baby_care' => $request->new_born_baby_care,    //new
                    'complications_pregnancy' => $request->complications_pregnancy,
                    'overall_annual_outpatient' => $this->replaceCommas($request->overall_annual_outpatient),   //new
                    'antenatal_postnatal' => $this->replaceCommas($request->antenatal_postnatal),
                    'preexisting_chronic_conditions' => $this->replaceCommas($request->preexisting_chronic_conditions),  //new   
                    'post_hospitalization_treatment' => $request->post_hospitalization_treatment,
                    'outpatient_surgical_operations' => $request->outpatient_surgical_operations,
                    'optometry_limit' => $this->replaceCommas($request->optometry_limit),
                    'simple_outer_surgeries' => $request->simple_outer_surgeries,   //new varchar
                    'visual_acuity_tests' => $request->visual_acuity_tests,   //new varchar
                    'lens_frames_glasses' => $request->lens_frames_glasses,   //new varchar
                    'dental_limit' => $this->replaceCommas($request->dental_limit),
                    'consultation_and_treatment' => $request->consultation_and_treatment,    //new
                    'extractions' => $request->extractions,    //new varchar
                    'infections' => $request->infections,    //new varchar
                    'fillings' => $request->fillings,    //new varchar
                    'root_canal' => $request->root_canal,    //new varchar
                    'xray_mri_petscan_ctscan' => $request->xray_mri_petscan_ctscan,    //new varchar
                    'non_surgical_extractions' => $request->non_surgical_extractions,    //new varchar
                    'scaling_polishing' => $request->scaling_polishing,
                    'annual_health_checks' => $this->replaceCommas($request->annual_health_checks),
                    'personal_accident_child' => $this->replaceCommas($request->personal_accident_child),
                    'personal_accident' => $this->replaceCommas($request->personal_accident), // this is adult
                    'funeral' => $this->replaceCommas($request->funeral),
                    'maternity_cover' => $request->maternity_cover,  //new varchar
                    'chronics_development_on_cover' => $request->chronics_development_on_cover,  //new varchar
                    'admissions_icu' => $request->admissions_icu,
                    'room_limit' => $this->replaceCommas($request->room_limit),
                    'general_surgery' => $request->general_surgery,
                    'pathology_diagnostic_tests' => $request->general_surgery,
                    'x_ray' => $request->x_ray,
                    'outpatient_fund' => $this->replaceCommas($request->outpatient_fund),
                ]);

                if ($save_policy_retail->wasRecentlyCreated === true) {
                    array_push($response, "Retail policy document saved successfully with policy no $munomukabi_policy_no");
                } else {
                    array_push($response, "Error saving retail policy document");
                }
                break;
            case 'bancasurance':
                $bancasurance_policy_no = 'BANCASURANCE' . '/' . $today . '/' . $policy_no_count;
                $save_policy_retail = PolicyDocument::create([
                    'policy_no' => $bancasurance_policy_no,
                    'user_id' => $request->user_id,
                    'anniv' => $request->anniv,
                    'zone' => $request->zone,
                    'health_plan' => $request->health_plan,
                    'lives' => $request->lives,
                    'create_date' => $create_date,
                    'date_entered' => $create_date,
                    'overall_annual_limit' => $this->replaceCommas($request->overall_annual_limit),
                    'member_no' => $request->scheme == 2 ?  $request->member : null,
                    'corp_id' => $request->scheme == 1 ?  $request->corporate : null,
                    'deleted' => 0,
                    'annual_health_checks' => $this->replaceCommas($request->annual_health_checks),
                    'antenatal' => $request->antenatal,
                    'chronic_conditions_developed_on_cover' => $this->replaceCommas($request->chronic_conditions_developed_on_cover),
                    'congenital_conditions' => $this->replaceCommas($request->congenital_and_genetics),
                    'general_eye_examinations' => $request->general_eye_examinations,
                    'consultation_and_eye_treatment' => $request->consultation_and_eye_treatment,
                    'covid19_ip' => $this->replaceCommas($request->covid19_ip),
                    'covid19_op' => $this->replaceCommas($request->covid19_op),
                    'dental_limit' =>  $this->replaceCommas($request->dental_op),
                    'emergency_and_road' => $request->emergency_and_road,
                    'eye_treatment' => $request->eye_treatment,
                    'funeral' => $this->replaceCommas($request->funeral),
                    'general_practitioners' => $request->general_practitioners,
                    'health_plan' => $request->health_plan,
                    'health_promotions' => $request->health_promotions,
                    'hiv_and_aids' => $request->hiv_and_aids,
                    'illness_accident_hospitalization' => $request->illness_accident_hospitalization,
                    'illness_hospitalization' => $request->illness_hospitalization,
                    'internal_prostheses' => $request->internal_prostheses,
                    'international_emergency_medical' => $request->international_emergency_medical,
                    'international_emergency_rescue' => $request->international_emergency_rescue,
                    'lens_frames_glasses' => $request->lens_frames_glasses, // new varchar
                    'maternity' => $this->replaceCommas($request->maternity),
                    'minor_surgeries' => $request->minor_surgeries, //new varchar
                    'non_surgical_extractions' => $request->non_surgical_extractions, //new varchar
                    'nursing_fees' => $request->nursing_fees,
                    'oncology_tests_drugs_and_consultation_op' => $request->oncology_tests_drugs_and_consultation_op, //new
                    'oncology_tests_drugs_and_consultation_ip' => $request->oncology_tests_drugs_and_consultation_ip, //new
                    'preexisting_conditions' => $request->preexisting_conditions,
                    'reconstructive_surgery' => $this->replaceCommas($request->reconstructive_surgery),
                    'prescribed_medicines' => $request->prescribed_medicines,
                    'xray' => $request->xray,
                    'pathology' => $request->pathology,
                    'psychiatric_treatment' => $this->replaceCommas($request->psychiatric_treatment),
                    'physiotherapy' => $request->physiotherapy,
                    'parent_accomodation' => $request->parent_accomodation,
                    'congenital_and_genetics' => $this->replaceCommas($request->congenital_and_genetics), //new
                    'inpatient_treatment_hiv' => $request->inpatient_treatment_hiv,
                    'outpatient_treatment_hiv' => $request->outpatient_treatment_hiv, //varchar
                    'evacuation' => $request->evacuation,
                    'maternity' => $this->replaceCommas($request->maternity),
                    'delivery_postnatal_treatment' => $this->replaceCommas($request->delivery_postnatal_treatment),   // new
                    'premature_cover' => $this->replaceCommas($request->premature_cover),
                    'new_born_baby_care' => $request->new_born_baby_care,    //new
                    'complications_pregnancy' => $request->complications_pregnancy,
                    'overall_annual_outpatient' => $this->replaceCommas($request->overall_annual_outpatient),   //new
                    'antenatal_postnatal' => $this->replaceCommas($request->antenatal_postnatal),
                    'preexisting_chronic_conditions' => $this->replaceCommas($request->preexisting_chronic_conditions),  //new   
                    'post_hospitalization_treatment' => $request->post_hospitalization_treatment,
                    'outpatient_surgical_operations' => $request->outpatient_surgical_operations,
                    'optometry_limit' => $this->replaceCommas($request->optometry_limit),
                    'simple_outer_surgeries' => $request->simple_outer_surgeries,   //new varchar
                    'visual_acuity_tests' => $request->visual_acuity_tests,   //new varchar
                    'lens_frames_glasses' => $request->lens_frames_glasses,   //new varchar
                    'dental_limit' => $this->replaceCommas($request->dental_limit),
                    'consultation_and_treatment' => $request->consultation_and_treatment,    //new
                    'extractions' => $request->extractions,    //new varchar
                    'infections' => $request->infections,    //new varchar
                    'fillings' => $request->fillings,    //new varchar
                    'root_canal' => $request->root_canal,    //new varchar
                    'xray_mri_petscan_ctscan' => $request->xray_mri_petscan_ctscan,    //new varchar
                    'non_surgical_extractions' => $request->non_surgical_extractions,    //new varchar
                    'scaling_polishing' => $request->scaling_polishing,
                    'annual_health_checks' => $this->replaceCommas($request->annual_health_checks),
                    'personal_accident_child' => $this->replaceCommas($request->personal_accident_child),
                    'personal_accident' => $this->replaceCommas($request->personal_accident), // this is adult
                    'funeral' => $this->replaceCommas($request->funeral),
                    'maternity_cover' => $request->maternity_cover,  //new varchar
                    'chronics_development_on_cover' => $request->chronics_development_on_cover,  //new varchar
                    'admissions_icu' => $request->admissions_icu,
                    'room_limit' => $this->replaceCommas($request->room_limit),
                    'general_surgery' => $request->general_surgery,
                    'pathology_diagnostic_tests' => $request->general_surgery,
                    'x_ray' => $request->x_ray,
                    'outpatient_fund' => $request->outpatient_fund,
                ]);

                if ($save_policy_retail->wasRecentlyCreated === true) {
                    array_push($response, "Retail policy document saved successfully with policy no $bancasurance_policy_no");
                } else {
                    array_push($response, "Error saving retail policy document");
                }
                break;
            case 'liberty_blue':
                // return $request;
                $liberty_blue_policy_no = 'AARBLUE' . '/' . $today . '/' . $policy_no_count;
                $save_policy_retail = PolicyDocument::create([
                    'policy_no' => $liberty_blue_policy_no,
                    'corp_id' => $request->corp_id,
                    'user_id' => $request->user_id,
                    'anniv' => $request->anniv,
                    'health_plan' => $request->benefit_plan,
                    'create_date' => $create_date,
                    'date_entered' => $create_date,
                    'overall_annual_limit' => $this->replaceCommas($request->overall_annual_limit),
                    'funeral' => $this->replaceCommas($request->funeral),
                    'network_provider' => $request->network_provider, // new in db
                    'prescribed_acute' => $this->replaceCommas($request->prescribed_acute), //new in db
                    'optical' =>  $this->replaceCommas($request->optical),  //new in db
                    'optical_two' =>  $this->replaceCommas($request->optical_two),  //new in db
                    'basic_dentistry' => $this->replaceCommas($request->basic_dentistry),  //new in db
                    'basic_dentistry_two' => $this->replaceCommas($request->basic_dentistry_two),  //new in db
                    'specialised_dentistry' => $this->replaceCommas($request->specialised_dentistry),  //new in db
                    'neonatal' => $this->replaceCommas($request->neonatal),  //new in db
                    'rehabilitation_ten' => $request->rehabilitation_ten,  //new in db
                    'rehabilitation_thirty' => $request->rehabilitation_thirty,  //new in db
                    'specialised_radiology' => $this->replaceCommas($request->specialised_radiology),  //new in db
                    'prosthesis_devices' => $request->prosthesis_devices,  //new in db
                    'external_medical_appliances' => $request->external_medical_appliances,  //new in db
                    'major_diseases_benefits_limit' => $this->replaceCommas($request->major_diseases_benefits_limit),  //new in db
                    'repatriation_of_mortal_remains' => $this->replaceCommas($request->repatriation_of_mortal_remains), //new in db
                    'day_to_day_one' => $request->day_to_day_one, //new in db
                    'day_to_day_two' => $request->day_to_day_two, //new in db
                    'day_to_day_three' => $request->day_to_day_three, //new in db
                    'chronic_condition_bene' => $this->replaceCommas($request->chronic_condition_bene), //new in db
                    'dental_limit' => $this->replaceCommas($request->dental_limit),
                    'out_patient_maternity_care' => $this->replaceCommas($request->out_patient_maternity_care),
                    'accommodation' => $this->replaceCommas($request->accommodation),
                    'lives'=>$this->replaceCommas($request->lives),
                    'premium_per_life'=>$this->replaceCommas($request->premium_per_life),
                ]);

                if ($save_policy_retail->wasRecentlyCreated === true) {
                    array_push($response, "Retail policy document saved successfully with policy no $liberty_blue_policy_no");
                } else {
                    array_push($response, "Error saving retail policy document");
                }
                break;
        }

        //save access log
        app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);

        return $response;
    }

    public function replaceCommas($val)
    {
        $val =   str_replace(',', '', $val);
        // $val = floatval(preg_replace("/[^d.]/", "", $val));
        return (int)$val;
    }
}
