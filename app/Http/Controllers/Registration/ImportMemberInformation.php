<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\ProductName;
use App\Models\Products;
use App\Models\Registration\MemberAcceptance;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportMemberInformation extends Controller
{
    public function validateMemberData(Request $request)
    {
        ini_set('max_execution_time', '0');
        $data = array();
        $relation = 0;
        $max_idx_principal = PrincipalApplicant::max(DB::raw("isnull(idx, 0)"));
        for ($i = 0; $i < count($request->member_no); $i++) {
            //check if its principal or dependant
            if ($request->principal_dependant[$i] == 24) {
                $relation = 0;
                $max_idx_principal++;
                $famno = "UG" . $max_idx_principal;
                $memno = "UG" . $max_idx_principal . "-00";
                $dt = [
                    'family_no' => $famno,
                    'member_no' => $memno
                ];
                array_push($data, $dt);
            } else if ($request->principal_dependant[$i] == 25) {
                $relation++;
                $famno = "UG" . $max_idx_principal;
                if ($relation < 10) {
                    $memno = "UG" . $max_idx_principal . "-0" . $relation;
                } else {
                    $memno = "UG" . $max_idx_principal  . '-' . $relation;
                }
                $dt = [
                    'family_no' => $famno,
                    'member_no' => $memno
                ];
                array_push($data, $dt);
            } else {
                $dt = [
                    'family_no' => 0,
                    'member_no' => 0
                ];
                array_push($data, $dt);
            }
        }
        return $data;
    }

    public function saveMemberData(Request $request)
    {
        // return $request;
        ini_set('max_execution_time', '0');
        try {
            $response = array();
            $relation = 0;
            $famno = $memno = '';
            $max_idx_principal = 0;
            $date_entered = date("Y-m-d");
            for ($i = 0; $i < count($request->index); $i++) {
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

                    //validate start date and end date
                    $start = Carbon::parse($start_date);
                    $end   = Carbon::parse($end_date);


                    //create member nos and family nos for principal and dependants
                    if ($request->principal_dependant[$i] == 24) {
                        $max_idx_principal = PrincipalApplicant::max(DB::raw("isnull(idx, 0)"));
                        $max_idx_principal++;
                        $relation = 0;
                        $famno = "UG" . $max_idx_principal;
                        $memno = "UG" . $max_idx_principal . "-00";
                    } else if ($request->principal_dependant[$i] == 25) {
                        $relation++;
                        $famno = "UG" . $max_idx_principal;
                        if ($relation < 10) {
                            $memno = "UG" . $max_idx_principal . "-0" . $relation;
                        } else {
                            $memno = "UG" . $max_idx_principal  . '-' . $relation;
                        }
                    }

                    //CHECK DUBLICATE DATA
                    $dublicate_member = MemberInfo::where('surname', $request->surname[$i])
                        ->where('first_name', $request->first_name[$i])
                        ->where('other_names', $request->other_names[$i])
                        ->where('dob', $dob)
                        ->get();
                    //IF DUBLICATE AND DEPENDANT CHECK IF PRINCIPAL EXISTS FIRST
                    $principal = PrincipalApplicant::where('family_no', $famno)->get();

                    if ((count($dublicate_member) === 0 && $request->principal_dependant[$i] == 24) || (count($dublicate_member) === 0 && count($principal) != 0) || ($request->allow_dublicates[$i] == 1 && count($principal) != 0) || ($request->allow_dublicates[$i] == 1 && $request->principal_dependant[$i] == 24)) {
                        $member_no_exists = MemberInfo::where('member_no', $memno)->get();
                        //check if member no exists
                        if (count($member_no_exists) == 0) {
                            $existing_corp = Corporate::where('corp_id', $request->corp_id[$i])->pluck('corp_id');
                            //check if corp exists
                            if (count($existing_corp) != 0) {
                                $plan = ProductName::where('product_name', $request->option[$i])->pluck('code');
                                if (count($plan) != 0) {
                                    $max_anniv = CorpAnniversary::where('corp_id', $request->corp_id[$i])->max('anniv');
                                    //fetch agent for corporate for current anniv of corporate
                                    $agent_id = CorpAnniversary::where('corp_id', $request->corp_id[$i])->where('anniv', $max_anniv)->pluck('agent_id');
                                    $agent_id = count($agent_id) !== 0 ? $agent_id[0] : '';
                                    //get individual value from corporate data
                                    $individual = Corporate::select('individual')->where('corp_id', $request->corp_id[$i])->get();
                                    $individual = count($individual) !== 0 ?  $individual[0]->individual : null;
                                    //1 is corporate and 2 is individual
                                    // $individual = $request->corp_id[$i] == 14 || $request->corp_id[$i] == 658 || $request->corp_id[$i] == 763 || $request->corp_id[$i] == 775? 2 : 1;
                                    // $renewal_date = CorpAnniversary::where('corp_id', $request->corp_id[$i])->where('anniv', $max_anniv)->pluck('renewal_date');
                                    // $renewal_date = count($renewal_date) !== 0 ? $renewal_date[0] : '';
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
                                            $start = Carbon::parse($start_date);
                                            $end   = Carbon::parse($end_date);
                                            //check if start date and end date are less than one year apart
                                            if (!$end->gte($start->copy()->addYear())) {

                                                DB::transaction(function () use ($request, $i, $date_entered, $plan, $agent_id, $individual, $max_idx_principal, $dob, $renewal_date, $max_anniv, $benefits, $memno, $famno, $start_date, $end_date) {
                                                    //check if its principal or dependant
                                                    if ($request->principal_dependant[$i] == 24) {
                                                        //save in principal applicant                                           
                                                        PrincipalApplicant::create([
                                                            'family_no' => $famno,
                                                            'member_no' => $memno,
                                                            'surname' => $request->surname[$i],
                                                            'first_name' => $request->first_name[$i],
                                                            'other_names' => $request->other_names[$i],
                                                            'corp_id' => $request->corp_id[$i],
                                                            'mobile_no' => $request->mobile_no[$i],
                                                            'tel_no' => $request->tel_no[$i],
                                                            'relation_to_family' => $request->principal_dependant[$i],
                                                            'user_id' => $request->user,
                                                            'date_entered' => $date_entered,
                                                            'idx' => $max_idx_principal,
                                                            'plan' => count($plan) != 0 ? $plan[0] : 0,
                                                            'category' => $request->health_plan[$i],
                                                            'agent_id' => $agent_id,
                                                            'individual' => (int)$individual,
                                                            'family_size' => 1,
                                                            // 'smart_sync' => 1,
                                                            // 'sync' => 0
                                                        ]);
                                                    } else {
                                                        $fam_size = MemberInfo::where('family_no', $request->family_no[$i])->count();
                                                        PrincipalApplicant::where('family_no', $request->family_no[$i])
                                                            ->update([
                                                                'family_size' => $fam_size + 1,
                                                                'smart_sync' => 1,
                                                                'sync' => 0
                                                            ]);
                                                    }
                                                    //save in member info
                                                    MemberInfo::create([
                                                        'family_no' => $famno,
                                                        'member_no' => $memno,
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
                                                        'hc_status' => $request->hc_status[$i],
                                                        'email' => $request->email[$i],
                                                        'tel_no' => $request->tel_no[$i],
                                                        'id_pp_no' => $request->id_no[$i],
                                                    ]);

                                                    //member acceptance 
                                                    MemberAcceptance::create([
                                                        'MEMBER_NO' => $memno,
                                                        // 'STATUS' => (int)$request->status[$i],
                                                        'STATUS' => 4, // 4 is pending
                                                        'STATUS_DATE' => $date_entered,
                                                        'USER_ID' => $request->user,
                                                        'DATE_ENTERED' => $date_entered,
                                                        'STATUS_AFTER_APPROVAL' => (int)$request->status[$i]
                                                    ]);

                                                    //member anniversary
                                                    MemberAnniversary::create([
                                                        'member_no' => $memno,
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
                                                        $existing_benefit = MemberBenefits::where('member_no', $memno)
                                                            ->where('benefit', $bene->benefit)
                                                            ->where('anniv', $max_anniv)
                                                            ->count();
                                                        if ($existing_benefit == 0) {
                                                            MemberBenefits::create([
                                                                'member_no' => $memno,
                                                                'benefit' => $bene->benefit,
                                                                'limit' => $bene->limit,
                                                                'sharing' => $bene->sharing,
                                                                'anniv' => $max_anniv,
                                                                'sub_limit_of' => $bene->sub_limit_of,
                                                                'suspended' => $bene->suspend_at,
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
                                                        'family_no' => $famno,
                                                        'member_no' => $memno,
                                                        'index' => $request->index[$i],
                                                        'res' => "Saved successfully (age = $age)"
                                                    ];
                                                } else {
                                                    $dt = [
                                                        'family_no' => $famno,
                                                        'member_no' => $memno,
                                                        'index' => $request->index[$i],
                                                        'res' => "Member saved successfully"
                                                    ];
                                                }

                                                array_push($response, $dt);
                                                // } else {
                                                //     $dt = [
                                                //         'family_no' => '',
                                                //         'member_no' => '',
                                                //         'index' => $request->index[$i],
                                                //         'res' => "Error dob cannot be greater than 70"
                                                //     ];
                                                //     array_push($response, $dt);
                                                // }
                                            } else {
                                                $dt = [
                                                    'family_no' => '',
                                                    'member_no' => '',
                                                    'index' => $request->index[$i],
                                                    'res' => "Error Start and end date must be less than one year apart"
                                                ];
                                                array_push($response, $dt);
                                            }
                                        } else {
                                            $dt = [
                                                'family_no' => '',
                                                'member_no' => '',
                                                'index' => $request->index[$i],
                                                'res' => "Error dob cannot be today or in the future"
                                            ];
                                            array_push($response, $dt);
                                        }
                                    } else {
                                        $dt = [
                                            'family_no' => '',
                                            'member_no' => '',
                                            'index' => $request->index[$i],
                                            'res' => "Error product combination does not exist for this corporate"
                                        ];
                                        array_push($response, $dt);
                                    }
                                } else {
                                    $dt = [
                                        'family_no' => '',
                                        'member_no' => '',
                                        'index' => $request->index[$i],
                                        'res' => "Error health plan does not exist"
                                    ];
                                    array_push($response, $dt);
                                }
                            } else {
                                $dt = [
                                    'family_no' => '',
                                    'member_no' => '',
                                    'index' => $request->index[$i],
                                    'res' => "Error corporate doesn't exist"
                                ];
                                array_push($response, $dt);
                            }
                        } else {
                            $dt = [
                                'family_no' => '',
                                'member_no' => '',
                                'index' => $request->index[$i],
                                'res' => "Error Member with same member number already exists"
                            ];
                            array_push($response, $dt);
                        }
                    } else {
                        $dt = [
                            'family_no' => '',
                            'member_no' => '',
                            'index' => $request->index[$i],
                            'res' => "Error Member with same names and dob already exists"
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
                        'family_no' => '',
                        'member_no' => '',
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
