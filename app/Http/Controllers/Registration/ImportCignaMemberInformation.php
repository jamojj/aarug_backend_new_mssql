<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\cigna_member_anniversary;
use App\Models\CignaMemberInfo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportCignaMemberInformation extends Controller
{
    public function saveCignaMemberData(Request $request)
    {
        // return $request;
        try {
            $response = array();
            $max_idx = CignaMemberInfo::max('idx');
            $date_entered = date("Y-m-d");
            for ($i = 0; $i < count($request->index); $i++) {
                $max_idx++;
                //check whether new or renewal
                if ($request->new_or_renewal[$i] == 'N') {
                    //CHECK DUBLICATE DATA
                    $dublicate_member = CignaMemberInfo::where('family_no', $request->family_no[$i])
                        ->where('person_no', $request->person_no[$i])
                        ->get();

                    if (count($dublicate_member) === 0) {

                        DB::transaction(function () use ($request, $i, $date_entered, $max_idx) {
                            CignaMemberInfo::create([
                                'idx' => $max_idx,
                                'name' => $request->name[$i],
                                'family_no' => $request->family_no[$i],
                                'person_no' => $request->person_no[$i],
                                'location' => $request->location[$i],
                                'DOB' => $request->DOB[$i],
                                'address_1' => $request->address_1[$i],
                                'address_2' => $request->address_2[$i],
                                'address_3' => $request->address_3[$i],
                                'address_4' => $request->address_4[$i],
                                'email' => $request->email[$i],
                                'date_entered' => $date_entered,
                                'user_id' => $request->user_id
                            ]);

                            cigna_member_anniversary::create([
                                'family_no' => $request->family_no[$i],
                                'person_no' => $request->person_no[$i],
                                'new_or_renewal' => $request->new_or_renewal[$i],
                                'plan' => $request->plan[$i],
                                'dental' => $request->dental[$i],
                                'AOC' => $request->AOC[$i],
                                'start_date' => $request->start_date[$i],
                                'end_date' => $request->end_date[$i],
                                'payment_frequency' => $request->payment_frequency[$i],
                                'premium_loading' => $request->premium_loading[$i],
                                'premium_deduction' => $request->premium_deduction[$i],
                                'date_entered' => $date_entered,
                                'anniv' => 1,
                                'user_id' => $request->user_id
                            ]);
                        });
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => "Cigna member(s) saved successfully"
                        ];

                        array_push($response, $dt);
                    } else {
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => "Error. Member with same family and person number exists"
                        ];

                        array_push($response, $dt);
                    }
                } else if ($request->new_or_renewal[$i] == 'R') {
                    //check if member exists
                    $exists = CignaMemberInfo::where('family_no', $request->family_no[$i])
                        ->where('person_no', $request->person_no[$i])
                        ->get();

                    if (count($exists) !== 0) {
                        $max_anniv = cigna_member_anniversary::where('family_no', $request->family_no[$i])
                            ->where('person_no', $request->person_no[$i])
                            ->max('anniv');
                        $max_anniv++;
                        DB::transaction(function () use ($request, $i, $date_entered,  $max_anniv) {
                            cigna_member_anniversary::create([
                                'family_no' => $request->family_no[$i],
                                'person_no' => $request->person_no[$i],
                                'new_or_renewal' => $request->new_or_renewal[$i],
                                'plan' => $request->plan[$i],
                                'dental' => $request->dental[$i],
                                'AOC' => $request->AOC[$i],
                                'start_date' => $request->start_date[$i],
                                'end_date' => $request->end_date[$i],
                                'payment_frequency' => $request->payment_frequency[$i],
                                'premium_loading' => $request->premium_loading[$i],
                                'premium_deduction' => $request->premium_deduction[$i],
                                'date_entered' => $date_entered,
                                'anniv' => $max_anniv,
                                'user_id' => $request->user_id
                            ]);
                        });
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => "Successfully added."
                        ];

                        array_push($response, $dt);
                    } else {
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => "Error. Member does not exist"
                        ];

                        array_push($response, $dt);
                    }
                }
            }
        } catch (Exception $e) {
            array_push($response, $e->getMessage());
        }
        return $response;
    }
}
