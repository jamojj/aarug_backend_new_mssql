<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;

class ImportContactDetailsController extends Controller
{
    //update contact details
    public function updateContactDetails(Request $request)
    {
        // return $request;
        try {
            $response = array();
            for ($i = 0; $i < count($request->member_no); $i++) {
                $existing_member = MemberInfo::select('relation_to_principal')->where('member_no', $request->member_no[$i])->get();
                if (count($existing_member) != 0) {
                    $max_anniv = MemberAnniversary::where('member_no', $request->member_no[$i])->max('anniv');
                    $relation = $existing_member[0]->relation_to_principal;
                    if ($relation == "24" || $relation == "3") {
                        PrincipalApplicant::where([
                            'MEMBER_NO' => $request->member_no[$i]
                        ])->update([
                            'TEL_NO' => $request->tel_no[$i],
                            'MOBILE_NO' => $request->mobile_no[$i],
                            'EMAIL' => $request->email[$i],
                            'POSTAL_ADD' => $request->postal_add[$i],
                            'smart_sync' => 1,
                            'sync' => 0
                        ]);
                    }

                    MemberInfo::where([
                        'member_no' => $request->member_no[$i]
                    ])->update([
                        'tel_no' => $request->tel_no[$i],
                        'email' => $request->email[$i],
                    ]);

                    MemberAnniversary::where('member_no', $request->member_no[$i])
                        ->where('anniv', $max_anniv)
                        ->update([
                            'smart_sync' => 1,
                            'sync' => 0
                        ]);

                    $dt = [
                        'index' => $request->index[$i],
                        'res' => "Contact(s) Updated Successfully"
                    ];
                    array_push($response, $dt);
                } else {
                    $dt = [
                        'index' => $request->index[$i],
                        'res' => "Error! Member_No doesn't exist"
                    ];
                    array_push($response, $dt);
                }
            }
        } catch (Exception $e) {
            array_push($response, $e->getMessage());
        }
        return $response;
    }

    //update id nos
    public function updateMemberIdNo(Request $request)
    {
        // return $request;
        try {
            $response = array();
            for ($i = 0; $i < count($request->member_no); $i++) {
                $existing_member = MemberInfo::where('member_no', $request->member_no[$i])->get();
                if (count($existing_member) !== 0) {
                    MemberInfo::where([
                        'MEMBER_NO' => $request->member_no[$i]
                    ])->update([
                        'id_pp_no' => $request->id_no[$i]
                    ]);
                    $dt = [
                        'index' => $request->index[$i],
                        'res' => "ID NO Updated Successfully"
                    ];
                    array_push($response, $dt);
                } else {
                    $dt = [
                        'index' => $request->index[$i],
                        'res' => "Error! Member does not exist"
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
