<?php

namespace App\Http\Controllers;

use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberSummaryController extends Controller
{

     public function fetchMemberSummary($memberNo)
    { 
        ini_set('max_execution_time', '0');
        $memberNo = str_replace(' ', '', $memberNo); // Remove spaces from member
        $kyc = DB::select("CALL member_summary_details(?)", [$memberNo]);
        $benefits = DB::select("CALL member_summary_benefits(?)", [$memberNo]);
       

        return ['kyc' => $kyc, 'benefits' => $benefits ];
    }
    public function fetchMemberSummary2($memberNo)
    {
        ini_set('max_execution_time', '0');
        $kyc = MemberInfo::select(
            'member_info.family_no',
            'member_info.member_no',
            'occupation.occupation',
            'member_info.id_pp_no as id',
            'member_info.blood_group',
            'member_info.email',
            'corporate.corporate',
            'principal_applicant.postal_add',
            'principal_applicant.phy_loc',
            'anniv_data.health_plan',
            't_product_name.product_name',
            'anniv_data.anniv',
            'anniv_data.start_date',
            'anniv_data.end_date',
            'member_info.dob',
            'family_relation.relation as relation_to_principal',
            'fr.relation as family_title',

            DB::raw("CONCATanniv_data.health_plan, ' - ', t_product_name.product_name) AS product"),
            DB::raw("CONCAT(member_info.surname,' ', member_info.first_name, ' ', member_info.other_names) as member_name"),
            DB::raw("CONCAT(principal_applicant.surname,' ', principal_applicant.first_name, ' ', principal_applicant.other_names) as principal_name"),
            DB::raw("CASE 
                WHEN member_info.tel_no IS NOT NULL THEN member_info.tel_no
                ELSE
                    CASE 
                        WHEN principal_applicant.tel_no IS NOT NULL THEN principal_applicant.tel_no
                        ELSE principal_applicant.mobile_no
                    END
             END AS contact"),

            DB::raw("CASE 
                WHEN principal_applicant.family_size = 1 THEN 'M'
                ELSE 'M + ' + CONVERT(VARCHAR, principal_applicant.family_size - 1)
             END AS family_size"),

            DB::raw("CASE 
                WHEN principal_applicant.individual = 1 THEN 'CORPORATE MEMBER'
                WHEN principal_applicant.individual = 2 THEN 'RETAIL MEMBER'
                WHEN principal_applicant.individual = 3 THEN 'SME MEMBER'
             END AS corporate_type"),

            DB::raw("CASE
                WHEN member_acceptance.status = 2 THEN 'SUSPENDED'
                WHEN member_acceptance.status = 3 THEN 'REJECTED'
                WHEN member_acceptance.status = 4 THEN 'PENDING'
                ELSE
                    CASE 
                        WHEN member_info.cancelled = 1 THEN 'CANCELLED'
                        ELSE 
                            CASE 
                                WHEN anniv_data.end_date IS NOT NULL AND anniv_data.end_date < GETDATE() THEN 'EXPIRED'
                                WHEN anniv_data.start_date IS NOT NULL AND anniv_data.start_date > GETDATE() THEN 'FUTURE DATE'
                                ELSE 'ACTIVE'
                            END
                    END
             END AS status")
        )
            ->leftJoin('occupation', 'occupation.code', '=', 'member_info.occupation')
            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
            ->leftJoin('family_relation as fr', 'fr.code', '=', 'member_info.family_title')
            ->leftJoin('t_gender', 't_gender.code', '=', 'member_info.gender')
            ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->join('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
            ->join('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')

            ->join(DB::raw("(
                            SELECT MAX(anniv) AS anniv, member_no, start_date, end_date, health_plan, h_option 
                            FROM member_anniversary 
                            GROUP BY member_no, start_date, end_date, health_plan, h_option
                        ) AS anniv_data"), 'anniv_data.member_no', '=', 'member_info.member_no')

            ->join('t_product_name', 't_product_name.code', '=', 'anniv_data.h_option')
            ->where('member_info.member_no', $memberNo)
            ->toSql();

            return $kyc;



        $benefitsBaseQuery = "SELECT
                mb.member_no,
                b.benefit,
                mb.limit,
                -- mb2.limit as sub_limit_of_limit,
                bs.sharing,
                mb.anniv,
                mb.category,
                pn.product_name,
                family_child_benefit_data.family_child_payable,
                family_parent_benefit_data.family_parent_payable,
                member_child_benefit_data.member_child_payable,
                member_parent_benefit_data.member_parent_payable,
                bs.code as sharing_code,
                CASE 
                    WHEN ISNULL(mb.sub_limit_of, 0) = 0 THEN mb.limit
                    ELSE mb2.limit
                END as sub_limit_of_limit,
                b2.benefit as sub_limit_of,
                mb.sub_limit_of as sub_limit_of_code,
                mb.sub_limit_of as tts2,
                bs.code,
                CASE 
                    WHEN bs.code = 1 and isnull(mb.sub_limit_of, 0) != 0 THEN family_child_benefit_data.family_child_payable
                    WHEN bs.code = 1 and isnull(mb.sub_limit_of, 0) = 0 THEN family_parent_benefit_data.family_parent_payable
                    WHEN bs.code != 1 and isnull(mb.sub_limit_of, 0) != 0 THEN member_child_benefit_data.member_child_payable
                    WHEN bs.code != 1 and isnull(mb.sub_limit_of, 0) = 0 THEN member_parent_benefit_data.member_parent_payable
                    ELSE 0
                END as consumption,
                mb2.limit as mb2_limit
            FROM member_benefits as mb
                JOIN benefit as b ON b.code = mb.benefit
                JOIN t_product_name as pn ON pn.code = mb.product_name
                LEFT JOIN benefit as b2 ON b2.code = mb.sub_limit_of
                JOIN benefit_sharing as bs ON bs.code = mb.sharing
                JOIN (
                    SELECT MAX(anniv) AS anniv, member_no
                    FROM member_anniversary
                    GROUP BY member_no
                ) AS anniv_data ON anniv_data.member_no = mb.member_no
                JOIN member_info as mi ON mi.member_no = mb.member_no
                LEFT JOIN member_benefits as mb2 ON mb2.benefit = mb.sub_limit_of AND mb2.member_no = mb.member_no AND mb2.anniv = mb.anniv
                LEFT JOIN (
                    SELECT member_no, anniv, claim_nature, SUM(ISNULL(invoiced_amount, 0) - ISNULL(deduction_amount, 0)) as member_child_payable
                    FROM bills
                    GROUP BY member_no, anniv, claim_nature
                ) as member_child_benefit_data ON member_child_benefit_data.member_no = mb.member_no AND member_child_benefit_data.anniv = mb.anniv AND member_child_benefit_data.claim_nature = mb.benefit
                LEFT JOIN (
                    SELECT pa.family_no, b.anniv, b.claim_nature, SUM(ISNULL(b.invoiced_amount, 0) - ISNULL(b.deduction_amount, 0)) as family_child_payable
                    FROM bills b
                    JOIN principal_applicant pa ON pa.member_no = b.member_no
                    GROUP BY pa.family_no, b.anniv, b.claim_nature
                ) as family_child_benefit_data ON family_child_benefit_data.family_no = mi.family_no AND family_child_benefit_data.anniv = mb.anniv AND family_child_benefit_data.claim_nature = mb.benefit
                LEFT JOIN (
                        SELECT 
                            mb1.benefit AS parent_benefit,
                            mb1.member_no,
                            mb1.anniv,
                            SUM(ISNULL(b.invoiced_amount, 0) - ISNULL(b.deduction_amount, 0)) AS member_parent_payable
                        FROM member_benefits mb1
                        JOIN bills b 
                            ON b.member_no = mb1.member_no
                            AND b.anniv = mb1.anniv
                            AND (
                                b.claim_nature = mb1.benefit
                                OR b.claim_nature IN (
                                    SELECT benefit 
                                    FROM member_benefits 
                                    WHERE sub_limit_of = mb1.benefit
                                    AND member_no = mb1.member_no
                                    AND anniv = mb1.anniv
                                )
                            )
                        WHERE ISNULL(mb1.sub_limit_of, 0) = 0
                        GROUP BY mb1.benefit, mb1.member_no, mb1.anniv
                    ) AS member_parent_benefit_data 
                    ON member_parent_benefit_data.member_no = mb.member_no 
                    AND member_parent_benefit_data.anniv = mb.anniv 
                    AND member_parent_benefit_data.parent_benefit = mb.benefit
               LEFT JOIN (
                SELECT 
                    mb1.benefit AS parent_benefit,
                    pa.family_no,
                    mb1.anniv,
                    SUM(ISNULL(b.invoiced_amount, 0) - ISNULL(b.deduction_amount, 0)) AS family_parent_payable
                FROM member_benefits mb1
                JOIN principal_applicant pa ON pa.member_no = mb1.member_no
                JOIN bills b 
                    ON b.anniv = mb1.anniv
                    AND (
                        b.claim_nature = mb1.benefit
                        OR b.claim_nature IN (
                            SELECT benefit 
                            FROM member_benefits 
                            WHERE sub_limit_of = mb1.benefit
                            AND anniv = mb1.anniv
                            AND member_no IN (
                                SELECT member_no 
                                FROM member_info 
                                WHERE family_no = pa.family_no
                            )
                        )
                    )
                    AND b.member_no IN (
                        SELECT member_no 
                        FROM member_info 
                        WHERE family_no = pa.family_no
                    )
                WHERE ISNULL(mb1.sub_limit_of, 0) = 0
                GROUP BY mb1.benefit, pa.family_no, mb1.anniv
            ) AS family_parent_benefit_data 
            ON family_parent_benefit_data.parent_benefit = mb.benefit 
            AND family_parent_benefit_data.family_no = mi.family_no 
            AND family_parent_benefit_data.anniv = mb.anniv
               
            WHERE mb.member_no = ?
        ";

        $benefitsOuterQuery = "SELECT *,
                CASE 
                    WHEN isnull(sub_limit_of_code, 0) != 0 THEN isnull(limit, 0) - isnull(consumption, 0)
                    WHEN isnull(sub_limit_of_code, 0) = 0 THEN isnull(sub_limit_of_limit, 0) - isnull(consumption, 0)
                END as balance,
                CASE 
                    WHEN isnull(sub_limit_of_code, 0) != 0 and isnull(consumption, 0) !=0 and  isnull(limit, 0) != 0 THEN (consumption / limit) * 100 
                    WHEN isnull(sub_limit_of_code, 0) != 0 and isnull(consumption, 0) = 0 and  isnull(limit, 0) != 0 THEN 0
                    WHEN isnull(sub_limit_of_code, 0) != 0 and isnull(consumption, 0) != 0 and  isnull(limit, 0) = 0 THEN consumption 
                    WHEN isnull(sub_limit_of_code, 0) = 0 and isnull(consumption, 0) !=0 and  isnull(sub_limit_of_limit, 0) != 0 THEN (consumption / sub_limit_of_limit) * 100 
                    WHEN isnull(sub_limit_of_code, 0) = 0 and isnull(consumption, 0) =0 and  isnull(sub_limit_of_limit, 0) != 0 THEN 0 
                    WHEN isnull(sub_limit_of_code, 0) = 0 and isnull(consumption, 0) !=0 and  isnull(sub_limit_of_limit, 0) = 0 THEN consumption 
                    ELSE 0
                END as percent_consumption,
                CASE 
                    WHEN isnull(sub_limit_of_code, 0) = 0 AND (
                        CASE 
                            WHEN isnull(consumption, 0) !=0 and  isnull(sub_limit_of_limit, 0) != 0 THEN (consumption / sub_limit_of_limit) * 100 
                            WHEN isnull(consumption, 0) =0 and  isnull(sub_limit_of_limit, 0) != 0 THEN 0 
                            WHEN isnull(consumption, 0) !=0 and  isnull(sub_limit_of_limit, 0) = 0 THEN consumption 
                            ELSE 0
                        END > 100) THEN 'MAIN BENEFIT LIMIT EXCEEDED'
                    WHEN isnull(sub_limit_of_code, 0) = 0 AND (
                        CASE 
                            WHEN isnull(consumption, 0) !=0 and  isnull(sub_limit_of_limit, 0) != 0 THEN (consumption / sub_limit_of_limit) * 100 
                            WHEN isnull(consumption, 0) =0 and  isnull(sub_limit_of_limit, 0) != 0 THEN 0 
                            WHEN isnull(consumption, 0) !=0 and  isnull(sub_limit_of_limit, 0) = 0 THEN consumption 
                            ELSE 0
                        END = 100) THEN 'MAIN BENEFIT LIMIT REACHED'
                    WHEN isnull(sub_limit_of_code, 0) != 0 AND (
                        CASE 
                            WHEN isnull(consumption, 0) !=0 and  isnull(limit, 0) != 0 THEN (consumption / limit) * 100 
                            WHEN isnull(consumption, 0) =0 and  isnull(limit, 0) != 0 THEN 0 
                            WHEN isnull(consumption, 0) !=0 and  isnull(limit, 0) = 0 THEN consumption 
                            ELSE 0
                        END > 100) THEN 'BENEFIT LIMIT EXCEEDED'
                    WHEN isnull(sub_limit_of_code, 0) != 0 AND (
                        CASE 
                            WHEN isnull(consumption, 0) !=0 and  isnull(limit, 0) != 0 THEN (consumption / limit) * 100 
                            WHEN isnull(consumption, 0) =0 and  isnull(limit, 0) != 0 THEN 0 
                            WHEN isnull(consumption, 0) !=0 and  isnull(limit, 0) = 0 THEN consumption 
                            ELSE 0
                        END = 100) THEN 'MAIN BENEFIT LIMIT REACHED'
                    ELSE  'WITHIN LIMIT'
                END as consumption_status
            FROM (" . $benefitsBaseQuery . ") as base
            ORDER BY benefit ASC
        ";

        $benefits = DB::select($benefitsOuterQuery, [$memberNo]);
        return ['kyc' => $kyc, 'benefits' => $benefits];
    }
}
