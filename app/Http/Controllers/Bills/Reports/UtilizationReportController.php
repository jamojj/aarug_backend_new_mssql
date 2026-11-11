<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\Corporate\Corporate;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isNull;

class UtilizationReportController extends Controller
{
    //fetch utilization report


    public function fetchUtilizationReport(Request $request)
    {
        ini_set('max_execution_time', '0');
        $data =  [];
        switch ($request->option) {
            //corporate
            case '1':
                $data = DB::select("CALL corporate_utilization(?,?)", [$request->corporate, $request->anniv]);
                // $data = $this->commonData("WHERE member_benefits.anniv = $request->anniv AND principal_applicant.corp_id = $request->corporate");
                break;
            //family
            case '2':
                $data = DB::select("CALL family_utilization(?,?)", [$request->family_no, $request->family_anniv]);
                // $data = $this->commonData("WHERE member_benefits.anniv = $request->family_anniv AND principal_applicant.family_no = '$request->family_no'");
                break;
            //member
            case '3':
                $data = DB::select("CALL member_utilization(?,?)", [$request->member_no, $request->member_anniv]);
                // $data = $this->commonData("WHERE member_benefits.anniv = $request->member_anniv AND member_info.member_no = '$request->member_no'");
                break;
            //all corporates
            case '4':
                $corp_data = Corporate::select(
                    DB::raw("max(corp_anniversary.anniv) as max_anniv"),
                    'corporate.corp_id',
                )
                    ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                    ->groupBy('corporate.corp_id')
                    ->get();

                foreach ($corp_data as $cd) {
                    $dt = DB::select("EXEC corporate_utilization @in_anniv = $cd->max_anniv, @_corp_id = $cd->corp_id");
                    // $dt = $this->commonData("WHERE member_benefits.anniv = $cd->max_anniv AND principal_applicant.corp_id = $cd->corp_id");
                    array_push($data, $dt);
                }

                break;
        }
        return $data;
    }

