<?php

namespace App\Http\Controllers\Bills\BillProcess;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\ClaimForm;
use App\Models\LastNumber;
use App\Models\MemberDiagnosis;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChronicManagementController extends Controller
{

    public function chronicDashboard()
    {
        $today = date("Ymd");
        $month = date("Ym");
        $chronic_members = MemberInfo::where('chronic_member', 1)->count();
        $enrolled_members_daily = MemberInfo::where(DB::raw("FORMAT(chronic_member_date_entered, 'yyyyMMdd')"), $today)->where('chronic_member', 1)->count();
        $enrolled_members_monthly = MemberInfo::where(DB::raw("FORMAT(chronic_member_date_entered, 'YYYYmm')"), $month)->where('chronic_member', 1)->count();
        $prescriptions_daily = Bills::where(DB::raw("FORMAT(date_entered, 'yyyyMMdd')"), $today)->where(DB::raw("isnull(chronic_bill, 0)"), 1)->count();
        $prescriptions_monthly = Bills::where(DB::raw("FORMAT(date_entered, 'YYYYmm')"), $month)->where(DB::raw("isnull(chronic_bill, 0)"), 1)->count();
        $pending_drug_refills = Bills::where(DB::raw("FORMAT(next_date_of_refill, 'yyyyMMdd')"), '>', $today)->where(DB::raw("isnull(chronic_bill, 0)"), 1)->count();
        // $pending_drug_refills_monthly = Bills::where(DB::raw("FORMAT(next_date_of_refill, 'YYYYmm')"), '>', $month)->where(DB::raw("isnull(chronic_bill, 0)"), 1)->count();
        $dt = [
            'chronic_members' => $chronic_members,
            'enrolled_members_daily' => $enrolled_members_daily,
            'enrolled_members_monthly' => $enrolled_members_monthly,
            'prescriptions_daily' => $prescriptions_daily,
            'prescriptions_monthly' => $prescriptions_monthly,
            'pending_drug_refills' => $pending_drug_refills,
            // 'pending_drug_refills_monthly' => $pending_drug_refills_monthly,
        ];

        return $dt;
    }

    public function fetchPrescriptionMembers(Request $request)
    {
        $member = [];

        switch ($request->option) {
            case '1':
                $member = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    'member_info.family_no',
                    'member_info.member_no',
                    't_gender.gender',
                    'member_info.dob',
                )
                    ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
                    ->where('principal_applicant.corp_id', $request->corp)
                    ->where(function ($q) use ($request) {
                        $q->where('member_info.member_no', '=', $request->member)
                            ->orWhere('member_info.surname', 'like', '%' . $request->member . '%')
                            ->orWhere('member_info.first_name', 'like', '%' . $request->member . '%')
                            ->orWhere('member_info.other_names', 'like', '%' . $request->member . '%');
                    })
                    ->where('chronic_member', 1)
                    ->get();
                break;
            case '2':
                $member = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    'member_info.family_no',
                    'member_info.member_no',
                    't_gender.gender',
                    'member_info.dob',
                )
                    ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
                    ->where('member_info.member_no', '=', $request->member)
                    ->where('chronic_member', 1)
                    ->get();
                break;
            case '3':
                $member = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    'member_info.family_no',
                    'member_info.member_no',
                    't_gender.gender',
                    'member_info.dob',
                )
                    ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
                    ->where('chronic_member', 1)
                    ->get();
                break;
        }


        return $member;
    }

    public function saveChronicImportMembers(Request $request)
    {
        // return $request;
        try {
            $response = $response2 = $errors = [];
            $date_entered = date("Y-m-d");
            if (isset($request->index)) {
                for ($i = 0; $i < count($request->index); $i++) {
                    //check if ticked as chronic member
                    if ($request->chronic_member[$i] === "0" ||  $request->chronic_member[$i] === "1") {
                        //check if member exists
                        $exists = MemberInfo::where('member_no', $request->member_no[$i])->count();
                        if ($exists !== 0) {
                            DB::transaction(function () use ($request, $i, $date_entered) {
                                MemberInfo::where('member_no', $request->member_no[$i])->update([
                                    'chronic_member' => $request->chronic_member[$i],
                                    'chronic_member_date_entered' => $date_entered,
                                    'chronic_diagnosis' => $request->chronic_diagnosis[$i],
                                    'chronic_user' => $request->user
                                ]);
                            });
                            $dt = [
                                'index' => $request->index[$i],
                                'res' => "Successfully added / removed"
                            ];
                            array_push($response, $dt);
                        } else {
                            $dt = [
                                'index' => $request->index[$i],
                                'res' => "Error, Member does not exist!"
                            ];
                            array_push($response, $dt);
                        }
                    } else {
                        $dt = [
                            'index' => $request->index[$i],
                            'res' => "Error, Chronic member column should be 0 or 1"
                        ];
                        array_push($response, $dt);
                    }
                }
            }
            if (isset($request->member_no_appended)) {
                for ($i = 0; $i < count($request->member_no_appended); $i++) {
                    //check if ticked as chronic member
                    if ($request->chronic_member_appended[$i] === "0" || $request->chronic_member_appended[$i] === "1") {
                        //check if member exists
                        $exists = MemberInfo::where('member_no', $request->member_no_appended[$i])->count();
                        if ($exists !== 0) {
                            DB::transaction(function () use ($request, $i, $date_entered) {
                                MemberInfo::where('member_no', $request->member_no_appended[$i])->update([
                                    'chronic_member' => $request->chronic_member_appended[$i],
                                    'chronic_member_date_entered' => $date_entered,
                                    'chronic_diagnosis' => $request->chronic_diagnosis_appended[$i],
                                    'chronic_user' => $request->user
                                ]);
                            });
                            $dt = [
                                'member_no' => $request->member_no_appended[$i],
                                'res' => "Successfully added / removed"
                            ];
                            array_push($response2, $dt);
                        } else {
                            $dt = [
                                'member_no' => $request->member_no_appended[$i],
                                'res' => "Error, Member does not exist!"
                            ];
                            array_push($response2, $dt);
                        }
                    } else {
                        $dt = [
                            'member_no' => $request->member_no_appended[$i],
                            'res' => "Error, Chronic member column should be 0 or 1"
                        ];
                        array_push($response2, $dt);
                    }
                }
            }
        } catch (Exception $e) {
            array_push($errors, $e->getMessage());
        }
        return ['imported' => $response, 'appended' => $response2, 'errors' => $errors];
    }



    public function fetchMemberDetailsChronic($member_no)
    {
        $Member_info = MemberInfo::select(
            'member_info.family_no',
            'member_info.member_no',
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            'member_info.relation_to_principal',
            'member_info.family_title',
            'corporate.corporate'
        )
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where('member_info.member_no', $member_no)
            ->get();

        $max_anniv = MemberAnniversary::where('member_no', $member_no)->max('anniv');
        $past_prescriptions = Bills::select(
            DB::raw("FORMAT(date_of_refill, 'dd/mm/yyyy') as date_of_refill"),
            DB::raw("FORMAT(next_date_of_refill, 'dd/mm/yyyy') as next_date_of_refill"),
            'id',
            'claim_no',
            'member_no',
            'service.service',
            'anniv',
            'prescription',
            'prescription_days',
            'user_id'
        )
            ->leftJoin('service', 'service.code', '=', 'bills.service')
            ->where('member_no', $member_no)
            ->where('chronic_bill', 1)
            ->get();
        $member_cover_dates = MemberAnniversary::select(
            'start_date',
            'end_date',
            'renewal_date'
        )
            ->where('member_no',  $member_no)
            ->where('anniv', $max_anniv)
            ->get();

        return ['member_info' => $Member_info, 'member_cover_dates' => $member_cover_dates, 'past_prescriptions' => $past_prescriptions, 'anniv' => $max_anniv];
    }


    public function fetchChronicPrescription(Request $request)
    {
        $prescription = Bills::where('id', $request->id)->get();
        $member_diagnosis = MemberDiagnosis::select(
            'member_diagnosis.claim_no',
            'member_diagnosis.member_no',
            'clinical_diagnosis.clinical_diagnosis'
        )
            ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
            ->where('member_diagnosis.claim_no', $request->claim_no)
            ->get();
        return ['prescription' => $prescription, 'member_diagnosis' => $member_diagnosis];
    }

    public function saveChronicPrescription(Request $request)
    {
        // return $request;
        try {
            //check if entry exist
            $service = Bills::select('*')
                ->where('service', $request->service)
                ->where('invoice_no', $request->invoice_no)
                ->where('invoice_date', $request->invoice_date)
                ->where('member_no', $request->member_no)
                ->get();

            if (count($service) !== 0) {
                return ['Notice! A similar invoice exists..cannot proceed. Please rectify'];
            } else {
                $ll_last = LastNumber::where('task', 4)->get('last_no');
                $ll_last = count($ll_last) !== 0 ? $ll_last[0]->last_no : 0;
                $ll_last += 1;


                //create batch no
                $batch_no = 'BAT-' . $request->batch;

                $family_no = explode('-', $request->member_no);
                $family_no = $family_no[0];
                $corp_id = MemberInfo::where('member_no', $request->member_no)->get('corp_id');
                $corp_id = $corp_id[0]['corp_id'];
                $max_id = Bills::max('id') + 1;


                $claim_no = 'CLM' . ' ' . $ll_last;

                DB::transaction(function () use ($request, $claim_no, $batch_no, $family_no, $corp_id, $max_id) {
                    //set inserted, updated, and deleted items
                    $inserted_item = $claim_no;
                    $updated_item =  null;
                    $deleted_item = null;

                    Bills::create([
                        'CLAIM_NO' => $claim_no,
                        'INVOICE_NO' => $request->invoice_no,
                        'CLAIM_FORM_NO' => $claim_no,
                        'PROVIDER' => $request->provider,
                        'MEMBER_NO' => $request->member_no,
                        'SERVICE' => $request->service,
                        'CLAIM_NATURE' => $request->benefit,
                        'INVOICE_DATE' => $request->invoice_date,
                        'INVOICED_AMOUNT' => $request->invoiced_amount,
                        'BATCH_NO' => $batch_no,
                        'USER_ID' => $request->user,
                        'DATE_ENTERED' => today(),
                        'anniv' => $request->anniv,
                        'fund' => $request->fund,
                        'date_received' => $request->date_received,
                        'pre_auth_no' => $request->pre_auth_no,
                        'family_no' => $family_no,
                        'corp_id' => $corp_id,
                        'id' => $max_id,
                        'claim_form' => $claim_no,
                        'REFUND' => (int)$request->pay_to,
                        'chronic_bill' => 1,
                        'prescription' => $request->prescription,
                        'prescription_days' => $request->prescription_days,
                        'date_of_refill' => $request->date_of_refill,
                        'next_date_of_refill' => $request->next_date_of_refill,
                        'product_name' => $request->product_name

                    ]);

                    //save claims tab into table claim form
                    ClaimForm::create([
                        'CLAIM_NO' => $claim_no,
                        'USER_ID' => $request->user,
                        'DATE_ENTERED' => today(),
                        'VISIT_DATE'  => today(),
                    ]);

                    //diagnosis
                    //save member diagnosis if it exists
                    if (isset($request->diagnosis_idx)) {
                        for ($i = 0; $i < count($request->diagnosis_idx); $i++) {
                            $exists = MemberDiagnosis::where('claim_no', $claim_no)
                                ->where('member_no', $request->member_no)
                                ->where('diagnosis', $request->diagnosis_idx[$i])
                                ->get();

                            if (count($exists) === 0) {
                                MemberDiagnosis::create([
                                    'claim_no' => $claim_no,
                                    'member_no' => $request->member_no,
                                    'diagnosis' => (int)$request->diagnosis_idx[$i]
                                ]);
                            }
                        }
                    }

                    //increment last number
                    LastNumber::where('TASK', 4)->increment('LAST_NO');

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                });
                return ["Successful. Prescription No : $claim_no"];
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function updatePastPrescription(Request $request)
    {
        // return $request;
        try {
            $batch_no =  $request->batch;

            if (strpos($request->batch, 'BAT') === false) {
                $batch_no = 'BAT-' . $request->batch;
            }

            $family_no = explode('-', $request->member_no);
            $family_no = $family_no[0];
            $corp_id = MemberInfo::where('member_no', $request->member_no)->get('corp_id');
            $corp_id = $corp_id[0]['corp_id'];


            DB::transaction(function () use ($request, $batch_no, $family_no, $corp_id) {
                //set inserted, updated, and deleted items
                $inserted_item = null;
                $updated_item =  " id : $request->id, claim_no : $request->claim_no";
                $deleted_item = null;

                Bills::where('id', $request->id)->update([
                    'CLAIM_NO' => $request->claim_no,
                    'INVOICE_NO' => $request->invoice_no,
                    'CLAIM_FORM_NO' => $request->claim_no,
                    'PROVIDER' => $request->provider,
                    'MEMBER_NO' => $request->member_no,
                    'SERVICE' => $request->service,
                    'CLAIM_NATURE' => $request->benefit,
                    'INVOICE_DATE' => $request->invoice_date,
                    'INVOICED_AMOUNT' => $request->invoiced_amount,
                    'BATCH_NO' => $batch_no,
                    'USER_ID' => $request->user,
                    'fund' => $request->fund,
                    'date_received' => $request->date_received,
                    'pre_auth_no' => $request->pre_auth_no,
                    'family_no' => $family_no,
                    'corp_id' => $corp_id,
                    'claim_form' => $request->claim_no,
                    'REFUND' => (int)$request->pay_to,
                    'prescription' => $request->prescription,
                    'prescription_days' => $request->prescription_days,
                    'date_of_refill' => $request->date_of_refill,
                    'next_date_of_refill' => $request->next_date_of_refill,
                    'product_name' => $request->product_name
                ]);

                //save claims tab into table claim form
                ClaimForm::where('claim_no', $request->claim_no)->update([
                    'USER_ID' => $request->user,
                ]);

                //diagnosis
                //save member diagnosis if it exists
                if (isset($request->diagnosis_idx)) {
                    for ($i = 0; $i < count($request->diagnosis_idx); $i++) {
                        $exists = MemberDiagnosis::where('claim_no', $request->claim_no)
                            ->where('member_no', $request->member_no)
                            ->where('diagnosis', $request->diagnosis_idx[$i])
                            ->get();

                        if (count($exists) === 0) {
                            MemberDiagnosis::create([
                                'claim_no' => $request->claim_no,
                                'member_no' => $request->member_no,
                                'diagnosis' => (int)$request->diagnosis_idx[$i]
                            ]);
                        }
                    }
                }


                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });
            return ["Updated successfully."];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function enrolledMemberDetailedSummary()
    {
        $members = MemberInfo::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
            'member_info.family_no',
            'member_info.member_no',
            'chronic_member_date_entered',
            'corporate.corporate',
            'chronic_user',
            'kin_names'
        )
            ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
            ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->where('chronic_member', 1)
            ->get();

        foreach ($members as $member) {
            $ttl_prescriptions = Bills::where('member_no', $member->member_no)->where('chronic_bill', 1)->count();
            $member->kin_names = $ttl_prescriptions;
        }

        return $members;
    }

    public function fetchPrescriptionReport(Request $request)
    {
        $report = [];
        $chronic_members = [];
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));

        switch ($request->option) {
                //corporate
            case "1":
                $families = PrincipalApplicant::select('family_no')->where('corp_id', $request->corporate)->get();
                foreach ($families as $family) {
                    $mem_no = MemberInfo::select('member_no')->where(DB::raw("isnull(chronic_member, 0)"), 1)->where('family_no', $family->family_no)->get();
                    if (count($mem_no) !== 0) {
                        array_push($chronic_members, $mem_no[0]->member_no);
                    }
                }
                $report = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'corporate.corporate',
                    'bills.member_no',
                    'bills.prescription',
                    'bills.date_of_refill',
                    'bills.prescription_days',
                    'bills.next_date_of_refill',
                    'bills.user_id',
                    'member_info.email',
                    'member_info.tel_no'
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->where(DB::raw("FORMAT(bills.date_of_refill, 'yyyyMMdd')"), '>=', $from)
                    ->where(DB::raw("FORMAT(bills.next_date_of_refill, 'yyyyMMdd')"), '<=', $to)
                    ->where('bills.chronic_bill', 1)
                    ->whereIn(
                        'bills.member_no',
                        $chronic_members
                    )
                    ->get();
                break;
                //member
            case "2":
                $report = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'corporate.corporate',
                    'bills.member_no',
                    'bills.prescription',
                    'bills.date_of_refill',
                    'bills.prescription_days',
                    'bills.next_date_of_refill',
                    'bills.user_id',
                    'member_info.email',
                    'member_info.tel_no'
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->where(DB::raw("FORMAT(bills.date_of_refill, 'yyyyMMdd')"), '>=', $from)
                    ->where(DB::raw("FORMAT(bills.next_date_of_refill, 'yyyyMMdd')"), '<=', $to)
                    ->where('bills.chronic_bill', 1)
                    ->where('bills.member_no', $request->member_no)
                    ->get();
                //all corporate
                break;
            case "3":
                $report = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'corporate.corporate',
                    'bills.member_no',
                    'bills.prescription',
                    'bills.date_of_refill',
                    'bills.prescription_days',
                    'bills.next_date_of_refill',
                    'bills.user_id',
                    'member_info.email',
                    'member_info.tel_no'
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->where(DB::raw("FORMAT(bills.date_of_refill, 'yyyyMMdd')"), '>=', $from)
                    ->where(DB::raw("FORMAT(bills.next_date_of_refill, 'yyyyMMdd')"), '<=', $to)
                    ->where('bills.chronic_bill', 1)
                    ->get();
                break;
        }




        return $report;
    }
    public function fetchDrugRefillReport(Request $request)
    {
        $report = [];
        $chronic_members = [];
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));

        switch ($request->option) {
                //corporate
            case "1":
                $families = PrincipalApplicant::select('family_no')->where('corp_id', $request->corporate)->get();
                foreach ($families as $family) {
                    $mem_no = MemberInfo::select('member_no')->where(DB::raw("isnull(chronic_member, 0)"), 1)->where('family_no', $family->family_no)->get();
                    if (count($mem_no) !== 0) {
                        array_push($chronic_members, $mem_no[0]->member_no);
                    }
                }
                $report = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'corporate.corporate',
                    'bills.member_no',
                    'bills.prescription',
                    'bills.date_of_refill',
                    'bills.prescription_days',
                    'bills.next_date_of_refill',
                    'bills.user_id',
                    'member_info.email',
                    'member_info.tel_no'
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->whereBetween(DB::raw("FORMAT(bills.next_date_of_refill, 'yyyyMMdd')"), [$from, $to])
                    ->where('bills.chronic_bill', 1)
                    ->whereIn(
                        'bills.member_no',
                        $chronic_members
                    )
                    ->get();
                break;
                //member
            case "2":
                $report = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'corporate.corporate',
                    'bills.member_no',
                    'bills.prescription',
                    'bills.date_of_refill',
                    'bills.prescription_days',
                    'bills.next_date_of_refill',
                    'bills.user_id',
                    'member_info.email',
                    'member_info.tel_no'
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->whereBetween(DB::raw("FORMAT(bills.next_date_of_refill, 'yyyyMMdd')"), [$from, $to])
                    ->where('bills.chronic_bill', 1)
                    ->where('bills.member_no', $request->member_no)
                    ->get();
                //all corporate
                break;
            case "3":
                $report = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'corporate.corporate',
                    'bills.member_no',
                    'bills.prescription',
                    'bills.date_of_refill',
                    'bills.prescription_days',
                    'bills.next_date_of_refill',
                    'bills.user_id',
                    'member_info.email',
                    'member_info.tel_no'
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->whereBetween(DB::raw("FORMAT(bills.next_date_of_refill, 'yyyyMMdd')"), [$from, $to])
                    ->where('bills.chronic_bill', 1)
                    ->get();
                break;
        }

        return $report;
    }
   

    public function fetchMemberEnrollementReport()
    {
        $report = MemberInfo::select(
            'corporate.corporate',
            'member_info.member_no',
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            'member_info.tel_no',
            'member_info.email',
            'member_info.chronic_diagnosis',
            'member_info.chronic_member_date_entered',
            'member_info.chronic_user',

        )
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->orderBy('member_info.chronic_member_date_entered', 'desc')
            ->orderBy('member_name', 'asc')
            ->where('member_info.chronic_member', 1)
            ->get();

        return $report;
    }
}
