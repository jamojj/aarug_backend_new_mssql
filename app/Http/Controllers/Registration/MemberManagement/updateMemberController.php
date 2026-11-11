<?php

namespace App\Http\Controllers\Registration\MemberManagement;

use App\Http\Controllers\Controller;
use App\Models\Registration\MemberAcceptance;
use App\Models\Registration\MemberAllergy;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberExclusions;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\MemberMedical;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class updateMemberController extends Controller
{
    // updating members
    public function updateMember(Request $request)
    {

        // return $request;
        $date_entered = date("Y-m-d");
        //CHECK IF MEMBER EXISTS
        $dublicate_member = MemberInfo::query()

            // Handle surname
            ->when($request->surname_personal, function ($q) use ($request) {
                $q->where('surname', $request->surname_personal);
            }, function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('surname')
                        ->orWhere('surname', '');
                });
            })

            // Handle first_name
            ->when($request->first_name_personal, function ($q) use ($request) {
                $q->where('first_name', $request->first_name_personal);
            }, function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('first_name')
                        ->orWhere('first_name', '');
                });
            })

            // Handle other_names
            ->when($request->other_names_personal, function ($q) use ($request) {
                $q->where('other_names', $request->other_names_personal);
            }, function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('other_names')
                        ->orWhere('other_names', '');
                });
            })

            // Handle dob
            ->when($request->dob, function ($q) use ($request) {
                $q->where('dob', $request->dob);
            }, function ($q) {
                $q->whereNull('dob');
            })

            ->get();




        if (count($dublicate_member) > 1) {
            return ['Dublicate. Member with same names and dob already exists!'];
        }
        try {
            //perform transaction
            DB::transaction(function () use ($request, $date_entered) {
                if ($request->relation_to_principal == "24") {
                    //update principal data
                    PrincipalApplicant::where('family_no', $request->family_no_personal)->update([
                        'surname' => $request->surname_personal,
                        'first_name' => $request->first_name_personal,
                        'other_names' => $request->other_names_personal,
                        'marital_status' => $request->marital_status,
                        'tel_no' => $request->tel_no_personal == '' ? $request->tel_no : $request->tel_no_personal,
                        'email' => $request->email_personal == '' ? $request->email : $request->email_personal,
                        'mobile_no' => $request->mobile_no
                        // 'smart_sync'=>1,
                        // 'sync'=>0
                    ]);
                    $this->saveFunc($request, $date_entered);
                } else {
                    $this->saveFunc($request, $date_entered);
                }
            });
            return ['Member updated successfully.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function saveFunc($request, $date_entered)
    {
        //member info
        MemberInfo::where('member_no', $request->member_no_personal)->update([
            'surname' => $request->surname_personal,
            'first_name' => $request->first_name_personal,
            'other_names' => $request->other_names_personal,
            'dob' => $request->dob,
            'occupation' => (int)$request->occupation,
            'relation_to_principal' => (int)$request->relation_to_principal,
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
            'join_date' => $request->start_date, //changed from join_date since they're the same
            'title' => (int)$request->title,
            'is_vip' => (int)$request->is_vip,
            'id_pp_no' => $request->idno,
            'photo_n_form' => (int)$request->photo_availed,
            'email' => $request->email_personal,
            'tel_no' => $request->tel_no_personal

        ]);
        //member allergy
        if (isset($request->allergy)) {
            for (
                $i = 0;
                $i < count($request->allergy);
                $i++
            ) {
                $allergy_exists = MemberAllergy::where('member_no', $request->member_no_personal)->where('allergy', $request->allergy[$i])->count();
                if ($allergy_exists == 0) {
                    MemberAllergy::create([
                        'member_no' => $request->member_no_personal,
                        'allergy' => (int)$request->allergy[$i]
                    ]);
                }
            }
        }
        // member medical
        if (isset($request->illness)) {
            for (
                $i = 0;
                $i < count($request->illness);
                $i++
            ) {
                $member_medical_exists = MemberMedical::where('member_no', $request->member_no_illness[$i])
                    ->where('illnes', $request->illness[$i])
                    ->count();
                if ($member_medical_exists == 0) {
                    MemberMedical::create([
                        'member_no' => $request->member_no_illness[$i],
                        'anniv' => (int)$request->anniv_illness[$i],
                        'illnes' => (int)$request->illness[$i],
                        'conditiondt' => $request->condition_date[$i]
                    ]);
                }
            }
        }
        //member exclusion
        if (isset($request->diagnosis)) {
            for ($i = 0; $i < count($request->member_no_diagnosis); $i++) {
                $exclusion_exists = MemberExclusions::where('member_no', $request->member_no_diagnosis[$i])
                    ->where('diag_code', $request->diagnosis[$i])
                    ->count('member_no');
                if ($exclusion_exists == 0) {
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

        //member benefits
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
                } else {
                    MemberBenefits::where([
                        'anniv' => $request->anniv[$i],
                        'member_no' => $request->member_no_benefit[$i],
                        'benefit' => $request->benefit[$i]
                    ])->update([
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
        //member acceptance 
        MemberAcceptance::where('member_no', $request->member_no_vetting)->update([
            'STATUS' => (int)$request->vetting_status,
            'STATUS_DATE' => $request->status_date,
            'USER_ID' => $request->underwriter,
            'DATE_ENTERED' => $date_entered,
            'COMMENTS' => $request->comment,
            'DEF_REJ' => (int)$request->reason
        ]);
        // //member activation
        MemberAnniversary::where('member_no', $request->member_no_activation)
            ->where('anniv', $request->activation_anniv)
            ->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'renewal_date' => $request->renewal_date,
                'user_id' => $request->underwriter,
                'smart_sync' => 1,
                'sync' => 0
                // 'sync' => $request->sync,
                // 'smart_sync' => $request->smart_sync,
            ]);
    }
}
