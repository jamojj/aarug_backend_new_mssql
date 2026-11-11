<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimsExperienceExtractController extends Controller
{
     //fetch claims
     public function fetchClaimsExperienceExtractReport(Request $request)
     {
          //return $request;
          ini_set('memory_limit', '-1');
          ini_set('max_execution_time', '0');

          $from = $request->date_from;
          $from = date("Ymd", strtotime($from));
          $to = $request->date_to;
          $to = date("Ymd", strtotime($to));



          // 		$dt1 = DB::select("select claim_no claimno, smart_bill_id Eclaim, provider providerCode,
          //      (select left(vendor_id, len(vendor_id) -4) from provider where code
          // = providerCode) providerNo,
          //      (select provider from provider where code = providerCode)
          // provider_name,
          //      case
          //          when CHARINDEX('AAR',  provider_name) > 0 then 'AAR'
          //          else
          //            'PPO'
          //      END as provider_type,
          //      case
          //          when isnull(vet_status, 0) = 0 then 'ENTRY'
          //          when vet_status = 2 then 'VETTED REJECTED'
          //          when vet_status = 3 then 'VETTED PEND'
          //          when vet_status = 1 and (select payment_no from bills where id =
          // ll_idx) > 0 then 'PAID'
          //          when vet_status = 1 and (select len(trim(voucher_no)) from bills
          // where id = ll_idx) > 0 then 'VOUCHERED'
          //          else 'VETTED APPROVED'
          //      END as status,
          //      (select family_no from member_info where member_no =
          // bills.member_no) family_code,
          //         member_no memberno,
          //      (select join_date from member_info where member_no =
          // bills.member_no),
          //      (select dob from member_info where member_no = bills.member_no),
          //      (select case when gender = 1 then 'M' when gender = 2 then 'F' end
          // case as gender from member_info where member_no = bills.member_no),
          //      (select id_pp_no from member_info where member_no =
          // bills.member_no),
          //      (select first_name||' '||other_names||' '||surname from member_info
          // where member_no = bills.member_no) member_name,
          //      (select corp_id from principal_applicant where family_no =
          // family_code) account_id,
          //      account_id as employer_id,
          //      (select corporate from corporate where corp_id = account_id)
          // employer_name,
          //         (select max(anniv) from member_anniversary where
          // bills.invoice_date>=start_date and bills.invoice_date <= end_date and
          // member_no = bills.member_no) pol_period,
          //      (select start_date from member_anniversary where anniv = pol_period
          // and member_no = bills.member_no) policy_from,
          //      (select end_date from member_anniversary where anniv = pol_period
          // and member_no = bills.member_no) policy_to,
          //      (select health_plan from member_anniversary where anniv = pol_period
          // and member_no = bills.member_no) health_plan,
          //      (select product_name from t_product_name where code = (select
          // h_option from member_anniversary where anniv = pol_period and member_no
          // = bills.member_no)) h_option,
          //       pre_auth_no auth_no, invoice_no refno, date_received
          // claim_received,
          // case when 
          //     (select bene_class from benefit where code = benefit_id and claim_no = claimno ) = 1 then
          //         case when 
          //             (select date_admitted from claim_form where claim_no = claimno) is null 
          //                then 
          //                     (select max(invoice_date) from bills where claim_no = claimno and invoice_date is not null)
          //                else 
          //                     (select date_admitted from claim_form where claim_no = claimno)
          //                END

          // else 
          //    invoice_date
          // END as service_from, 

          // case when 
          //     (select bene_class from benefit where code = benefit_id and claim_no = claimno ) = 1 then
          //          case when 
          //               (select date_discharged from claim_form where claim_no = claimno) is null 
          //                 then 
          //                     (select max(invoice_date) from bills where claim_no = claimno and invoice_date is not null)
          //                 else
          //                     (select date_discharged from claim_form where claim_no = claimno)
          //               END 

          // else 
          //    invoice_date
          // END as service_to,
          // invoiced_amount as master_claim_amt,
          //      case
          //          when refund = 0 then 'Provider'
          //          when refund = 1 then 'Corporate'
          //          when refund = 2 then 'Member'
          //      END as PayType,
          //      service tariff_code, (select service from service where code =
          // tariff_code) tariff_desc,
          //      claim_nature benefit_id, (select benefit from benefit where code =
          // benefit_id) benefit_desc,
          //      case
          //          when fund = 1 then 'Fund'
          //          when fund = 2 then 'Capitation'
          //          else 'Insured'
          //      END as Benefit_type,
          //      invoiced_amount ClaimAmt, deduction_amount Adj_Amt,
          //      (select deduct_reason from deduction_reason where code =
          // bills.deduction_reason) deduction_reason,
          //      amount_payable Net_Amt,
          //      (select diag_code from clinical_diagnosis where idx = (select
          // max(isnull(diagnosis, 0)) from member_diagnosis where claim_no =
          // claimno)),
          //      (select clinical_diagnosis from clinical_diagnosis where idx =
          // (select max(isnull(diagnosis, 0)) from member_diagnosis where claim_no =
          // claimno)) DiagnosisDesc,
          //      voucher_no, voucher_date,
          //      (select cheque_no from bills_payment where payment_no =
          // bills.payment_no),
          //      (select cheque_date from bills_payment where payment_no =
          // bills.payment_no),

          //         id ll_idx, invoice_date, deduction_notes

          //      from bills
          // where FORMAT(invoice_date, 'yyyyMMdd') between '$from' and
          // '$to' order by claimno;");

          $dt = DB::select("select 
          b.claim_no claimno, b.smart_bill_id Eclaim, b.provider providerCode,
          left(p.vendor_id, len(p.vendor_id) -4) as providerNo,
          p.provider as provider_name,
          case
          when CHARINDEX('AAR',  provider_name) > 0 then 'AAR'
          else
               'PPO'
          END as provider_type,
          case
          WHEN isnull(b.vet_status, 0) = 0 then 'ENTRY'
          WHEN b.vet_status = 2 then 'VETTED REJECTED'
          WHEN b.vet_status = 3 then 'VETTED PEND'
               WHEN b.vet_status = 1 AND bp2.payment_no IS NOT NULL THEN 'PAID'
          WHEN b.vet_status = 1 AND LEN(TRIM(b.voucher_no)) > 0 THEN 'VOUCHERED'
          else 'VETTED APPROVED'
          END as status,          
          mi.family_no as family_code,
          b.member_no memberno,
          mi.join_date,
          mi.dob,
          case when mi.gender = 1 then 'M' when mi.gender = 2 then 'F' end
          case as gender,
          mi.id_pp_no,
          mi.first_name||' '||mi.other_names||' '||mi.surname as member_name,
          pa.corp_id as account_id,    
          account_id as employer_id,
          c.corporate as employer_name,
          
          
     
          ma2.anniv AS pol_period,
          ma3.start_date AS policy_from,
          ma3.end_date AS policy_to,
          ma3.health_plan,
          tpn.product_name AS h_option,
          b.pre_auth_no auth_no, b.invoice_no refno, b.date_received claim_received,

          case when 
          bene.bene_class = 1 then
               case when 
                    cf.date_admitted is null 
                         then 
                              mid.max_invoice_date
                         else 
                              cf.date_admitted
                         END

          else 
          b.invoice_date
          END as service_from, 

          case when 
          bene.bene_class = 1 then
               case when 
                    cf.date_discharged is null 
                         then 
                              mid.max_invoice_date
                         else
                              cf.date_discharged
                    END 

          else 
          b.invoice_date
          END as service_to,
          b.invoiced_amount as master_claim_amt,
     case
         when b.refund = 0 then 'Provider'
         when b.refund = 1 then 'Corporate'
         when b.refund = 2 then 'Member'
     END as PayType,
     b.service tariff_code, 
     s.service as tariff_desc,
     b.claim_nature benefit_id,
     bene.benefit as benefit_desc,
     CASE 
          WHEN bene.bene_class = 1 THEN 'IP'
          ELSE 'OP'
     END AS benefit_class,
     case
         when b.fund = 1 then 'Fund'
         when b.fund = 2 then 'Capitation'
         else 'Insured'
     END as Benefit_type,
     b.invoiced_amount ClaimAmt, b.deduction_amount Adj_Amt,
     dr.deduct_reason as deduction_reason,
     b.amount_payable Net_Amt,
      cd.diag_code,
     cd.clinical_diagnosis AS DiagnosisDesc,
     b.voucher_no, voucher_date,
     bp.cheque_no, bp.cheque_date,

        b.id ll_idx, b.invoice_date, b.deduction_notes

     from bills b
     LEFT JOIN provider p on p.code = b.provider
     JOIN member_info mi on mi.member_no = b.member_no
     JOIN principal_applicant pa on pa.family_no = mi.family_no 
     JOIN corporate c on c.corp_id = pa.corp_id
     JOIN benefit bene on bene.code = b.claim_nature
     JOIN claim_form cf on cf.claim_no = b.claim_no
     JOIN service s on s.code = b.service
     LEFT JOIN deduction_reason dr on dr.code = b.deduction_reason
     LEFT JOIN bills_payment bp on bp.payment_no = b.payment_no
    
     JOIN (
		    SELECT claim_no, MAX(invoice_date) AS max_invoice_date
		    FROM bills
		    WHERE invoice_date IS NOT NULL
		    GROUP BY claim_no
		) AS mid ON mid.claim_no = b.claim_no
     JOIN (
     SELECT 
          ma.member_no, 
          b2.claim_no,
          MAX(ma.anniv) AS anniv
     FROM 
          member_anniversary ma
     JOIN bills b2 ON ma.member_no = b2.member_no 
     WHERE 
          b2.invoice_date >= ma.start_date
          AND b2.invoice_date <= ma.end_date
     GROUP BY 
          ma.member_no, b2.claim_no
     ) AS ma2 ON ma2.member_no = b.member_no AND ma2.claim_no = b.claim_no
	JOIN member_anniversary ma3 ON ma3.member_no = ma2.member_no AND ma3.anniv = ma2.anniv
	 JOIN t_product_name tpn on tpn.code = ma3.h_option 
	LEFT JOIN 
    (
        SELECT 
            claim_no,
            MAX(ISNULL(diagnosis, 0)) AS max_diagnosis
        FROM 
            member_diagnosis
        GROUP BY 
            claim_no
    ) md_max ON md_max.claim_no = b.claim_no
	LEFT JOIN 
	    clinical_diagnosis cd ON cd.idx = md_max.max_diagnosis
    LEFT JOIN 
    	bills_payment bp2 ON bp2.payment_no = b.payment_no 
	    
where FORMAT(b.invoice_date, 'yyyyMMdd') between '$from' and
'$to' order by claimno;");

          return  json_encode($dt, JSON_INVALID_UTF8_IGNORE);
     }
}
