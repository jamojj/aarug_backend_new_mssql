<?php

namespace App\Http\Controllers\Reusables;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\AgentDeduction;
use App\Models\AgentType;
use App\Models\BankAccount;
use App\Models\Banks;
use App\Models\Bdm;
use App\Models\Bills;
use App\Models\CardReprintReason;
use App\Models\cigna_member_anniversary;
use App\Models\CignaMemberInfo;
use App\Models\ClassGroup;
use App\Models\ClientAddress;
use App\Models\ClientType;
use App\Models\ConversionRates;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\CorpGroups;
use App\Models\Corporate\Corporate;
use App\Models\Corporate\PolicyDocument;
use App\Models\CorpVisitIssue;
use App\Models\Currency;
use App\Models\DeclineReason;
use App\Models\DeductionReason;
use App\Models\Department;
use App\Models\Doctors;
use App\Models\ExchangeRates;
use App\Models\FamilyRelation;
use App\Models\FamilySize;
use App\Models\Financer;
use App\Models\HaisTriggers;
use App\Models\HospitalWard;
use App\Models\Levy;
use App\Models\pahapaMemberType;
use App\Models\pahapaMessageType;
use App\Models\PayeeType;
use App\Models\PaymentMode;
use App\Models\Permissions;
use App\Models\PremiumInvoiceDetailsSammary;
use App\Models\ProductCategory;
use App\Models\ProductName;
use App\Models\Products;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\ReInsurer;
use App\Models\RejectReason;
use App\Models\ResendBillReason;
use App\Models\SmartBillReturns;
use App\Models\tProviderCancellationReason;
use App\Models\TUserRoleNew;
use App\Models\UnitManagers;
use App\Models\Units;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserPermissionTypes;
use App\Models\Users;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;

class FetchAnyItemController extends Controller
{
    //function to fetch individuals for select dropdown

