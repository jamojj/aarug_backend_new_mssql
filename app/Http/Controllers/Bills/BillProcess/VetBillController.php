<?php

namespace App\Http\Controllers\Bills\BillProcess;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillVet;
use App\Models\ClaimForm;
use App\Models\ClaimsReserve;
use App\Models\ClinicalDiagnosis;
use App\Models\CmsBillReturns;
use App\Models\LineItems;
use App\Models\MemberDiagnosis;
use App\Models\PreAuthorization;
use App\Models\Provider;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberExclusions;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\SmartBillReturns;
use App\Models\Tariffs;
use App\Models\TariffYear;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class VetBillController extends Controller
{

    public function fetchProviderVetBill()
    {
        $providers = Provider::select('provider.code', 'provider.provider')
            ->join('bills', 'bills.provider', '=', 'provider.code')
            ->distinct('provider.provider')
            ->orderBy('provider.provider', 'ASC')
            ->where('provider.provider', '!=', '')
            ->where(DB::raw("isnull(bills.vet_status, 0)"), 0)
            // ->where('provider.fax_no', 'ACTIVE')
            ->get();

        return $providers;
    }

    public function fetchClaimsPerProvider($provider)
    {
        $claims = Bills::select('claim_no')
            ->distinct()
            ->where('provider', $provider)
            ->where(function ($q) {
                $q->where('vet_status', 0)->orWhere('vet_status', null);
            })
            ->orderBy('claim_no', 'ASC')
            ->get();
        return $claims;
    }



    public function fetchClaimsPerClaimNo(Request $request)
    {

        $total = 0;
        $claims = [];
        $batch_no = "BAT-" . $request->claim_input;
        $claim_no = "CLM " . $request->claim_input;
        $member_no = $request->claim_input;

        switch ($request->options) {
            case '1':
                $claims = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                    'service.code',
                    'service.service',
                    'provider.code',
                    'provider.provider',
                    'bills.claim_no',
                    'bills.invoice_no',
                    'bills.member_no',
                    'bills.invoiced_amount',
                    'bills.amount_payable',
                    'bills.invoice_date',
                    'bills.payee',
                    'bills.voucher_no',
                    'bills.id',
                    't_product_name.code',
                    't_product_name.product_name',
                    'bills.batch_no',
                    'bills.smart_bill_id',
                    'benefit.code as benefit_code',
                    'benefit.benefit',
                    'bills.anniv',
                    'bills.date_entered',
                    'bills.integ',

                )
                    ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
                    ->leftJoin('service', 'bills.service', '=', 'service.code')
                    ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
                    ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
                    ->orderBy('names', 'ASC')
                    ->where(DB::raw("isnull(bills.vet_status,0)"), 0)
                    ->where('provider.code', $request->provider)
                    ->where('bills.claim_no', $request->claim_no)
                    ->get();
                break;
            case '2':
                $claims = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                    'service.code',
                    'service.service',
                    'provider.code',
                    'provider.provider',
                    'bills.claim_no',
                    'bills.invoice_no',
                    'bills.member_no',
                    'bills.invoiced_amount',
                    'bills.amount_payable',
                    'bills.invoice_date',
                    'bills.payee',
                    'bills.voucher_no',
                    'bills.id',
                    't_product_name.code',
                    't_product_name.product_name',
                    'bills.batch_no',
                    'bills.smart_bill_id',
                    'benefit.code as benefit_code',
                    'benefit.benefit',
                    'bills.anniv',
                    'bills.date_entered',
                    'bills.integ',

                )
                    ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
                    ->leftJoin('service', 'bills.service', '=', 'service.code')
                    ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
                    ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
                    ->orderBy('names', 'ASC')
                    ->where(DB::raw("isnull(bills.vet_status,0)"), 0)
                    ->where('bills.claim_no', $claim_no)
                    ->get();
                break;
            case '3':
                $claims = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                    'service.code',
                    'service.service',
                    'provider.code',
                    'provider.provider',
                    'bills.claim_no',
                    'bills.invoice_no',
                    'bills.member_no',
                    'bills.invoiced_amount',
                    'bills.amount_payable',
                    'bills.invoice_date',
                    'bills.payee',
                    'bills.voucher_no',
                    'bills.id',
                    't_product_name.code',
                    't_product_name.product_name',
                    'bills.batch_no',
                    'bills.smart_bill_id',
                    'benefit.code as benefit_code',
                    'benefit.benefit',
                    'bills.anniv',
                    'bills.date_entered',
                    'bills.integ',

                )
                    ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
                    ->leftJoin('service', 'bills.service', '=', 'service.code')
                    ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
                    ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
                    ->orderBy('names', 'ASC')
                    ->where(DB::raw("isnull(bills.vet_status,0)"), 0)
                    ->where('bills.batch_no', $batch_no)
                    ->get();
                break;
            case '4':
                $claims = Bills::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                    'service.code',
                    'service.service',
                    'provider.code',
                    'provider.provider',
                    'bills.claim_no',
                    'bills.invoice_no',
                    'bills.member_no',
                    'bills.invoiced_amount',
                    'bills.amount_payable',
                    'bills.invoice_date',
                    'bills.payee',
                    'bills.voucher_no',
                    'bills.id',
                    't_product_name.code',
                    't_product_name.product_name',
                    'bills.batch_no',
                    'bills.smart_bill_id',
                    'benefit.code as benefit_code',
                    'benefit.benefit',
                    'bills.anniv',
                    'bills.date_entered',
                    'bills.integ',

                )
                    ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
                    ->leftJoin('service', 'bills.service', '=', 'service.code')
                    ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
                    ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
                    ->orderBy('names', 'ASC')
                    ->where(DB::raw("isnull(bills.vet_status,0)"), 0)
                    ->where('bills.member_no', $member_no)
                    ->get();
                break;
        }


        foreach ($claims as $claim) {
            $total += $claim->invoiced_amount;
        }

        $data = [
            'claims' => $claims,
            'total' => number_format($total, 2)
        ];

        return $data;
    }

    public function fetchClaimsPerClaimNoAll($claim_no)
    {
        $total = 0;

        $claims = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            'service.code',
            'service.service',
            'provider.code',
            'provider.provider',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.member_no',
            'bills.invoiced_amount',
            'bills.amount_payable',
            'bills.invoice_date',
            'bills.payee',
            'bills.voucher_no',
            'bills.id',
            't_product_name.code',
            't_product_name.product_name',
            'bills.batch_no',
            'bills.smart_bill_id',
            'benefit.code',
            'benefit.benefit',
            'bills.anniv',
            'bills.date_entered',
            'bills.integ',

        )
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
            ->where('bills.claim_no', $claim_no)
            ->get();

        foreach ($claims as $claim) {
            $total += $claim->invoiced_amount;
        }

        $data = [
            'claims' => $claims,
            'total' => number_format($total, 2)
        ];

        return $data;
    }

    public function fetchClaimById($id)
    {
        $data = [];


        $claim = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            DB::raw("FORMAT(bills.date_entered, 'yyyy-mm-dd') as date_entered"),
            DB::raw("FORMAT(claim_form.visit_date, 'yyyy-mm-dd') as visit_date"),
            DB::raw("FORMAT(claim_form.doctor_date, 'yyyy-mm-dd') as doctor_date"),
            'service.code as service_code',
            'service.service',
            'provider.code as provider_code',
            'provider.provider',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.member_no',
            'bills.invoiced_amount',
            'bills.amount_payable',
            'bills.payee',
            'bills.voucher_no',
            'bills.id',
            't_product_name.code',
            't_product_name.product_name',
            'bills.batch_no',
            'bills.smart_bill_id',
            'benefit.code as benefit_code',
            'benefit.benefit',
            'bills.anniv',
            'bills.integ',
            'bills.user_id as bills_user_id',
            'bill_vet.user_id as bill_vet_user_id',
            'bills.vet_status',
            't_gender.gender',
            'family_relation.relation',
            'claim_form.doctor_sign',
            'claim_form.claim_form_signed',
            'claim_form.date_admitted',
            'claim_form.date_discharged'
        )
            ->leftJoin('bill_vet', 'bill_vet.claim_no', '=', 'bills.claim_no')
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
            ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
            ->leftJoin('claim_form', 'bills.claim_no', '=', 'claim_form.claim_no')
            ->whereRaw('ISNULL(bills.vet_status, 0) = ?', [0])
            ->where('bills.id', $id)
            ->get();



        // Get the claim safely
        $claimItem = (!empty($claim) && isset($claim[0]))
            ? $claim[0]
            : null;

        $optical = "";

        // Continue only if a claim exists and the benefit is optical
        if ($claimItem !== null && $claimItem->benefit_code == 36) {

            // Check if another optical claim exists in the same anniv
            $optical_claims_count = Bills::where('claim_nature', 36)
                ->where('member_no', $claimItem->member_no)
                ->where('anniv', $claimItem->anniv)
                ->count();

            if ($optical_claims_count > 1) {
                $optical = $claimItem->anniv;
            } elseif ($claimItem->anniv != 1) {

                // Find the previous optical anniv
                $prev_anniv = Bills::where('claim_nature', 36)
                    ->where('member_no', $claimItem->member_no)
                    ->where('anniv', '<', $claimItem->anniv)
                    ->max('anniv');

                if (
                    !is_null($prev_anniv) &&
                    ($claimItem->anniv - $prev_anniv) === 1
                ) {
                    $optical = $prev_anniv;
                }
            }
        }

        $data[] = ['optical' => $optical];


        $visit_rate = 0;

        if (!empty($claim) && isset($claim[0])) {
            $visit_rate = Bills::where('anniv', $claim[0]->anniv)
                ->where('member_no', $claim[0]->member_no)
                ->count(DB::raw("distinct(claim_no)"));
        }

        $visit_rate_data = collect();

        if (!empty($claim) && isset($claim[0])) {

            $visit_rate_data = Bills::select(
                DB::raw("FORMAT(bills.invoice_date, 'dd/mm/YYYY') as invoice_date"),
                'bills.claim_no',
                'provider.provider',
                'bills.diag_code'
            )
                ->join('provider', 'provider.code', '=', 'bills.provider')
                ->where('bills.anniv', $claim[0]->anniv)
                ->where('bills.member_no', $claim[0]->member_no)
                ->orderBy('bills.claim_no', 'desc')
                ->distinct('claim_no')
                ->get();

            if ($visit_rate_data->isNotEmpty()) {

                // Get all claim numbers
                $claimNos = $visit_rate_data->pluck('claim_no')->unique();

                // Fetch all diagnoses in one query
                $diagnoses = MemberDiagnosis::select(
                    'member_diagnosis.claim_no',
                    'clinical_diagnosis.clinical_diagnosis'
                )
                    ->join(
                        'clinical_diagnosis',
                        'clinical_diagnosis.idx',
                        '=',
                        'member_diagnosis.diagnosis'
                    )
                    ->whereIn('member_diagnosis.claim_no', $claimNos)
                    ->get()
                    ->groupBy('claim_no');

                // Attach diagnoses to each visit
                foreach ($visit_rate_data as $vrd) {
                    $diag_name = '';

                    foreach ($diagnoses->get($vrd->claim_no, collect()) as $diag) {
                        $diag_name .= $diag->clinical_diagnosis . ', ';
                    }

                    $vrd->diag_code = $diag_name;
                }
            }
        }


        $waiting_period = 0;
        $start_date = '';

        if (!empty($claim) && isset($claim[0])) {

            $member_benefit_data = MemberBenefits::select(
                DB::raw('isnull(member_benefits.waiting_period, 0) as waiting_period'),
                'member_anniversary.start_date'
            )
                ->join('member_anniversary', function ($join) {
                    $join->on(
                        'member_anniversary.member_no',
                        '=',
                        'member_benefits.member_no'
                    )
                        ->on(
                            'member_anniversary.anniv',
                            '=',
                            'member_benefits.anniv'
                        );
                })
                ->where('member_benefits.member_no', $claim[0]->member_no)
                ->where('member_benefits.anniv', $claim[0]->anniv)
                ->where('member_benefits.benefit', $claim[0]->benefit_code)
                ->get();

            if ($member_benefit_data->isNotEmpty()) {
                $waiting_period = $member_benefit_data[0]->waiting_period;
                $start_date = $member_benefit_data[0]->start_date;
            }
        }


        $provider_status = '';
        $provider_cancelled_status = 0;
        $provider_cancel_suspend_date = '';
        $provider_end_date = '';

        if (!empty($claim) && isset($claim[0])) {

            $provider_data = Provider::select(
                'fax_no',
                'end_date',
                'cancel_suspend_date'
            )
                ->where('code', $claim[0]->provider_code)
                ->get();

            if ($provider_data->isNotEmpty()) {
                $provider = $provider_data[0];

                $provider_cancelled_status = $provider->fax_no ?? 0;
                $provider_cancel_suspend_date = $provider->cancel_suspend_date ?? '';

                $provider_end_date = !empty($provider->end_date)
                    ? date("Y-m-d", strtotime($provider->end_date . " +1 day"))
                    : '';
            }

            if (
                $provider_cancelled_status != 'ACTIVE' &&
                !empty($provider_cancel_suspend_date)
            ) {
                if ($claim[0]->invoice_date > $provider_cancel_suspend_date) {
                    $provider_status = '2';
                } else {
                    $provider_status = '1';
                }
            } else {
                $provider_status = '1';
            }
        }

        $data[] = ['visit_rate' => $visit_rate];
        $data[] = ['claim_data' => $claim];
        $data[] = ['visit_rate_data' => $visit_rate_data];
        $data[] = ['waiting_period' => $waiting_period];
        $data[] = ['start_date' => $start_date];
        $data[] = ['provider_status' => $provider_status];
        $data[] = ['provider_cancel_suspend_date' => $provider_cancel_suspend_date];

        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }
    public function fetchClaimById2($id)
    {
        $data = [];

        $claim = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            DB::raw("FORMAT(bills.date_entered, 'yyyy-mm-dd') as date_entered"),
            DB::raw("FORMAT(claim_form.visit_date, 'yyyy-mm-dd') as visit_date"),
            DB::raw("FORMAT(claim_form.doctor_date, 'yyyy-mm-dd') as doctor_date"),
            'service.code as service_code',
            'service.service',
            'provider.code as provider_code',
            'provider.provider',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.member_no',
            'bills.invoiced_amount',
            'bills.amount_payable',
            'bills.payee',
            'bills.voucher_no',
            'bills.id',
            't_product_name.code',
            't_product_name.product_name',
            'bills.batch_no',
            'bills.smart_bill_id',
            'benefit.code as benefit_code',
            'benefit.benefit',
            'bills.anniv',
            'bills.integ',
            'bills.user_id as bills_user_id',
            'bill_vet.user_id as bill_vet_user_id',
            'bills.id',
            'bills.claim_no',
            'bills.vet_status',
            't_gender.gender',
            'family_relation.relation',
            'claim_form.doctor_sign',
            'claim_form.claim_form_signed',
            'claim_form.date_admitted',
            'claim_form.date_discharged',


        )
            ->leftJoin('bill_vet', 'bill_vet.claim_no', '=', 'bills.claim_no')
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
            ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
            ->leftJoin('claim_form', 'bills.claim_no', 'claim_form.claim_no')
            ->where(DB::raw("isnull(bills.vet_status,0)"), 0)
            ->where('bills.id', $id)
            ->get();


        //check if optical benefit was used in two consecutive annivs
        // check if benefit is optical
        if ($claim[0]->benefit_code == 36) {
            //check if another optical was consumed in same anniv
            $optical_claims = Bills::where('claim_nature', 36)
                ->where('member_no', $claim[0]->member_no)
                ->where('anniv', $claim[0]->anniv)
                ->get();
            if (count($optical_claims) > 1) {
                array_push($data, ['optical' => $claim[0]->anniv]);
            } else {
                //check if anniv is 1
                if ($claim[0]->anniv == 1) {
                    array_push($data, ['optical' => ""]);
                } else {
                    //check if consumed in previous consecutive anniv
                    $prev_anniv = Bills::where('claim_nature', 36)
                        ->where('member_no', $claim[0]->member_no)
                        ->where('anniv', '<', $claim[0]->anniv)
                        ->max('anniv');

                    if (!is_null($prev_anniv)) {
                        $check_anniv_for_optical = $claim[0]->anniv - $prev_anniv;
                        if ($check_anniv_for_optical === 1) {
                            array_push($data, ['optical' => $prev_anniv]);
                        } else {
                            array_push($data, ['optical' => ""]);
                        }
                    } else {
                        array_push($data, ['optical' => ""]);
                    }
                }
            }
        } else {
            array_push($data, ['optical' => ""]);
        }

        //check visit rate
        $visit_rate = Bills::where('anniv', $claim[0]->anniv)->where('member_no', $claim[0]->member_no)->count(DB::raw("distinct(claim_no)"));
        $visit_rate_data = Bills::select(
            DB::raw("FORMAT(bills.invoice_date, 'dd/mm/YYYY') as invoice_date"),
            'bills.claim_no',
            'provider.provider',
            // 'bills.invoice_date',
            'bills.diag_code',
        )
            ->join('provider', 'provider.code', '=', 'bills.provider')
            ->orderBy('claim_no', 'desc')
            ->distinct('claim_no')
            ->where('anniv', $claim[0]->anniv)
            ->where('member_no', $claim[0]->member_no)
            ->get();
        foreach ($visit_rate_data as $vrd) {
            $diag_name = '';
            $diagnosis = MemberDiagnosis::select('clinical_diagnosis.clinical_diagnosis')
                ->join('clinical_diagnosis', 'clinical_diagnosis.idx', '=', 'member_diagnosis.diagnosis')
                ->where('claim_no', $vrd->claim_no)
                ->get();
            foreach ($diagnosis as $diag) {
                $diag_name .= $diag->clinical_diagnosis . ', ';
            }
            $vrd->diag_code = $diag_name;
        }

        //check waiting period
        $member_benefit_data = MemberBenefits::select(
            DB::raw('isnull(member_benefits.waiting_period, 0) as waiting_period'),
            'member_anniversary.start_date'
        )
            ->join('member_anniversary', function ($join) {
                $join->on('member_anniversary.member_no', '=', 'member_benefits.member_no')
                    ->on('member_anniversary.anniv', '=', 'member_benefits.anniv');
            })
            ->where('member_benefits.member_no', $claim[0]->member_no)
            ->where('member_benefits.anniv', $claim[0]->anniv)
            ->where('member_benefits.benefit', $claim[0]->benefit_code)
            ->get();

        $waiting_period = count($member_benefit_data) != 0 ? $member_benefit_data[0]->waiting_period : 0;
        $start_date = count($member_benefit_data) != 0 ? $member_benefit_data[0]->start_date : '';


        //check provider status (cancelled, end_date, cancellation/suspension)
        $provider_status = '';
        $provider_data = Provider::select('fax_no', 'end_date', 'cancel_suspend_date')->where('code', $claim[0]->provider_code)->get();
        $provider_cancelled_status = count($provider_data) != 0 ? $provider_data[0]->fax_no : 0;
        $provider_cancel_suspend_date = count($provider_data) != 0 ? $provider_data[0]->cancel_suspend_date  : '';
        $provider_end_date = count($provider_data) != 0 ? date("Y-m-d", strtotime($provider_data[0]->end_date . "+1 day")) : '';
        if ($provider_cancelled_status != 'ACTIVE' && !empty($provider_cancel_suspend_date)) {
            if ($claim[0]->invoice_date > $provider_cancel_suspend_date) {
                $provider_status = '2';
            } else {
                $provider_status = '1';
            }
        } else {
            $provider_status = '1';
        }

        array_push($data, ['visit_rate' => $visit_rate]);
        array_push($data, ['claim_data' => $claim]);
        array_push($data, ['visit_rate_data' => $visit_rate_data]);
        array_push($data, ['waiting_period' => $waiting_period]);
        array_push($data, ['start_date' => $start_date]);
        array_push($data, ['provider_status' => $provider_status]);
        array_push($data, ['provider_cancel_suspend_date' => $provider_cancel_suspend_date]);

        //check if provider was active when bill was created

        // return $data;
        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }

    public function fetchClaimByIdAll($id)
    {
        $claim = Bills::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
            DB::raw("FORMAT(member_info.dob, 'yyyy-mm-dd') as dob"),
            DB::raw("FORMAT(bills.invoice_date, 'yyyy-mm-dd') as invoice_date"),
            DB::raw("FORMAT(bills.date_entered, 'yyyy-mm-dd') as date_entered"),
            DB::raw("FORMAT(claim_form.visit_date, 'yyyy-mm-dd') as visit_date"),
            DB::raw("FORMAT(claim_form.doctor_date, 'yyyy-mm-dd') as doctor_date"),
            'service.code as service_code',
            'service.service',
            'provider.code as provider_code',
            'provider.provider',
            'bills.claim_no',
            'bills.invoice_no',
            'bills.member_no',
            'bills.invoiced_amount',
            'bills.amount_payable',
            'bills.payee',
            'bills.voucher_no',
            'bills.id',
            't_product_name.code',
            't_product_name.product_name',
            'bills.batch_no',
            'bills.smart_bill_id',
            'benefit.code',
            'benefit.benefit',
            'bills.anniv',
            'bills.integ',
            'bills.user_id',
            'bills.id',
            'bills.claim_no',
            'bills.vet_status',
            't_gender.gender',
            'family_relation.relation',
            'claim_form.doctor_sign',
            'claim_form.claim_form_signed',


        )
            ->leftJoin('provider', 'bills.provider', '=', 'provider.code')
            ->leftJoin('service', 'bills.service', '=', 'service.code')
            ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
            ->leftJoin('t_product_name', 'bills.product_name', '=', 't_product_name.code')
            ->leftJoin('benefit', 'bills.claim_nature', '=', 'benefit.code')
            ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
            ->leftJoin('family_relation', 'family_relation.code', '=', 'member_info.relation_to_principal')
            ->leftJoin('claim_form', 'bills.claim_no', 'claim_form.claim_no')
            ->where('bills.id', $id)
            ->get();


        return $claim;
    }

    public function fetchMemberDiagnosis(Request $request)
    {
        $member_diagnosis = MemberDiagnosis::select('member_diagnosis.member_no', 'member_diagnosis.claim_no', 'clinical_diagnosis.clinical_diagnosis')
            ->where('member_diagnosis.member_no', $request->member_no)
            ->where('member_diagnosis.claim_no', $request->claim_no)
            ->join('clinical_diagnosis', 'member_diagnosis.diagnosis', 'clinical_diagnosis.idx')
            ->get();
        return json_encode($member_diagnosis, JSON_INVALID_UTF8_IGNORE);
    }
    public function fetchMemberExclusion(Request $request)
    {
        // $member_diagnosis = MemberDiagnosis::join('clinical_diagnosis', 'member_diagnosis.diagnosis', 'clinical_diagnosis.idx')
        //     ->where('member_diagnosis.member_no', $request->member_no)
        //     ->where('member_diagnosis.claim_no', $request->claim_no)
        //     ->pluck('clinical_diagnosis.diag_code');
        $exclusions =  explode(', ', $request->diag_exclusion);
        $member_exclusion = MemberExclusions::select('clinical_diagnosis.clinical_diagnosis')
            ->join('clinical_diagnosis', 'member_exclusions.diag_code', '=', 'clinical_diagnosis.diag_code')
            ->whereIn('clinical_diagnosis.clinical_diagnosis', $exclusions)
            ->where('member_exclusions.member_no', $request->member_no)
            ->get();
        return json_encode($member_exclusion, JSON_INVALID_UTF8_IGNORE);
    }

    public function fetchPreauthBills(Request $request)
    {
        $preauths = PreAuthorization::where('member_no', $request->member_no)
            ->where('authority_type', $request->benefit)
            ->where('anniv', $request->anniv)
            ->get();
        return $preauths;
    }
    public function fetchClaimsReserveForPreauth($preauth_no)
    {
        $reserve = ClaimsReserve::select(
            DB::raw("ISNULL(SUM(ISNULL(credit, 0)), 0) - ISNULL(SUM(ISNULL(debit, 0)), 0) as reserve")
        )
            ->where('pre_auth_no', $preauth_no)->get();
        return $reserve;
    }

    public function fetchClaimBalance(Request $request)
    {

        $data = $dt = array();
        // $total_claim_amt = $total_reserves = $total_limit = 0.00;
        $fam_no = MemberInfo::where('member_no', $request->member_no)->pluck('family_no');
        if (count($fam_no) === 0) {
            return [];
        }
        $sharing = MemberBenefits::where('member_benefits.benefit', $request->benefit)
            ->where('member_benefits.anniv', $request->anniv)
            ->where('member_benefits.member_no', $request->member_no)
            ->pluck('sharing');

        $limit = MemberBenefits::where('benefit', $request->benefit)
            ->where('anniv', $request->anniv)
            ->where('member_no', $request->member_no)
            ->pluck('limit');

        $limit = count($limit) === 0  ? 0.00 : $limit[0];

        if (!is_null($sharing[0])) {
            if ($sharing[0] == 1) {
                $member_nos = MemberInfo::where('family_no', $fam_no)->pluck('member_no');

                // foreach ($member_nos as $memno) {

                $claims_amt = Bills::select(
                    DB::raw("isnull(sum(isnull(CASE WHEN isnull(vet_status, 0) in (0, 5) 
                                            THEN invoiced_amount
                                    WHEN isnull(vet_status, 0) = 1 
                                            THEN amount_payable                                      
                                      END, 0)),0) as claims_amt")
                )
                    ->where('claim_nature', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->whereIn('member_no', $member_nos)
                    ->get();

                $claims_amt = count($claims_amt) === 0 ? 0.00 :  $claims_amt[0]->claims_amt;


                $reserves = ClaimsReserve::select(

                    DB::raw("isnull(sum(isnull(claims_reserve.credit,0)), 0) - isnull(sum(isnull(claims_reserve.debit,0)),0) as reserves"),
                )
                    ->where('benefit', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->whereIn('member_no', $member_nos)
                    ->get();


                $reserves = count($reserves) === 0 ? 0 :  $reserves[0]->reserves;

                // $total_claim_amt += $claims_amt;
                // $total_reserves += $reserve;
                // }
                // $balance = $limit - ($total_claim_amt + $total_reserves);
                //calculating balance we don't include reserves
                $balance = $limit - $claims_amt;
                $dt = ['claims' => $claims_amt, 'reserves' => 0, 'limit' => $limit, 'balance' => $balance];

                array_push($data, $dt);
            } else {
                $claims_amt = Bills::select(
                    DB::raw("isnull(sum(isnull(CASE WHEN isnull(bills.vet_status, 0) in (0, 5) 
                                            THEN bills.invoiced_amount
                                    WHEN isnull(vet_status, 0) = 1 
                                            THEN bills.amount_payable                                      
                                      END, 0)),0) as claims_amt")
                )
                    ->where('claim_nature', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->where('member_no', $request->member_no)
                    ->get();

                $claims_amt = count($claims_amt) === 0 ? 0.00 :  $claims_amt[0]->claims_amt;


                $reserve = ClaimsReserve::select(
                    DB::raw("isnull(sum(isnull(claims_reserve.credit,0)), 0) - isnull(sum(isnull(claims_reserve.debit,0)), 0) as reserves"),
                )
                    ->where('benefit', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->where('member_no', $request->member_no)
                    ->get();


                $reserve = count($reserve) === 0 ? 0 :  $reserve[0]->reserves;

                // $balance = $limit - ($claims_amt + $reserve);
                //calculating balance we don't include reserves
                $balance = $limit - $claims_amt;

                // $dt = ['claims' => $claims_amt, 'reserves' => $reserve, 'limit' => $limit, 'balance' => $balance];
                //the reserve is no longer included when calculating balance
                $dt = ['claims' => $claims_amt, 'reserves' => 0, 'limit' => $limit, 'balance' => $balance];

                array_push($data, $dt);
            }
        }
        return $data;
    }
    public function fetchClaimBalanceCare(Request $request)
    {

        $data = $dt = array();
        // $total_claim_amt = $total_reserves = $total_limit = 0.00;
        $fam_no = MemberInfo::where('member_no', $request->member_no)->pluck('family_no');
        if (count($fam_no) === 0) {
            return [];
        }
        $sharing = MemberBenefits::where('member_benefits.benefit', $request->benefit)
            ->where('member_benefits.anniv', $request->anniv)
            ->where('member_benefits.member_no', $request->member_no)
            ->pluck('sharing');

        $limit = MemberBenefits::where('benefit', $request->benefit)
            ->where('anniv', $request->anniv)
            ->where('member_no', $request->member_no)
            ->pluck('limit');

        $limit = count($limit) === 0  ? 0.00 : $limit[0];

        if (!is_null($sharing[0])) {
            if ($sharing[0] == 1) {
                $member_nos = MemberInfo::where('family_no', $fam_no)->pluck('member_no');

                // foreach ($member_nos as $memno) {

                $claims_amt = Bills::select(
                    DB::raw("isnull(sum(isnull(CASE WHEN isnull(vet_status, 0) in (0, 5) 
                                            THEN invoiced_amount
                                    WHEN isnull(vet_status, 0) = 1 
                                            THEN amount_payable                                      
                                      END, 0)),0) as claims_amt")
                )
                    ->where('claim_nature', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->whereIn('member_no', $member_nos)
                    ->get();

                $claims_amt = count($claims_amt) === 0 ? 0.00 :  $claims_amt[0]->claims_amt;


                $reserves = ClaimsReserve::select(

                    DB::raw("isnull(sum(isnull(claims_reserve.credit,0)), 0) - isnull(sum(isnull(claims_reserve.debit,0)),0) as reserves"),
                )
                    ->where('benefit', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->whereIn('member_no', $member_nos)
                    ->get();


                $reserves = count($reserves) === 0 ? 0 :  $reserves[0]->reserves;

                // $total_claim_amt += $claims_amt;
                // $total_reserves += $reserve;
                // }
                // $balance = $limit - ($total_claim_amt + $total_reserves);
                //calculating balance we don't include reserves
                $balance = $limit - $claims_amt;
                $dt = ['claims' => $claims_amt, 'reserves' => 0, 'limit' => $limit, 'balance' => $balance];

                array_push($data, $dt);
            } else {
                $claims_amt = Bills::select(
                    DB::raw("isnull(sum(isnull(CASE WHEN isnull(bills.vet_status, 0) in (0, 5) 
                                            THEN bills.invoiced_amount
                                    WHEN isnull(vet_status, 0) = 1 
                                            THEN bills.amount_payable                                      
                                      END, 0)),0) as claims_amt")
                )
                    ->where('claim_nature', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->where('member_no', $request->member_no)
                    ->get();

                $claims_amt = count($claims_amt) === 0 ? 0.00 :  $claims_amt[0]->claims_amt;


                $reserve = ClaimsReserve::select(
                    DB::raw("isnull(sum(isnull(claims_reserve.credit,0)), 0) - isnull(sum(isnull(claims_reserve.debit,0)), 0) as reserves"),
                )
                    ->where('benefit', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->where('member_no', $request->member_no)
                    ->get();


                $reserve = count($reserve) === 0 ? 0 :  $reserve[0]->reserves;

                $balance = $limit - ($claims_amt + $reserve);

                $dt = ['claims' => $claims_amt, 'reserves' => $reserve, 'limit' => $limit, 'balance' => $balance];

                array_push($data, $dt);
            }
        }
        return $data;
    }

    public function fetchMainBalance(Request $request)
    {

        $sharing = [];
        // $total_claims = $total_reserves = 0.00;
        $balance_data = array();
        //family_no and member nos
        $fam_no = MemberInfo::where('member_no', $request->member_no)->pluck('family_no');
        if (count($fam_no) === 0) {
            return [];
        }
        $member_nos = MemberInfo::where('family_no', $fam_no[0])->pluck('member_no');

        //getting the main benefit
        $bene = MemberBenefits::where('benefit', $request->benefit)
            ->where('anniv', $request->anniv)
            ->where('member_no', $request->member_no)
            ->pluck('sub_limit_of');


        //getting sharing for the main benefit
        if (is_null($bene[0])) {
            $bene = $request->benefit;
            $sharing = MemberBenefits::where('anniv', $request->anniv)
                ->where('benefit', $bene)
                ->where('member_no', $request->member_no)
                ->pluck('sharing');
        } else {
            $bene = $bene[0];
            $sharing = MemberBenefits::where('anniv', $request->anniv)
                ->where('benefit', $bene)
                ->where('member_no', $request->member_no)
                ->pluck('sharing');
        }


        $sharing = count($sharing) === 0 ? 0 : $sharing[0];
        //getting limit 
        $limit = MemberBenefits::where('anniv', $request->anniv)
            ->where('benefit', $bene)
            ->where('member_no', $request->member_no)
            ->pluck('limit');

        $limit = count($limit) === 0 ? 0.00 : $limit[0];



        if ($sharing == 1) {

            //getting claims per family
            // foreach ($member_nos as $memno) {
            //fetch all benefits for each member per main benefit (including main benefit)
            // $benefits = MemberBenefits::where(function ($q) use ($bene) {
            //     $q->where('sub_limit_of', $bene)
            //         ->orWhere('benefit', $bene);
            // })
            //     ->where('anniv', $request->anniv)
            //     ->whereIn('member_no', $member_nos)
            //     ->pluck('benefit');


            //fetch sum of all claims for each benefit (vetted & unvetted)
            // foreach ($benefits as $benefit) {
            $claims_amt = Bills::select(
                DB::raw("SUM(ISNULL(bills.invoiced_amount, 0)) - SUM(ISNULL(bills.deduction_amount, 0)) as claims_amt")

            )
                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'bills.member_no')
                        ->on('member_benefits.benefit', '=', 'bills.claim_nature')
                        ->on('member_benefits.anniv', '=', 'bills.anniv');
                })
                ->where('bills.anniv', $request->anniv)
                ->where(function ($q) use ($bene) {
                    $q->where('member_benefits.sub_limit_of', $bene)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->whereIn('bills.member_no', $member_nos)
                ->get();

            $claims_amt = count($claims_amt) !== 0 ? $claims_amt[0]->claims_amt : 0.00;


            $reserve = ClaimsReserve::select(
                DB::raw("isnull(sum(isnull(credit,0)),0) - isnull(sum(isnull(debit,0)),0) as reserves")
            )

                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'claims_reserve.member_no')
                        ->on('member_benefits.benefit', '=', 'claims_reserve.claim_nature')
                        ->on('member_benefits.anniv', '=', 'claims_reserve.anniv');
                })
                ->where('claims_reserve.anniv', $request->anniv)
                ->where(function ($q) use ($bene) {
                    $q->where('member_benefits.sub_limit_of', $bene)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->whereIn('claims_reserve.member_no', $member_nos)
                ->get();

            $reserve = count($reserve) !== 0 ? $reserve[0]->reserves : 0.00;


            // $total_claims += $claims_amt;
            // $total_reserves += $reserve;
            // }
            // }
            // $balance = $limit - ($total_claims + $total_reserves);
            //no longer using reserve to calculate balance
            $balance = $limit - $claims_amt;
            $dt = [
                'limit' => $limit,
                'claims' => $claims_amt,
                'reserves' => 0,
                'balance' => $balance
            ];
            array_push($balance_data, $dt);
        } else {
            //getting claims 
            // $benefits = MemberBenefits::where(function ($q) use ($request) {
            //     $q->where('sub_limit_of', $request->benefit)
            //         ->orWhere('benefit', $request->benefit);
            // })
            //     ->where('anniv', $request->anniv)
            //     ->where('member_no', $request->member_no)
            //     ->pluck('benefit');
            //fetch sum of all claims for each benefit (vetted & unvetted)
            // foreach ($benefits as $benefit) {
            $claims_amt = Bills::select(
                DB::raw("SUM(ISNULL(bills.invoiced_amount, 0)) - SUM(ISNULL(bills.deduction_amount, 0)) as claims_amt")

            )
                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'bills.member_no')
                        ->on('member_benefits.benefit', '=', 'bills.claim_nature')
                        ->on('member_benefits.anniv', '=', 'bills.anniv');
                })
                ->where('bills.anniv', $request->anniv)
                ->where(function ($q) use ($bene) {
                    $q->where('member_benefits.sub_limit_of', $bene)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->where('bills.member_no', $request->member_no)
                ->get();

            $claims_amt = count($claims_amt) !== 0 ? $claims_amt[0]->claims_amt : 0.00;


            $reserve = ClaimsReserve::select(
                DB::raw("isnull(sum(isnull(credit,0)),0) - isnull(sum(isnull(debit,0)),0) as reserves")
            )

                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'claims_reserve.member_no')
                        ->on('member_benefits.benefit', '=', 'claims_reserve.claim_nature')
                        ->on('member_benefits.anniv', '=', 'claims_reserve.anniv');
                })
                ->where('claims_reserve.anniv', $request->anniv)
                ->where(function ($q) use ($bene) {
                    $q->where('member_benefits.sub_limit_of', $bene)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->where('claims_reserve.member_no', $request->member_no)
                ->get();

            $reserve = count($reserve) !== 0 ? $reserve[0]->reserves : 0.00;

            // $total_claims += $claims;
            // $total_reserves += $reserve;
            // }


            // $balance = $limit - ($total_claims + $total_reserves);
            //no longer using reserve to calculate balance
            $balance = $limit - $claims_amt;

            $dt = [
                'limit' => $limit,
                'claims' => $claims_amt,
                'reserves' => 0,
                'balance' => $balance
            ];

            array_push($balance_data, $dt);
        }

        return $balance_data;
    }
    public function fetchMainBalanceCare(Request $request)
    {

        $sharing = [];
        // $total_claims = $total_reserves = 0.00;
        $balance_data = array();
        //family_no and member nos
        $fam_no = MemberInfo::where('member_no', $request->member_no)->pluck('family_no');
        if (count($fam_no) === 0) {
            return [];
        }
        $member_nos = MemberInfo::where('family_no', $fam_no[0])->pluck('member_no');

        //getting the main benefit
        $bene = MemberBenefits::where('benefit', $request->benefit)
            ->where('anniv', $request->anniv)
            ->where('member_no', $request->member_no)
            ->pluck('sub_limit_of');


        //getting sharing for the main benefit
        if (is_null($bene[0])) {
            $bene = $request->benefit;
            $sharing = MemberBenefits::where('anniv', $request->anniv)
                ->where('benefit', $bene)
                ->where('member_no', $request->member_no)
                ->pluck('sharing');
        } else {
            $bene = $bene[0];
            $sharing = MemberBenefits::where('anniv', $request->anniv)
                ->where('benefit', $bene)
                ->where('member_no', $request->member_no)
                ->pluck('sharing');
        }


        $sharing = count($sharing) === 0 ? 0 : $sharing[0];
        //getting limit 
        $limit = MemberBenefits::where('anniv', $request->anniv)
            ->where('benefit', $bene)
            ->where('member_no', $request->member_no)
            ->pluck('limit');

        $limit = count($limit) === 0 ? 0.00 : $limit[0];



        if ($sharing == 1) {

            //getting claims per family
            // foreach ($member_nos as $memno) {
            //fetch all benefits for each member per main benefit (including main benefit)
            // $benefits = MemberBenefits::where(function ($q) use ($bene) {
            //     $q->where('sub_limit_of', $bene)
            //         ->orWhere('benefit', $bene);
            // })
            //     ->where('anniv', $request->anniv)
            //     ->where('member_no', $memno)
            //     ->pluck('benefit');


            //fetch sum of all claims for each benefit (vetted & unvetted)
            // foreach ($benefits as $benefit) {
            $claims_amt = Bills::select(
                DB::raw("SUM(ISNULL(bills.invoiced_amount, 0)) - SUM(ISNULL(bills.deduction_amount, 0)) as claims_amt")

            )
                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'bills.member_no')
                        ->on('member_benefits.benefit', '=', 'bills.claim_nature')
                        ->on('member_benefits.anniv', '=', 'bills.anniv');
                })
                ->where('bills.anniv', $request->anniv)
                ->where(function ($q) use ($bene) {
                    $q->where('member_benefits.sub_limit_of', $bene)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->whereIn('bills.member_no', $member_nos)
                ->get();

            $claims_amt = count($claims_amt) !== 0 ? $claims_amt[0]->claims_amt : 0.00;


            $reserve = ClaimsReserve::select(
                DB::raw("isnull(sum(isnull(credit,0)),0) - isnull(sum(isnull(debit,0)),0) as reserves")
            )

                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'claims_reserve.member_no')
                        ->on('member_benefits.benefit', '=', 'claims_reserve.claim_nature')
                        ->on('member_benefits.anniv', '=', 'claims_reserve.anniv');
                })
                ->where('claims_reserve.anniv', $request->anniv)
                ->where(function ($q) use ($bene) {
                    $q->where('member_benefits.sub_limit_of', $bene)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->whereIn('claims_reserve.member_no', $member_nos)
                ->get();

            $reserve = count($reserve) !== 0 ? $reserve[0]->reserves : 0.00;


            // $total_claims += $claims_amt;
            // $total_reserves += $reserve;
            // }
            // }
            $balance = $limit - ($claims_amt + $reserve);
            $dt = [
                'limit' => $limit,
                'claims' => $claims_amt,
                'reserves' =>  $reserve,
                'balance' => $balance
            ];
            array_push($balance_data, $dt);
        } else {
            //getting claims 
            // $benefits = MemberBenefits::where(function ($q) use ($request) {
            //     $q->where('sub_limit_of', $request->benefit)
            //         ->orWhere('benefit', $request->benefit);
            // })
            //     ->where('anniv', $request->anniv)
            //     ->where('member_no', $request->member_no)
            //     ->pluck('benefit');
            //fetch sum of all claims for each benefit (vetted & unvetted)
            // foreach ($benefits as $benefit) {
            $claims_amt = Bills::select(
                DB::raw("SUM(ISNULL(bills.invoiced_amount, 0)) - SUM(ISNULL(bills.deduction_amount, 0)) as claims_amt")

            )
                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'bills.member_no')
                        ->on('member_benefits.benefit', '=', 'bills.claim_nature')
                        ->on('member_benefits.anniv', '=', 'bills.anniv');
                })
                ->where('bills.anniv', $request->anniv)
                ->where(function ($q) use ($bene) {
                    $q->where('member_benefits.sub_limit_of', $bene)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->where('bills.member_no', $request->member_no)
                ->get();

            $claims_amt = count($claims_amt) !== 0 ? $claims_amt[0]->claims_amt : 0.00;


            $reserve = ClaimsReserve::select(
                DB::raw("isnull(sum(isnull(credit,0)),0) - isnull(sum(isnull(debit,0)),0) as reserves")
            )

                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'claims_reserve.member_no')
                        ->on('member_benefits.benefit', '=', 'claims_reserve.claim_nature')
                        ->on('member_benefits.anniv', '=', 'claims_reserve.anniv');
                })
                ->where('claims_reserve.anniv', $request->anniv)
                ->where(function ($q) use ($bene) {
                    $q->where('member_benefits.sub_limit_of', $bene)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->where('claims_reserve.member_no', $request->member_no)
                ->get();

            $reserve = count($reserve) !== 0 ? $reserve[0]->reserves : 0.00;

            // $total_claims += $claims;
            // $total_reserves += $reserve;
            // }


            $balance = $limit - ($claims_amt + $reserve);

            $dt = [
                'limit' => $limit,
                'claims' => $claims_amt,
                'reserves' => $reserve,
                'balance' => $balance
            ];

            array_push($balance_data, $dt);
        }

        return $balance_data;
    }


    public function deleteDiagnosis(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $clinical_diagnosis_idx = ClinicalDiagnosis::where('clinical_diagnosis', $request->diagnosis)->pluck('idx');
                $clinical_diagnosis_idx = count($clinical_diagnosis_idx) != 0 ? $clinical_diagnosis_idx[0] : '';

                MemberDiagnosis::where([
                    'claim_no' => $request->claim_no,
                    'member_no' => $request->member_no,
                    'diagnosis' => $clinical_diagnosis_idx,
                ])
                    ->delete();
            });
            return ['Diagnosis successfully deleted.'];
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }

    //fetch claims line items
    public function fetchClaimsLineItems(Request $request)
    {
        // return $request;
        $data = [];
        $line_items = LineItems::select(
            DB::raw("FORMAT(line_items.date_entered, 'yyyy-mm-dd') as date_entered"),
            'line_items.item_code',
            'line_items.invoice_no',
            'line_items.service as service_code',
            'service.service',
            'line_items.provider as provider_code',
            'provider.provider',
            'line_items.idx',
            'line_items.category',
            'line_items.service_items',
            'line_items.unit_of_measure',
            'line_items.invoiced_amt',  // THIS AMOUNT IS FOR I QUANTITY NOT A COMBINATION
            'line_items.item_class',
            'line_items.user_id',
            'line_items.year', //this is a code not the actual year
            'line_items.quantity',
            'line_items.recomm_amt',
            'line_items.variation',
            'line_items.claim_no',
            'line_items.smart_bill_id',
            'line_items.auto_vetted',
            'line_items.adjusted_amt',
            'line_items.parent_id'
        )
            ->leftJoin('provider', 'provider.code', '=', 'line_items.provider')
            ->leftJoin('service', 'service.code', '=', 'line_items.service')
            ->where('line_items.claim_no', $request->claim_no)
            ->where('line_items.invoice_no', $request->invoice_no)
            ->where('line_items.service', $request->service_code)
            ->get();


        foreach ($line_items as $item) {
            //check recommended unit price
            // $price = Tariffs::select('price')
            // ->where('provider', $item->provider_code)
            // ->where('item_code', $item->item_code)
            // ->where('year', $item->year)
            // ->get();            
            // $recommended_unit_price = count($price) === 0 ? 0 : $price[0]->price;

            //get actual year
            $year = TariffYear::where('code', $item->year)->pluck('year');
            $year = count($year) > 0 ? $year[0] : null;

            $price = Tariffs::selectRaw('TOP 1 price')
                ->where('provider', $item->provider_code)
                ->where('item_code', $item->item_code)
                ->where('year', $item->year)
                ->orderBy('date_entered', 'desc')
                ->get()
                ->pluck('price')
                ->first() ?? 0;
            $recommended_unit_price = $price;
            $invoice_unit_price = $item->invoiced_amt;   // INVOICED AMOUNT IS SAME AS UNIT PRICE
            $total_invoiced_amount = $item->invoiced_amt * $item->quantity;
            $recommended_cost = $recommended_unit_price * $item->quantity;
            $overcharge = $total_invoiced_amount > $recommended_cost ? $total_invoiced_amount - $recommended_cost : 0;
            $auto_vetted = $overcharge > 0 ? 2 : 1;

            $dt = [
                'date_entered' => $item->date_entered,
                'item_code' => $item->item_code,
                'invoice_no' => $item->invoice_no,
                'service_code' => $item->service_code,
                'service' => $item->service,
                'provider_code' => $item->provider_code,
                'provider' => $item->provider,
                'idx' => $item->idx,
                'category' => $item->category,
                'service_items' => $item->service_items,
                'unit_of_measure' => $item->unit_of_measure,
                'invoiced_amt' => $total_invoiced_amount,
                'item_class' => $item->item_class,
                'user_id' => $item->user_id,
                'year' => $year,
                'quantity' => $item->quantity,
                'recomm_amt' => $item->recomm_amt,
                'variation' => $item->variation,
                'claim_no' => $item->claim_no,
                'smart_bill_id' => $item->smart_bill_id,
                'adjusted_amt' => $item->adjusted_amt,
                'parent_id' => $item->parent_id,
                'unit_price' => $invoice_unit_price,
                'recommended_unit_price' => $recommended_unit_price,
                'provider_cost' => $item->invoiced_amt,
                'recommended_cost' => $recommended_cost,
                'overcharge' => $overcharge,
                2,
                'auto_vetted' => $auto_vetted
            ];

            array_push($data, $dt);
        }

        return $data;
    }

    public function saveVet(Request $request)
    {
        // return $request;
        $response = array();
        $max_id = BillVet::max('id') + 1;
        $date_entered = date("Y-m-d");

        //check if vetter is same as creator
        $bill_creator = Bills::where('id', $request->id)->pluck('user_id');
        $bill_creator = $bill_creator[0];

        if ($bill_creator != $request->user) {
            try {
                DB::transaction(function () use ($request, $max_id, $date_entered) {
                    //set inserted, updated, and deleted items
                    $inserted_item = "id : $max_id, claim_no : $request->claim_no, vet_status : $request->vet_status";
                    $updated_item = "id : $request->id,  vet_status : $request->vet_status, provider : $request->provider, inv_amt : $request->invoiced_amount, ded_amt : $request->deduction_amount, original_amt_payable : $request->original_amount_payable, final_amt_payable : $request->amount_payable, original_benefit : $request->original_benefit, final_benefit : $request->benefit, vet_status : $request->vet_status";
                    $deleted_item = null;

                    //save in vet bill
                    BillVet::create([
                        'id' => $max_id,
                        'claim_no' => $request->claim_no,
                        'invoice_no' => $request->invoice_no,
                        'service' => (int)$request->service,
                        'provider' => (int)$request->provider,
                        'vet_status' => (int)$request->vet_status,
                        'reasons' => (int)$request->bill_reason,
                        'remarks' => $request->remarks,
                        'user_id' => $request->change_benefit_user,
                        'date_entered' => $date_entered
                    ]);

                    //update vet status of particular claim in bills
                    Bills::where(
                        'id',
                        $request->id
                    )
                        ->update([
                            'vet_status' => (int)$request->vet_status,
                            'deduction_amount' => (int)$request->deduction_amount,
                            'overcharge_deduction_amount' => (int)$request->overcharge_deduction_amount,
                            'deduction_reason' => $request->deduction_reason,
                            'invoiced_amount' => (int)$request->invoiced_amount,
                            'amount_payable' => (int)$request->amount_payable,
                            'deduction_notes' => $request->deduction_notes,
                        ]);

                    //update claim nature for all claims with the claim no 
                    if (isset($request->benefit)) {
                        Bills::where(
                            'claim_no',
                            $request->claim_no
                        )
                            ->update([
                                'claim_nature' => $request->benefit,
                            ]);
                    }

                    //update claim info in claim form
                    ClaimForm::where('claim_no', $request->claim_no)
                        ->update([
                            'date_admitted' => $request->date_admitted,
                            'date_discharged' => $request->date_discharged
                        ]);

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
                    //important variables for smart bill returns cms bill returns
                    $max_id_cms_bill_returns = CmsBillReturns::max('id');

                    $family_no = explode("-", $request->member_no);
                    $family_no = $family_no[0];
                    $corp_id = PrincipalApplicant::select('corp_id')->where('family_no', $family_no)->get();
                    $corp_id =  count($corp_id) !== 0 ?  $corp_id[0]->corp_id : null;

                    //save manual and smart bills in different bill returns tables              
                    //check if its a smart bill
                    $smart_bill = MemberAnniversary::select('sync')->where('member_no', $request->member_no)->where('anniv', $request->anniv)->get();
                    if (!empty($smart_bill)) { //smart bill

                        if ($request->smart_bill_id > 0 && $request->vet_status == 1 && $request->deduction_amount > 0) {  //deduction bill
                            SmartBillReturns::create([
                                'return_amt' => isset($request->overcharge_deduction_amount) ? $request->deduction_amount + $request->overcharge_deduction_amount :  $request->deduction_amount, //factor in vet by line items module
                                'return_code' => 7,
                                'return_reason' => "part payment",
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'smart_bill_id' => $request->smart_bill_id,
                                'smart_provider' => (string)$request->provider,
                                'smart_benefit' => (string)$request->benefit,
                                'provider' => (int)$request->provider,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        } else if ($request->smart_bill_id == 0 && $request->vet_status == 1) { // manual bill
                            SmartBillReturns::create([
                                'return_amt' => $request->amount_payable,
                                'return_code' => 9,
                                'return_reason' => "non smart bill",
                                'action_code' => 2,
                                'action_name' => "Debit/minus",
                                'smart_bill_id' => $request->smart_bill_id,
                                'smart_provider' => (string)$request->provider,
                                'smart_benefit' => (string)$request->benefit,
                                'provider' => (int)$request->provider,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        } else if ($request->smart_bill_id > 0 && $request->vet_status == 2) { // rejection bill
                            SmartBillReturns::create([
                                'return_amt' => $request->invoiced_amount,
                                'return_code' => 8,
                                'return_reason' => "rejected",
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'smart_bill_id' => $request->smart_bill_id,
                                'smart_provider' => (string)$request->provider,
                                'smart_benefit' => (string)$request->benefit,
                                'provider' => (int)$request->provider,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        }
                    } else { //non smart bill   
                        //get practice no to be used as provider
                        $practice_no = Provider::select('practice_no')->where('code', $request->provider)->get();
                        $practice_no = empty($practice_no) ? NULL :  $practice_no[0]->practice_no;
                        $max_id_cms_bill_returns += 1;
                        if ($request->smart_bill_id > 0 && $request->vet_status == 1 && $request->deduction_amount > 0) {  //deduction bill
                            CmsBillReturns::create([
                                'id' => $max_id_cms_bill_returns,
                                'return_amt' => isset($request->overcharge_deduction_amount) ? $request->deduction_amount + $request->overcharge_deduction_amount :  $request->deduction_amount,
                                'return_code' => 7,
                                'return_reason' => "part payment",
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'provider' => $practice_no,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        } else if ($request->smart_bill_id == 0 && $request->vet_status == 1) { // manual bill
                            CmsBillReturns::create([
                                'id' => $max_id_cms_bill_returns,
                                'return_amt' => $request->amount_payable,
                                'return_code' => 9,
                                'return_reason' => "non smart bill",
                                'action_code' => 2,
                                'action_name' => "Debit/minus",
                                'provider' => $practice_no,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        } else if ($request->smart_bill_id > 0 && $request->vet_status == 2) { // rejection bill
                            CmsBillReturns::create([
                                'id' => $max_id_cms_bill_returns,
                                'return_amt' => $request->invoiced_amount,
                                'return_code' => 8,
                                'return_reason' => "rejected",
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'provider' => $practice_no,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        }
                    }
                    //save benefit change in smart bills return
                    if (isset($request->benefit)) {
                        if ($request->benefit !== $request->original_benefit) {

                            //save original bill credit
                            SmartBillReturns::create([
                                // 'return_amt' => $request->original_amount_payable,  
                                'return_amt' => $request->invoiced_amount,
                                'return_code' => 7,
                                'return_reason' => "Benefit Change",
                                'date_entered' => $date_entered,
                                'smart_picked' => 0,
                                'member_no' => $request->member_no,
                                'smart_benefit' => $request->original_benefit,
                                'anniv' => $request->anniv,
                                'service' => (int)$request->service,
                                'provider' => (int)$request->provider,
                                'invoice_no' => $request->invoice_no,
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'smart_provider' => (int)$request->provider,
                                'benefit' => $request->original_benefit,
                                'user_id' => $request->change_benefit_user,
                                'sent_to_smart' => 0,
                                'product_name' => $request->product_name,
                                'corp_id' => $corp_id,
                                'family_no' => $family_no,
                                'invoice_date' => $request->invoice_date,
                                'change_benefit_module_name' => $request->module,
                                'claim_changed' => $request->claim_no
                            ]);

                            //save new bill debit
                            SmartBillReturns::create([
                                // 'return_amt' => $request->amount_payable, 
                                'return_amt' => $request->invoiced_amount,
                                'return_code' => 9,
                                'return_reason' => "Benefit Change",
                                'date_entered' => $date_entered,
                                'smart_picked' => 0,
                                'member_no' => $request->member_no,
                                'smart_benefit' => $request->benefit,
                                'anniv' => $request->anniv,
                                'service' => (int)$request->service,
                                'provider' => (int)$request->provider,
                                'invoice_no' => $request->invoice_no,
                                'action_code' => 2,
                                'action_name' => "Debit/minus",
                                'smart_provider' => (int)$request->provider,
                                'benefit' => $request->benefit,
                                'user_id' => $request->change_benefit_user,
                                'sent_to_smart' => 0,
                                'product_name' => $request->product_name,
                                'corp_id' => $corp_id,
                                'family_no' => $family_no,
                                'invoice_date' => $request->invoice_date,
                                'change_benefit_module_name' => $request->module
                            ]);
                        }
                    }

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                });
                array_push($response, "Claim vetted successfully.");
            } catch (Exception $e) {
                array_push($response, "Error " . $e->getMessage());
            }
        } else {
            array_push($response, "You cannot create and vet the same claim!");
        }
        return $response;
    }
    public function saveVetAnyway(Request $request)
    {
        // return $request;
        $response = array();
        $max_id = BillVet::max('id') + 1;
        $date_entered = date("Y-m-d");

        //check if vetter is same as creator
        $bill_creator = Bills::where('id', $request->id)->pluck('user_id');
        $bill_creator = $bill_creator[0];

        if ($bill_creator != $request->user) {
            try {
                DB::transaction(function () use ($request, $max_id, $date_entered) {
                    //set inserted, updated, and deleted items
                    $inserted_item = "id : $max_id, claim_no : $request->claim_no";
                    $updated_item = "id : $request->id, claim_no : $request->claim_no, provider : $request->provider, inv_amt : $request->invoiced_amount, ded_amt : $request->deduction_amount, original_amt_payable : $request->original_amount_payable, final_amt_payable : $request->amount_payable, original_benefit : $request->original_benefit, final_benefit : $request->benefit, vet_status : $request->vet_status";
                    $deleted_item = null;

                    //save in vet bill
                    BillVet::create([
                        'id' => $max_id,
                        'claim_no' => $request->claim_no,
                        'invoice_no' => $request->invoice_no,
                        'service' => (int)$request->service,
                        'provider' => (int)$request->provider,
                        'vet_status' => 5,
                        'reasons' => (int)$request->bill_reason,
                        'remarks' => $request->remarks,
                        'user_id' => $request->change_benefit_user,
                        'date_entered' => $date_entered,
                        'approval_vet_status' => (int)$request->vet_status,
                        'approval_reason' => $request->approval_reason,
                        'bill_approval_id' => $request->id,
                    ]);

                    //update vet status of claim in bills
                    Bills::where(
                        'id',
                        $request->id
                    )
                        ->update([
                            'vet_status' => 5,
                            'deduction_amount' => (int)$request->deduction_amount,
                            'overcharge_deduction_amount' => (int)$request->overcharge_deduction_amount,
                            'deduction_reason' => $request->deduction_reason,
                            'invoiced_amount' => (int)$request->invoiced_amount,
                            'amount_payable' => (int)$request->amount_payable,
                            'deduction_notes' => $request->deduction_notes,
                        ]);

                    //update claim nature for all claims with the claim no 
                    if (isset($request->benefit)) {
                        Bills::where(
                            'claim_no',
                            $request->claim_no
                        )
                            ->update([
                                'claim_nature' => $request->benefit,
                            ]);
                    }

                    //update claim info in claim form
                    ClaimForm::where('claim_no', $request->claim_no)
                        ->update([
                            'date_admitted' => $request->date_admitted,
                            'date_discharged' => $request->date_discharged
                        ]);

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

                    //important variables for smart bill returns cms bill returns
                    $max_id_cms_bill_returns = CmsBillReturns::max('id');

                    $family_no = explode("-", $request->member_no);
                    $family_no = $family_no[0];
                    $corp_id = PrincipalApplicant::select('corp_id')->where('family_no', $family_no)->get();
                    $corp_id =  count($corp_id) !== 0 ?  $corp_id[0]->corp_id : null;

                    //save manual and smart bills in different bill returns tables              
                    //check if its a smart bill
                    $smart_bill = MemberAnniversary::select('sync')->where('member_no', $request->member_no)->where('anniv', $request->anniv)->get();
                    if (!empty($smart_bill)) { //smart bill

                        if ($request->smart_bill_id > 0 && $request->vet_status == 1 && $request->deduction_amount > 0) {  //deduction bill
                            SmartBillReturns::create([
                                'return_amt' => isset($request->overcharge_deduction_amount) ? $request->deduction_amount + $request->overcharge_deduction_amount :  $request->deduction_amount, //factor in vet by line items module
                                'return_code' => 7,
                                'return_reason' => "part payment",
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'smart_bill_id' => $request->smart_bill_id,
                                'smart_provider' => (string)$request->provider,
                                'smart_benefit' => (string)$request->benefit,
                                'provider' => (int)$request->provider,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        } else if ($request->smart_bill_id == 0 && $request->vet_status == 1) { // manual bill
                            SmartBillReturns::create([
                                'return_amt' => $request->amount_payable,
                                'return_code' => 9,
                                'return_reason' => "non smart bill",
                                'action_code' => 2,
                                'action_name' => "Debit/minus",
                                'smart_bill_id' => $request->smart_bill_id,
                                'smart_provider' => (string)$request->provider,
                                'smart_benefit' => (string)$request->benefit,
                                'provider' => (int)$request->provider,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        } else if ($request->smart_bill_id > 0 && $request->vet_status == 2) { // rejection bill
                            SmartBillReturns::create([
                                'return_amt' => $request->invoiced_amount,
                                'return_code' => 8,
                                'return_reason' => "rejected",
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'smart_bill_id' => $request->smart_bill_id,
                                'smart_provider' => (string)$request->provider,
                                'smart_benefit' => (string)$request->benefit,
                                'provider' => (int)$request->provider,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        }
                    } else { //non smart bill   
                        //get practice no to be used as provider
                        $practice_no = Provider::select('practice_no')->where('code', $request->provider)->get();
                        $practice_no = empty($practice_no) ? NULL :  $practice_no[0]->practice_no;
                        $max_id_cms_bill_returns += 1;
                        if ($request->smart_bill_id > 0 && $request->vet_status == 1 && $request->deduction_amount > 0) {  //deduction bill
                            CmsBillReturns::create([
                                'id' => $max_id_cms_bill_returns,
                                'return_amt' => isset($request->overcharge_deduction_amount) ? $request->deduction_amount + $request->overcharge_deduction_amount :  $request->deduction_amount,
                                'return_code' => 7,
                                'return_reason' => "part payment",
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'provider' => $practice_no,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        } else if ($request->smart_bill_id == 0 && $request->vet_status == 1) { // manual bill
                            CmsBillReturns::create([
                                'id' => $max_id_cms_bill_returns,
                                'return_amt' => $request->amount_payable,
                                'return_code' => 9,
                                'return_reason' => "non smart bill",
                                'action_code' => 2,
                                'action_name' => "Debit/minus",
                                'provider' => $practice_no,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        } else if ($request->smart_bill_id > 0 && $request->vet_status == 2) { // rejection bill
                            CmsBillReturns::create([
                                'id' => $max_id_cms_bill_returns,
                                'return_amt' => $request->invoiced_amount,
                                'return_code' => 8,
                                'return_reason' => "rejected",
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'provider' => $practice_no,
                                'date_entered' => $date_entered,
                                'anniv' => $request->anniv,
                                'member_no' => $request->member_no,
                                'invoice_no' => $request->invoice_no,
                                'benefit' => $request->original_benefit,
                                'service' => (int)$request->service,
                                'user_id' => $request->change_benefit_user,
                                'invoice_date' => $request->invoice_date,
                                'family_no' => $family_no,
                                'corp_id' => $corp_id,
                                'product_name' => $request->product_name,
                            ]);
                        }
                    }
                    //save benefit change in smart bills return
                    if (isset($request->benefit)) {
                        if ($request->benefit !== $request->original_benefit) {

                            //save original bill credit
                            SmartBillReturns::create([
                                // 'return_amt' => $request->original_amount_payable,  
                                'return_amt' => $request->invoiced_amount,
                                'return_code' => 7,
                                'return_reason' => "Benefit Change",
                                'date_entered' => $date_entered,
                                'smart_picked' => 0,
                                'member_no' => $request->member_no,
                                'smart_benefit' => $request->original_benefit,
                                'anniv' => $request->anniv,
                                'service' => (int)$request->service,
                                'provider' => (int)$request->provider,
                                'invoice_no' => $request->invoice_no,
                                'action_code' => 1,
                                'action_name' => "Credit/add",
                                'smart_provider' => (int)$request->provider,
                                'benefit' => $request->original_benefit,
                                'user_id' => $request->change_benefit_user,
                                'sent_to_smart' => 0,
                                'product_name' => $request->product_name,
                                'corp_id' => $corp_id,
                                'family_no' => $family_no,
                                'invoice_date' => $request->invoice_date,
                                'change_benefit_module_name' => $request->module,
                                'claim_changed' => $request->claim_no
                            ]);

                            //save new bill debit
                            SmartBillReturns::create([
                                // 'return_amt' => $request->amount_payable, 
                                'return_amt' => $request->invoiced_amount,
                                'return_code' => 9,
                                'return_reason' => "Benefit Change",
                                'date_entered' => $date_entered,
                                'smart_picked' => 0,
                                'member_no' => $request->member_no,
                                'smart_benefit' => $request->benefit,
                                'anniv' => $request->anniv,
                                'service' => (int)$request->service,
                                'provider' => (int)$request->provider,
                                'invoice_no' => $request->invoice_no,
                                'action_code' => 2,
                                'action_name' => "Debit/minus",
                                'smart_provider' => (int)$request->provider,
                                'benefit' => $request->benefit,
                                'user_id' => $request->change_benefit_user,
                                'sent_to_smart' => 0,
                                'product_name' => $request->product_name,
                                'corp_id' => $corp_id,
                                'family_no' => $family_no,
                                'invoice_date' => $request->invoice_date,
                                'change_benefit_module_name' => $request->module
                            ]);
                        }
                    }

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                });
                array_push($response, "Claim vetted successfully.");
            } catch (Exception $e) {
                array_push($response, "Error " . $e->getMessage());
            }
        } else {
            array_push($response, "You cannot create and vet the same claim!");
        }
        return $response;
    }

    public function saveVetEnmass(Request $request)
    {

        // return $request;
        $user_id = $request->user_id;
        $ids =  json_decode($request->ids);
        try {
            $date_entered = date("Y-m-d");
            if (count($ids) != 0) {
                for ($i = 0; $i < count($ids); $i++) {
                    $max_id = BillVet::max('id') + 1;
                    $dt = Bills::where('id', $ids[$i])->get();

                    DB::transaction(function () use ($request, $user_id, $dt, $max_id, $date_entered, $ids, $i) {
                        //set inserted, updated, and deleted items

                        $inserted_item = "id :$ids[$i], claim_no : " . $dt[0]->CLAIM_NO . ", vet_status : $request->vet_status, vet enmass";
                        $updated_item = "id :$ids[$i],  vet_status : $request->vet_status, amt_payable :  $dt[0]->INVOICED_AMOUNT, vet enmass";
                        $deleted_item = null;
                        //save in vet bill
                        BillVet::create([
                            'id' => $max_id,
                            'claim_no' => $dt[0]->CLAIM_NO,
                            'invoice_no' => $dt[0]->INVOICE_NO,
                            'service' => (int)$dt[0]->SERVICE,
                            'provider' => (int)$dt[0]->PROVIDER,
                            'vet_status' => $request->vet_status,
                            'user_id' => $user_id,
                            'date_entered' => $date_entered
                        ]);

                        //update vet status and amount payable of particular claim in bills
                        Bills::where(
                            'id',
                            $ids[$i]
                        )
                            ->update([
                                'vet_status' => $request->vet_status,
                                'amount_payable' => $dt[0]->INVOICED_AMOUNT,
                            ]);

                        //save member diagnosis if it exists
                        if (isset($request->diagnosis)) {
                            for ($i = 0; $i < count($request->diagnosis); $i++) {
                                $exists = MemberDiagnosis::where('claim_no', $dt[0]->CLAIM_NO)
                                    ->where('member_no', $dt[0]->MEMBER_NO)
                                    ->where('diagnosis', (int)$request->diagnosis[$i])
                                    ->get();

                                if (count($exists) === 0) {
                                    MemberDiagnosis::create([
                                        'claim_no' => $dt[0]->CLAIM_NO,
                                        'member_no' => $dt[0]->MEMBER_NO,
                                        'diagnosis' => (int)$request->diagnosis[$i]
                                    ]);
                                }
                            }
                        }

                        //save access log
                        app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                    });
                }
            }
            return ["Line Items vetted successfully."];
        } catch (Exception $e) {
            return ["Error " . $e->getMessage()];
        }
    }
}
