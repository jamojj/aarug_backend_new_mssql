<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Banks;
use App\Models\Benefit;
use App\Models\Bills;
use App\Models\BillVet;
use App\Models\Corporate\Corporate;
use App\Models\Corporate\PolicyDocument;
use App\Models\PreAuthorization;
use App\Models\PremiumInvoice;
use App\Models\Provider;
use App\Models\Registration\MemberCancellation;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $corporates = Corporate::count();
        $active_corporates = Corporate::where('CANCELLED', 0)->orWhere('CANCELLED', null)->count();
        $active_members = MemberInfo::where('CANCELLED', 0)->orWhere('CANCELLED', null)->count();
        $agents = Agent::count();
        $providers = Provider::count();
        $benefits = Benefit::count();
        $banks = Banks::count();
        $pre_authorization = PreAuthorization::count();

        $data = [
            'corporates' => $corporates,
            'active_corporates' => $active_corporates,
            'active_members' => $active_members,
            'agents' => $agents,
            'providers' => $providers,
            'benefits' => $benefits,
            'banks' => $banks,
            'pre_authorizations' => $pre_authorization
        ];
        return $data;
    }
    public function dashboardAdvanced($username)
    {
        $this_month = date('Ym');
        $next_month = date('Ym', strtotime("next Month"));
        //user specific
        $bills_entered_daily = Bills::where('user_id', $username)
            ->where('date_entered', today())
            ->distinct('claim_no')
            ->count();
        $bills_entered_monthly = Bills::where('user_id', $username)
            ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyymm')"), [$this_month, $next_month])
            ->distinct('claim_no')
            ->count();
        $bills_vetted_daily = BillVet::where('user_id', $username)
            ->where('date_entered', today())
            ->distinct('claim_no')
            ->count();
        $bills_vetted_monthly = BillVet::where('user_id', $username)
            ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyymm')"), [$this_month, $next_month])
            ->distinct('claim_no')
            ->count();
        $bills_vouchered_daily = Bills::where('voucher_user', $username)
            ->where('date_entered', today())
            ->distinct('claim_no')
            ->count();
        $bills_vouchered_monthly = Bills::where('voucher_user', $username)
            ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyymm')"), [$this_month, $next_month])
            ->distinct('claim_no')
            ->count();
        $bills_approved_daily = BillVet::where('approval_user_id', $username)
            ->where('approval_date', today())
            ->count();
        $bills_approved_monthly = BillVet::where('approval_user_id', $username)
            ->whereBetween(DB::raw("FORMAT(approval_date, 'yyyymm')"), [$this_month, $next_month])
            ->count();
        $policy_created_daily = PolicyDocument::where('user_id', $username)->where('date_entered', today())->count();
        $policy_created_monthly = PolicyDocument::where('user_id', $username)
            ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyymm')"), [$this_month, $next_month])
            ->count();
        $members_added_daily = MemberInfo::where('user_id', $username)->where('date_entered', today())->count();
        $members_added_monthly = MemberInfo::where('user_id', $username)
            ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyymm')"), [$this_month, $next_month])
            ->count();
        $members_cancelled_daily = MemberCancellation::where('user_id', $username)->where('date_entered', today())->where('cancelled', 1)->count();
        $members_cancelled_monthly = MemberCancellation::where('user_id', $username)
            ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyymm')"), [$this_month, $next_month])
            ->count();
        $members_reinstated_daily = MemberCancellation::where('user_id', $username)->where('date_entered', today())->where('cancelled', 0)->count();
        $members_reinstated_monthly = MemberCancellation::where('user_id', $username)
            ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyymm')"), [$this_month, $next_month])
            ->count();
        $debits_created_daily = PremiumInvoice::where('user_id', $username)->where('invoice_date', today())->count();
        $debits_created_monthly = PremiumInvoice::where('user_id', $username)
            ->whereBetween(DB::raw("FORMAT(invoice_date, 'yyyymm')"), [$this_month, $next_month])
            ->count();
        //overall
        $corporates = Corporate::count();
        $active_corporates = Corporate::where(DB::raw("isnull(cancelled, 0)"), 0)->count();
        $all_active_members = MemberInfo::where(DB::raw("isnull(cancelled, 0)"), 0)->count();
        $active_corporate_members = MemberInfo::join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->whereIn('principal_applicant.individual', [1, 3])
            ->where(DB::raw("isnull(member_info.hc_status, 0)"), 0)
            ->count();
        $active_retail_members = MemberInfo::join('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
            ->where(DB::raw("isnull(member_info.cancelled, 0)"), 0)
            ->where('principal_applicant.individual', 2)
            ->where(DB::raw("isnull(member_info.hc_status, 0)"), 0)
            ->count();
        $active_hospital_cash_members = MemberInfo::where(DB::raw("isnull(cancelled, 0)"), 0)->where('hc_status', 1)->count();
        $agents = Agent::count();
        $providers = Provider::count();
        $benefits = Benefit::count();
        $banks = Banks::count();
        $pre_authorization = PreAuthorization::count();
        $unvetted_bills = Bills::where(DB::raw("isnull(vet_status, 0)"), 0)->count();
        $approved_bills = Bills::where(DB::raw("isnull(vet_status, 0)"), 1)->count();
        $rejected_bills = Bills::where(DB::raw("isnull(vet_status, 0)"), 2)->count();
        $suspended_bills = Bills::where(DB::raw("isnull(vet_status, 0)"), 3)->count();

        $data = [
            'bills_entered_daily' => $bills_entered_daily,
            'bills_entered_monthly' => $bills_entered_monthly,
            'bills_vetted_daily' => $bills_vetted_daily,
            'bills_vetted_monthly' => $bills_vetted_monthly,
            'bills_vouchered_daily' => $bills_vouchered_daily,
            'bills_vouchered_monthly' => $bills_vouchered_monthly,
            'bills_approved_daily' => $bills_approved_daily,
            'bills_approved_monthly' => $bills_approved_monthly,
            'unvetted_bills' => $unvetted_bills,
            'approved_bills' => $approved_bills,
            'rejected_bills' => $rejected_bills,
            'suspended_bills' => $suspended_bills,
            'policy_created_daily' => $policy_created_daily,
            'policy_created_monthly' => $policy_created_monthly,
            'members_added_daily' => $members_added_daily,
            'members_added_monthly' => $members_added_monthly,
            'members_cancelled_daily' => $members_cancelled_daily,
            'members_cancelled_monthly' => $members_cancelled_monthly,
            'members_reinstated_daily' => $members_reinstated_daily,
            'members_reinstated_monthly' => $members_reinstated_monthly,
            'debits_created_daily' => $debits_created_daily,
            'debits_created_monthly' => $debits_created_monthly,
            'corporates' => $corporates,
            'active_corporates' => $active_corporates,
            'all_active_members' => $all_active_members,
            'active_corporate_members' => $active_corporate_members,
            'active_retail_members' => $active_retail_members,
            'active_hospital_cash_members' => $active_hospital_cash_members,
            'agents' => $agents,
            'providers' => $providers,
            'benefits' => $benefits,
            'banks' => $banks,
            'pre_authorizations' => $pre_authorization
        ];
        return $data;
    }

    public function fetchDashboardData($val)
    {
        switch ($val) {
            case 'vetted_bills':
                // $vetted_bills = Bills::select(
                //     'user_id',
                //     DB::raw("count(vet_status > 0 where date_entered = today()) as vetted")
                // )
                //     ->get();
                $month = date("Ym");
                $month_header = date("m/Y");
                $day = date("d/m/Y");
                $vetted_bills = Bills::select('user_id')
                    ->selectRaw("SUM(CASE WHEN vet_status > 0 AND FORMAT(date_entered, 'yyyyMMdd') = today() THEN 1 ELSE 0 END) as vetted_daily")
                    ->selectRaw("SUM(CASE WHEN vet_status > 0 AND FORMAT(date_entered, 'yyyymm') = '$month' THEN 1 ELSE 0 END) as vetted_monthly")
                    ->whereNotNull('user_id')
                    ->groupBy('user_id')
                    ->havingRaw("SUM(CASE WHEN vet_status > 0 AND FORMAT(date_entered, 'yyyyMMdd') = today() THEN 1 ELSE 0 END) > 0 OR SUM(CASE WHEN vet_status > 0 AND FORMAT(date_entered, 'yyyymm') = ? THEN 1 ELSE 0 END) > 0", [$month])
                    ->orderBy('user_id', 'asc')
                    ->get();

                $headers = ["Names", "Daily Count ($day)", "Monthly Count ($month_header)"];
                $dt = [
                    'tbldata' => $vetted_bills,
                    'headers' => $headers
                ];
                return $dt;
                break;
            case '1':
                $corporate = Corporate::select('corp_id', 'corporate', 'phy_loc', 'email', 'policy_no')
                    ->orderBy('corp_id', 'ASC')
                    ->get();
                $headers = ['Corp Id', 'Corporate', 'Physical Location', 'Email', 'Policy No'];
                $dt = [
                    'tbldata' => $corporate,
                    'headers' => $headers
                ];
                return $dt;
                break;
            case '2':
                $active_corporates = Corporate::select('corp_id', 'corporate', 'phy_loc', 'email', 'policy_no')->orderBy('corp_id', 'ASC')
                    ->where(DB::raw("isnull(cancelled, 0)"), 0)
                    ->get();
                $headers = ['Corp Id', 'Corporate', 'Physical Location', 'Email', 'Policy No'];
                $dt = [
                    'tbldata' => $active_corporates,
                    'headers' => $headers
                ];
                return $dt;
                break;
            case '3':
                $active_members =
                    DB::select('Select TOP 500  family_no, member_no, surname, first_name, dob from member_info where isnull(cancelled, 0) = 0 order by family_no ASC');
                $headers = ['Family No', 'Member No', 'Surname', 'First Name', 'Dob'];
                $dt = [
                    'tbldata' => $active_members,
                    'headers' => $headers
                ];
                return $dt;
                break;
            case '4':
                $agents = Agent::select('agent_id', 'agent_name')->orderBy('agent_id', 'ASC')->get();
                $headers = ['Agent ID', 'Agent Name'];
                $dt = [
                    'tbldata' => $agents,
                    'headers' => $headers
                ];
                return $dt;
                break;
            case '5':
                $providers = Provider::select('code', 'provider', 'tel_no')->orderBy('code', 'ASC')->get();
                $headers = ['Code', 'Provider', 'Tel No'];
                $dt = [
                    'tbldata' => $providers,
                    'headers' => $headers
                ];
                return $dt;
                break;
            case '6':
                $benefits = Benefit::select('code', 'benefit')->orderBy('code', 'ASC')->get();
                $headers = ['Code', 'Benefit'];
                $dt = [
                    'tbldata' => $benefits,
                    'headers' => $headers
                ];
                return $dt;
                break;
            case '7':
                $banks = Banks::select('code', 'bank')->orderBy('code', 'ASC')->get();
                $headers = ['Code', 'Bank'];
                $dt = [
                    'tbldata' => $banks,
                    'headers' => $headers
                ];
                return $dt;
                break;
            case '8':
                $pre_authorization = DB::select('Select TOP 300  member_no, pre_diagnosis, admit_days, notes from pre_authorization order by member_no ASC');

                $headers = ['Member No', 'Diagnosis', 'Admition Days', 'Notes'];
                $dt = [
                    'tbldata' => $pre_authorization,
                    'headers' => $headers
                ];
                return $dt;
                break;
        }
    }
}
