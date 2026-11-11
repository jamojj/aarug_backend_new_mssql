<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\ProductName;
use App\Models\Registration\MemberAcceptance;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportPreloadedMembers extends Controller
{
    //save preloaded members
    public function savePreloadedMemberData(Request $request)
    {

        // return $request;
        ini_set('max_execution_time', '0');
        try {
        $response = array();
        $max_idx_principal = PrincipalApplicant::max(DB::raw("isnull(idx, 0)"));
        $date_entered = date("Y-m-d");
        for ($i = 0; $i < count($request->member_no); $i++) {
             //validate required input 
             $validator = Validator::make($request->all(), [
                "surname.$i" => 'required',
                "first_name.$i" => 'required',
                "corp_id.$i" => 'required',
                "principal_dependant.$i" => 'required',
                "health_plan.$i" => 'required',
                "option.$i" => 'required',
                "dob.$i" => 'required',
                "family_relation.$i" => 'required',
                "gender.$i" => 'required',
                "start_date.$i" => 'required',
                "end_date.$i" => 'required',
            ]);

            if (!$validator->fails()) { 

            //validate date of birth
            $dob = str_replace("/", "-", $request->dob[$i]);
            $dob =  date("Y-m-d", strtotime($dob));
            //validate start date
            $start_date = str_replace("/", "-", $request->start_date[$i]);
            $start_date =  date("Y-m-d", strtotime($start_date));
            //validate end date
            $end_date = str_replace("/", "-", $request->end_date[$i]);
            $end_date =  date("Y-m-d", strtotime($end_date));

            //renewal date
            $renewal_date = date("Y-m-d", strtotime($end_date . "+ 1day"));

            $exploded_fam_no = explode('-', $request->member_no[$i]);
            $exploded_fam_no = $exploded_fam_no[0];
            $existing_family = MemberInfo::where('family_no', $request->family_no[$i])->pluck('family_no');
            $existing_dependant = MemberInfo::where('member_no', $request->member_no[$i])->pluck('member_no');
            $existing_corp = Corporate::where('corp_id', $request->corp_id[$i])->pluck('corp_id');
            if (count($existing_family) != 0) {
                if ($exploded_fam_no == $request->family_no[$i]) {
                    if (count($existing_dependant) == 0) {
                        if (count($existing_corp) != 0) {
                            $plan = ProductName::where('product_name', $request->option[$i])->pluck('code');
                            if (count($plan) != 0) {
                                $max_anniv = CorpAnniversary::where('corp_id', $request->corp_id[$i])->max('anniv');
                                //fetch agent for corporate for current anniv of corporate
                                $agent_id = CorpAnniversary::where('corp_id', $request->corp_id[$i])->where('anniv', $max_anniv)->pluck('agent_id');
                                $agent_id = count($agent_id) != 0 ? $agent_id[0] : 0;
                                //1 is corporate and 2 is individual
                                // $individual = $request->corp_id[$i] == 14 ? 2 : 1;
                                // $renewal_date = CorpAnniversary::where('corp_id', $request->corp_id[$i])->where('anniv', $max_anniv)->pluck('renewal_date');
                                // $renewal_date = count($renewal_date) != 0 ? $renewal_date[0] : '';
                                $benefits = CorpGroups::where('category', $request->health_plan[$i])
                                    ->where('product_name', $plan[0])
                                    ->where('corp_id', $request->corp_id[$i])
                                    ->get();
                                //check if benefits exist from combination of product
                                if (count($benefits) != 0) {
                                    //check if dob is correct
                                    //get age
                                    $date1 = new DateTime($dob);
                                    $date2 = new DateTime();
                                    $age = $date1->diff($date2)->y;
                                    //check that dob is not equal to today or in the future
                                    if ($dob < $date_entered) {
                                        //check if dob < = 65 which is recommended
                                        // if ($age <= 65) {
                                            DB::transaction(function () use ($request, $i, $date_entered, $renewal_date, $max_anniv, $plan, $benefits, $dob, $start_date, $end_date) {
                                                //update family size
                                                $fam_size = MemberInfo::where('family_no', $request->family_no[$i])->count();
                                                PrincipalApplicant::where('family_no', $request->family_no[$i])
                                                    ->update([
                                                        'family_size' => $fam_size + 1,
                                                        // 'smart_sync' => 1,
                                                        // 'sync' => 0
                                                    ]);
                                                //save member info
                                                MemberInfo::create([
                                                    'family_no' => $request->family_no[$i],
                                                    'member_no' => $request->member_no[$i],
                                                    'surname' => $request->surname[$i],
                                                    'first_name' => $request->first_name[$i],
                                                    'other_names' => $request->other_names[$i],
                                                    'dob' => $dob,
                                                    'relation_to_principal' => $request->principal_dependant[$i],
                                                    'user_id' => $request->user,
                                                    'date_entered' => $date_entered,
                                                    'family_title' => $request->family_relation[$i],
                                                    'employment_no' => $request->employment_no[$i],
                                                    'gender' => $request->gender[$i],
                                                    'corp_id' => $request->corp_id[$i],
                                                    'bank_name' => $request->bank_name[$i],
                                                    'bank_account_name' => $request->bank_account_name[$i],
                                                    'bank_account_number' => $request->bank_account_number[$i],
                                                    'kin_names' => $request->kin_names[$i],
                                                    'kin_phone_no' => $request->kin_phone_no[$i],
                                                    'kin_email_address' => $request->kin_email_address[$i],
                                                    'email' => $request->email[$i],
                                                    'tel_no' => $request->tel_no[$i],
                                                ]);


                                                //member acceptance 
                                                MemberAcceptance::create([
                                                    'MEMBER_NO' => $request->member_no[$i],
                                                    // 'STATUS' => (int)$request->status[$i],
                                                    'STATUS' => 4, // 4 is pending 
                                                    'STATUS_DATE' => $date_entered,
                                                    'USER_ID' => $request->user,
                                                    'DATE_ENTERED' => $date_entered,
                                                    'STATUS_AFTER_APPROVAL' => (int)$request->status[$i]
                                                ]);


                                                //member anniversary
                                                MemberAnniversary::create([
                                                    'member_no' => $request->member_no[$i],
                                                    'start_date' => $start_date,
                                                    'end_date' => $end_date,
                                                    'renewal_date' => $renewal_date,
                                                    'anniv' => $max_anniv,
                                                    'user_id' => $request->user,
                                                    'date_entered' => $date_entered,
                                                    'health_plan' => $request->health_plan[$i],
                                                    'h_option' => $plan[0],
                                                    'smart_sync' => 1,
                                                    'sync' => 0
                                                ]);



                                                //member benefits
                                                foreach ($benefits as $bene) {
                                                    $existing_benefit = MemberBenefits::where('member_no', $request->member_no[$i])
                                                        ->where('benefit', $bene->benefit)
                                                        ->where('anniv', $max_anniv)
                                                        ->count();
                                                    if ($existing_benefit == 0) {
                                                        MemberBenefits::create([
                                                            'member_no' => $request->member_no[$i],
                                                            'benefit' => $bene->benefit,
                                                            'limit' => $bene->limit,
                                                            'sharing' => $bene->sharing,
                                                            'anniv' => $max_anniv,
                                                            'sub_limit_of' => $bene->sub_limit_of,
                                                            'suspended' => $bene->suspend_at,
                                                            'suspended_date' => $bene->suspend_date,
                                                            'capitated' => $bene->capitated,
                                                            'fund' => $bene->fund,
                                                            'quantity' => $bene->quantity,
                                                            'product_name' => $bene->product_name,
                                                            'category' => $bene->category,
                                                            // 'smart_sync' => 1,
                                                            // 'sync' => 0
                                                        ]);
                                                    }
                                                }
                                            });
                                            //check if dob < = 65 which is recommended
                                            if ($age > 65) {
                                                $dt = [
                                                    'index' => $request->index[$i],
                                                    'res' => "Saved successfully (age = $age)"
                                                ];
                                            } else {
                                                $dt = [
                                                    'index' => $request->index[$i],
                                                    'res' => "Member saved successfully"
                                                ];
                                            }
                                            array_push($response, $dt);
                                        // } else {
                                        //     $dt = [
                                        //         'index' => $request->index[$i],
                                        //         'res' => "Error! dob cannot be greater than 70"
                                        //     ];
                                        //     array_push($response, $dt);
                                        // }
                                    } else {
                                        $dt = [
                                            'index' => $request->index[$i],
                                            'res' => "Error! dob cannot be today or in the future"
                                        ];
                                        array_push($response, $dt);
                                    }
                                } else {
                                    $dt = [
                                        'index' => $request->index[$i],
                                        'res' => "Error! product combination does not exist for this corporate"
                                    ];
                                    array_push($response, $dt);
                                }
                            } else {
                                $dt = [
                                    'index' => $request->index[$i],
                                    'res' => "Error! health plan doesn't exist"
                                ];
                                array_push($response, $dt);
                            }
                        } else {
                            $dt = [
                                'index' => $request->index[$i],
                                'res' => "Error! corporate doesn't exist"
                            ];
                            array_push($response, $dt);
                        }
                    } else {
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => "Error! dependant with that No exist"
                        ];
                        array_push($response, $dt);
                    }
                } else {
                    $dt = [
                        'index' => $request->index[$i],
                        'res' => "Error! member no does not match family no"
                    ];
                    array_push($response, $dt);
                }
            } else {
                $dt = [
                    'index' => $request->index[$i],
                    'res' => "Error! family entered doesn't exist"
                ];
                array_push($response, $dt);
            }
        } else {
            // Collect all error messages
            $errors = $validator->errors();

            // Flatten the error messages and transform them to a string
            $errorString = collect($errors->all())->map(function ($message) {
                return str_replace(['.0', '.1', '.2', '.3', '.4', '.5', '.6', '.7', '.8', '.9', 'field is required.', 'The'], '', $message);
            })->join(', ');
            $errorString = "Error : (" .  $errorString . ") is required!";

            // Return or redirect with the error string
            // return redirect()->back()->withErrors(['errors' => $errorString])->withInput();
            $dt = [
                'index' => $request->index[$i],
                'res' => $errorString
            ];
            array_push($response, $dt);
        }
        }
        } catch (Exception $e) {
            array_push($response, $e->getMessage());
        }
        return $response;
    }
}
