<?php

namespace App\Http\Controllers\Registration\Reports;

use App\Http\Controllers\Controller;
use App\Models\Registration\MemberAnniversary;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValidListController extends Controller
{

    //fetch valid list
    public function fetchValidListReport(Request $request)
    {
        ini_set('max_execution_time', '0');
        $date = date("Y-m-d", strtotime($request->date));
        $data = [];
        try {
            if ($request->option === "1") {
                if ($request->corp_id != "0") {

                    $data = MemberAnniversary::select(
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as client_name"),
                        DB::raw("FORMAT(member_anniversary.start_date, 'yyyy-mm-dd') as start_date"),
                        DB::raw("FORMAT(member_anniversary.end_date, 'yyyy-mm-dd') as end_date"),
                        DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
                        DB::raw("case  
                    when member_info.gender = 1 then 'M'  
                    when member_info.gender = 2 then 'F' 
                                END as gender"),
                        'member_info.id_pp_no as id',
                        DB::raw("case 
                                when member_acceptance.status = 2 then 'Suspended'
                                when member_acceptance.status = 3 then 'Rejected'
                                when member_acceptance.status = 4 then 'Pending'
                                else
                                    case 
                                        when member_anniversary.end_date < '$date' then 'Cover Expired'
                                        else
                                            case 
                                            when member_info.cancelled = 1 then 'Cancelled'
                                            else 'Active' 
                                            END                                       
                                            END
                                            END as Status"),
                        'member_anniversary.member_no',
                        'member_anniversary.premium',
                        'member_anniversary.premium_installment',
                        'corporate.corporate',
                        'member_anniversary.health_plan',
                        't_product_name.product_name',
                        'family_relation.relation',
                        'member_info.email',
                        // DB::raw("CASE WHEN isnull(member_info.tel_no, '') = '' THEN principal_applicant.tel_no else member_info.tel_no END AS tel_no"),
                        // 'member_info.tel_no',
                        DB::raw("
                                    CASE 
                                        WHEN ISNULL(member_info.tel_no, '') != '' THEN member_info.tel_no
                                        WHEN ISNULL(principal_applicant.tel_no, '') != '' THEN '(PRI NO) - ' + principal_applicant.tel_no
                                        ELSE ''
                                    END AS tel_no
                                "),
                        'principal_applicant.mobile_no',
                    )
                        ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
                        ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                        ->leftJoin('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
                        ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                        ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.family_title')
                        ->orderBy('member_anniversary.member_no', 'ASC')
                        ->where(function ($q) use ($date) {
                            $q->where('member_anniversary.start_date', '<=', $date);
                            $q->where('member_anniversary.end_date', '>=', $date);
                        })
                        // ->where('member_anniversary.start_date', '<=', $date)
                        // ->where('member_anniversary.end_date', '>=', $date)
                        ->where('principal_applicant.corp_id', $request->corp_id)
                        ->get();

                    $data = json_encode($data, JSON_INVALID_UTF8_IGNORE);
                }
            } else {
                $data = MemberAnniversary::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as client_name"),
                    DB::raw("FORMAT(member_anniversary.start_date, 'yyyy-mm-dd') as start_date"),
                    DB::raw("FORMAT(member_anniversary.end_date, 'yyyy-mm-dd') as end_date"),
                    DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
                    DB::raw("case  
                    when member_info.gender = 1 then 'M'  
                    when member_info.gender = 2 then 'F' 
                                END as gender"),
                    DB::raw("case 
                                when member_acceptance.status = 2 then 'Suspended'
                                when member_acceptance.status = 3 then 'Rejected'
                                 when member_acceptance.status = 4 then 'Pending'
                                else
                                    case 
                                        when member_anniversary.end_date < '$date' then 'Cover Expired'
                                        else
                                            case 
                                            when member_info.cancelled = 1 then 'Cancelled'
                                            else 'Active' 
                                            END                                       
                                            END
                                            END as Status"),
                    'member_anniversary.member_no',
                    'member_anniversary.premium',
                    'member_anniversary.premium_installment',
                    'corporate.corporate',
                    'member_anniversary.health_plan',
                    't_product_name.product_name',
                    'member_info.email',
                    'family_relation.relation',
                    // 'member_info.tel_no',
                    // 'principal_applicant.mobile_no',
                    DB::raw("
                            CASE 
                                WHEN ISNULL(member_info.tel_no, '') != '' THEN member_info.tel_no
                                WHEN ISNULL(principal_applicant.tel_no, '') != '' THEN '(PRI NO) - ' + principal_applicant.tel_no
                                ELSE ''
                            END AS tel_no
                        "),
                    'principal_applicant.mobile_no',
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'member_anniversary.member_no')
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
                    ->leftJoin('t_product_name', 't_product_name.code', '=', 'member_anniversary.h_option')
                    ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.family_title')
                    ->orderBy('member_anniversary.member_no', 'ASC')
                    ->where(function ($q) use ($date) {
                        $q->where('member_anniversary.start_date', '<=', $date);
                        $q->where('member_anniversary.end_date', '>=', $date);
                    })
                    // ->where('member_anniversary.start_date', '<=', $date)
                    // ->where('member_anniversary.end_date', '>=', $date)
                    ->get();

                $data = json_encode($data, JSON_INVALID_UTF8_IGNORE);
            }
            return $data;
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
