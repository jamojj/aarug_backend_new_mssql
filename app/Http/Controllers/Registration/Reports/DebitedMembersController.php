<?php

namespace App\Http\Controllers\Registration\Reports;

use App\Http\Controllers\Controller;
use App\Models\PremiumInvoice;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;

class DebitedMembersController extends Controller
{
    //fetche member and debit notes
    public function fetchDebitedMembersReport(Request $request)
    {
        //return $request;
        //        try {
        //$arr = array();
        switch ($request->select_radio_option) {
            case '1':
                $data = PrincipalApplicant::select(
                    'corporate.corporate',
                    'principal_applicant.family_no',
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'member_info.member_no',
                    'member_anniversary.anniv',
                    'member_info.dealing_user',
                    'principal_applicant.employer'
                )
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                    ->where('member_info.relation_to_principal', 24)
                    //->where('principal_applicant.corp_id', $request -> corp_id)
                    ->groupBy(
                        'corporate.corporate',
                        'principal_applicant.family_no',
                        DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names)"),
                        DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names)"),
                        'member_info.member_no',
                        'member_anniversary.anniv',
                        'member_info.dealing_user',
                        'principal_applicant.employer'
                    )
                    ->get();
                $data = json_encode($data, JSON_INVALID_UTF8_IGNORE);
                return $data;
                break;
            case '2':
                $data = PrincipalApplicant::select(
                    'corporate.corporate',
                    'principal_applicant.family_no',
                    DB::raw("CONCAT(principal_applicant.surname,' ',principal_applicant.first_name,' ',principal_applicant.other_names) as principal_name"),
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'member_info.member_no',
                    'member_anniversary.anniv',
                    'member_info.dealing_user',
                    'principal_applicant.employer'
                )
                    ->leftJoin('corporate', 'corporate.corp_id', '=', 'principal_applicant.corp_id')
                    ->leftJoin('member_info', 'member_info.family_no', '=', 'principal_applicant.family_no')
                    ->leftJoin('member_anniversary', 'member_anniversary.member_no', '=', 'member_info.member_no')
                    ->where('member_info.relation_to_principal', 24)
                    ->where('principal_applicant.corp_id', $request->corp_id)
                    ->get();

                foreach ($data as $item) {
                    $debits = PremiumInvoice::select('premium_invoice.invoice_no', 'premium_invoice_details.member_no')
                        ->leftJoin('premium_invoice_details', 'premium_invoice_details.invoice_no', '=', 'premium_invoice.invoice_no')
                        ->where('premium_invoice.anniv', $item->anniv)
                        ->where('premium_invoice_details.member_no', $item->member_no)
                        ->get();

                    if (!$debits->count()) {
                        $item->invoice_no = null;
                    } else {
                        $item->invoice_no = $debits[0]['invoice_no'];
                    }
                }
                $data = json_encode($data, JSON_INVALID_UTF8_IGNORE);
                return $data;
                $arr = json_decode(json_encode($data), true);
                //return $arr;
                //                    return $arr['anniv'];
                for ($i = 0; $i < count($arr); $i++) {
                    //$anniv = $arr[$i]['anniv'];
                    echo 'Hi';
                    //return $anniv;
                    $anniv = isset($arr[$i]['anniv']) ? $arr[$i]['anniv'] : '';
                    $member_no = isset($arr[$i]['member_no']) ? $arr[$i]['member_no'] : '';
                    //return $anniv;
                    //return $arr[$i]['anniv'];
                    //return [$arr[$i]['anniv'], $arr[$i]['member_no']];
                    $debits = PremiumInvoice::select('premium_invoice.invoice_no')
                        ->leftJoin('premium_invoice_details', 'premium_invoice_details.invoice_no', '=', 'premium_invoice.invoice_no')
                        ->where('premium_invoice.anniv', $anniv)
                        ->where('premium_invoice_details.member_no', $member_no)
                        ->get();
                    echo 'Hello';
                    array_push($arr, $debits);

                    //                        return count($debits);
                    //                        if (count($debits) <= 0){
                    //                            array_push($arr, '');
                    //                        }else{
                    //                            array_push($arr, $debits);
                    //                        }

                    //return $debits;
                    //                        for ($j = 0; $j < count($debits); $j++){
                    //                            array_push($arr, $debits);
                    //                        }

                }
                //
                return $arr;
                break;
            default:
                break;
        }
        //        }
        //        catch (\Exception $e) {
        //            return ['error' => $e->getMessage()];
        //        }
    }
}
