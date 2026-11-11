<?php

namespace App\Http\Controllers\Care;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\Bills;
use App\Models\ClaimsReserve;
use App\Models\ClientAddress;
use App\Models\HospitalWard;
use App\Models\PreAuthorization;
use App\Models\Provider;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

//pdf generator
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;

class PreauthorizationController extends Controller
{
    public function fetchMemberPreauth(Request $request)
    {
        $member = [];

        if ($request->option === "1") {
            $member = MemberInfo::select(
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                'member_info.family_no',
                'member_info.member_no',
                't_gender.gender',
                'member_info.dob',
            )
                ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
                ->where('principal_applicant.corp_id', $request->corp)
                ->where(function ($q) use ($request) {
                    $q->where('member_info.member_no', '=', $request->member)
                        ->orWhere('member_info.surname', 'like', '%' . $request->member . '%')
                        ->orWhere('member_info.first_name', 'like', '%' . $request->member . '%')
                        ->orWhere('member_info.other_names', 'like', '%' . $request->member . '%');
                })
                ->get();
        } else {
            $member = MemberInfo::select(
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                'member_info.family_no',
                'member_info.member_no',
                't_gender.gender',
                'member_info.dob',
            )
                ->join('principal_applicant', 'member_info.family_no', '=', 'principal_applicant.family_no')
                ->leftJoin('t_gender', 'member_info.gender', '=', 't_gender.code')
                ->where('member_info.member_no', '=', $request->member)
                ->get();
        }

        return $member;
    }

