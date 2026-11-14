<?php

namespace App\Http\Controllers\Registration\Reports;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\FamilyRelation;
use App\Models\PremiumInvoice;
use App\Models\ProductName;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MembershipListController extends Controller
{
    public function fetchCorpAnnivs($corp_id)
    {
        $annivs = CorpAnniversary::select('anniv')
            ->where('corp_id', $corp_id)
            ->orderBy('anniv', 'ASC')
            ->get();

        return $annivs;
    }
    //fetch max anniv of corp
    public function fetchMaxCorpAnniv($corp_id)
    {
        $max_anniv = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');

        return [$max_anniv];
    }

    public function fetchMembershipListReport(Request $request)
    {
        ini_set('max_execution_time', '0');
        
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));


        switch ($request->category) {
            case '1':
                //valid members with limits
                $headers = ['Member No', 'Member Name', 'Relation', 'Dob', 'ID NO', 'Benefit', 'Limit', 'Start Date', 'End Date'];
                $body = MemberInfo::select(
                    DB::raw("CONCAT(member_info.first_name,' ',member_info.other_names,' ',member_info.surname) as member_names"),
                    DB::raw("CONCAT(principal_applicant.first_name,' ',principal_applicant.other_names,' ',principal_applicant.surname) as principal_applicant"),
                    DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
                    DB::raw("FORMAT(member_anniversary.start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(member_anniversary.end_date, 'yyyy-mm-dd') as end_date"),
                    'member_info.member_no',
                    'member_info.dealing_user',
                    'member_info.id_pp_no',
                    'member_info.employment_no',
                    'benefit.benefit',
                    'member_benefits.limit',
                    'member_info.occupation',
                    'family_relation.relation',

                )
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                    ->leftJoin('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                    ->leftJoin('benefit', 'benefit.code', '=', 'member_benefits.benefit')
                    ->where(DB::raw(" isnull(member_info.cancelled,0)"), 0)
                    ->where(DB::raw(" isnull(member_benefits.suspended,0)"), 0)
                    ->where('member_benefits.anniv', $request->anniv)
                    ->where('member_anniversary.anniv', $request->anniv)
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->get();

                $dt = [
                    'headers' => $headers,
                    'body' => $body
                ];

                return $dt;
            case '2':
                //cancelled members
                $headers = ['Corporate', 'Principal Applicant', 'Member No', 'Member Names', 'Id No', 'Cancellation Date'];
                $body = MemberInfo::select(
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                    'corporate.corporate',
                    'member_info.id_pp_no',
                    'member_cancellation.date_can'
                )
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('member_cancellation', 'member_cancellation.member_no', '=', 'member_info.member_no')
                    ->where(DB::raw(" isnull(member_info.cancelled,0)"), 1)
                    ->whereBetween(DB::raw("FORMAT(member_cancellation.date_can, 'yyyyMMdd')"), [$from, $to])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->get();

                $dt = [
                    'headers' => $headers,
                    'body' => $body
                ];
                return $dt;
            case '3':
                //additional members
                $headers = ['Corporate', 'Principal Applicant', 'Member No', 'Member Names', 'Id No', 'Benefit', 'Start Date', 'End Date', 'Dob', 'Employment No'];
                $body = MemberInfo::select(
                    DB::raw("CONCAT(principal_applicant.first_name,' ',principal_applicant.other_names,' ',principal_applicant.surname) as principal_applicant"),
                    DB::raw("CONCAT(member_info.first_name,' ',member_info.other_names,' ',member_info.surname) as member_names"),
                    'member_info.member_no',
                    'member_info.id_pp_no',
                    'member_info.dob',
                    'member_info.employment_no',
                    'member_anniversary.start_date',
                    'member_anniversary.end_date',
                    'benefit.benefit',
                    'corporate.corporate',
                )
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                    ->leftJoin('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('benefit', 'benefit.code', '=', 'member_benefits.benefit')
                    ->where(DB::raw("isnull(member_info.cancelled,0)"), 0)
                    ->whereBetween(DB::raw("FORMAT(member_anniversary.start_date, 'yyyyMMdd')"), [$from, $to])
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->get();

                $dt = [
                    'headers' => $headers,
                    'body' => $body
                ];
                return $dt;
            case '4':
                //category wise
                $headers = ['Category', 'Principal Applicant', 'Member Names', 'Member No', 'Employment No'];
                $body = MemberInfo::select(
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                    'member_info.member_no',
                    'principal_applicant.category',
                    'member_info.employment_no',
                )
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->get();

                $dt = [
                    'headers' => $headers,
                    'body' => $body
                ];
                return $dt;
                break;
            case '5':
                //individual members
                $body = array();
                $headers = ['Principal Name', 'Member Names', 'Member No', 'Start Date', 'End Date', 'Renewal Date', 'Anniversary'];
                $rows = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                    'member_info.member_no',
                    'member_info.family_no',
                )
                    ->leftJoin('principal_applicant', 'principal_applicant.member_no', '=', 'member_info.member_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->where('corporate.individual', 2)
                    ->get();

                foreach ($rows as $row) {
                    $ls_prin = PrincipalApplicant::select(
                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                    )
                        ->where('family_no', $row->family_no)
                        ->get();

                    $mem_anniv = MemberAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                        DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
                    )
                        ->where('member_no', $row->member_no)
                        ->get();

                    $ll_anniv = MemberAnniversary::where('member_no', $row->member_no)->pluck('anniv');

                    $dt = [
                        'principal_name' => $ls_prin[0]->principal_applicant,
                        'member_names' => $row->member_names,
                        'member_no' => $row->member_no,
                        'start_date' => $mem_anniv[0]->start_date,
                        'end_date' => $mem_anniv[0]->end_date,
                        'renewal_date' => $mem_anniv[0]->renewal_date,
                        'anniv' => $ll_anniv[0]
                    ];
                    array_push($body, $dt);
                }
                $data = [
                    'headers' => $headers,
                    'body' => $body
                ];
                return $data;
                break;
            case '6':
                $body = array();
                $headers = ['Principal Name', 'Member Names', 'Member No', 'Invoice No', 'Invoice Date'];
                $rows = PremiumInvoice::select(
                    DB::raw("FORMAT(premium_invoice.invoice_date, 'yyyy-mm-dd') as invoice_date"),
                    'premium_invoice.invoice_no',
                    'premium_invoice_details.family_no',
                    'premium_invoice_details.member_no',
                    'premium_invoice_details.family_size',
                )
                    ->leftJoin('premium_invoice_details', 'premium_invoice_details.invoice_no', '=', 'premium_invoice.invoice_no')
                    ->where('premium_invoice.anniv', $request->anniv)
                    ->where('premium_invoice.corp_id', $request->corporate)
                    ->get();

                foreach ($rows as $row) {
                    $member = MemberInfo::select(
                        DB::raw("CONCAT(surname,' ',first_name,' ',other_names) as member_names"),
                    )
                        ->where('family_no', $row->family_no)
                        ->where('member_no', $row->member_no)
                        ->get();

                    $principal = PrincipalApplicant::select(
                        DB::raw("CONCAT(surname,' ',first_name,' ',other_names) as principal_applicant"),
                    )
                        ->where('family_no', $row->family_no)
                        ->get();


                    $dt = [
                        'principal_name' => $principal[0]->principal_applicant,
                        'member_names' => $member[0]->member_names,
                        'member_no' => $row->member_no,
                        'invoice_no' => $row->invoice_no,
                        'invoice_date' => $row->invoice_date,
                    ];
                    array_push($body, $dt);
                }
                $data = [
                    'headers' => $headers,
                    'body' => $body
                ];
                return $data;
                break;
            case '7':
                $headers = ['Member No', 'Member Name', 'Relation', 'Dob', 'ID NO', 'Benefit', 'Start Date', 'End Date'];
                $body = MemberInfo::select(
                    DB::raw("CONCAT(member_info.first_name,' ',member_info.other_names,' ',member_info.surname) as member_names"),
                    DB::raw("CONCAT(principal_applicant.first_name,' ',principal_applicant.other_names,' ',principal_applicant.surname) as principal_applicant"),
                    DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
                    DB::raw("FORMAT(member_anniversary.start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(member_anniversary.end_date, 'yyyy-mm-dd') as end_date"),
                    'member_info.member_no',
                    'member_info.dealing_user',
                    'member_info.id_pp_no',
                    'member_info.employment_no',
                    'benefit.benefit',
                    'member_info.occupation',
                    'family_relation.relation',
                    'corporate.corporate'

                )
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                    ->leftJoin('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                    ->leftJoin('benefit', 'benefit.code', '=', 'member_benefits.benefit')
                    ->where(DB::raw(" isnull(member_info.cancelled,0)"), 0)
                    ->where(DB::raw(" isnull(member_benefits.suspended,0)"), 1)
                    ->where('member_benefits.anniv', $request->anniv)
                    ->where('member_anniversary.anniv', $request->anniv)
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->get();

                $dt = [
                    'headers' => $headers,
                    'body' => $body
                ];

                return $dt;
                break;
            case '8':
                $body = [];
                $headers = ['Group Name', 'Member Names', 'Relation', 'Gender', 'Category', 'Member No', 'Dob', 'Start Date', 'End Date', 'Product Name', 'Corporate'];
                $rows = MemberInfo::select(
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                    'principal_applicant.category',
                    'principal_applicant.corp_id',
                    'member_info.member_no',
                    'member_info.family_title',
                )
                    ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->where('principal_applicant.corp_id', '!=', 107)
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->get();

                foreach ($rows as $row) {
                    $corporate = Corporate::where('corp_id', $row->corp_id)->pluck('corporate');
                    $relation = FamilyRelation::where('code', $row->family_title)->pluck('relation');
                    $gender = MemberInfo::where('member_no', $row->member_no)->pluck('gender');
                    $dob = MemberInfo::where('member_no', $row->member_no)->pluck('dob');


                    if (count($gender) == 0) {
                        $gender = "";
                    } else {
                        if ($gender[0] == 1) {
                            $gender = "MALE";
                        } else if ($gender[0] == 2) {
                            $gender = "FEMALE";
                        }
                    }
                    $max_anniv = MemberBenefits::where('member_no', $row->member_no)->max('anniv');

                    $product = MemberBenefits::where('anniv', $max_anniv)
                        ->where('member_no', $row->member_no)
                        ->pluck('product_name');


                    $product_name = ProductName::where('code', $product[0])
                        ->pluck('product_name');
                    $anniv_dates = MemberAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                    )
                        ->where('anniv', $max_anniv)
                        ->where('member_no', $row->member_no)
                        ->get();


                    $dt = [
                        'member_no' => $row->member_no,
                        'member_names' => $row->member_names,
                        'corporate' => count($corporate) != 0 ? $corporate[0] : "",
                        'group_name' => $row->principal_applicant,
                        'relation' => count($relation) != 0 ? $relation[0] : "",
                        'dob' => count($dob) != 0 ? $dob[0] : "",
                        'gender' => $gender,
                        'product_name' => count($product_name) != 0 ? $product_name[0] : "",
                        'start_date' =>  $anniv_dates[0]->start_date,
                        'end_date' => $anniv_dates[0]->end_date,
                        'category' => count($product_name) != 0 ? $product_name[0] : ""

                    ];
                    array_push($body, $dt);
                }
                $data = [
                    'headers' => $headers,
                    'body' => $body
                ];
                return $data;
                break;
            case '9':
                $body = [];
                $headers = ['Group Name', 'Member Names', 'Relation', 'Gender', 'Category', 'Member No', 'Dob', 'Start Date', 'End Date', 'Product Name', 'Corporate'];
                $rows = MemberInfo::select(
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_applicant"),
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                    'principal_applicant.category',
                    'principal_applicant.corp_id',
                    'member_info.member_no',
                    'member_info.family_title',
                )
                    ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
                    ->get();
                foreach ($rows as $row) {
                    $corporate = Corporate::where('corp_id', $row->corp_id)->pluck('corporate');
                    $relation = FamilyRelation::where('code', $row->family_title)->pluck('relation');
                    $gender = MemberInfo::where('member_no', $row->member_no)->pluck('gender');
                    $dob = MemberInfo::select(DB::raw("FORMAT(dob, 'yyyy-mm-dd') as dob"))->where('member_no', $row->member_no)->get();


                    if (count($gender) == 0) {
                        $gender = "";
                    } else {
                        if ($gender[0] == 1) {
                            $gender = "MALE";
                        } else if ($gender[0] == 2) {
                            $gender = "FEMALE";
                        }
                    }
                    $max_anniv = MemberBenefits::where('member_no', $row->member_no)->max('anniv');

                    $product = MemberBenefits::where('anniv', $max_anniv)
                        ->where('member_no', $row->member_no)
                        ->pluck('product_name');


                    $product_name = ProductName::where('code', $product[0])
                        ->pluck('product_name');
                    $anniv_dates = MemberAnniversary::select(
                        DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
                        DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
                    )
                        ->where('anniv', $max_anniv)
                        ->where('member_no', $row->member_no)
                        ->get();


                    $dt = [
                        'member_no' => $row->member_no,
                        'member_names' => $row->member_names,
                        'corporate' => count($corporate) != 0 ? $corporate[0] : "",
                        'group_name' => $row->principal_applicant,
                        'relation' => count($relation) != 0 ? $relation[0] : "",
                        'dob' => count($dob) != 0 ? $dob[0]->dob : "",
                        'gender' => $gender,
                        'product_name' => count($product_name) != 0 ? $product_name[0] : "",
                        'start_date' =>  $anniv_dates[0]->start_date,
                        'end_date' => $anniv_dates[0]->end_date,
                        'category' => count($product_name) != 0 ? $product_name[0] : ""

                    ];

                    array_push($body, $dt);
                }
                $data = [
                    'headers' => $headers,
                    'body' => $body
                ];
                return $data;
                break;
            case '10':
                try {
                    $body = array();
                    $headers = ['Corporate', 'Member No', 'Member Names', 'Relation', 'Dob', 'ID NO', 'Start Date', 'End Date', 'Status'];
                    $rows = MemberInfo::select(
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                        'member_info.member_no',
                        'member_info.family_no',
                        'corporate.corporate',
                        'member_info.relation_to_principal',
                        'member_info.dob',
                        'member_info.id_pp_no'
                    )
                        ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                        ->join('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
                        ->where('member_acceptance.status', 1)
                        ->get();
                    foreach ($rows as $row) {
                        $relation = FamilyRelation::where('code', $row->relation_to_principal)->pluck('relation');
                        $anniv = MemberAnniversary::where('member_no', $row->member_no)->max('anniv');
                        $cancelled = MemberInfo::where('member_no', $row->member_no)->pluck('cancelled');
                        $dates = MemberAnniversary::select('start_date', 'end_date')
                            ->where('anniv', $anniv[0])
                            ->where('member_no', $row->member_no)
                            ->get();

                        if (count($cancelled) == 0) {
                            $cancelled = "";
                        } else {
                            if ($cancelled[0] == 1) {
                                $cancelled = "CANCELLED";
                            } else {
                                $cancelled = "ACTIVE";
                            }
                        }

                        $dt = [
                            'corporate' => $row->corporate,
                            'member_no' => $row->member_no,
                            'member_names' => $row->member_names,
                            'relation' => $relation[0],
                            'dob' => $row->dob,
                            'id_pp_no' => $row->id_pp_no,
                            'start_date' => $dates[0]->start_date,
                            'end_date' => $dates[0]->end_date,
                            'status' => $cancelled
                        ];

                        array_push($body, $dt);
                    }
                    $data = [
                        'headers' => $headers,
                        'body' => $body
                    ];
                    return $data;
                } catch (Exception $e) {
                    return [$e->getMessage()];
                }
                break;
            case '11':
                $body = array();
                $headers = ['Family No', 'Member No', 'Member Name', 'Relation', 'Status', 'Dob', 'Tel No', 'Mobile No', 'Email Address'];
                $rows = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                    'member_info.member_no',
                    'member_info.family_no',
                    'corporate.corporate',
                    'member_info.relation_to_principal',
                    'member_info.dob',
                    'member_info.id_pp_no',
                    'member_info.family_title',
                    'member_info.cancelled'
                )
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->where('corporate.corp_id', $request->corporate)
                    ->get();
                foreach ($rows as $row) {
                    $cancelled = "";
                    $relation = FamilyRelation::where('code', $row->relation_to_principal)->pluck('relation');


                    if ($row->cancelled == 1) {
                        $cancelled = "CANCELLED";
                    } else {
                        $cancelled = "ACTIVE";
                    }


                    if ($row->relation_to_principal == 24) {
                        $contacts = PrincipalApplicant::select('tel_no', 'mobile_no', 'email')
                            ->where('member_no', $row->member_no)
                            ->get();

                        $dt = [
                            'family_no' => $row->family_no,
                            'member_no' => $row->member_no,
                            'member_names' => $row->member_names,
                            'relation' => $relation[0],
                            'dob' => $row->dob,
                            'tel_no' => $contacts[0]->tel_no,
                            'mobile_no' => $contacts[0]->mobile_no,
                            'email' => $contacts[0]->email,
                            'status' => $cancelled
                        ];
                    } else {

                        $dt = [
                            'family_no' => $row->family_no,
                            'member_no' => $row->member_no,
                            'member_names' => $row->member_names,
                            'relation' => $relation[0],
                            'dob' => $row->dob,
                            'tel_no' => "",
                            'mobile_no' => "",
                            'email' => "",
                            'status' => $cancelled
                        ];
                    }


                    array_push($body, $dt);
                }
                $data = [
                    'headers' => $headers,
                    'body' => $body
                ];
                return $data;
                break;
            case '12':
                $headers = ['Member No', 'Member Name', 'Relation', 'Gender', 'Dob', 'ID NO', 'Employment No', 'Start Date', 'End Date', 'Category', 'Product'];
                $body = MemberInfo::select(
                    DB::raw("CONCAT(member_info.first_name,' ',member_info.other_names,' ',member_info.surname) as member_names"),
                    DB::raw("CONCAT(principal_applicant.first_name,' ',principal_applicant.other_names,' ',principal_applicant.surname) as principal_applicant"),
                    DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
                    DB::raw("FORMAT(member_anniversary.start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(member_anniversary.end_date, 'yyyy-mm-dd') as end_date"),
                    'corporate.corporate',
                    'member_info.member_no',
                    'member_info.id_pp_no',
                    'member_info.employment_no',
                    'principal_applicant.category',
                    'family_relation.relation',
                    't_gender.gender',
                    't_product_name.product_name',

                )
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                    ->leftJoin('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.family_title')
                    ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'principal_applicant.plan')
                    ->where(DB::raw(" isnull(member_info.cancelled,0)"), 0)
                    ->where('member_acceptance.status', 1)
                    ->where('member_anniversary.anniv', $request->anniv)
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->get();

                $dt = [
                    'headers' => $headers,
                    'body' => $body
                ];

                return $dt;
                break;
        }
    }
}
