<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\MemberDiagnosis;
use App\Models\Registration\MemberBenefits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimsExperienceController extends Controller
{
    public function fetchClaimsExperience(Request $request)
    {
        ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', '0');
        $response = [];
        $header = [];

        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));

        switch ($request->options) {
            case '1': //corporate
                $header = ['Principal Names', 'Member Names', 'Relation', 'Member No', 'Claim No', 'Invoice No', 'Invoice Date', 'Claim Nature', 'Provider', 'Diagnosis', 'Invoiced Amount', 'Payable Amount'];
                if (isset($request->tasks) && isset($request->corporates)) {

                    switch ($request->tasks) {
                        case '1': //outpatient
                            $response = Bills::select(
                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                'member_info.member_no',
                                'member_info.family_no',
                                'bills.claim_no',
                                'bills.invoice_no',
                                'service.service',
                                'family_relation.relation',
                                'provider.provider',
                                'bills.invoice_date',
                                'principal_applicant.corp_id',
                                'benefit.benefit',
                                // 'diagnosis.diagnosis',
                                // 'bills.diag_code',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")

                            )
                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                // ->leftJoin('diagnosis', 'diagnosis.code', '=', 'member_diagnosis.diagnosis')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                ->leftJoin('service', 'service.code', '=', 'bills.service')
                                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                ->where('benefit.bene_class', 2)
                                ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                                ->where('bills.corp_id', $request->corporates)
                                ->groupBy(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                    DB::raw("isnull(bills.invoiced_amount, 0)"),
                                    DB::raw("isnull(bills.amount_payable, 0)"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit'
                                )
                                ->orderBy('benefit.benefit', 'ASC')
                                ->get();

                            // foreach ($response as $res) {
                            //     $diag_string = '';
                            //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                            //         ->where('member_diagnosis.claim_no', $res->claim_no)
                            //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                            //     if (count($diagnosis) !== 0) {
                            //         foreach ($diagnosis as $key => $value) {
                            //             if ($key == 0) {
                            //                 $diag_string .= $value;
                            //             } else {
                            //                 $diag_string .= " , $value";
                            //             }
                            //         }
                            //         $res->diag_code = $diag_string;
                            //     } else {
                            //         $res->diag_code = '';
                            //     }
                            // }

                            break;
                        case '2': //benefit wise
                            $response = Bills::select(
                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                'member_info.member_no',
                                'member_info.family_no',
                                'bills.claim_no',
                                'bills.invoice_no',
                                'service.service',
                                'family_relation.relation',
                                'provider.provider',
                                'bills.invoice_date',
                                'principal_applicant.corp_id',
                                'benefit.benefit',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                // 'diagnosis.diagnosis',
                                // 'bills.diag_code',
                            )

                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                // ->leftJoin('diagnosis', 'diagnosis.code', '=', 'member_diagnosis.diagnosis')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                ->leftJoin('service', 'service.code', '=', 'bills.service')
                                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                                ->where('principal_applicant.corp_id', $request->corporates)
                                ->groupBy(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                    DB::raw("isnull(bills.invoiced_amount, 0)"),
                                    DB::raw("isnull(bills.amount_payable, 0)"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit'
                                )
                                ->orderBy('benefit.benefit', 'ASC')
                                ->get();

                            // foreach ($response as $res) {
                            //     $diag_string = '';

                            //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                            //         ->where('member_diagnosis.claim_no', $res->claim_no)
                            //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                            //     if (count($diagnosis) !== 0) {
                            //         foreach ($diagnosis as $key => $value) {
                            //             if ($key == 0) {
                            //                 $diag_string .= $value;
                            //             } else {
                            //                 $diag_string .= " , $value";
                            //             }
                            //         }
                            //         $res->diag_code = $diag_string;
                            //     } else {
                            //         $res->diag_code = '';
                            //     }
                            // }

                            break;
                        case '3': //all claims
                            $response = Bills::select(
                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                'member_info.member_no',
                                'member_info.family_no',
                                'bills.claim_no',
                                'bills.invoice_no',
                                'service.service',
                                'family_relation.relation',
                                'provider.provider',
                                'bills.invoice_date',
                                'principal_applicant.corp_id',
                                'benefit.benefit',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                // 'bills.diag_code'

                            )
                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                ->leftJoin('service', 'service.code', '=', 'bills.service')
                                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                                ->where('principal_applicant.corp_id', $request->corporates)
                                ->groupBy(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                    DB::raw("isnull(bills.invoiced_amount, 0)"),
                                    DB::raw("isnull(bills.amount_payable, 0)"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit'
                                )
                                ->orderBy('benefit.benefit', 'ASC')
                                ->get();
                            // foreach ($response as $res) {
                            //     $diag_string = '';

                            //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                            //         ->where('member_diagnosis.claim_no', $res->claim_no)
                            //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                            //     if (count($diagnosis) !== 0) {
                            //         foreach ($diagnosis as $key => $value) {
                            //             if ($key == 0) {
                            //                 $diag_string .= $value;
                            //             } else {
                            //                 $diag_string .= " , $value";
                            //             }
                            //         }
                            //         $res->diag_code = $diag_string;
                            //     } else {
                            //         $res->diag_code = '';
                            //     }
                            // }
                            break;
                        case '4': //inpatient
                            $response = Bills::select(
                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                'member_info.member_no',
                                'member_info.family_no',
                                'bills.claim_no',
                                'bills.invoice_no',
                                'service.service',
                                'family_relation.relation',
                                'provider.provider',
                                'bills.invoice_date',
                                'principal_applicant.corp_id',
                                'benefit.benefit',
                                // 'diagnosis.diagnosis',
                                // 'bills.diag_code',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                            )

                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                // ->leftJoin('diagnosis', 'diagnosis.code', '=', 'member_diagnosis.diagnosis')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                ->leftJoin('service', 'service.code', '=', 'bills.service')
                                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                ->where('benefit.bene_class', 1)
                                ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                                ->where('bills.corp_id', $request->corporates)
                                ->groupBy(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                    DB::raw("isnull(bills.invoiced_amount, 0)"),
                                    DB::raw("isnull(bills.amount_payable, 0)"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit'
                                )
                                ->orderBy('benefit.benefit', 'ASC')
                                ->get();

                            // foreach ($response as $res) {
                            //     $diag_string = '';

                            //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                            //         ->where('member_diagnosis.claim_no', $res->claim_no)
                            //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                            //     if (count($diagnosis) !== 0) {
                            //         foreach ($diagnosis as $key => $value) {
                            //             if ($key == 0) {
                            //                 $diag_string .= $value;
                            //             } else {
                            //                 $diag_string .= " , $value";
                            //             }
                            //         }
                            //         $res->diag_code = $diag_string;
                            //     } else {
                            //         $res->diag_code = '';
                            //     }
                            // }
                            break;
                        case '5': //claims co sharing
                            //under mainteinance :-)
                            break;
                        case '6': //Claims n Balances
                            $header = ['Principal Names', 'Member Names', 'Member No', 'Claim No', 'Invoice No', 'Invoice Date', 'Claim Nature', 'Provider', 'Diagnosis', 'Invoiced Amount', 'Payable Amount', 'Total', 'Limit', 'Balance'];

                            $balance = $limit = $total = 0;
                            // $mem_no = "";

                            $rows = Bills::select(
                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                'member_info.member_no',
                                'member_info.family_no',
                                'bills.claim_no',
                                'bills.invoice_no',
                                'service.service',
                                'family_relation.relation',
                                'provider.provider',
                                'bills.invoice_date',
                                'principal_applicant.corp_id',
                                'bills.batch_no',
                                'benefit.benefit',
                                'bills.claim_nature',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                            )
                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                ->leftJoin('service', 'service.code', '=', 'bills.service')
                                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                ->where('bills.anniv', $request->anniv)
                                ->where('principal_applicant.corp_id', $request->corporates)
                                ->groupBy(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                    DB::raw("isnull(bills.invoiced_amount, 0)"),
                                    DB::raw("isnull(bills.amount_payable, 0)"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'bills.batch_no',
                                    'benefit.benefit',
                                    'bills.claim_nature'
                                )
                                ->orderBy('benefit.benefit', 'ASC')
                                ->get();
                            foreach ($rows as $value) {
                                //fetch diagnosis for each claim no
                                // $diag_string = '';

                                // $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                //     ->where('member_diagnosis.claim_no', $value->claim_no)
                                //     ->pluck('clinical_diagnosis.clinical_diagnosis');

                                // if (count($diagnosis) !== 0) {
                                //     foreach ($diagnosis as $key => $v) {
                                //         if ($key == 0) {
                                //             $diag_string .= $v;
                                //         } else {
                                //             $diag_string .= " , $v";
                                //         }
                                //     }
                                // }


                                // if ($mem_no != $value->member_no) {
                                $member_bene_data = MemberBenefits::select('limit', 'sharing')
                                    ->where('anniv', $request->anniv)
                                    ->where('benefit', $value->claim_nature)
                                    ->where('member_no', $value->member_no)
                                    ->get();

                                $lc_limit = $member_bene_data[0]->limit;
                                $ll_share = $member_bene_data[0]->sharing;


                                //sharing by family
                                if ($ll_share == 1) {
                                    $lc_expense = Bills::select(DB::raw("sum(isnull(bills.amount_payable, 0)) as amount_payable"))
                                        ->join('member_info', 'bills.member_no', '=', 'member_info.member_no')
                                        ->where('bills.vet_status', '=', 1)
                                        ->where('bills.anniv', $request->anniv)
                                        ->where('bills.claim_nature', $value->claim_nature)
                                        ->where('member_info.family_no', $value->family_no)
                                        ->get();

                                    $lc_expense = $lc_expense[0]->amount_payable;
                                    //sharing by member
                                } else if ($ll_share == 2) {
                                    $lc_expense = Bills::select(DB::raw("sum(isnull(amount_payable, 0)) as amount_payable"))
                                        ->where('vet_status', '=', 1)
                                        ->where('anniv', $request->anniv)
                                        ->where('claim_nature', $value->claim_nature)
                                        ->where('member_no', $value->member_no)
                                        ->get();

                                    $lc_expense = $lc_expense[0]->amount_payable;
                                }

                                if (is_null($lc_expense)) {
                                    $lc_expense = 0;
                                }

                                $total = number_format($lc_expense, 2);
                                $limit = number_format($lc_limit, 2);
                                $balance = $lc_limit - $lc_expense;
                                $balance = number_format($balance, 2);

                                // $mem_no = $value->member_no;
                                // } else {
                                //     $total = "";
                                //     $limit = "";
                                //     $balance = "";
                                // }
                                $dt = [
                                    "total" => $total,
                                    "limit" => $limit,
                                    "balance" => $balance,
                                    "amount_payable" => $value->amount_payable,
                                    "batch_no" => $value->batch_no,
                                    "benefit" => $value->benefit,
                                    "claim_nature" => $value->claim_nature,
                                    "claim_no" => $value->claim_no,
                                    "corp_id" => $value->corp_id,
                                    "family_no" => $value->family_no,
                                    "invoice_date" => $value->invoice_date,
                                    "invoice_no" => $value->invoice_no,
                                    "invoiced_amount" => $value->invoiced_amount,
                                    "member_names" => $value->member_names,
                                    "member_no" => $value->member_no,
                                    "principal_applicant" => $value->principal_applicant,
                                    "provider" => $value->provider,
                                    "relation" => $value->relation,
                                    "service" => $value->service,
                                    "diag_code" => $value->diag_code
                                ];

                                array_push($response, $dt);
                            }
                            break;
                        case '7': // All Claims Summary
                            $header = ['Provider', 'Diagnosis', 'Invoiced Amount', 'Payable Amount'];

                            $response = Bills::select(
                                DB::raw("sum(isnull(bills.invoiced_amount, 0)) as invoiced_amount"),
                                DB::raw("sum(isnull(bills.amount_payable, 0)) as amount_payable"),
                                'provider.provider',
                                // 'bills.diag_code',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                            )
                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->join('provider', 'provider.code', '=', 'bills.provider')
                                ->groupBy('provider.provider')
                                ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                                ->get();

                            // foreach ($response as $res) {
                            //     $diag_string = '';

                            //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                            //         ->where('member_diagnosis.claim_no', $res->claim_no)
                            //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                            //     if (count($diagnosis) !== 0) {
                            //         foreach ($diagnosis as $key => $value) {
                            //             if ($key == 0) {
                            //                 $diag_string .= $value;
                            //             } else {
                            //                 $diag_string .= " , $value";
                            //             }
                            //         }
                            //         $res->diag_code = $diag_string;
                            //     } else {
                            //         $res->diag_code = '';
                            //     }
                            // }
                            break;
                    }
                }
                break;
            case '2': //member
                $header = ['Principal Names', 'Member Names', 'Relation', 'Member No', 'Claim No', 'Invoice No', 'Invoice Date', 'Claim Nature', 'Provider', 'Diagnosis', 'Invoiced Amount', 'Payable Amount'];

                if (isset($request->from) && isset($request->to) && isset($request->member_no)) {
                    $response = Bills::select(
                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                        DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                        DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                        'member_info.member_no',
                        'member_info.family_no',
                        'bills.claim_no',
                        'bills.invoice_no',
                        'service.service',
                        'family_relation.relation',
                        'provider.provider',
                        'bills.invoice_date',
                        'principal_applicant.corp_id',
                        'benefit.benefit',
                        // 'bills.diag_code',
                        DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                    )
                        ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                        ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                        ->leftJoin('service', 'service.code', '=', 'bills.service')
                        ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                        ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                        // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                        ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                        ->where('bills.member_no', $request->member_no)
                        ->groupBy(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                            DB::raw("isnull(bills.invoiced_amount, 0)"),
                            DB::raw("isnull(bills.amount_payable, 0)"),
                            'member_info.member_no',
                            'member_info.family_no',
                            'bills.claim_no',
                            'bills.invoice_no',
                            'service.service',
                            'family_relation.relation',
                            'provider.provider',
                            'bills.invoice_date',
                            'principal_applicant.corp_id',
                            'benefit.benefit'
                        )
                        ->orderBy('benefit.benefit', 'ASC')
                        ->get();

                    // foreach ($response as $res) {
                    //     $diag_string = '';

                    //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                    //         ->where('member_diagnosis.claim_no', $res->claim_no)
                    //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                    //     if (count($diagnosis) !== 0) {
                    //         foreach ($diagnosis as $key => $value) {
                    //             if ($key == 0) {
                    //                 $diag_string .= $value;
                    //             } else {
                    //                 $diag_string .= " , $value";
                    //             }
                    //         }
                    //         $res->diag_code = $diag_string;
                    //     } else {
                    //         $res->diag_code = '';
                    //     }
                    // }
                }
                break;
            case '3': //family
                $header = ['Principal Names', 'Member Names', 'Relation', 'Member No', 'Claim No', 'Invoice No', 'Invoice Date', 'Claim Nature', 'Provider',  'Diagnosis', 'Invoiced Amount', 'Payable Amount'];

                if (isset($request->from) && isset($request->to) && isset($request->family_no)) {
                    $response = Bills::select(
                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                        DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                        DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                        'member_info.member_no',
                        'member_info.family_no',
                        'bills.claim_no',
                        'bills.invoice_no',
                        'service.service',
                        'family_relation.relation',
                        'provider.provider',
                        'bills.invoice_date',
                        'principal_applicant.corp_id',
                        'benefit.benefit',
                        // 'bills.diag_code',
                        DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                    )
                        ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                        ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                        ->leftJoin('service', 'service.code', '=', 'bills.service')
                        ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                        ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                        // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                        ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                        ->where('principal_applicant.family_no', $request->family_no)
                        ->groupBy(
                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                            DB::raw("isnull(bills.invoiced_amount, 0)"),
                            DB::raw("isnull(bills.amount_payable, 0)"),
                            'member_info.member_no',
                            'member_info.family_no',
                            'bills.claim_no',
                            'bills.invoice_no',
                            'service.service',
                            'family_relation.relation',
                            'provider.provider',
                            'bills.invoice_date',
                            'principal_applicant.corp_id',
                            'benefit.benefit'
                        )
                        ->orderBy('benefit.benefit', 'ASC')
                        ->get();

                    // foreach ($response as $res) {
                    //     $diag_string = '';

                    //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                    //         ->where('member_diagnosis.claim_no', $res->claim_no)
                    //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                    //     if (count($diagnosis) !== 0) {
                    //         foreach ($diagnosis as $key => $value) {
                    //             if ($key == 0) {
                    //                 $diag_string .= $value;
                    //             } else {
                    //                 $diag_string .= " , $value";
                    //             }
                    //         }
                    //         $res->diag_code = $diag_string;
                    //     } else {
                    //         $res->diag_code = '';
                    //     }
                    // }
                }
                break;
            case '4': //all
                $header = ['Principal Names', 'Member Names', 'Relation', 'Member No', 'Claim No', 'Invoice No', 'Invoice Date', 'Claim Nature', 'Provider', 'Diagnosis', 'Invoiced Amount', 'Payable Amount'];

                if (isset($request->tasks)) {

                    switch ($request->tasks) {
                        case '1': //outpatient
                            $response = Bills::select(
                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                'member_info.member_no',
                                'member_info.family_no',
                                'bills.claim_no',
                                'bills.invoice_no',
                                'service.service',
                                'family_relation.relation',
                                'provider.provider',
                                'bills.invoice_date',
                                'principal_applicant.corp_id',
                                'benefit.benefit',
                                // 'bills.diag_code',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                            )
                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                ->leftJoin('service', 'service.code', '=', 'bills.service')
                                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                                ->where('benefit.bene_class', 2)
                                ->groupBy(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                    DB::raw("isnull(bills.invoiced_amount, 0)"),
                                    DB::raw("isnull(bills.amount_payable, 0)"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit'
                                )
                                ->orderBy('benefit.benefit', 'ASC')
                                ->get();

                            // foreach ($response as $res) {
                            //     $diag_string = '';

                            //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                            //         ->where('member_diagnosis.claim_no', $res->claim_no)
                            //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                            //     if (count($diagnosis) !== 0) {
                            //         foreach ($diagnosis as $key => $value) {
                            //             if ($key == 0) {
                            //                 $diag_string .= $value;
                            //             } else {
                            //                 $diag_string .= " , $value";
                            //             }
                            //         }
                            //         $res->diag_code = $diag_string;
                            //     } else {
                            //         $res->diag_code = '';
                            //     }
                            // }
                            break;
                        case '2': //benefit wise
                            // no code available :-)

                            $response = Bills::select(
                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) AS principal_applicant"),
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) AS member_names"),
                                DB::raw("ISNULL(bills.invoiced_amount, 0) AS invoiced_amount"),
                                DB::raw("ISNULL(bills.amount_payable, 0) AS amount_payable"),
                                'member_info.member_no',
                                'member_info.family_no',
                                'bills.claim_no',
                                'bills.invoice_no',
                                'service.service',
                                'family_relation.relation',
                                'provider.provider',
                                'bills.invoice_date',
                                'principal_applicant.corp_id',
                                'benefit.benefit',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                            )
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                ->leftJoin('service', 'service.code', '=', 'bills.service')
                                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                                ->groupBy(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                    DB::raw("ISNULL(bills.invoiced_amount, 0)"),
                                    DB::raw("ISNULL(bills.amount_payable, 0)"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit'
                                )
                                ->orderBy('benefit.benefit', 'ASC')
                                ->get();
                            break;
                        case '3': //all claims
                            $response = Bills::select(
                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                'member_info.member_no',
                                'member_info.family_no',
                                'bills.claim_no',
                                'bills.invoice_no',
                                'service.service',
                                'family_relation.relation',
                                'provider.provider',
                                'bills.invoice_date',
                                'principal_applicant.corp_id',
                                'benefit.benefit',
                                // 'bills.diag_code',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                            )
                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                ->leftJoin('service', 'service.code', '=', 'bills.service')
                                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                                ->groupBy(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                    DB::raw("isnull(bills.invoiced_amount, 0)"),
                                    DB::raw("isnull(bills.amount_payable, 0)"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit'
                                )
                                ->orderBy('benefit.benefit', 'ASC')
                                ->get();

                            // foreach ($response as $res) {
                            //     $diag_string = '';

                            //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                            //         ->where('member_diagnosis.claim_no', $res->claim_no)
                            //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                            //     if (count($diagnosis) !== 0) {
                            //         foreach ($diagnosis as $key => $value) {
                            //             if ($key == 0) {
                            //                 $diag_string .= $value;
                            //             } else {
                            //                 $diag_string .= " , $value";
                            //             }
                            //         }
                            //         $res->diag_code = $diag_string;
                            //     } else {
                            //         $res->diag_code = '';
                            //     }
                            // }
                            break;
                        case '4': //inpatient
                            $response = Bills::select(
                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                'member_info.member_no',
                                'member_info.family_no',
                                'bills.claim_no',
                                'bills.invoice_no',
                                'service.service',
                                'family_relation.relation',
                                'provider.provider',
                                'bills.invoice_date',
                                'principal_applicant.corp_id',
                                'benefit.benefit',
                                // 'bills.diag_code',
                                DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                            )
                                ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                ->leftJoin('service', 'service.code', '=', 'bills.service')
                                ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                ->whereBetween(DB::raw("FORMAT(bills.invoice_date, 'yyyyMMdd')"), [$from, $to])
                                ->where('benefit.bene_class', 1)
                                ->groupBy(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                    DB::raw("isnull(bills.invoiced_amount, 0)"),
                                    DB::raw("isnull(bills.amount_payable, 0)"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit'
                                )
                                ->orderBy('benefit.benefit', 'ASC')
                                ->get();

                            // foreach ($response as $res) {
                            //     $diag_string = '';

                            //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                            //         ->where('member_diagnosis.claim_no', $res->claim_no)
                            //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                            //     if (count($diagnosis) !== 0) {
                            //         foreach ($diagnosis as $key => $value) {
                            //             if ($key == 0) {
                            //                 $diag_string .= $value;
                            //             } else {
                            //                 $diag_string .= " , $value";
                            //             }
                            //         }
                            //         $res->diag_code = $diag_string;
                            //     } else {
                            //         $res->diag_code = '';
                            //     }
                            // }
                            break;
                        case '5': //claims co sharing
                            // not applicable :-)
                            break;
                        case '6': // claims and balances
                            // not applicable :-)
                            break;
                        case '7': // all claims summary
                            // not applicable :-)
                            break;
                    }
                }
                break;
            case '5': //smart
                $header = ['Principal Names', 'Member Names', 'Relation', 'Member No', 'Claim No', 'Invoice No', 'Invoice Date', 'Claim Nature', 'Provider', 'Diagnosis', 'Invoiced Amount', 'Payable Amount'];

                if (isset($request->options2)) {

                    switch ($request->options2) {
                        case '1': //corporate
                            if (isset($request->tasks2) && isset($request->corporates)) {

                                switch ($request->tasks2) {
                                    case '1': //outpatient
                                        $response = Bills::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                            DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                            'member_info.member_no',
                                            'member_info.family_no',
                                            'bills.claim_no',
                                            'bills.invoice_no',
                                            'service.service',
                                            'family_relation.relation',
                                            'provider.provider',
                                            'bills.invoice_date',
                                            'principal_applicant.corp_id',
                                            'benefit.benefit',
                                            // 'bills.diag_code',
                                            DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                        )
                                            ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                            ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                            ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                            ->leftJoin('service', 'service.code', '=', 'bills.service')
                                            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                            // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                            ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                            ->where('benefit.bene_class', 2)
                                            ->where('bills.corp_id', $request->corporates)
                                            ->groupBy(
                                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                                DB::raw("isnull(bills.invoiced_amount, 0)"),
                                                DB::raw("isnull(bills.amount_payable, 0)"),
                                                'member_info.member_no',
                                                'member_info.family_no',
                                                'bills.claim_no',
                                                'bills.invoice_no',
                                                'service.service',
                                                'family_relation.relation',
                                                'provider.provider',
                                                'bills.invoice_date',
                                                'principal_applicant.corp_id',
                                                'benefit.benefit'
                                            )
                                            ->orderBy('benefit.benefit', 'ASC')
                                            ->get();

                                        // foreach ($response as $res) {
                                        //     $diag_string = '';

                                        //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                        //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                        //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                        //     if (count($diagnosis) !== 0) {
                                        //         foreach ($diagnosis as $key => $value) {
                                        //             if ($key == 0) {
                                        //                 $diag_string .= $value;
                                        //             } else {
                                        //                 $diag_string .= " , $value";
                                        //             }
                                        //         }
                                        //         $res->diag_code = $diag_string;
                                        //     } else {
                                        //         $res->diag_code = '';
                                        //     }
                                        // }
                                        break;
                                    case '2': //benefit wise
                                        $response = Bills::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                            DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                            'member_info.member_no',
                                            'member_info.family_no',
                                            'bills.claim_no',
                                            'bills.invoice_no',
                                            'service.service',
                                            'family_relation.relation',
                                            'provider.provider',
                                            'bills.invoice_date',
                                            'principal_applicant.corp_id',
                                            'benefit.benefit',
                                            // 'bills.diag_code',
                                            DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                        )
                                            ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                            ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                            ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                            ->leftJoin('service', 'service.code', '=', 'bills.service')
                                            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                            // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                            ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                            ->where('principal_applicant.corp_id', $request->corporates)
                                            ->groupBy(
                                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                                DB::raw("isnull(bills.invoiced_amount, 0)"),
                                                DB::raw("isnull(bills.amount_payable, 0)"),
                                                'member_info.member_no',
                                                'member_info.family_no',
                                                'bills.claim_no',
                                                'bills.invoice_no',
                                                'service.service',
                                                'family_relation.relation',
                                                'provider.provider',
                                                'bills.invoice_date',
                                                'principal_applicant.corp_id',
                                                'benefit.benefit'
                                            )
                                            ->orderBy('benefit.benefit', 'ASC')
                                            ->get();

                                        // foreach ($response as $res) {
                                        //     $diag_string = '';

                                        //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                        //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                        //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                        //     if (count($diagnosis) !== 0) {
                                        //         foreach ($diagnosis as $key => $value) {
                                        //             if ($key == 0) {
                                        //                 $diag_string .= $value;
                                        //             } else {
                                        //                 $diag_string .= " , $value";
                                        //             }
                                        //         }
                                        //         $res->diag_code = $diag_string;
                                        //     } else {
                                        //         $res->diag_code = '';
                                        //     }
                                        // }
                                        break;
                                    case '3': // all claims
                                        $response = Bills::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                            DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                            'member_info.member_no',
                                            'member_info.family_no',
                                            'bills.claim_no',
                                            'bills.invoice_no',
                                            'service.service',
                                            'family_relation.relation',
                                            'provider.provider',
                                            'bills.invoice_date',
                                            'principal_applicant.corp_id',
                                            'benefit.benefit',
                                            // 'bills.diag_code',
                                            DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                        )
                                            ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                            ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                            ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                            ->leftJoin('service', 'service.code', '=', 'bills.service')
                                            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                            // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                            ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                            ->where('bills.corp_id', $request->corporates)
                                            ->groupBy(
                                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                                DB::raw("isnull(bills.invoiced_amount, 0)"),
                                                DB::raw("isnull(bills.amount_payable, 0)"),
                                                'member_info.member_no',
                                                'member_info.family_no',
                                                'bills.claim_no',
                                                'bills.invoice_no',
                                                'service.service',
                                                'family_relation.relation',
                                                'provider.provider',
                                                'bills.invoice_date',
                                                'principal_applicant.corp_id',
                                                'benefit.benefit'
                                            )
                                            ->orderBy('benefit.benefit', 'ASC')
                                            ->get();

                                        // foreach ($response as $res) {
                                        //     $diag_string = '';

                                        //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                        //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                        //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                        //     if (count($diagnosis) !== 0) {
                                        //         foreach ($diagnosis as $key => $value) {
                                        //             if ($key == 0) {
                                        //                 $diag_string .= $value;
                                        //             } else {
                                        //                 $diag_string .= " , $value";
                                        //             }
                                        //         }
                                        //         $res->diag_code = $diag_string;
                                        //     } else {
                                        //         $res->diag_code = '';
                                        //     }
                                        // }
                                        break;
                                    case '4': //inpatient
                                        $response = Bills::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                            DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                            'member_info.member_no',
                                            'member_info.family_no',
                                            'bills.claim_no',
                                            'bills.invoice_no',
                                            'service.service',
                                            'family_relation.relation',
                                            'provider.provider',
                                            'bills.invoice_date',
                                            'principal_applicant.corp_id',
                                            'benefit.benefit',
                                            // 'bills.diag_code',
                                            DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                        )
                                            ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                            ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                            ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                            ->leftJoin('service', 'service.code', '=', 'bills.service')
                                            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                            // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                            ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                            ->where('benefit.bene_class', 1)
                                            ->where('bills.corp_id', $request->corporates)
                                            ->groupBy(
                                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                                DB::raw("isnull(bills.invoiced_amount, 0)"),
                                                DB::raw("isnull(bills.amount_payable, 0)"),
                                                'member_info.member_no',
                                                'member_info.family_no',
                                                'bills.claim_no',
                                                'bills.invoice_no',
                                                'service.service',
                                                'family_relation.relation',
                                                'provider.provider',
                                                'bills.invoice_date',
                                                'principal_applicant.corp_id',
                                                'benefit.benefit'
                                            )
                                            ->orderBy('benefit.benefit', 'ASC')
                                            ->get();

                                        // foreach ($response as $res) {
                                        //     $diag_string = '';

                                        //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                        //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                        //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                        //     if (count($diagnosis) !== 0) {
                                        //         foreach ($diagnosis as $key => $value) {
                                        //             if ($key == 0) {
                                        //                 $diag_string .= $value;
                                        //             } else {
                                        //                 $diag_string .= " , $value";
                                        //             }
                                        //         }
                                        //         $res->diag_code = $diag_string;
                                        //     } else {
                                        //         $res->diag_code = '';
                                        //     }
                                        // }
                                        break;
                                }
                            }
                            break;
                        case '2'://member
                            if (isset($request->member_no)) {
                                $response = Bills::select(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                    DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                    DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit',
                                    // 'bills.diag_code',
                                    DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                )
                                    ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                    ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                    ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                    ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                    ->leftJoin('service', 'service.code', '=', 'bills.service')
                                    ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                    ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                    // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                    ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                    ->where('bills.member_no', $request->member_no)
                                    ->groupBy(
                                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                        DB::raw("isnull(bills.invoiced_amount, 0)"),
                                        DB::raw("isnull(bills.amount_payable, 0)"),
                                        'member_info.member_no',
                                        'member_info.family_no',
                                        'bills.claim_no',
                                        'bills.invoice_no',
                                        'service.service',
                                        'family_relation.relation',
                                        'provider.provider',
                                        'bills.invoice_date',
                                        'principal_applicant.corp_id',
                                        'benefit.benefit'
                                    )
                                    ->orderBy('benefit.benefit', 'ASC')
                                    ->get();

                                // foreach ($response as $res) {
                                //     $diag_string = '';

                                //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                //     if (count($diagnosis) !== 0) {
                                //         foreach ($diagnosis as $key => $value) {
                                //             if ($key == 0) {
                                //                 $diag_string .= $value;
                                //             } else {
                                //                 $diag_string .= " , $value";
                                //             }
                                //         }
                                //         $res->diag_code = $diag_string;
                                //     } else {
                                //         $res->diag_code = '';
                                //     }
                                // }
                            }
                            break;
                        case '3'://family
                            if (isset($request->family_no)) {
                                $response = Bills::select(
                                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                    DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                    DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                    'member_info.member_no',
                                    'member_info.family_no',
                                    'bills.claim_no',
                                    'bills.invoice_no',
                                    'service.service',
                                    'family_relation.relation',
                                    'provider.provider',
                                    'bills.invoice_date',
                                    'principal_applicant.corp_id',
                                    'benefit.benefit',
                                    // 'bills.diag_code',
                                    DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                )

                                    ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                    ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                    ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                    ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                    ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                    ->leftJoin('service', 'service.code', '=', 'bills.service')
                                    ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                    ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                    // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                    ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                    ->where('bills.family_no', $request->family_no)
                                    ->groupBy(
                                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                        DB::raw("isnull(bills.invoiced_amount, 0)"),
                                        DB::raw("isnull(bills.amount_payable, 0)"),
                                        'member_info.member_no',
                                        'member_info.family_no',
                                        'bills.claim_no',
                                        'bills.invoice_no',
                                        'service.service',
                                        'family_relation.relation',
                                        'provider.provider',
                                        'bills.invoice_date',
                                        'principal_applicant.corp_id',
                                        'benefit.benefit'
                                    )
                                    ->orderBy('benefit.benefit', 'ASC')
                                    ->get();

                                // foreach ($response as $res) {
                                //     $diag_string = '';

                                //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                //     if (count($diagnosis) !== 0) {
                                //         foreach ($diagnosis as $key => $value) {
                                //             if ($key == 0) {
                                //                 $diag_string .= $value;
                                //             } else {
                                //                 $diag_string .= " , $value";
                                //             }
                                //         }
                                //         $res->diag_code = $diag_string;
                                //     } else {
                                //         $res->diag_code = '';
                                //     }
                                // }
                            }
                            break;
                        case '4': //all
                            if (isset($request->tasks2)) {

                                switch ($request->tasks2) {
                                    case '1': //outpatient
                                        $response = Bills::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                            DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                            'member_info.member_no',
                                            'member_info.family_no',
                                            'bills.claim_no',
                                            'bills.invoice_no',
                                            'service.service',
                                            'family_relation.relation',
                                            'provider.provider',
                                            'bills.invoice_date',
                                            'principal_applicant.corp_id',
                                            'benefit.benefit',
                                            // 'bills.diag_code',
                                            DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                        )
                                            ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                            ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                            ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                            ->leftJoin('service', 'service.code', '=', 'bills.service')
                                            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                            // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                            ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                             ->where('benefit.bene_class', 2)
                                            ->groupBy(
                                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                                DB::raw("isnull(bills.invoiced_amount, 0)"),
                                                DB::raw("isnull(bills.amount_payable, 0)"),
                                                'member_info.member_no',
                                                'member_info.family_no',
                                                'bills.claim_no',
                                                'bills.invoice_no',
                                                'service.service',
                                                'family_relation.relation',
                                                'provider.provider',
                                                'bills.invoice_date',
                                                'principal_applicant.corp_id',
                                                'benefit.benefit'
                                            )
                                            ->orderBy('benefit.benefit', 'ASC')
                                            ->get();

                                        // foreach ($response as $res) {
                                        //     $diag_string = '';

                                        //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                        //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                        //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                        //     if (count($diagnosis) !== 0) {
                                        //         foreach ($diagnosis as $key => $value) {
                                        //             if ($key == 0) {
                                        //                 $diag_string .= $value;
                                        //             } else {
                                        //                 $diag_string .= " , $value";
                                        //             }
                                        //         }
                                        //         $res->diag_code = $diag_string;
                                        //     } else {
                                        //         $res->diag_code = '';
                                        //     }
                                        // }
                                        break;
                                    case '2': //benefit wise
                                        $response = Bills::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                            DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                            'member_info.member_no',
                                            'member_info.family_no',
                                            'bills.claim_no',
                                            'bills.invoice_no',
                                            'service.service',
                                            'family_relation.relation',
                                            'provider.provider',
                                            'bills.invoice_date',
                                            'principal_applicant.corp_id',
                                            'benefit.benefit',
                                            // 'bills.diag_code',
                                            DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                        )
                                            ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                            ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                            ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                            ->leftJoin('service', 'service.code', '=', 'bills.service')
                                            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                            // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                            ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                            ->where('bills.claim_nature', 13)
                                            ->groupBy(
                                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                                DB::raw("isnull(bills.invoiced_amount, 0)"),
                                                DB::raw("isnull(bills.amount_payable, 0)"),
                                                'member_info.member_no',
                                                'member_info.family_no',
                                                'bills.claim_no',
                                                'bills.invoice_no',
                                                'service.service',
                                                'family_relation.relation',
                                                'provider.provider',
                                                'bills.invoice_date',
                                                'principal_applicant.corp_id',
                                                'benefit.benefit'
                                            )
                                            ->orderBy('benefit.benefit', 'ASC')
                                            ->get();

                                        // foreach ($response as $res) {
                                        //     $diag_string = '';

                                        //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                        //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                        //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                        //     if (count($diagnosis) !== 0) {
                                        //         foreach ($diagnosis as $key => $value) {
                                        //             if ($key == 0) {
                                        //                 $diag_string .= $value;
                                        //             } else {
                                        //                 $diag_string .= " , $value";
                                        //             }
                                        //         }
                                        //         $res->diag_code = $diag_string;
                                        //     } else {
                                        //         $res->diag_code = '';
                                        //     }
                                        // }
                                        break;
                                    case '3': //all claims
                                        $response = Bills::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                            DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                            'member_info.member_no',
                                            'member_info.family_no',
                                            'bills.claim_no',
                                            'bills.invoice_no',
                                            'service.service',
                                            'family_relation.relation',
                                            'provider.provider',
                                            'bills.invoice_date',
                                            'principal_applicant.corp_id',
                                            'benefit.benefit',
                                            // 'bills.diag_code',
                                            DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")

                                        )

                                            ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                            ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                            ->leftJoin('diagnosis', 'diagnosis.code', '=', 'member_diagnosis.diagnosis')
                                            ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                            ->leftJoin('service', 'service.code', '=', 'bills.service')
                                            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                            // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                            ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                            ->where('bills.claim_nature', 13)
                                            ->groupBy(
                                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                                DB::raw("isnull(bills.invoiced_amount, 0)"),
                                                DB::raw("isnull(bills.amount_payable, 0)"),
                                                'member_info.member_no',
                                                'member_info.family_no',
                                                'bills.claim_no',
                                                'bills.invoice_no',
                                                'service.service',
                                                'family_relation.relation',
                                                'provider.provider',
                                                'bills.invoice_date',
                                                'principal_applicant.corp_id',
                                                'benefit.benefit'
                                            )
                                            ->orderBy('benefit.benefit', 'ASC')
                                            ->get();

                                        // foreach ($response as $res) {
                                        //     $diag_string = '';

                                        //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                        //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                        //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                        //     if (count($diagnosis) !== 0) {
                                        //         foreach ($diagnosis as $key => $value) {
                                        //             if ($key == 0) {
                                        //                 $diag_string .= $value;
                                        //             } else {
                                        //                 $diag_string .= " , $value";
                                        //             }
                                        //         }
                                        //         $res->diag_code = $diag_string;
                                        //     } else {
                                        //         $res->diag_code = '';
                                        //     }
                                        // }
                                        break;
                                    case '4': //inpatient
                                        $response = Bills::select(
                                            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                                            DB::raw("isnull(bills.invoiced_amount, 0) as invoiced_amount"),
                                            DB::raw("isnull(bills.amount_payable, 0) as amount_payable"),
                                            'member_info.member_no',
                                            'member_info.family_no',
                                            'bills.claim_no',
                                            'bills.invoice_no',
                                            'service.service',
                                            'family_relation.relation',
                                            'provider.provider',
                                            'bills.invoice_date',
                                            'principal_applicant.corp_id',
                                            'benefit.benefit',
                                            // 'bills.diag_code',
                                            DB::raw("LIST(ISNULL(clinical_diagnosis.clinical_diagnosis, ''), ', ') AS diag_code")
                                        )

                                            ->leftJoin('member_diagnosis', 'member_diagnosis.claim_no', '=', 'bills.claim_no')
                                            ->leftJoin('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                            ->leftJoin('diagnosis', 'diagnosis.code', '=', 'member_diagnosis.diagnosis')
                                            ->join('smart_bills', 'bills.smart_bill_id', '=', 'smart_bills.smart_bill_id')
                                            ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                                            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                                            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                                            ->leftJoin('service', 'service.code', '=', 'bills.service')
                                            ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                                            ->leftJoin('benefit', 'benefit.code', '=', 'bills.claim_nature')
                                            // ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3, 5])
                                            ->where('benefit.bene_class', 1)
                                            ->whereBetween(DB::raw("FORMAT(smart_bills.smart_trans_date, 'yyyyMMdd')"), [$from, $to])
                                            ->where('bills.corp_id', $request->corporates)
                                            ->groupBy(
                                                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                                                DB::raw("isnull(bills.invoiced_amount, 0)"),
                                                DB::raw("isnull(bills.amount_payable, 0)"),
                                                'member_info.member_no',
                                                'member_info.family_no',
                                                'bills.claim_no',
                                                'bills.invoice_no',
                                                'service.service',
                                                'family_relation.relation',
                                                'provider.provider',
                                                'bills.invoice_date',
                                                'principal_applicant.corp_id',
                                                'benefit.benefit'
                                            )
                                            ->orderBy('benefit.benefit', 'ASC')
                                            ->get();

                                        // foreach ($response as $res) {
                                        //     $diag_string = '';

                                        //     $diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                                        //         ->where('member_diagnosis.claim_no', $res->claim_no)
                                        //         ->pluck('clinical_diagnosis.clinical_diagnosis');

                                        //     if (count($diagnosis) !== 0) {
                                        //         foreach ($diagnosis as $key => $value) {
                                        //             if ($key == 0) {
                                        //                 $diag_string .= $value;
                                        //             } else {
                                        //                 $diag_string .= " , $value";
                                        //             }
                                        //         }
                                        //         $res->diag_code = $diag_string;
                                        //     } else {
                                        //         $res->diag_code = '';
                                        //     }
                                        // }
                                        break;
                                }
                            }
                            break;
                    }
                }
                break;
        }

        return ["header" => $header, "body" => json_encode($response, JSON_INVALID_UTF8_IGNORE)];
    }
}