    public function fetchSpecificMemberDataPreauth($memno)
    {
        $today = date("Y-m-d");
        $max_ref_no = PreAuthorization::max('code') + 1;
        $max_anniv = MemberAnniversary::where('member_no', $memno)->max('anniv');
        $member_cover_dates = MemberAnniversary::select(
            'start_date',
            'end_date',
            'renewal_date'
        )
            ->where('member_no',  $memno)
            ->where('anniv', $max_anniv)
            ->get();

        //check member status
        $status = MemberInfo::select(
            DB::raw("case 
                        when member_acceptance.status = 2 then 'Suspended'
                        when member_acceptance.status = 3 then 'Rejected'
                         when member_acceptance.status = 4 then 'Pending'
                        else
                            case 
                                when member_anniversary.end_date < '$today' then 'Cover Expired'
                                else
                                    case 
                                    when member_info.cancelled = 1 then 'Cancelled'
                                    else 'Active' 
                                    END                                       
                                    END
                                    END as status")
        )

            ->leftJoin('member_acceptance', 'member_acceptance.member_no', '=', 'member_info.member_no')
            ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
            ->where('member_info.member_no', $memno)
            ->where('member_anniversary.anniv', $max_anniv)
            ->get();

        $status = count($status) !== 0 ? $status[0]->status : '';

        if ($status !== '') {
            if ($status === 'Active') {
                $member = MemberInfo::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'family_relation.code as relation_code',
                    'family_relation.relation as relation',
                    't_gender.code',
                    't_gender.gender',
                    'member_info.dob',
                    'member_info.member_no',
                    'corporate.corporate'
                )
                    ->join('family_relation', 'member_info.family_title', '=', 'family_relation.code')
                    ->join('t_gender', 'member_info.gender', '=', 't_gender.code')
                    ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->where('member_info.member_no', $memno)
                    ->get();

                $member_benefits = MemberBenefits::select('benefit.code', 'benefit.benefit')
                    ->leftJoin('benefit', 'member_benefits.benefit', '=', 'benefit.code')
                    ->orderBy('benefit.benefit', 'ASC')
                    ->where('member_benefits.anniv', $max_anniv)
                    ->where('member_benefits.member_no', $memno)
                    ->get();

                $past_preauths = PreAuthorization::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    DB::raw("case when isnull(pre_authorization.cancelled, 0) = 1 then 'CANCELLED' else '' END as status"),
                    'provider.provider',
                    'benefit.benefit',
                    'pre_authorization.code',
                    'pre_authorization.member_no',
                    'pre_authorization.date_reported',
                    'pre_authorization.date_authorized',
                    'pre_authorization.pre_diagnosis',
                    'pre_authorization.available_limit',
                    'pre_authorization.admit_days',
                    'pre_authorization.reserve',
                    'pre_authorization.notes',
                    'pre_authorization.anniv',
                    'pre_authorization.reserve_exceeded_limit_reason'

                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'pre_authorization.member_no')
                    ->leftJoin('provider', 'provider.code', '=', 'pre_authorization.provider')
                    ->leftJoin('benefit', 'benefit.code', '=', 'pre_authorization.authority_type')
                    ->orderBy('pre_authorization.code', 'ASC')
                    ->where('pre_authorization.member_no', $memno)
                    ->get();

                $past_claims_reserve = ClaimsReserve::select(
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'benefit.benefit',
                    'claims_reserve.pre_auth_no',
                    'claims_reserve.member_no',
                    'claims_reserve.trans_type',
                    'claims_reserve.debit',
                    'claims_reserve.credit',
                    'claims_reserve.anniv',
                    'claims_reserve.id',
                )
                    ->leftJoin('member_info', 'member_info.member_no', '=', 'claims_reserve.member_no')
                    ->leftJoin('benefit', 'benefit.code', '=', 'claims_reserve.benefit')
                    ->where('claims_reserve.member_no', $memno)
                    ->get();

                $data = [
                    'member' => $member,
                    'anniv' => $max_anniv,
                    'm_benes' => $member_benefits,
                    'ref_no' => $max_ref_no,
                    'member_cover_dates' => $member_cover_dates,
                    'today' => $today,
                    'past_preauths' => $past_preauths,
                    'past_claims_reserve' => $past_claims_reserve,
                ];

                return $data;
            } else {
                return ['Member status is ' . $status];
            }
        } else {
            return ['Member status is unknown'];
        }
    }

    public function fetchMemberPreauths($memno)
    {
        $max_anniv = MemberAnniversary::where('member_no', $memno)->max('anniv');
        $member_preauths = PreAuthorization::select(
            'pre_authorization.code',
            'pre_authorization.auth_batch_no',
            'provider.provider',
            'benefit.benefit',
            'pre_authorization.available_limit',
            'pre_authorization.reserve'
        )
            ->leftJoin('provider', 'pre_authorization.provider', '=', 'provider.code')
            ->leftJoin('benefit', 'pre_authorization.authority_type', '=', 'benefit.code')
            ->where('member_no', $memno)
            ->where('anniv', $max_anniv)
            ->orderBy('pre_authorization.code', 'ASC')
            ->get();
        return $member_preauths;
    }

    public function fetchMemberPreauthData($code)
    {
        $preauth_data = PreAuthorization::where('code', $code)->get();
        return $preauth_data;
    }

    public function saveExtensionPreauth(Request $request)
    {
        $response = [];
        $max_ref_no = PreAuthorization::max('code') + 1;
        //set inserted, updated, and deleted items
        $inserted_item = "code : $max_ref_no";
        $updated_item = null;
        $deleted_item = null;

        $save_preauth = PreAuthorization::create([
            'code' => $max_ref_no,
            'member_no' => $request->member_no,
            'provider' => $request->provider,
            'date_reported' => $request->date_reported,
            'reported_by' => $request->reported_by,
            'date_authorized' => $request->date_autd,
            'authorized_by' => $request->authed_by,
            'pre_diagnosis' => $request->diagnosis,
            'authority_type' => $request->authority_type,
            'ward' => $request->ward,
            'available_limit' => $request->reserve,
            'admit_days' => $request->admit_days,
            'reserve' => $request->reserve,
            'notes' => $request->notes,
            'anniv' => $request->anniv,
            'auth_batch_no' => $request->auth_batch_no,
            'admission_date' => $request->admission,
            'discharge_date' => $request->discharge,
            'doc_fee' => $request->doc_fees
        ]);

        if ($save_preauth->wasRecentlyCreated === true) {
            array_push($response, $max_ref_no, "Preauth saved successfully");
            $max_claims_reserve_id = ClaimsReserve::max('id') + 1;
            $save_claims_reserve = ClaimsReserve::create([
                'id' => $max_claims_reserve_id,
                'pre_auth_no' => $max_ref_no,
                'member_no' => $request->member_no,
                'trans_type' => 1,
                'credit' => $request->reserve,
                'user_id' => $request->authed_by,
                'date_entered' => $request->date_autd,
                'benefit' => $request->authority_type,
                'anniv' => $request->anniv,
                'auth_batch_no' => $request->auth_batch_no
            ]);

            if ($save_claims_reserve->wasRecentlyCreated === true) {
                array_push($response, "Claims reserve saved successfully");

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            } else {
                array_push($response, "Error saving claims reserve");
            }
        } else {
            array_push($response, "Error saving Preauth");
        }

        return $response;
    }

    public function savePreauth(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $max_ref_no = PreAuthorization::max('code') + 1;
                $max_claims_reserve_id = ClaimsReserve::max('id') + 1;

                PreAuthorization::create([
                    'code' => $max_ref_no,
                    'member_no' => $request->member_no,
                    'provider' => $request->provider,
                    'date_reported' => $request->date_reported,
                    'reported_by' => $request->reported_by,
                    'date_authorized' => $request->date_autd,
                    'authorized_by' => $request->authed_by,
                    'pre_diagnosis' => $request->diagnosis,
                    'authority_type' => $request->authority_type,
                    'ward' => $request->ward,
                    'available_limit' => $request->reserve,
                    'admit_days' => $request->admit_days,
                    'reserve' => $request->reserve,
                    'notes' => $request->notes,
                    'anniv' => $request->anniv,
                    'auth_batch_no' => $max_ref_no,
                    'admission_date' => $request->admission,
                    'discharge_date' => $request->discharge,
                    'doc_fee' => $request->doc_fees,
                    'reserve_exceeded_limit_reason' => $request->reason_for_exceeding
                ]);

                ClaimsReserve::create([
                    'id' => $max_claims_reserve_id,
                    'pre_auth_no' => $max_ref_no,
                    'member_no' => $request->member_no,
                    'trans_type' => 1,
                    'credit' => $request->reserve,
                    'user_id' => $request->authed_by,
                    'date_entered' => $request->date_autd,
                    'benefit' => $request->authority_type,
                    'anniv' => $request->anniv,
                    'auth_batch_no' => $max_ref_no
                ]);
            });
            return ['Save successful.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function updatePreauth(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                //set inserted, updated, and deleted items
                $inserted_item = null;
                $updated_item = "code : $request->reference_no, provider : $request->provider, available_limit : $request->reserve";
                $deleted_item = null;
                PreAuthorization::where('code', $request->reference_no)->update([
                    'provider' => $request->provider,
                    'date_reported' => $request->date_reported,
                    'reported_by' => $request->reported_by,
                    'date_authorized' => $request->date_autd,
                    'authorized_by' => $request->authed_by,
                    'pre_diagnosis' => $request->diagnosis,
                    'authority_type' => $request->authority_type,
                    'ward' => $request->ward,
                    'available_limit' => $request->reserve,
                    'admit_days' => $request->admit_days,
                    'reserve' => $request->reserve,
                    'notes' => $request->notes,
                    'admission_date' => $request->admission,
                    'discharge_date' => $request->discharge,
                    'doc_fee' => $request->doc_fees,
                    'reserve_exceeded_limit_reason' => $request->reason_for_exceeding
                ]);

                ClaimsReserve::where('pre_auth_no', $request->reference_no)->update([
                    'user_id' => $request->authed_by,
                    'date_entered' => $request->date_autd,
                    'benefit' => $request->authority_type,
                    'credit' => $request->reserve,
                ]);

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });
            return ['Update successful.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function releaseReserve(Request $request)
    {
        try {
            $preauth_code = $request->preauth_code;

            $preauth = PreAuthorization::where('code', $preauth_code)->get();
            if (count($preauth) === 0) {
                return ['Error: Pre-authorization not found.'];
            }

            $bill_debits = Bills::where('pre_auth_no', $preauth_code)->where(DB::raw("isnull(vet_status, 0)"), 1)->sum(DB::raw('ISNULL(amount_payable, 0)'));
            $preauth_debits = ClaimsReserve::where('pre_auth_no', $preauth_code)->sum(DB::raw('ISNULL(debit, 0)'));
            $preauth_credits = ClaimsReserve::where('pre_auth_no', $preauth_code)->sum(DB::raw('ISNULL(credit, 0)'));
            $release = $preauth_credits - ($preauth_debits + $bill_debits);

            if ($release <= 0) {
               return ['Warning: Pre-auth has already been released!'];
            }
            $today = date("Y-m-d");
            $max_id = (ClaimsReserve::max('id') ?? 0) + 1;

            DB::transaction(function () use ($request, $preauth, $release, $max_id, $today) {
                //set inserted, updated, and deleted items
                $inserted_item = "preauth_no : $preauth[0]->code, member_no : $preauth[0]->member_no, release_amount : $release, release_id : $max_id";
                $updated_item = null;
                $deleted_item = null;

                ClaimsReserve::create([
                    'id' => $max_id,
                    'pre_auth_no' => $preauth[0]->code,
                    'member_no' => $preauth[0]->member_no,
                    'trans_type' => 2,
                    'debit' => $release,
                    'user_id' => $request->user,
                    'date_entered' => $today,
                    'benefit' => $preauth[0]->authority_type,
                    'anniv' => $preauth[0]->anniv,
                    'auth_batch_no' => $preauth[0]->code
                ]);

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });
            return ['Reserve released successfully.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function generatePdf(Request $request)
    {
        $module = $request->module == "preauth" ? "" : "[Extension]";
        $provider = Provider::where('code', $request->provider)->pluck('provider');
        $provider = strtoupper($provider[0]);
        $benefit = Benefit::where('code', $request->authority_type)->pluck('benefit');
        $benefit = strtoupper($benefit[0]);
        $ward = HospitalWard::where('code', $request->ward)->pluck('ward');
        $ward = strtoupper($ward[0]);
        $address = ClientAddress::where('code', 4)->get();
        $add = $address[0];
        $user = strtoupper($request->authed_by);
        $reported_by = strtoupper($request->reported_by);
        $diagnosis = strtoupper($request->diagnosis);
        $full_name = strtoupper($request->member_name);
        $notes = strtoupper($request->notes);
        $limit = number_format($request->reserve, 2);
        $room_limit = number_format($request->doc_fees, 2);
        $full_name_user = Users::select('full_names')->where('user_name', $request->authed_by)->get();
        $full_name_user = count($full_name_user) != '' ? $full_name_user[0]->full_names : $request->authed_by;


        $path = '';
        $user = strtolower(trim($user));

        $path_jpg = public_path("storage/imgs/care_signatures/{$user}.jpg");
        $path_jpeg = public_path("storage/imgs/care_signatures/{$user}.jpeg");
        $path_png = public_path("storage/imgs/care_signatures/{$user}.png");

        if (file_exists($path_jpg)) {
            $path = $this->getImage($path_jpg);
        } elseif (file_exists($path_png)) {
            $path = $this->getImage($path_png);
        } elseif (file_exists($path_jpeg)) {
            $path = $this->getImage($path_jpeg);
        }




        $signature = $path !== ''
            ? "<img src=\"$path\" height=\"60\"  width=\"150\"/>"
            : '';

        $logo = $this->getImage('storage/imgs/logo/aarug.png');

        $addr =
            " <span style='text-align:right'>$add->client_name<br>
                            $add->physical_location<br>
                            $add->box_no<br>
                            $add->tel_cell<br>
                            $add->fax<br>
                            $add->email<br>
                            $add->url<br> </span>";
        $body = "
                <br>
                <div style='font-size:17px'>
                The above named member of AAR GENERAL INSURANCE UGANDA LIMITED will be/was admitted to your hospital on the date noted above.<br/>
                AAR GENERAL INSURANCE UGANDA LIMITED will be responsible for hospital costs arising solely from care and treatment of the condition as stated above and not 
                for the care and treatment of any other condition without prior approval from the AAR GENERAL INSURANCE UGANDA LIMITED Case Management Team.<br>
                AAR GENERAL INSURANCE UGANDA LIMITED will not pay for any extras i.e. new born costs, attendants bills, extra bed charges, outside hospital stay, undisclosed 
                medical information related to exclusions, booking and other self requested charges, family planning among others.<br/>
                Should the charges exceed as per this commitment, recover the balance from the client.<br/>
                Please submit your invoice with a copy of this letter within 30 days from date of discharge. Invoices submitted after 30 days will be 
                considered stale and AAR GENERAL INSURANCE UGANDA LIMITED will therefore be unable to honour them.<br/>
                Thank you for your continued support.<br/>
                </div>
                <span style='font-weight:bold'>
                    Yours Sincerely,<br/>
                    <div style='height:100px;'>
                        " . ($signature !== '' ? $signature . "<br>" : "<br><br><br>") . "
                        $full_name_user<br/>
                        <u>CARE MANAGER</u><br/>
                        AAR GENERAL INSURANCE UGANDA LIMITED<br/>
                    </div>
                </span>
                ";

        $pdfDoc =
            "   
            <table style='width:100%; border-collapse:collapse; border: none;'>
                <tbody>
                    <tr style='border: none;'>
                        <td style='width:50%; border: none;'>
                            <img src='$logo' height='110' width='110'/>
                        </td>
                        <td style='width:50%; border: none;'>
                            $addr
                        </td>
                    </tr>
                </tbody>
                </table>
                <span style='text-align:center;font-weight:bold;font-size:15px;'>ADMISSION AUTHORIZATION $module</span>
                 <table style='border:1px solid black;'>
              <tbody>
              <tr>
              <td>
                <br>
                <div style='width:100%;'>
                    <table style='margin:0 auto;width:100%;font-weight:bold;font-size:12px;'>
                        <tr style='border:0'><td style='color:black;'>Full Name</td><td>:</td><td>$full_name</td><td></td><td></td><td style='color:black;'>Reference No</td><td>:</td><td>$request->reference_no</td></tr>
                        <tr style='border:0'><td style='color:black;'>Member No</td><td>:</td><td>$request->member_no</td><td></td><td></td><td style='color:black;'>Anniversary</td><td>:</td><td>$request->anniv</td></tr>
                        <tr style='border:0'><td style='color:black;'>Provider</td><td>:</td><td> $provider</td><td></td><td></td><td style='color:black;'>Reported By</td><td>:</td><td>$reported_by</td></tr>
                        <tr style='border:0'><td style='color:black;'>Benefit</td><td>:</td><td>$benefit</td><td></td><td></td><td style='color:black;'>Limit up to (Ugx)</td><td>:</td><td>$limit</td></tr>
                        <tr style='border:0'><td style='color:black;'>Diagnosis</td><td>:</td><td>$diagnosis</td><td></td><td></td><td style='color:black;'>Ward</td><td>:</td><td>$ward</td></tr>
                        <tr style='border:0'><td style='color:black;'>Date Reported</td><td>:</td><td>" . date("d/m/Y", strtotime($request->date_reported)) . "</td><td></td><td></td><td style='color:black;'>Date Authorized</td><td>:</td><td>" . date("d/m/Y", strtotime($request->date_autd)) . "</td></tr>
                        <tr style='border:0'><td style='color:black;'>Authorized By</td><td>:</td><td>$user</td><td></td><td></td><td>Room Limit</td><td>:</td><td>" . $room_limit  . "</td></tr>
                        <tr style='border:0'><td style='color:black;'>Notes</td><td>:</td><td>$notes</td><td></td><td></td><td></td><td></td><td></td></tr>
                    </table>
                </div>
                <div style='width:100%;'>
                 $body
                </div>
                 </td>
                </tr>
                </tbody>
             </table";

        return ['doc' => $pdfDoc];
    }

    private function getImage($path)
    {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
