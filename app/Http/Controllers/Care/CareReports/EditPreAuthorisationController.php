<?php

namespace App\Http\Controllers\Care\CareReports;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\ClientAddress;
use App\Models\HospitalWard;
use App\Models\PreAuthorization;
use App\Models\Provider;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EditPreAuthorisationController extends Controller
{
    //fetch pre auth data
    public function fetchPreAuthsToEdit(Request $request)
    {

        ini_set('max_execution_time', '0');
        $date_from = date('Ymd', strtotime($request->date_from));
        $date_to = date('Ymd', strtotime($request->date_to));
        $data = [];

        //search by member no and dates
        if ($request->options === "1") {
            $data = PreAuthorization::select(
                'pre_authorization.code',
                'pre_authorization.member_no',
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                'pre_authorization.provider as provider_code',
                'provider.provider',
                'pre_authorization.date_reported',
                'pre_authorization.date_authorized',
                'pre_authorization.pre_diagnosis',
                'principal_applicant.corp_id',
                'corporate.corporate',
                'pre_authorization.authority_type',
                'benefit.benefit',
                'pre_authorization.ward as ward_code',
                't_hospital_ward.ward',
                'pre_authorization.available_limit',
                'pre_authorization.admit_days',
                'pre_authorization.reserve',
                'pre_authorization.notes',
                'pre_authorization.co_signee',
                'pre_authorization.admission_date',
                'pre_authorization.discharge_date'
            )
                ->leftJoin('provider', 'provider.code', '=', 'pre_authorization.provider')
                ->leftJoin('benefit', 'benefit.code', '=', 'pre_authorization.authority_type')
                ->leftJoin('t_hospital_ward', 't_hospital_ward.code', '=', 'pre_authorization.ward')
                ->leftJoin('member_info', 'member_info.member_no', '=', 'pre_authorization.member_no')
                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->orderBy('pre_authorization.code')
                ->whereBetween(DB::raw("FORMAT(pre_authorization.date_authorized, 'yyyyMMdd')"), [$date_from, $date_to])
                ->where('pre_authorization.member_no', $request->member_no)
                ->get();

            //search all by dates
        } else {
            $data = PreAuthorization::select(
                'pre_authorization.code',
                'pre_authorization.member_no',
                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_names"),
                'pre_authorization.provider as provider_code',
                'provider.provider',
                'pre_authorization.date_reported',
                'pre_authorization.date_authorized',
                'pre_authorization.pre_diagnosis',
                'principal_applicant.corp_id',
                'corporate.corporate',
                'pre_authorization.authority_type',
                'benefit.benefit',
                'pre_authorization.ward as ward_code',
                't_hospital_ward.ward',
                'pre_authorization.available_limit',
                'pre_authorization.admit_days',
                'pre_authorization.reserve',
                'pre_authorization.notes',
                'pre_authorization.co_signee',
                'pre_authorization.admission_date',
                'pre_authorization.discharge_date'
            )
                ->leftJoin('provider', 'provider.code', '=', 'pre_authorization.provider')
                ->leftJoin('benefit', 'benefit.code', '=', 'pre_authorization.authority_type')
                ->leftJoin('t_hospital_ward', 't_hospital_ward.code', '=', 'pre_authorization.ward')
                ->leftJoin('member_info', 'member_info.member_no', '=', 'pre_authorization.member_no')
                ->leftJoin('principal_applicant', 'principal_applicant.family_no', '=', 'member_info.family_no')
                ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                ->orderBy('pre_authorization.code')
                ->whereBetween(DB::raw("FORMAT(pre_authorization.date_authorized, 'yyyyMMdd')"), [$date_from, $date_to])
                ->get();
        }



        return json_encode($data, JSON_INVALID_UTF8_IGNORE);
    }

    public function fetchOnePreauthToEdit($code)
    {
        $data = PreAuthorization::where('code', $code)->get();
        return $data;
    }

    //cancel preauth to release reserve amount
    public function cancelPreAuthToReleaseReserve($pre_auth_code)
    {
        //return $pre_auth_code;
        //set reserve and available limit to null
        try {
            $available_limit = PreAuthorization::select('available_limit')
                ->where('code', $pre_auth_code)
                ->pluck('available_limit');

            $reserve = PreAuthorization::select('reserve')
                ->where('code', $pre_auth_code)
                ->pluck('reserve');
            if ($available_limit[0] == null || $reserve[0] == null) {
                return ['PreAuth has no Available Limit nor Reserve'];
            } else {
                PreAuthorization::where([
                    'code' => $pre_auth_code
                ])
                    ->update([
                        'available_limit' => NULL,
                        'reserve' => NULL,
                    ]);

                return ['Cancellation OK ! Reserve Amount Released'];
            }
        } catch (\Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    //save pre_auth edit
    public function savePreAuthEdit(Request $request)
    {
        ini_set('max_execution_time', '0');
        try {
            for ($i = 0; $i < count($request->code); $i++) {
                DB::transaction(function () use ($request, $i) {
                    PreAuthorization::where([
                        'code' => $request->code[$i]
                    ])
                        ->update([
                            'member_no' => $request->member_no[$i],
                            'provider' => $request->provider[$i],
                            'date_reported' => $request->date_reported[$i],
                            'date_authorized' => $request->date_authorized[$i],
                            'pre_diagnosis' => $request->pre_diagnosis[$i],
                            'authority_type' => $request->authority_type[$i],
                            'ward' => $request->ward[$i],
                            'available_limit' => $request->available_limit[$i],
                            'admit_days' => $request->admit_days[$i],
                            'reserve' => $request->reserve[$i],
                            'notes' => $request->notes[$i],
                            'co_signee' => $request->co_signee[$i],
                            'admission_date' => $request->admission_date[$i],
                            'discharge_date' => $request->discharge_date[$i],
                        ]);
                });
            }
            return ['Save Ok. PreAuths Edit Successful'];
        } catch (\Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    //generate pdf for edit preauth
    public function editPreauthGeneratePdf(Request $request)
    {
        $data = PreAuthorization::select(
            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
            'provider.provider',
            'benefit.benefit',
            'pre_authorization.code',
            'pre_authorization.member_no',
            'pre_authorization.date_reported',
            'pre_authorization.date_authorized',
            'pre_authorization.reported_by',
            'pre_authorization.pre_diagnosis',
            'pre_authorization.available_limit',
            'pre_authorization.admit_days',
            'pre_authorization.reserve',
            'pre_authorization.notes',
            'pre_authorization.anniv',
            'pre_authorization.authorized_by',
            'pre_authorization.auth_batch_no',
            'pre_authorization.reserve_exceeded_limit_reason',
            't_hospital_ward.ward',
            'pre_authorization.doc_fee',

        )
            ->leftJoin('member_info', 'member_info.member_no', '=', 'pre_authorization.member_no')
            ->leftJoin('provider', 'provider.code', '=', 'pre_authorization.provider')
            ->leftJoin('benefit', 'benefit.code', '=', 'pre_authorization.authority_type')
            ->leftJoin('t_hospital_ward', 't_hospital_ward.code', '=', 'pre_authorization.ward')
            ->orderBy('pre_authorization.code', 'ASC')
            ->where('pre_authorization.code', $request->code)
            ->get();


        if (count($data) !== 0) {
            $module = $data[0]->auth_batch_no == $data[0]->code ? "" : "[Extension]";
            $provider = strtoupper($data[0]->provider);
            $benefit = strtoupper($data[0]->benefit);
            $ward = strtoupper($data[0]->ward);
            $address = ClientAddress::where('code', 4)->get();
            $add = $address[0];
            $authorized_by = strtoupper($data[0]->authorized_by);
            $reported_by = strtoupper($data[0]->reported_by);
            $diagnosis = strtoupper($data[0]->pre_diagnosis);
            $full_name = strtoupper($data[0]->member_name);
            $notes = strtoupper($data[0]->notes);
            $limit = number_format($data[0]->reserve, 2);
            $mem_no = $data[0]->member_no;
            $date_reported = $data[0]->date_reported;
            $reference_no = $data[0]->code;
            $anniv = $data[0]->anniv;
            $date_autd = $data[0]->date_authorized;
            $room_limit = number_format($data[0]->doc_fees, 2);

            $full_name_user = Users::select('full_names')->where('user_name', $authorized_by)->get();
            $full_name_user = count($full_name_user) != '' ? $full_name_user[0]->full_names : $authorized_by;

            $path = '';
            $authorized_by = strtolower(trim($authorized_by));

            $path_jpg = public_path("storage/imgs/care_signatures/{$authorized_by}.jpg");
            $path_png = public_path("storage/imgs/care_signatures/{$authorized_by}.png");

            if (file_exists($path_jpg)) {
                $path = $this->getImage($path_jpg);
            } elseif (file_exists($path_png)) {
                $path = $this->getImage($path_png);
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
                    </div>
                    Thank you for your continued support.<br/>
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
                        <tr style='border:0'><td style='color:black;'>Full Name</td><td>:</td><td>$full_name</td><td></td><td></td><td style='color:black;'>Reference No</td><td>:</td><td>$reference_no</td></tr>
                        <tr style='border:0'><td style='color:black;'>Member No</td><td>:</td><td> $mem_no</td><td></td><td></td><td style='color:black;'>Anniversary</td><td>:</td><td>$anniv</td></tr>
                        <tr style='border:0'><td style='color:black;'>Provider</td><td>:</td><td> $provider</td><td></td><td></td><td style='color:black;'>Reported By</td><td>:</td><td>$reported_by</td></tr>
                        <tr style='border:0'><td style='color:black;'>Benefit</td><td>:</td><td>$benefit</td><td></td><td></td><td style='color:black;'>Limit up to (Ugx)</td><td>:</td><td>$limit</td></tr>
                        <tr style='border:0'><td style='color:black;'>Diagnosis</td><td>:</td><td>$diagnosis</td><td></td><td></td><td style='color:black;'>Ward</td><td>:</td><td>$ward</td></tr>
                        <tr style='border:0'><td style='color:black;'>Date Reported</td><td>:</td><td>" . date("d/m/Y", strtotime($date_reported)) . "</td><td></td><td></td><td style='color:black;'>Date Authorized</td><td>:</td><td>" . date("d/m/Y", strtotime($date_autd)) . "</td></tr>
                       <tr style='border:0'><td style='color:black;'>Authorized By</td><td>:</td><td>$authorized_by</td><td></td><td></td><td>Room Limit</td><td>:</td><td>" . $room_limit  . "</td></tr>
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
    }

    private function getImage($path)
    {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