    public function fetchAllIndividuals()
    {
        $individuals = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),
            'PRINCIPAL_APPLICANT.member_no'
        )
            ->join(
                'MEMBER_INFO',
                'MEMBER_INFO.family_no',
                '=',
                'PRINCIPAL_APPLICANT.family_no'
            )
            ->orderBy('PRINCIPAL_APPLICANT.surname')
            ->where(
                DB::raw("isnull(MEMBER_INFO.cancelled, 0)"),
                0
            )
            ->where('MEMBER_INFO.relation_to_principal', 24)
            ->where('PRINCIPAL_APPLICANT.individual', 2)
            ->get();


        return json_encode($individuals, JSON_INVALID_UTF8_IGNORE);
    }

    public function fetchIndividuals()
    {
        $today = date('Y-m-d');
        // $data = [];
        // $individuals = PrincipalApplicant::select(
        //     DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),
        //     'PRINCIPAL_APPLICANT.member_no'
        // )
        //     ->join('MEMBER_INFO', 'MEMBER_INFO.family_no', '=', 'PRINCIPAL_APPLICANT.family_no')
        //     ->orderBy('PRINCIPAL_APPLICANT.surname')
        //     ->where(DB::raw("isnull(MEMBER_INFO.cancelled, 0)"), 0)
        //     ->where('MEMBER_INFO.relation_to_principal', 24)
        //     ->where('PRINCIPAL_APPLICANT.individual', 2)
        //     ->get();

        // foreach ($individuals as $individual) {
        //     $max_anniv = MemberAnniversary::where('member_no', $individual->member_no)->max('anniv');
        //     $cover_expired = MemberAnniversary::where('end_date', '<', $today)->where('anniv', $max_anniv)->where('member_no', $individual->member_no)->get();
        //     if (count($cover_expired) == 0) {
        //         $dt = [
        //             'principal_names' => $individual->principal_names,
        //             'member_no' => $individual->member_no
        //         ];
        //         array_push($data, $dt);
        //     }
        // }

        $individuals = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname, ' ', PRINCIPAL_APPLICANT.first_name, ' ', PRINCIPAL_APPLICANT.other_names) as principal_names"),
            'PRINCIPAL_APPLICANT.member_no'
        )
            ->join('MEMBER_INFO', 'MEMBER_INFO.family_no', '=', 'PRINCIPAL_APPLICANT.family_no')
            ->leftJoin('MEMBER_ANNIVERSARY', function ($join) {
                $join->on('MEMBER_ANNIVERSARY.member_no', '=', 'PRINCIPAL_APPLICANT.member_no')
                    ->where('MEMBER_ANNIVERSARY.anniv', '=', DB::raw('(SELECT MAX(anniv) FROM MEMBER_ANNIVERSARY WHERE member_no = PRINCIPAL_APPLICANT.member_no)'));
            })
            ->orderBy('PRINCIPAL_APPLICANT.surname')
            ->whereRaw("(isnull(MEMBER_INFO.cancelled, 0) = 0)")
            ->where('MEMBER_INFO.relation_to_principal', 24)
            ->where('PRINCIPAL_APPLICANT.individual', 2)
            ->where(function ($query) use ($today) {
                $query->whereRaw("(isnull(MEMBER_ANNIVERSARY.end_date, '') >= '$today')")
                    ->orWhereNull('MEMBER_ANNIVERSARY.end_date');
            })
            ->get();

        return json_encode($individuals, JSON_INVALID_UTF8_IGNORE);
    }
    public function fetchIndividualsQueryDebit()
    {
        $individuals = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),
            'PRINCIPAL_APPLICANT.member_no'
        )
            ->join('MEMBER_INFO', 'MEMBER_INFO.family_no', '=', 'PRINCIPAL_APPLICANT.family_no')
            ->orderBy('PRINCIPAL_APPLICANT.surname')
            ->where('MEMBER_INFO.relation_to_principal', 24)
            ->where('PRINCIPAL_APPLICANT.individual', 2)
            ->get();


        return json_encode($individuals, JSON_INVALID_UTF8_IGNORE);
    }

    public function fetchIndividualsRenewal()
    {
        $individuals = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),
            'PRINCIPAL_APPLICANT.member_no'
        )
            ->join(
                'MEMBER_INFO',
                'MEMBER_INFO.family_no',
                '=',
                'PRINCIPAL_APPLICANT.family_no'
            )
            ->orderBy('PRINCIPAL_APPLICANT.surname')
            ->where(
                DB::raw("isnull(MEMBER_INFO.cancelled, 0)"),
                0
            )
            ->where('MEMBER_INFO.relation_to_principal', 24)
            ->where('PRINCIPAL_APPLICANT.individual', 2)
            ->get();


        return json_encode($individuals, JSON_INVALID_UTF8_IGNORE);
    }


    //check if benefit has already been changed
    public function checkBenefitHasAlreadyBeenChanged(Request $request)
    {
        $data = SmartBillReturns::where('claim_changed', $request->claim_no_changed)->get();
        if (count($data) === 0) {
            return [0];
        } else {
            return [1];
        }
    }
    //function to fetch hospital cash for select dropdown

    public function fetchHospitalCashMembers()
    {
        $hospital_cash = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),
            'PRINCIPAL_APPLICANT.member_no'
        )
            ->join('MEMBER_INFO', 'MEMBER_INFO.family_no', '=', 'PRINCIPAL_APPLICANT.family_no')
            ->orderBy('principal_names')
            ->where('MEMBER_INFO.relation_to_principal', 24)
            ->where(DB::raw("isnull(MEMBER_INFO.cancelled, 0)"), 0)
            ->where('PRINCIPAL_APPLICANT.individual', 2)
            ->where('MEMBER_INFO.hc_status', 1)
            ->get();

        return json_encode($hospital_cash, JSON_INVALID_UTF8_IGNORE);
    }
    public function fetchHospitalCashMembersPerCorporate($corp_id)
    {
        $hospital_cash = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),
            'PRINCIPAL_APPLICANT.member_no'
        )
            ->join('MEMBER_INFO', 'MEMBER_INFO.family_no', '=', 'PRINCIPAL_APPLICANT.family_no')
            ->orderBy('principal_names', 'asc')
             ->where('MEMBER_INFO.relation_to_principal', 24)
            ->where(DB::raw("isnull(MEMBER_INFO.cancelled, 0)"), 0)
            ->where('principal_applicant.corp_id', $corp_id)
            ->get();

        return json_encode($hospital_cash, JSON_INVALID_UTF8_IGNORE);
    }
    //fetch kitanda members
    public function fetchKitandaMembers()
    {
        $hospital_cash = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),
            'PRINCIPAL_APPLICANT.member_no'
        )
            ->join('MEMBER_INFO', 'MEMBER_INFO.family_no', '=', 'PRINCIPAL_APPLICANT.family_no')
            ->orderBy('PRINCIPAL_APPLICANT.surname')
            ->where('MEMBER_INFO.relation_to_principal', 24)
            ->where(DB::raw("isnull(MEMBER_INFO.cancelled, 0)"), 0)
            // ->where('PRINCIPAL_APPLICANT.individual', 2)
            ->where('MEMBER_INFO.hc_status', 2)
            ->get();

        return json_encode($hospital_cash, JSON_INVALID_UTF8_IGNORE);
    }
    //fetch munomukabi corporates
    public function fetchMunomukabiCorporates()
    {
        $data = Corporate::select(
            'corp_id',
            'corporate'
        )
            ->whereIn('individual', [1, 3])
            ->where('product_category', 2)
            ->orderBy('corporate', 'asc')
            ->get();

        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }
    //fetch bancasurance corporates
    public function fetchBancasuranceCorporates()
    {
        $data = Corporate::select(
            'corp_id',
            'corporate'
        )
            ->whereIn('individual', [1, 3])
            ->where('product_category', 3)
            ->orderBy('corporate', 'asc')
            ->get();

        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }
    //fetch munomukabi retails
    public function fetchMunomukabiRetails()
    {
        $data = Corporate::select(
            'corp_id',
            'corporate'
        )
            ->where('individual', 2)
            ->where('product_category', 2)
            ->orderBy('corporate', 'asc')
            ->get();

        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }
    //fetch bancasurance retails
    public function fetchBancasuranceRetails()
    {
        $data = Corporate::select(
            'corp_id',
            'corporate'
        )
            ->where('individual', 2)
            ->where('product_category', 3)
            ->orderBy('corporate', 'asc')
            ->get();

        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }
    //fetch annivs
    public function fetchAnnivs(Request $request)
    {
        $anniv = [];
        if ($request->scheme == 1) {
            $anniv = CorpAnniversary::select('anniv')->where('corp_id', $request->corporate)->get();
        }
       

        return $anniv;
    }
    //fetch retail members
    public function fetchRetailMembers(Request $request)
    {
        $members = [];
        if ($request->scheme == 2) {
            $members = PrincipalApplicant::select(
                DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),
                'PRINCIPAL_APPLICANT.member_no'
            )
                ->join('MEMBER_INFO', 'MEMBER_INFO.family_no', '=', 'PRINCIPAL_APPLICANT.family_no')
                ->where('MEMBER_INFO.relation_to_principal', 24)
                ->where(DB::raw("isnull(MEMBER_INFO.cancelled, 0)"), 0)
                ->where('PRINCIPAL_APPLICANT.corp_id', $request->retail)
                ->orderBy('PRINCIPAL_APPLICANT.surname')
                ->get();
    
            }
            return json_encode($members, JSON_INVALID_UTF8_IGNORE);
       

    }
    //fetch IandM members
    public function fetchIandMMembers()
    {
        $data = PrincipalApplicant::select(
            DB::raw("CONCAT(principal_applicant.surname, ' ',PRINCIPAL_APPLICANT.first_name,' ',PRINCIPAL_APPLICANT.other_names) as principal_names"),
            'PRINCIPAL_APPLICANT.member_no'
        )
            ->join('MEMBER_INFO', 'MEMBER_INFO.family_no', '=', 'PRINCIPAL_APPLICANT.family_no')
            ->where('MEMBER_INFO.relation_to_principal', 24)
            ->where(DB::raw("isnull(MEMBER_INFO.cancelled, 0)"), 0)
            ->where('PRINCIPAL_APPLICANT.corp_id', 763)
            ->orderBy('PRINCIPAL_APPLICANT.surname')
            ->get();

        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }

    //fetch cigna retail families
    public function fetchCignaRetail()
    {
        $data = CignaMemberInfo::select('name', 'family_no')->where('person_no', 1)->get();
        return $data;
    }

    //fetch cigna retail family data
    public function fetchCignaRetailData($family_no)
    {
        $max_anniv = cigna_member_anniversary::where('family_no', $family_no)->max('anniv');
        $data = CignaMemberInfo::join('cigna_member_anniversary', function ($join) {
            $join->on('cigna_member_anniversary.person_no', '=', 'cigna_member_info.person_no')
                ->on('cigna_member_anniversary.family_no', '=', 'cigna_member_info.family_no');
        })
            ->where('cigna_member_anniversary.anniv', $max_anniv)
            ->where('cigna_member_info.family_no', $family_no)
            ->get();
        return $data;
    }

    //fetch corporate anniversary dates for debitting
    public function getCorporateAnniversaryDates($task, $selected_value)
    {

        switch ($task) {
            //premium debiting corporate and manual debiting corporate
            case "1":
            case "5":
                $max_anniv = CorpAnniversary::where('corp_id', $selected_value)->max('anniv');
                $start_date = CorpAnniversary::where('corp_id', $selected_value)->where('anniv', $max_anniv)->pluck('START_DATE');
                $end_date = CorpAnniversary::where('corp_id', $selected_value)->where('anniv', $max_anniv)->pluck('END_DATE');

                $start_date_f = \Carbon\Carbon::parse($start_date[0]);
                $end_date_f = \Carbon\Carbon::parse($end_date[0]);

                $days = $start_date_f->diffInDays($end_date_f, false) + 1;
                $agent_id = Corporate::where('corp_id', $selected_value)->pluck('agent_id');
                $agent_id = $agent_id[0];
                $agent_status = Agent::select('status')->where('agent_id', $agent_id)->get();
                $agent_status = count($agent_status) !== 0 ?  $agent_status[0]->status : '';

                $unit_manager = Agent::where('agent_id', $agent_id)->pluck('unit_manager');

                $data = [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'days' => $days,
                    'max_anniv' => $max_anniv,
                    'agent_id' => $agent_id,
                    'agent_status' => $agent_status,
                    'unit_manager' => $unit_manager[0]
                ];
                return $data;
                break;
            //manual debiting for retail or family
            case "2":
                $max_anniv = MemberAnniversary::where('member_no', $selected_value)->max('anniv');
                $family_no = MemberInfo::where('member_no', $selected_value)->pluck('family_no');
                $start_date = MemberAnniversary::where('member_no', $selected_value)->where('anniv', $max_anniv)->pluck('START_DATE');
                $end_date = MemberAnniversary::where('member_no', $selected_value)->where('anniv', $max_anniv)->pluck('END_DATE');

                $start_date_f = \Carbon\Carbon::parse($start_date[0]);
                $end_date_f = \Carbon\Carbon::parse($end_date[0]);

                $days = $start_date_f->diffInDays($end_date_f, false) + 1;

                $agent_id = PrincipalApplicant::where('member_no', $selected_value)->pluck('agent_id');
                $agent_id = $agent_id[0];
                $agent_status = Agent::select('status')->where('agent_id', $agent_id)->get();
                $agent_status = count($agent_status) !== 0 ?  $agent_status[0]->status : '';

                $unit_manager = Agent::where('agent_id', $agent_id)->pluck('unit_manager');

                $data = [
                    'start_date' => $start_date[0],
                    'end_date' => $end_date[0],
                    'days' => $days,
                    'max_anniv' => $max_anniv,
                    'family_no' => $family_no[0],
                    'agent_id' => $agent_id,
                    'unit_manager' => $unit_manager[0],
                    'agent_status' => $agent_status,
                ];
                return $data;
                break;
            //premium debiting for retail or family
            case "3":
            case "6":
                $max_anniv = MemberAnniversary::where('member_no', $selected_value)->max('anniv');
                $family_no = MemberInfo::where('member_no', $selected_value)->pluck('family_no');
                $start_date = MemberAnniversary::where('member_no', $selected_value)->where('anniv', $max_anniv)->pluck('START_DATE');
                $end_date = MemberAnniversary::where('member_no', $selected_value)->where('anniv', $max_anniv)->pluck('END_DATE');

                $start_date_f = \Carbon\Carbon::parse($start_date[0]);
                $end_date_f = \Carbon\Carbon::parse($end_date[0]);

                $days = $start_date_f->diffInDays($end_date_f, false) + 1;

                $agent_id = PrincipalApplicant::where('member_no', $selected_value)->pluck('agent_id');
                $agent_id = $agent_id[0];
                $agent_status = Agent::select('status')->where('agent_id', $agent_id)->get();
                $agent_status = count($agent_status) !== 0 ?  $agent_status[0]->status : '';


                $unit_manager = Agent::where('agent_id', $agent_id)->pluck('unit_manager');

                $data = [
                    'start_date' => $start_date[0],
                    'end_date' => $end_date[0],
                    'days' => $days,
                    'max_anniv' => $max_anniv,
                    'family_no' => $family_no[0],
                    'agent_id' => $agent_id,
                    'unit_manager' => $unit_manager[0],
                    'agent_status' => $agent_status,
                ];
                return $data;
                break;
        }
    }

    //fetch payment modes
    public function fetchPaymentModes()
    {
        $payment_modes = PaymentMode::all();

        return $payment_modes;
    }

    //fetch all banks
    public function fetchBanks()
    {
        return Banks::where('bank', '>', '')->orderBy('bank')->get();
    }

    //fetch financer
    public function fetchFinancers()
    {
        return Financer::all();
    }

    //fetch users by username
    public function fetchUserNames()
    {
        return Users::select('user_name')->orderBy('user_name', 'ASC')->get();
    }

    //hospital ward
    public function fetchHospitalWards()
    {
        return HospitalWard::all();
    }

    //fetch all curriencies
    public function fetchCurrencies()
    {
        return Currency::all();
    }

    //fetch UGX curriencies
    public function fetchUGXCurrencies()
    {
        return Currency::whereIn('currency', ['UGX', 'USD'])->get();
    }

    public function fetchHaisTriggers()
    {
        $hais_triggers = HaisTriggers::all();
        return $hais_triggers;
    }
    public function fetchCardReprintReason()
    {
        $data = CardReprintReason::all();
        return $data;
    }

    //fetch bank accounts plus the combinations
    public function fetchBankAccounts()
    {
        $bank_accounts = BankAccount::select(
            'BANK_ACCOUNT.code',
            'BANK_ACCOUNT.bank_id',
            'BANKS.bank',
            'BANK_ACCOUNT.bank_account',
            'BANK_ACCOUNT.bank_id',
            'BANK_ACCOUNT.currencyid',
            'T_CURRENCY.currency'
        )
            ->leftJoin('BANKS', 'BANKS.code', '=', 'BANK_ACCOUNT.bank_id')
            ->leftJoin('T_CURRENCY', 'T_CURRENCY.code', '=', 'BANK_ACCOUNT.currencyid')
            ->get();

        return $bank_accounts;
    }

    //fetch client types
    public function fetchClientTypes()
    {
        $client_types = ClientType::select('*')->orderBy('client_type')->get();
        return $client_types;
    }
    //fetch product category
    public function fetchProductCategories()
    {
        $product_categories = ProductCategory::select('*')->orderBy('category')->get();
        return $product_categories;
    }

    //fetch client adress
    public function fetchClientAddress($code)
    {
        $client_address = ClientAddress::where('code', $code)->get();
        return $client_address;
    }

    public function fetchFamilyRelation()
    {
        $family_relation = FamilyRelation::all();
        return $family_relation;
    }

    public function fetchFamilyRelationSimple()
    {
        $relation = FamilyRelation::whereIn(
            'code',
            [24, 25]
        )->get();
        return $relation;
    }

    public function fetchResendBillReason()
    {
        $bill_reason = ResendBillReason::select('code', 'resend_reason')->orderBy('resend_reason')->get();
        return $bill_reason;
    }

    public function fetchDeductionReason()
    {
        $deduction_reason = DeductionReason::select('code', 'deduct_reason')->orderBy('deduct_reason')->get();
        return $deduction_reason;
    }

    //fetch member cover dates / anniversaries
    public function fetchMemberCoverDates($member_no)
    {
        $max_anniv = MemberAnniversary::where('member_no', $member_no)->max('anniv');
        return MemberAnniversary::select(
            DB::raw("FORMAT(start_date, 'yyyy-mm-dd') as start_date"),
            DB::raw("FORMAT(end_date, 'yyyy-mm-dd') as end_date"),
            DB::raw("FORMAT(renewal_date, 'yyyy-mm-dd') as renewal_date"),
        )
            ->where('member_no', $member_no)
            ->where('anniv', $max_anniv)
            ->get();
    }

    //fetch all admitting doctors - Care - Care Admissions
    public function fetchAdmittingDoctors()
    {
        return Doctors::all();
    }

    //fetch decline reason
    public function fetchDeclineReason()
    {
        $decline_reason = DeclineReason::all();
        return $decline_reason;
    }

    //fetch wards
    public function fetchWards()
    {
        return HospitalWard::all();
    }

    //fetch all users
    public function fetchAllUsersNames()
    {
        return User::select('user_name')->get();
    }

    //fetch corp visit issue
    public function fetchCorpVisitIssue()
    {
        return CorpVisitIssue::select('code', 'comments')->get();
    }

    //fetch all agent deduction types
    public function fetchAgentDeductionTypes()
    {
        return AgentDeduction::all();
    }

    //fetch all bdms
    public function fetchBDM()
    {
        return Bdm::select('*')->orderBy('name')->get();
    }

    //fetch all agent type
    public function fetchAgentTypes()
    {
        return AgentType::select('*')->orderBy('agent_type')->get();
    }

    //fetch all agency
    public function fetchAgency()
    {
        return Agency::select('*')->orderBy('name')->get();
    }

    //pay override bdm - voucher - fetch all ov numbers for dropdown
    public function fetchVoucherOVNumber()
    {
        return PremiumInvoiceDetailsSammary::select('bdm_vno')
            ->distinct()
            ->where(DB::raw('isnull(bdm_payno, 0)'), 0)
            ->get();
    }

    //fetch all payee types
    public function fetchAllPayeeTypes()
    {
        return PayeeType::all();
    }

    //fetch all principals
    public function fetchAllPrincipals()
    {
        $data = PrincipalApplicant::select(
            'member_no',
            'family_no',
            DB::raw("CONCATsurname,' ',first_name,' ', other_names) as principal_name")
        )
            ->where('individual', 2)
            ->orderBy('principal_name')
            ->get();
        $data = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        return $data;
    }

    //fetch all family annivs
    public function fetchAllFamilyAnnivs($family_no)
    {
        $member_no = $family_no . '-00';

        $annivs = MemberAnniversary::select('anniv')
            ->where('member_no', $member_no)
            ->orderBy('anniv', 'ASC')
            ->get();
        return $annivs;
    }

    //fetch family size
    public function fetchFamilySize()
    {
        $family_size = FamilySize::all();
        return $family_size;
    }

    //fetch rates relation
    public function fetchRateSheetRelation()
    {
        $rates_relation = FamilyRelation::whereIn('code', [3, 4, 5, 6, 7, 26])->get();
        return $rates_relation;
    }

    //fetch all reinsurers
    public function fetchQuotaShareReinsurers()
    {
        $data = ReInsurer::all();
        return $data;
    }

    //check if optical benefit was used in two consecutive annivs
    public function checkOpticalUsedConsecutively(Request $request)
    {

        $response = [];
        //member exists
        $exists = MemberInfo::where('member_no', $request->member_no)->get();

        if (count($exists) !== 0) {
            if ($request->benefit == 36) {
                $prev_anniv = Bills::where('claim_nature', 36)
                    ->where('member_no', $request->member_no)
                    ->where('anniv', '<=', $request->anniv)
                    ->max('anniv');


                if (!is_null($prev_anniv)) {
                    $check_anniv_for_optical = $request->anniv - $prev_anniv;
                    if ($check_anniv_for_optical === 1 || $check_anniv_for_optical === 0) {
                        array_push($response, ['optical' => $prev_anniv]);
                    } else {
                        array_push($response, ['optical' => ""]);
                    }
                } else {
                    array_push($response, ['optical' => ""]);
                }
            } else {
                array_push($response, ['optical' => ""]);
            }
        } else {
            array_push($response, ['optical' => ""]);
        }


        return $response;
    }

    //fetch levies
    public function fetchLevies()
    {
        $levies = Levy::all();
        return $levies;
    }

    //fetch all product names
    public function fetchProductNames()
    {
        $product_names = ProductName::all();
        return $product_names;
    }
    //fetch specific product names
    public function fetchSpecificProductNames($category)
    {
        $product_names = Products::select('t_product_name.code', 't_product_name.product_name')
            ->leftJoin('t_product_name', 't_product_name.code', '=', 't_products.product_name')
            ->where('t_products.health_plan', $category)
            ->distinct()
            ->get();
        return $product_names;
    }

    //fetch all conversion rates
    public function fetchConversionRates()
    {
        $conversion_rates = ConversionRates::all();
        return $conversion_rates;
    }

    //fetch units
    public function fetchUnits()
    {
        $units = Units::all();
        return $units;
    }

    //fetch units
    public function fetchUnitManagers()
    {
        $unit_managers = UnitManagers::all();
        return $unit_managers;
    }

    //fetch all agencies - order by code
    public function fetchAgencies()
    {
        $agencies = Agency::all();
        return $agencies;
    }

    //fetch all rejection reasons
    public function fetchRejectionReasons()
    {
        $reasons = RejectReason::all();
        return $reasons;
    }

    //fetch all class group plans
    public function fetchClassGroupPlans()
    {
        $plans = ClassGroup::all();
        return $plans;
    }

    //fetch all departments
    public function fetchDepartments()
    {
        $departments = Department::all();
        return $departments;
    }
    //fetch all user group
    public function fetchUserGroup()
    {
        $user_group = UserGroup::all();
        return $user_group;
    }
    //fetch all user roles
    public function fetchUserRole()
    {
        $user_role = TUserRoleNew::all();
        return $user_role;
    }
    //fetch all permissions
    public function fetchPermissions()
    {
        $permissions = Permissions::all();
        return $permissions;
    }
    public function fetchPermissionTypes()
    {
        $permission_types = UserPermissionTypes::all();
        return $permission_types;
    }
    //fetch provider cancellation reason
    public function fetchProviderCancellationReason()
    {
        $pCancellationReason = tProviderCancellationReason::all();
        return $pCancellationReason;
    }

    public function deleteBenefitLines(Request $request)
    {
        try {
            MemberBenefits::where([
                'member_no' => $request->member_no,
                'benefit' => $request->benefit,
                'anniv' => $request->anniv,
            ])->delete();
            return ['Benefit deleted successfully'];
        } catch (Exception $e) {
            return ['Error' . $e->getMessage()];
        }
    }

    public function fetchPahapaMemberTypes() {
        $data = pahapaMemberType::all();
        return $data;
        
    }
    public function fetchPahapaMessageTypes() {
        $data = pahapaMessageType::all();
        return $data;
        
    }
}