    private function commonData($common_string)
    {
        return DB::select("
WITH
main_benefit_limits AS (
    SELECT
        mb1.member_no,
        mb1.anniv,
        mb1.benefit,
        mb1.sub_limit_of,
        mb1.sharing,
        CASE
            WHEN mb1.sharing = 1 AND ISNULL(mb1.sub_limit_of, 0) != 0 THEN
                (SELECT TOP 1 ISNULL(mb2.limit, 0)
                 FROM member_benefits mb2
                 WHERE mb2.benefit = mb1.sub_limit_of
                   AND mb2.member_no = mb1.member_no
                   AND mb2.anniv = mb1.anniv
                   ORDER BY mb2.limit DESC)
            WHEN mb1.sharing != 1 AND ISNULL(mb1.sub_limit_of, 0) != 0 THEN
                (SELECT TOP 1 ISNULL(mb2.limit, 0)
                 FROM member_benefits mb2
                 JOIN member_info mi2 ON mi2.member_no = mb2.member_no
                 JOIN principal_applicant pa2 ON pa2.family_no = mi2.family_no
                 WHERE mb2.benefit = mb1.sub_limit_of
                   AND mi2.family_no = (SELECT mi3.family_no FROM member_info mi3 WHERE mi3.member_no = mb1.member_no)
                   AND mb2.anniv = mb1.anniv
                   ORDER BY mb2.limit DESC)
            ELSE NULL
        END AS main_benefit_limit
    FROM member_benefits mb1
),
main_benefit_claims AS (
    SELECT
        mb.member_no,
        mb.anniv,
        mb.benefit,
        mb.sub_limit_of,
        mb.sharing,
        CASE
            WHEN mb.sharing = 1 THEN
                (SELECT ISNULL(SUM(ISNULL(b.invoiced_amount, 0)) - SUM(ISNULL(b.deduction_amount, 0)), 0)
                 FROM bills b
                 JOIN member_info mi ON mi.member_no = b.member_no
                 JOIN principal_applicant pa ON pa.family_no = mi.family_no
                 WHERE pa.family_no = (SELECT mi2.family_no FROM member_info mi2 WHERE mi2.member_no = mb.member_no)
                   AND b.anniv = mb.anniv
                   AND b.claim_nature = mb.sub_limit_of)
            ELSE
                (SELECT ISNULL(SUM(ISNULL(b.invoiced_amount, 0)) - SUM(ISNULL(b.deduction_amount, 0)), 0)
                 FROM bills b
                 WHERE b.member_no = mb.member_no
                   AND b.anniv = mb.anniv
                   AND b.claim_nature = mb.sub_limit_of)
        END AS main_benefit_claims
    FROM member_benefits mb
),
ttl_payable_tbl AS (
    SELECT
        mb.member_no,
        mb.anniv,
        mb.benefit,
        mb.sharing,
        CASE
            WHEN mb.sharing = 1 THEN
                (SELECT ISNULL(SUM(ISNULL(b.invoiced_amount, 0)) - SUM(ISNULL(b.deduction_amount, 0)), 0)
                 FROM bills b
                 JOIN member_info mi ON mi.member_no = b.member_no
                 JOIN principal_applicant pa ON pa.family_no = mi.family_no
                 WHERE pa.family_no = (SELECT mi2.family_no FROM member_info mi2 WHERE mi2.member_no = mb.member_no)
                   AND b.anniv = mb.anniv
                   AND b.claim_nature = mb.benefit)
            ELSE
                (SELECT ISNULL(SUM(ISNULL(b.invoiced_amount, 0)) - SUM(ISNULL(b.deduction_amount, 0)), 0)
                 FROM bills b
                 WHERE b.member_no = mb.member_no
                   AND b.anniv = mb.anniv
                   AND b.claim_nature = mb.benefit)
        END AS ttl_payable
    FROM member_benefits mb
)

SELECT
    CONCAT(member_info.surname, ' ', member_info.first_name, ' ', member_info.other_names) AS member_name,
    CONCAT(principal_applicant.surname, ' ', principal_applicant.first_name, ' ', principal_applicant.other_names) AS principal_name,  
    member_benefits.benefit AS benefit_code,
    member_benefits.sharing AS sharing_code,
    benefit_sharing.sharing,
    family_relation.relation,
    member_benefits.anniv,
    benefit.benefit AS benefit,
    benefit2.code as sub_limit_of_code,
    member_info.member_no,
    member_info.family_no,
    member_benefits.sub_limit_of AS sub_limit_of_value,
    member_benefits.limit AS limit,
    SUM(ISNULL(bills.deduction_amount, 0)) as ded_ttl,

    ISNULL(mbl.main_benefit_limit, 0) AS main_benefit_limit,
    ISNULL(mbc.main_benefit_claims, 0) AS main_benefit_claims,
    ISNULL(tp.ttl_payable, 0) AS ttl_payable,

    -- status logic, unchanged, but now uses pre-aggregated values
    CASE
        WHEN ISNULL(member_benefits.limit, 0) != 0 AND ISNULL(member_benefits.sub_limit_of, 0) = 0 THEN
            CASE
                WHEN ISNULL(tp.ttl_payable, 0) < member_benefits.limit THEN 'WITHIN LIMIT'
                WHEN ISNULL(tp.ttl_payable, 0) = member_benefits.limit THEN 'LIMIT REACHED'
                WHEN ISNULL(tp.ttl_payable, 0) > member_benefits.limit THEN 'LIMIT EXCEEDED'
            END
        ELSE
            CASE
                WHEN ISNULL(mbl.main_benefit_limit, 0) > ISNULL(mbc.main_benefit_claims, 0) THEN
                    CASE
                        WHEN (ISNULL(mbl.main_benefit_limit, 0) - ISNULL(mbc.main_benefit_claims, 0)) >= member_benefits.limit THEN
                            CASE
                                WHEN ISNULL(tp.ttl_payable, 0) < member_benefits.limit THEN 'WITHIN LIMIT'
                                WHEN ISNULL(tp.ttl_payable, 0) = member_benefits.limit THEN 'LIMIT REACHED'
                                WHEN ISNULL(tp.ttl_payable, 0) > member_benefits.limit THEN 'LIMIT EXCEEDED'
                            END
                        ELSE
                            CASE
                                WHEN ISNULL(tp.ttl_payable, 0) = 0 THEN 'WITHIN LIMIT'
                                ELSE
                                    CASE
                                        WHEN ((ISNULL(mbl.main_benefit_limit, 0) - ISNULL(mbc.main_benefit_claims, 0)) - ISNULL(tp.ttl_payable, 0)) > 0 THEN 'WITHIN LIMIT'
                                        WHEN ((ISNULL(mbl.main_benefit_limit, 0) - ISNULL(mbc.main_benefit_claims, 0)) - ISNULL(tp.ttl_payable, 0)) = 0 THEN 'LIMIT REACHED'
                                        WHEN ((ISNULL(mbl.main_benefit_limit, 0) - ISNULL(mbc.main_benefit_claims, 0)) - ISNULL(tp.ttl_payable, 0)) < 0 THEN 'LIMIT EXCEEDED'
                                    END
                            END
                    END
                ELSE
                    CASE
                        WHEN ISNULL(mbc.main_benefit_claims, 0) = ISNULL(mbl.main_benefit_limit, 0) THEN 'MAIN BENEFIT LIMIT REACHED'
                        WHEN ISNULL(mbc.main_benefit_claims, 0) > ISNULL(mbl.main_benefit_limit, 0) THEN 'MAIN BENEFIT LIMIT EXCEEDED'
                    END
            END
    END AS status,

    ISNULL(member_benefits.limit, 0) - ISNULL(tp.ttl_payable, 0) as balance,

    (CASE WHEN member_benefits.limit = 0 THEN 0 ELSE (ISNULL(tp.ttl_payable, 0) / member_benefits.limit) * 100 END) as percent

FROM member_info
JOIN member_benefits ON member_benefits.member_no = member_info.member_no
JOIN family_relation ON family_relation.code = member_info.relation_to_principal
JOIN benefit ON benefit.code = member_benefits.benefit
LEFT JOIN (SELECT code, benefit FROM benefit) benefit2 on benefit2.code = member_benefits.sub_limit_of
JOIN benefit_sharing ON benefit_sharing.code = member_benefits.sharing
JOIN principal_applicant ON principal_applicant.family_no = member_info.family_no
JOIN bills ON bills.member_no = member_info.member_no and bills.anniv = member_benefits.anniv and bills.claim_nature = member_benefits.benefit and isnull(bills.vet_status, 0) in (0, 1, 3)
LEFT JOIN main_benefit_limits mbl ON mbl.member_no = member_info.member_no AND mbl.anniv = member_benefits.anniv AND mbl.benefit = member_benefits.benefit
LEFT JOIN main_benefit_claims mbc ON mbc.member_no = member_info.member_no AND mbc.anniv = member_benefits.anniv AND mbc.benefit = member_benefits.benefit
LEFT JOIN ttl_payable_tbl tp ON tp.member_no = member_info.member_no AND tp.anniv = member_benefits.anniv AND tp.benefit = member_benefits.benefit

$common_string
GROUP BY benefit.benefit, member_info.member_no, member_info.family_no, member_benefits.limit, member_benefits.sharing, member_benefits.anniv, member_benefits.sub_limit_of, member_benefits.benefit, member_name, principal_name, benefit_sharing.sharing, family_relation.relation, member_info.family_no, benefit2.code, mbl.main_benefit_limit, mbc.main_benefit_claims, tp.ttl_payable
ORDER BY member_info.member_no ASC
");

        // return  DB::select("SELECT
        //                                 CONCAT(member_info.surname, ' ', member_info.first_name, ' ', member_info.other_names) AS member_name,
        //                                 CONCAT(principal_applicant.surname, ' ', principal_applicant.first_name, ' ', principal_applicant.other_names) AS principal_name,  
        //                                 member_benefits.benefit AS benefit_code,
        //                                 member_benefits.sharing AS sharing_code,
        //                                 benefit_sharing.sharing,
        //                                 family_relation.relation,
        //                                 member_benefits.anniv,
        //                                 benefit.benefit,
        //                                 benefit2.code as sub_limit_of,

        //                                 member_info.member_no,
        //                                 member_info.family_no,
        //                                 benefit.benefit,
        //                                 member_benefits.sub_limit_of,
        //                                 member_benefits.limit,
        //                                 sum(isnull(bills.deduction_amount, 0) ) as ded_ttl,   
        //                                 CASE
        //                                     WHEN 
        //                                     member_benefits.sharing = 1 
        //                                     THEN 
        //                                         CASE
        //                                             WHEN 
        //                                                 isnull(member_benefits.sub_limit_of, 0) != 0
        //                                             THEN
        //                                                 (select isnull(member_benefits.limit, 0) from member_benefits mb where mb.benefit = member_benefits.sub_limit_of and mb.member_no =  member_info.member_no and mb.anniv = member_benefits.anniv)
        //                                         END     	
        //                                     ELSE
        //                                         CASE
        //                                             WHEN 
        //                                                 isnull(member_benefits.sub_limit_of, 0) != 0
        //                                             THEN
        //                                                 (
        //                                                 SELECT 
        //                                                 isnull(member_benefits.limit, 0) 
        //                                                 FROM
        //                                                 member_benefits mb 
        //                                                 JOIN member_info mi ON mi.member_no = mb.member_no
        //                                                 JOIN principal_applicant pa ON pa.family_no = mi.family_no
        //                                                 WHERE 
        //                                                 mb.benefit = member_benefits.sub_limit_of 
        //                                                 AND 
        //                                                 mi.family_no =  member_info.family_no 
        //                                                 AND 
        //                                                 mb.anniv = member_benefits.anniv
        //                                                 )
        //                                         END 
        //                                 END AS main_benefit_limit,
        //                             CASE 
        //                                 WHEN 
        //                                 member_benefits.sharing = 1 
        //                                 THEN 
        //                                     (
        //                                         SELECT SUM(ISNULL(b.invoiced_amount, 0)) -  sum(isnull(b.deduction_amount, 0) )
        //                                         FROM bills b
        //                                         JOIN member_info mi ON mi.member_no = b.member_no
        //                                         JOIN principal_applicant pa ON pa.family_no = mi.family_no
        //                                         WHERE pa.family_no = member_info.family_no
        //                                         AND b.anniv = member_benefits.anniv
        //                                         AND b.claim_nature = member_benefits.sub_limit_of
        //                                     )
        //                                 ELSE 
        //                                     (
        //                                         SELECT SUM(ISNULL(b.invoiced_amount, 0)) -  sum(isnull(b.deduction_amount, 0) ) 
        //                                         FROM bills b
        //                                         WHERE b.member_no = member_info.member_no
        //                                         AND b.anniv = member_benefits.anniv
        //                                         AND b.claim_nature = member_benefits.sub_limit_of
        //                                     )
        //                             END AS main_benefit_claims,


        //                                 CASE 
        //                                 WHEN 
        //                                 member_benefits.sharing = 1 
        //                                 THEN 
        //                                     (
        //                                         SELECT SUM(ISNULL(b.invoiced_amount, 0)) -  sum(isnull(b.deduction_amount, 0) )
        //                                         FROM bills b
        //                                         JOIN member_info mi ON mi.member_no = b.member_no
        //                                         JOIN principal_applicant pa ON pa.family_no = mi.family_no
        //                                         WHERE pa.family_no = member_info.family_no
        //                                         AND b.anniv = member_benefits.anniv
        //                                         AND b.claim_nature = member_benefits.benefit
        //                                     )
        //                                 ELSE 
        //                                     (
        //                                         SELECT SUM(ISNULL(b.invoiced_amount, 0)) -  sum(isnull(b.deduction_amount, 0) ) 
        //                                         FROM bills b
        //                                         WHERE b.member_no = member_info.member_no
        //                                         AND b.anniv = member_benefits.anniv
        //                                         AND b.claim_nature = member_benefits.benefit
        //                                     )
        //                             END AS ttl_payable,

        //                             CASE
        //                             WHEN isnull(member_benefits.limit, 0) != 0  and isnull(member_benefits.sub_limit_of,0) = 0 
        //                             THEN
        //                                 CASE 
        //                                     WHEN  ttl_payable < member_benefits.limit 
        //                                     THEN
        //                                         'WITHIN LIMIT'
        //                                     WHEN  ttl_payable = member_benefits.limit
        //                                     THEN
        //                                         'LIMIT REACHED'
        //                                     WHEN ttl_payable > member_benefits.limit	
        //                                     THEN
        //                                         'LIMIT EXCEEDED'
        //                                 END
        //                             ELSE
        //                                 CASE WHEN main_benefit_limit > main_benefit_claims 
        //                                     THEN
        //                                         CASE WHEN (main_benefit_limit - main_benefit_claims) >= member_benefits.limit
        //                                             THEN
        //                                                 CASE 
        //                                                     WHEN  ttl_payable < member_benefits.limit 
        //                                                     THEN
        //                                                         'WITHIN LIMIT'
        //                                                     WHEN  ttl_payable = member_benefits.limit
        //                                                     THEN
        //                                                         'LIMIT REACHED'
        //                                                     WHEN ttl_payable > member_benefits.limit	
        //                                                     THEN
        //                                                         'LIMIT EXCEEDED'
        //                                                 END
        //                                             ELSE
        //                                                 CASE
        //                                                     WHEN ttl_payable = 0
        //                                                     THEN
        //                                                         'WITHIN LIMIT'
        //                                                     ELSE
        //                                                         CASE
        //                                                             WHEN ((main_benefit_limit - main_benefit_claims) - ttl_payable)  > 0
        //                                                             THEN 
        //                                                                 'WITHIN LIMIT'
        //                                                             WHEN ((main_benefit_limit - main_benefit_claims) - ttl_payable)  = 0
        //                                                             THEN
        //                                                                 'LIMIT REACHED'
        //                                                             WHEN ((main_benefit_limit - main_benefit_claims) - ttl_payable)  < 0
        //                                                             THEN
        //                                                                 'LIMIT EXCEEDED'
        //                                                         END
        //                                                 END
        //                                             END			


        //                                     ELSE
        //                                         CASE 
        //                                             WHEN main_benefit_claims = main_benefit_limit
        //                                             THEN
        //                                                 'MAIN BENEFIT LIMIT REACHED'
        //                                             WHEN main_benefit_claims > main_benefit_limit
        //                                             THEN
        //                                                 'MAIN BENEFIT LIMIT EXCEEDED'
        //                                         END

        //                                     END

        //                                 END AS status,

        //                             isnull(member_benefits.limit, 0) - isnull(ttl_payable, 0)  as balance,

        //                             ((ttl_payable / limit) * 100) as percent



        //                             FROM member_info
        //                             JOIN member_benefits ON member_benefits.member_no = member_info.member_no
        //                             JOIN family_relation ON family_relation.code = member_info.relation_to_principal
        //                             JOIN benefit ON benefit.code = member_benefits.benefit
        //                             LEFT JOIN (SELECT code, benefit FROM benefit) benefit2 on benefit2.code = member_benefits.sub_limit_of
        //                             JOIN benefit_sharing ON benefit_sharing.code = member_benefits.sharing
        //                             JOIN principal_applicant ON principal_applicant.family_no = member_info.family_no
        //                             JOIN bills ON bills.member_no = member_info.member_no and bills.anniv = member_benefits.anniv and bills.claim_nature = member_benefits.benefit and isnull(bills.vet_status, 0) in (0, 1, 3)

        //                             $common_string
        //                             group by (benefit.benefit, member_info.member_no, member_info.family_no,member_benefits.limit, member_benefits.sharing, member_benefits.anniv, member_benefits.sub_limit_of, member_benefits.benefit, member_name, principal_name,  member_benefits.benefit, benefit_sharing.sharing, family_relation.relation,  member_info.family_no, benefit2.code)
        //                             order by  member_info.member_no asc
        //                 ");
    }







    public function fetchUtilizationReport2(Request $request)
    {
        // return $request;
        // ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', '0');
        $data = [];
        $response = [];
        $lc_ben_bal = $lc_main_ben_bal = 0;
        $ls_status = $lc_tot_payb = $lc_ben_bal = $ls_percent = '';

        switch ($request->option) {
            //corporate
            case '1':
                $data = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    'member_benefits.limit',
                    'member_benefits.benefit as benefit_code',
                    'member_benefits.sharing as sharing_code',
                    'benefit_sharing.sharing',
                    'member_info.family_no',
                    'family_relation.relation',
                    'member_info.member_no',
                    'member_benefits.anniv',
                    'benefit.benefit',
                    'benefit2.code as sub_limit_of'
                )
                    ->join('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                    ->join('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
                    ->join('benefit', 'benefit.code', '=', 'member_benefits.benefit')
                    ->join('benefit_sharing', 'benefit_sharing.code', '=', 'member_benefits.sharing')
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('benefit as benefit2', 'benefit2.code', '=', 'member_benefits.sub_limit_of')
                    ->join('bills', function ($join) {
                        $join->on('bills.member_no', '=', 'member_info.member_no')
                            ->on('bills.anniv', '=', 'member_benefits.anniv')
                            ->on('bills.claim_nature', '=', 'member_benefits.benefit');
                    })
                    ->where('member_benefits.anniv', $request->anniv)
                    ->where('principal_applicant.corp_id', $request->corporate)
                    ->get();
                break;
            //family
            case '2':
                $data = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    'member_benefits.limit',
                    'member_benefits.benefit as benefit_code',
                    'member_benefits.sharing as sharing_code',
                    'benefit_sharing.sharing',
                    'member_info.family_no',
                    'member_info.relation_to_principal',
                    'member_info.member_no',
                    'member_benefits.anniv',
                    'benefit.benefit',
                    'benefit2.code as sub_limit_of'

                )
                    ->join('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                    ->join('benefit', 'benefit.code', '=', 'member_benefits.benefit')
                    ->join('benefit_sharing', 'benefit_sharing.code', '=', 'member_benefits.sharing')
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('benefit as benefit2', 'benefit2.code', '=', 'member_benefits.sub_limit_of')
                    ->join('bills', function ($join) {
                        $join->on('bills.member_no', '=', 'member_info.member_no')
                            ->on('bills.anniv', '=', 'member_benefits.anniv')
                            ->on('bills.claim_nature', '=', 'member_benefits.benefit');
                    })
                    ->where('member_benefits.anniv', $request->family_anniv)
                    ->where('principal_applicant.family_no', $request->family_no)
                    ->get();
                break;
            //member
            case '3':
                $data = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    'member_benefits.limit',
                    'member_benefits.benefit as benefit_code',
                    'member_benefits.sharing as sharing_code',
                    'benefit_sharing.sharing',
                    'member_info.family_no',
                    'member_info.relation_to_principal',
                    'member_info.member_no',
                    'member_benefits.anniv',
                    'benefit.benefit',
                    'benefit2.code as sub_limit_of'

                )
                    ->join('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                    ->join('benefit', 'benefit.code', '=', 'member_benefits.benefit')
                    ->join('benefit_sharing', 'benefit_sharing.code', '=', 'member_benefits.sharing')
                    ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('benefit as benefit2', 'benefit2.code', '=', 'member_benefits.sub_limit_of')
                    ->join('bills', function ($join) {
                        $join->on('bills.member_no', '=', 'member_info.member_no')
                            ->on('bills.anniv', '=', 'member_benefits.anniv')
                            ->on('bills.claim_nature', '=', 'member_benefits.benefit');
                    })
                    ->where('member_benefits.anniv', $request->member_anniv)
                    ->where('member_info.member_no', $request->member_no)
                    ->get();

                break;
            //all corporates
            case '4':
                $data = [];

                $corp_data = Corporate::select(
                    DB::raw("max(corp_anniversary.anniv) as max_anniv"),
                    'corporate.corp_id',
                )
                    ->join('corp_anniversary', 'corp_anniversary.corp_id', '=', 'corporate.corp_id')
                    ->groupBy('corporate.corp_id')
                    ->get();

                foreach ($corp_data as $cd) {
                    $dt = MemberInfo::select(
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                        'member_benefits.limit',
                        'member_benefits.benefit as benefit_code',
                        'member_benefits.sharing as sharing_code',
                        'benefit_sharing.sharing',
                        'member_info.family_no',
                        'member_info.relation_to_principal',
                        'member_info.member_no',
                        'member_benefits.anniv',
                        'benefit.benefit',
                        'benefit2.code as sub_limit_of'

                    )
                        ->join('member_benefits', 'member_benefits.member_no', '=', 'member_info.member_no')
                        ->join('benefit', 'benefit.code', '=', 'member_benefits.benefit')
                        ->join('benefit_sharing', 'benefit_sharing.code', '=', 'member_benefits.sharing')
                        ->join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                        ->leftJoin('benefit as benefit2', 'benefit2.code', '=', 'member_benefits.sub_limit_of')
                        ->join('bills', function ($join) {
                            $join->on('bills.member_no', '=', 'member_info.member_no')
                                ->on('bills.anniv', '=', 'member_benefits.anniv')
                                ->on('bills.claim_nature', '=', 'member_benefits.benefit');
                        })
                        ->where('member_benefits.anniv', $cd->max_anniv)
                        ->where('principal_applicant.corp_id', $cd->corp_id)
                        ->get();

                    array_push($data, $dt);
                }

                break;
        }

        foreach ($data as $dt) {
            $ls_memno = $dt->member_no;
            $lc_limit = $dt->limit;
            $ll_bene = $dt->benefit_code;
            $ll_share = $dt->sharing_code;
            $ls_famno = $dt->family_no;
            $anniv = $dt->anniv;
            $ll_sub_limit = $dt->sub_limit_of;


            // $ll_claim_nature_cnt = Bills::where('member_no', $ls_memno)
            //     ->where('anniv', $anniv)
            //     ->where('claim_nature', $ll_bene)
            //     ->count('claim_nature');



            // if ($ll_claim_nature_cnt > 0) {

            // $ll_sub_limit = MemberBenefits::select('sub_limit_of')
            //     ->where('member_no', $ls_memno)
            //     ->where('anniv', $anniv)
            //     ->where('benefit', $ll_bene)
            //     ->get();


            //if benefit sharing is per family
            if ($ll_share == 1) {
                if (is_null($ll_sub_limit) === 0) {
                    $lc_tot_payable = Bills::select(DB::raw("isnull(sum(isnull(invoiced_amount, 0)),0) - isnull(sum(isnull(deduction_amount, 0)), 0) as tot_payable"))
                        ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->where('member_info.family_no', $ls_famno)
                        ->where('bills.anniv', $anniv)
                        ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3])
                        ->where(function ($claim_nature) use ($ll_bene) {
                            $claim_nature->where('bills.claim_nature', $ll_bene)
                                ->orWhere('bills.sub_bene', $ll_bene);
                        })
                        ->get();
                    $lc_tot_payable = count($lc_tot_payable) === 0 ? 0 : $lc_tot_payable[0]['tot_payable'];
                    $lc_tot_payb = $lc_tot_payable;
                } else {
                    $lc_tot_payable = Bills::select(DB::raw("isnull(sum(isnull(invoiced_amount, 0)),0) - isnull(sum(isnull(deduction_amount, 0)), 0) as tot_payable"))
                        ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->where('member_info.family_no', $ls_famno)
                        ->where('bills.anniv', $anniv)
                        ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3])
                        ->where(function ($claim_nature) use ($ll_bene) {
                            $claim_nature->where('bills.claim_nature', $ll_bene)
                                ->orWhere('bills.sub_bene', $ll_bene);
                        })
                        ->get();
                    $lc_tot_payable = count($lc_tot_payable) === 0 ? 0 : $lc_tot_payable[0]['tot_payable'];
                    $lc_tot_payb = $lc_tot_payable;
                    //main benefit limit
                    $lc_main_limit = MemberBenefits::select('limit')
                        ->where('benefit', $ll_sub_limit)
                        ->where('member_no', $ls_memno)
                        ->where('anniv', $anniv)
                        ->get();
                    $lc_main_limit = count($lc_main_limit) === 0 ? 0 : $lc_main_limit[0]['limit'];

                    //main benefit claims
                    $lc_main_claims = Bills::select(DB::raw("isnull(sum(isnull(invoiced_amount, 0)),0) - isnull(sum(isnull(deduction_amount, 0)), 0) as main_claims"))
                        ->join('member_info', 'member_info.member_no', '=', 'bills.member_no')
                        ->where('member_info.family_no', $ls_famno)
                        ->where('bills.anniv', $anniv)
                        ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3])
                        ->where(function ($claim_nature) use ($ll_sub_limit) {
                            $claim_nature->where('bills.claim_nature', $ll_sub_limit)
                                ->orWhere('bills.sub_bene', $ll_sub_limit);
                        })
                        ->get();
                    $lc_main_claims = count($lc_main_claims) === 0 ? 0 : $lc_main_claims[0]['main_claims'];
                }
            } else {
                //if benefit sharing is not per family  -- main benefit                   
                if (is_null($ll_sub_limit) === 0) {
                    $lc_tot_payable = Bills::select(DB::raw("isnull(sum(isnull(invoiced_amount, 0)),0) - isnull(sum(isnull(deduction_amount, 0)), 0) as tot_payable"))
                        ->where('member_no', $ls_memno)
                        ->where('anniv', $anniv)
                        ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3])
                        ->where(function ($claim_nature) use ($ll_bene) {
                            $claim_nature->where('bills.claim_nature', $ll_bene)
                                ->orWhere('bills.sub_bene', $ll_bene);
                        })
                        ->get();
                    $lc_tot_payable = count($lc_tot_payable) === 0 ? 0 : $lc_tot_payable[0]['tot_payable'];
                    $lc_tot_payb = $lc_tot_payable;
                } else {
                    $lc_tot_payable = Bills::select(DB::raw("isnull(sum(isnull(invoiced_amount, 0)),0) - isnull(sum(isnull(deduction_amount, 0)), 0) as tot_payable"))
                        ->where('member_no', $ls_memno)
                        ->where('anniv', $anniv)
                        ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3])
                        ->where(function ($claim_nature) use ($ll_bene) {
                            $claim_nature->where('bills.claim_nature', $ll_bene)
                                ->orWhere('bills.sub_bene', $ll_bene);
                        })
                        ->get();


                    $lc_tot_payable = count($lc_tot_payable) === 0 ? 0 : $lc_tot_payable[0]['tot_payable'];
                    $lc_tot_payb = $lc_tot_payable;
                    //main benefit limit
                    $lc_main_limit = MemberBenefits::select('limit')
                        ->where('benefit', $ll_sub_limit)
                        ->where('member_no', $ls_memno)
                        ->where('anniv', $anniv)
                        ->get();
                    $lc_main_limit = count($lc_main_limit) === 0 ? 0 : $lc_main_limit[0]['limit'];


                    //main benefit claims
                    $lc_main_claims = Bills::select(DB::raw("isnull(sum(isnull(invoiced_amount, 0)),0) - isnull(sum(isnull(deduction_amount, 0)), 0) as main_claims"))
                        ->where('member_no', $ls_memno)
                        ->where('anniv', $anniv)
                        ->whereIn(DB::raw("isnull(bills.vet_status, 0)"), [0, 1, 3])
                        ->where(function ($claim_nature) use ($ll_sub_limit) {
                            $claim_nature->where('bills.claim_nature', $ll_sub_limit)
                                ->orWhere('bills.sub_bene', $ll_sub_limit);
                        })
                        ->get();
                    $lc_main_claims = count($lc_main_claims) === 0 ? 0 : $lc_main_claims[0]['main_claims'];
                }
            }
            //if member has  limit
            if (!is_null($lc_limit) && $lc_limit != 0) {
                if ($ll_share == 1) {
                    //main benefit
                    if (is_null($ll_sub_limit) === 0) {
                        $lc_ben_bal = $lc_limit - $lc_tot_payable;
                        $ls_percent = $lc_tot_payable == 0 ? 0 : (($lc_tot_payable / $lc_limit) * 100);
                        $ls_percent = number_format($ls_percent, 2) . '%';

                        if ($lc_tot_payable < $lc_limit) {
                            $ls_status = 'WITHIN LIMIT';
                        } elseif ($lc_tot_payable == $lc_limit) {
                            $ls_status = 'LIMIT REACHED';
                        } elseif ($lc_tot_payable > $lc_limit) {
                            $ls_status = 'LIMIT EXCEEDED';
                        }
                    } else {
                        if ($lc_main_claims < $lc_main_limit) {
                            $lc_main_ben_bal = $lc_main_ben_bal - $lc_main_claims;

                            if ($lc_main_ben_bal >= $lc_limit) {
                                $lc_ben_bal = $lc_limit - $lc_tot_payable;
                                $ls_percent = $lc_tot_payable == 0 ? 0 : (($lc_tot_payable / $lc_limit) * 100);
                                $ls_percent = number_format($ls_percent, 2) . '%';

                                if ($lc_tot_payable < $lc_limit) {
                                    $ls_status = 'WITHIN LIMIT';
                                } elseif ($lc_tot_payable == $lc_limit) {
                                    $ls_status = 'LIMIT REACHED';
                                } elseif ($lc_tot_payable > $lc_limit) {
                                    $ls_status = 'LIMIT EXCEEDED';
                                }
                            } else {
                                if ($lc_tot_payable == 0) {
                                    $lc_ben_bal = $lc_main_ben_bal;
                                    $ls_percent = $lc_tot_payable == 0 ? 0 : (($lc_tot_payable / $lc_limit) * 100);
                                    $ls_percent = number_format($ls_percent, 2) . '%';
                                    $ls_status = 'WITHIN LIMIT';
                                } else {
                                    $lc_ben_bal = $lc_main_ben_bal - $lc_tot_payable;
                                    $ls_percent = $lc_tot_payable == 0 ? 0 : (($lc_tot_payable / $lc_limit) * 100);
                                    $ls_percent = number_format($ls_percent, 2) . '%';

                                    if ($lc_ben_bal > 0) {
                                        $ls_status = 'WITHIN LIMIT';
                                    } elseif ($lc_ben_bal = 0) {
                                        $ls_status = 'LIMIT REACHED';
                                    } elseif ($lc_ben_bal < 0) {
                                        $ls_status = 'LIMIT EXCEEDED';
                                    }
                                }
                            }
                        } elseif ($lc_main_claims == $lc_main_limit) {
                            $ls_status = 'MAIN BENEFIT LIMIT REACHED';
                        } elseif ($lc_main_claims > $lc_main_limit) {
                            $ls_status = 'MAIN BENEFIT LIMIT EXCEEDED';
                        }
                    }
                } else {
                    //main benefit
                    if (is_null($ll_sub_limit) === 0) {
                        $lc_ben_bal = $lc_limit - $lc_tot_payable;
                        $ls_percent = $lc_tot_payable == 0 ? 0 : (($lc_tot_payable / $lc_limit) * 100);
                        $ls_percent = number_format($ls_percent, 2) . '%';

                        if ($lc_tot_payable < $lc_limit) {
                            $ls_status = 'WITHIN LIMIT';
                        } elseif ($lc_tot_payable == $lc_limit) {
                            $ls_status = 'LIMIT REACHED';
                        } elseif ($lc_tot_payable > $lc_limit) {
                            $ls_status = 'LIMIT EXCEEDED';
                        }
                    } else {
                        if ($lc_main_claims < $lc_main_limit) {
                            $lc_main_ben_bal = $lc_main_ben_bal - $lc_main_claims;

                            if ($lc_main_ben_bal >= $lc_limit) {
                                $lc_ben_bal = $lc_limit - $lc_tot_payable;
                                $ls_percent = $lc_tot_payable == 0 ? 0 : (($lc_tot_payable / $lc_limit) * 100);
                                $ls_percent = number_format($ls_percent, 2) . '%';
                                if ($lc_tot_payable < $lc_limit) {
                                    $ls_status = 'WITHIN LIMIT';
                                } elseif ($lc_tot_payable == $lc_limit) {
                                    $ls_status = 'LIMIT REACHED';
                                } elseif ($lc_tot_payable > $lc_limit) {
                                    $ls_status = 'LIMIT EXCEEDED';
                                }
                            } else {
                                if ($lc_tot_payable == 0) {
                                    $lc_ben_bal = $lc_main_ben_bal;
                                    $ls_percent = $lc_tot_payable == 0 ? 0 : (($lc_tot_payable / $lc_limit) * 100);
                                    $ls_percent = number_format($ls_percent, 2) . '%';
                                    $ls_status = 'WITHIN LIMIT';
                                } else {
                                    $lc_ben_bal = $lc_main_ben_bal - $lc_tot_payable;
                                    $ls_percent = $lc_tot_payable == 0 ? 0 : (($lc_tot_payable / $lc_limit) * 100);
                                    $ls_percent = number_format($ls_percent, 2) . '%';

                                    if ($lc_ben_bal > 0) {
                                        $ls_status = 'WITHIN LIMIT';
                                    } elseif ($lc_ben_bal = 0) {
                                        $ls_status = 'LIMIT REACHED';
                                    } elseif ($lc_ben_bal < 0) {
                                        $ls_status = 'LIMIT EXCEEDED';
                                    }
                                }
                            }
                        } elseif ($lc_main_claims == $lc_main_limit) {
                            $ls_status = 'MAIN BENEFIT LIMIT REACHED';
                        } elseif ($lc_main_claims > $lc_main_limit) {
                            $ls_status = 'MAIN BENEFIT LIMIT EXCEEDED';
                        }
                    }
                }
            }
            // } else {
            // $ls_status = $lc_tot_payb = $lc_ben_bal = $ls_percent = '';
            // }

            //formating 
            $lc_tot_payb = $lc_tot_payb !== '' ? number_format($lc_tot_payb, 2) : '';
            $lc_ben_bal = $lc_ben_bal !== '' ? number_format($lc_ben_bal, 2) : '';
            $limit =  $dt->limit !== '' && !is_null($dt->limit) ? number_format($dt->limit, 2) : '';



            $dt = [
                'member_name' => $dt->member_name,
                'lc_limit' =>  $limit,
                'benefit' => $dt->benefit,
                'll_bene' => $dt->benefit_code,
                'll_share' => $dt->sharing_code,
                'sharing' => $dt->sharing,
                'family_no' => $dt->family_no,
                'principal_name' => $dt->principal_name,
                'relation' => $dt->relation,
                'ls_memno' => $dt->member_no,
                'ls_status' => $ls_status,
                'lc_tot_payb' => $lc_tot_payb,
                'lc_ben_bal' => $lc_ben_bal,
                'ls_percent' => $ls_percent,
            ];
            array_push($response, $dt);
        }

        return $response;
    }
}
