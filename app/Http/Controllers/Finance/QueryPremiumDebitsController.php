<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetails;
use App\Models\PremiumInvoiceDetailsSammary;
use App\Models\PremiumPaymentSchedule;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isNull;

class QueryPremiumDebitsController extends Controller
{
    //fetch premium debits notes --- both corporate and individual
    public function fetchPremiumDebits($option, $input)
    {
        //return $input;
        switch ($option) {
            case '1':
                $debits = PremiumInvoice::select('invoice_no', 'idx')
                    ->distinct('invoice_no')
                    ->where('corp_id', $input)
                    ->orderBy('idx')
                    ->get();

                return $debits;
                break;
            case '2':
                $debits = PremiumInvoice::select('invoice_no', 'idx')
                    ->distinct('invoice_no')
                    ->where('member_no', $input)
                    ->orderBy('idx')
                    ->get();

                return $debits;
                break;
            default:
                break;
        }
    }

    //fetch invoice number details
    public function fetchInvoiceNoDetails($invoice_no)
    {

        $total_ttl = $prorated_prem_ttl = $discounted_prem_ttl = $loaded_prem_ttl = $smart_cost_ttl = $access_fee_ttl = 0;
        $summary_data = PremiumInvoice::select(
            'premium_invoice.invoice_no',
            'premium_invoice.agent_id',
            'premium_invoice.corp_id',
            'premium_invoice.invoice_date',
            'premium_invoice.invoice_total',
            'premium_invoice.premium_net',
            'premium_invoice.prorate_days',
            'premium_invoice.insurer as insurer_code',
            'premium_invoice.cover_start_date',
            'premium_invoice.load',
            'premium_invoice.stamp_duty',
            'premium_invoice.phcf',
            'premium_invoice.tl',
            'premium_invoice.invoice_net',
            'premium_invoice.new_renewal',
            'premium_invoice.credit_note',
            'premium_invoice.user_id',
            'premium_invoice.anniv',
            'premium_invoice.discount_amt',
            'premium_invoice.member_no',
            'premium_invoice.notes',
            'premium_invoice.end_date as cover_end_date',
            'premium_invoice.contigency',
            're_insurer.re_insurer',
            'premium_invoice.days',
            'premium_invoice.agent_rate',
            'premium_invoice.bdm_rate',
            'premium_invoice.ass_hos_rate',
            'premium_invoice.hos_rate',
            'premium_invoice.smart_cost',
            'premium_invoice.access_fee',
            'premium_invoice.notes',
            'premium_invoice.gift_items',
            'premium_invoice.first_aid',
            'premium_invoice.other',
            'premium_invoice.retained',
            'premium_invoice.ceded'
        )
            ->leftJoin('re_insurer', 're_insurer.code', '=', 'premium_invoice.insurer')
            ->where('invoice_no', $invoice_no)
            ->get();
        $new_renewal = $summary_data[0]->new_renewal;

        if ($new_renewal == 1) {
            $ls_new = 'New Business - Medical';
        } elseif ($new_renewal == 2) {
            $ls_new = 'Renewal Business - Medical';
        } elseif ($new_renewal == 3) {
            $ls_new = 'Additional Member(s) - Medical';
        } elseif ($new_renewal == 4) {
            $ls_new = 'Deletion Of Member(s) - Medical';
        } elseif ($new_renewal == 5) {
            $ls_new = 'Cover Up Grade - Medical';
        } elseif ($new_renewal == 6) {
            $ls_new = 'Cover Down Grade - Medical';
        } elseif ($new_renewal == 7) {
            $ls_new = 'Additional Members - Medical';
        } elseif ($new_renewal == 8) {
            $ls_new = 'Cover Extension - Medical';
        }

        $credit_note = $summary_data[0]->credit_note;
        if ($credit_note == 1) {
            $ls_doc_type = 'Debit Note';
        } elseif ($credit_note == 2) {
            $ls_doc_type = 'Credit Note';
        } elseif ($credit_note == 3) {
            $ls_doc_type = 'Endorsement';
        }

        //fetch client address
        if (is_null($summary_data[0]->member_no)) {
            $address = Corporate::select('corporate', 'postal_add', 'tel_no', 'town', 'policy_no')
                ->where('corp_id', $summary_data[0]->corp_id)
                ->get();
            $annivs = CorpAnniversary::select('end_date')
                ->where('corp_id', $summary_data[0]->corp_id)
                ->where('anniv', $summary_data[0]->anniv)
                ->get();
        } else {
            $address = PrincipalApplicant::select(
                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as member_name"),
                'principal_applicant.postal_add',
                'principal_applicant.tel_no',
                'principal_applicant.mobile_no',
                'principal_applicant.town',
                'corporate.corporate',
                'corporate.policy_no'
            )
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->where('member_no', $summary_data[0]->member_no)
                ->get();
            $annivs = MemberAnniversary::select('end_date')
                ->where('member_no', $summary_data[0]->member_no)
                ->where('anniv', $summary_data[0]->anniv)
                ->get();
        }

        foreach ($summary_data as $datum) {
            //append to data to frontend
            $datum->business_class = $ls_new;
            $datum->doc_type = $ls_doc_type;
            $datum->corporate = $address[0]->corporate;
            $datum->postal_add = $address[0]->postal_add;
            $datum->tel_no = $address[0]->tel_no;
            $datum->town = $address[0]->town;
            $datum->policy_no = $address[0]->policy_no;
            $datum->mobile_no = $address[0]->mobile_no;
            $datum->end_date = $annivs[0]->end_date;
        }

        //fetch premium details
        $details_data = PremiumInvoiceDetails::select(
            'premium_invoice_details.category',
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name, ' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name, ' ',member_info.other_names) as member_name"),
            'premium_invoice_details.product_name as product_code',
            't_product_name.product_name',
            'premium_invoice_details.family_size',
            'premium_invoice_details.family_title as family_title_code',
            'premium_invoice_details.member_age',
            'premium_invoice_details.effective_date',
            'premium_invoice_details.end_date',
            'premium_invoice_details.prorata_period',
            'premium_invoice_details.premium',
            'premium_invoice_details.total',
            'premium_invoice_details.prorated_prem',
            'premium_invoice_details.disc_amt',
            'premium_invoice_details.discounted_prem',
            'premium_invoice_details.load_amt',
            'premium_invoice_details.loaded_prem',
            'premium_invoice_details.smart_cost',
            'premium_invoice_details.access_fee',
            'premium_invoice_details.invoice_no',
            'family_relation.relation',
            'premium_invoice_details.member_no',
            'premium_invoice_details.benefit as benefit_code',
            'premium_invoice_details.limit',
            'benefit.benefit'
        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'premium_invoice_details.member_no')
            ->leftJoin('principal_applicant', 'principal_applicant.member_no', '=', 'premium_invoice_details.member_no')
            ->leftJoin('t_product_name', 't_product_name.code', '=', 'premium_invoice_details.product_name')
            ->leftJoin('family_relation', 'family_relation.code', '=', 'premium_invoice_details.family_title')
            ->leftJoin('benefit', 'benefit.code', '=', 'premium_invoice_details.benefit')
            ->where('invoice_no', $invoice_no)
            ->get();

        foreach ($details_data as $data) {
            $total_ttl += $data->total;
            $prorated_prem_ttl += $data->prorated_prem;
            $discounted_prem_ttl += $data->discounted_prem;
            $loaded_prem_ttl += $data->loaded_prem;
            $smart_cost_ttl += $data->smart_cost;
            $access_fee_ttl += $data->access_fee;
        }

        //fetch member schedule
        $schedule_data = MemberInfo::select(
            DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name, ' ',principal_applicant.other_names) as principal_name"),
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name, ' ',member_info.other_names) as member_name"),
            'member_info.member_no',
            'premium_invoice_details.invoice_no'
        )
            ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->leftJoin('premium_invoice_details', 'premium_invoice_details.family_no', '=', 'principal_applicant.family_no')
            ->where('premium_invoice_details.invoice_no', $invoice_no)
            ->get();

        //payment schedule data
        $payment_schedule = PremiumPaymentSchedule::select(
            'invoice_no',
            'payable_date',
            'payable_amt',
            'paid',
            'paid_date',
            'paid_amt',
        )
            ->where('invoice_no', $invoice_no)
            ->get();

        //view summary data
        $sammary = PremiumInvoice::select(
            DB::raw("FORMAT(premium_invoice.invoice_date, 'yyyy-mm-dd') AS invoice_date"),
            // DB::raw("isnull(premium_invoice.credit_note, 0) as credit_note"),
            DB::raw("
            CASE 
                WHEN premium_invoice.credit_note = 1 THEN 'DEBIT NOTE'
                WHEN premium_invoice.credit_note = 2 THEN 'CREDIT NOTE'             
                WHEN premium_invoice.credit_note = 3 THEN 'ENDORSMENT'  
            END AS credit_note
            "),
            DB::raw("
            CASE 
                WHEN premium_invoice.new_renewal = 1 THEN 'New Business - Medical'
                WHEN premium_invoice.new_renewal = 2 THEN 'Renewal Business - Medical'
                WHEN premium_invoice.new_renewal = 3 THEN 'Additional Member(s) - Medical'
                WHEN premium_invoice.new_renewal = 4 THEN 'Deletion Of Member(s) - Medical'	
                WHEN premium_invoice.new_renewal = 5 THEN 'Cover Up Grade - Medical'
                WHEN premium_invoice.new_renewal = 6 THEN 'Cover Down Grade - Medical'
                WHEN premium_invoice.new_renewal = 7 THEN 'Additional Members - Medical'
                WHEN premium_invoice.new_renewal = 8 THEN 'Cover Extension - Medical'
            END AS class
            "),
            'premium_invoice.member_no',
            'premium_invoice.corp_id',
            'premium_invoice.invoice_no',
            'premium_invoice.invoice_total',
            'premium_invoice.notes',
            'premium_invoice.user_id',
        )
            ->where('premium_invoice.invoice_no', $invoice_no)
            ->get();

        $client =  $cover_dates = $policy_no = $agent = "";
        if (!is_null($sammary[0]->member_no)) {
            $max_anniv = MemberAnniversary::where('member_no', $sammary[0]->member_no)->max('anniv');
            $client = MemberInfo::select(
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names")
            )
                ->where('member_no', $sammary[0]->member_no)
                ->get();

            $client = $client[0]->names;

            $cover_dates = MemberAnniversary::select(
                DB::raw("CONCATmax(FORMAT(member_anniversary.start_date, 'yyyy-mm-dd')),' to ',max(FORMAT(member_anniversary.end_date, 'yyyy-mm-dd'))) as cover_dates")

            )
                ->where('member_no', $sammary[0]->member_no)
                ->where('anniv', $max_anniv)
                ->get();
            $cover_dates =  $cover_dates[0]->cover_dates;
            $agent = Corporate::select('agent.agent_name')
                ->leftJoin('agent', 'agent.agent_id', 'corporate.agent_id')
                ->where('corp_id', 14)
                ->get();
            $agent = $agent[0]->agent_name;
        } else if (!is_null($sammary[0]->corp_id)) {
            $max_anniv = CorpAnniversary::where('corp_id', $sammary[0]->corp_id)->max('anniv');

            $corp_data = Corporate::select('corporate', 'policy_no')
                ->where('corp_id', $sammary[0]->corp_id)
                ->get();
            $client = $corp_data[0]->corporate;
            $policy_no = $corp_data[0]->policy_no;

            $cover_dates = CorpAnniversary::select(
                DB::raw("CONCATmax(FORMAT(corp_anniversary.start_date, 'yyyy-mm-dd')),' to ',max(FORMAT(corp_anniversary.end_date, 'yyyy-mm-dd'))) as cover_dates")

            )
                ->where('corp_id', $sammary[0]->corp_id)
                ->where('anniv', $max_anniv)
                ->get();
            $cover_dates =  $cover_dates[0]->cover_dates;
            $agent = Corporate::select('agent.agent_name')
                ->leftJoin('agent', 'agent.agent_id', 'corporate.agent_id')
                ->leftJoin('corp_anniversary', 'corp_anniversary.corp_id', 'corporate.corp_id')
                ->where('corporate.corp_id', $sammary[0]->corp_id)
                ->where('anniv', $max_anniv)
                ->get();
            $agent = $agent[0]->agent_name;
        }

        // $class = PremiumInvoiceDetailsSammary::where('invoice_no', $invoice_no)->distinct()->pluck('class');
        // $class = count($class) == 0 ? '' : $class[0];



        $view_summary = [
            'client' => $client,
            'agent' => $agent,
            'invoice_no' => $sammary[0]->invoice_no,
            'invoice_date' => $sammary[0]->invoice_date,
            'class' => $sammary[0]->class,
            'insurer' => 'AAR HEALTH SERVICES',
            'cover_period' => $cover_dates,
            'policy_no' => $policy_no,
            'invoice_total' => $sammary[0]->invoice_total,
            'user_id' => $sammary[0]->user_id,
            'notes' => $sammary[0]->notes,
            'credit_note' => $sammary[0]->credit_note
        ];


        return [
            'summary_data' => json_encode($summary_data[0], JSON_INVALID_UTF8_IGNORE),
            'details_data' => json_encode($details_data, JSON_INVALID_UTF8_IGNORE),
            'total_ttl' => $total_ttl,
            'prorated_prem_ttl' => $prorated_prem_ttl,
            'discounted_prem_ttl' => $discounted_prem_ttl,
            'loaded_prem_ttl' => $loaded_prem_ttl,
            'smart_cost_ttl' => $smart_cost_ttl,
            'access_fee_ttl' => $access_fee_ttl,
            'schedule_data' => json_encode($schedule_data, JSON_INVALID_UTF8_IGNORE),
            'payment_schedule' => json_encode($payment_schedule, JSON_INVALID_UTF8_IGNORE),
            'view_summary' => json_encode($view_summary, JSON_INVALID_UTF8_IGNORE)
        ];
    }
}
