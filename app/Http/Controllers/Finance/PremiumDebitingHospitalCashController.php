<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Corporate\CorpAnniversary;
use App\Models\Corporate\Corporate;
use App\Models\PremiumInvoice;
use App\Models\PremiumInvoiceDetails;
use App\Models\PremiumInvoiceDetailsSammary;
use App\Models\PremiumInvoiceMembers;
use App\Models\ProductName;
use App\Models\Registration\MemberAnniversary;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isNull;

class PremiumDebitingHospitalCashController extends Controller
{
    public function validateHospitalCashMembers(Request $request)
    {
        // return $request;
        ini_set('max_execution_time', '0');
        $response = [];
        $corp_id =  null;
        $ttl_iiu = $ttl_ira = $ttl_smart_card = $ttl_access_fee = $ttl_stamp_duty = $ttl_net_premium = $ttl_amount_payable = 0;

        if (isset($request->index)) {
            for ($i = 0; $i < count($request->index); $i++) {
                //calculate totals for corporate category
                // if ($request->category === "1" && $request->corporate[$i] !== 'RETAIL HOSPITAL CASH') {
                if ($request->category === "1") {
                    $ttl_iiu += $request->iiu[$i];
                    $ttl_ira += $request->ira[$i];
                    $ttl_smart_card += $request->smart_card[$i];
                    $ttl_access_fee += $request->access_fee[$i];
                    $ttl_stamp_duty += $request->stamp_duty[$i];
                    $ttl_net_premium += $request->net_premium[$i];
                }
                //check corporate
                $corporate = Corporate::where('corporate', $request->corporate[$i])->count();
                if ($corporate !== 0) {
                    //get corp_id
                    if (is_null($corp_id)) {
                        $corp_id = Corporate::select('corp_id')->where('corporate', $request->corporate[$i])->get();
                        $corp_id = count($corp_id) !== 0 ? $corp_id[0]->corp_id : null;
                    }

                    //check if members exist
                    $member_exist = MemberInfo::where('member_no', $request->member_no[$i])->count();
                    if ($member_exist !== 0) {
                        //check if member is under hospital cash
                        $hospital_cash_member = MemberInfo::where('member_no', $request->member_no[$i])->where('hc_status', 1)->count();
                        if ($hospital_cash_member !== 0) {
                            //member is principal
                            $principal = MemberInfo::where('relation_to_principal', 24)->where('member_no', $request->member_no[$i])->count();
                            if ($principal !== 0) {
                                //check if member is active
                                $member_active = MemberInfo::where('member_no', $request->member_no[$i])
                                    ->where(DB::raw("isnull(cancelled, 0)"), 0)
                                    ->count();

                                if ($member_active !== 0) {
                                    //check if start and end date are correct
                                    $check_cover_dates = MemberAnniversary::where('member_no', $request->member_no[$i])->where('start_date', $request->start_date[$i])->where('end_date', $request->end_date[$i])->count();
                                    if ($check_cover_dates !== 0) {
                                        //check if end date is greater than today
                                        $check_cover_dates = MemberAnniversary::where('member_no', $request->member_no[$i])->where('end_date', '>=', today())->count();
                                        if ($check_cover_dates !== 0) {
                                            //check health plan
                                            $max_anniv = MemberAnniversary::where('member_no', $request->member_no[$i])->max('anniv');
                                            $health_plan = MemberAnniversary::select('health_plan')->where('member_no', $request->member_no[$i])->where('anniv', $max_anniv)->get();
                                            $health_plan = count($health_plan) ? $health_plan[0]->health_plan : '';
                                            if ($health_plan === $request->health_plan[$i]) {
                                                // check product name
                                                $h_option = MemberAnniversary::select('h_option')->where('member_no', $request->member_no[$i])->where('anniv', $max_anniv)->get();
                                                $h_option = count($h_option) ? $h_option[0]->h_option : '';
                                                $product_name = ProductName::select('product_name')->where('code', $h_option)->get();
                                                $product_name = count($product_name) ? $product_name[0]->product_name : '';
                                                if ($product_name === $request->product_name[$i]) {
                                                    //check if agent exist
                                                    $agent = Agent::select('status')->where('agent_name', $request->agent[$i])->get();
                                                    if (count($agent) !== 0) {
                                                        //check agent status  
                                                        if ($agent[0]->status != 1) {
                                                            $dt = [
                                                                "index" => $request->index[$i],
                                                                "res" => "The agent is suspended or deactivated!"
                                                            ];
                                                            array_push($response, $dt);
                                                        } else {
                                                            $dt = [
                                                                "index" => $request->index[$i],
                                                                "res" => "Data is correct"

                                                            ];
                                                            array_push($response, $dt);
                                                        }





                                                        //check corporate
                                                        // $corporate = Corporate::where('corporate', $request->corporate[$i])->count();
                                                        // if ($corporate !== 0) {
                                                        //     //get corp_id
                                                        //     if (is_null($corp_id)) {
                                                        //         $corp_id = Corporate::select('corp_id')->where('corporate', $request->corporate[$i])->get();
                                                        //         $corp_id = count($corp_id) !== 0 ? $corp_id[0]->corp_id : null;
                                                        //     }
                                                        //     $dt = [
                                                        //         "index" => $request->index[$i],
                                                        //         "res" => "Data is correct"

                                                        //     ];
                                                        //     array_push($response, $dt);
                                                        // } else {
                                                        //     $dt = [
                                                        //         "index" => $request->index[$i],
                                                        //         "res" => "Corporate does not exist"

                                                        //     ];
                                                        //     array_push($response, $dt);
                                                        // }
                                                    } else {
                                                        $dt = [
                                                            "index" => $request->index[$i],
                                                            "res" => "Agent does not exist"

                                                        ];
                                                        array_push($response, $dt);
                                                    }
                                                } else {
                                                    $dt = [
                                                        "index" => $request->index[$i],
                                                        "res" => "Product Name is not correct"

                                                    ];
                                                    array_push($response, $dt);
                                                }
                                            } else {
                                                $dt = [
                                                    "index" => $request->index[$i],
                                                    "res" => "Health plan is not correct"

                                                ];
                                                array_push($response, $dt);
                                            }
                                        } else {
                                            $dt = [
                                                "index" => $request->index[$i],
                                                "res" => "Member cover is expired"

                                            ];
                                            array_push($response, $dt);
                                        }
                                    } else {
                                        $dt = [
                                            "index" => $request->index[$i],
                                            "res" => "Cover dates are  not correct"

                                        ];
                                        array_push($response, $dt);
                                    }
                                } else {
                                    $dt = [
                                        "index" => $request->index[$i],
                                        "res" => "Member is cancelled"

                                    ];
                                    array_push($response, $dt);
                                }
                            } else {
                                $dt = [
                                    "index" => $request->index[$i],
                                    "res" => "Member is not a principal"

                                ];
                                array_push($response, $dt);
                            }
                        } else {
                            $dt = [
                                "index" => $request->index[$i],
                                "res" => "Member is not a hospital cash member"

                            ];
                            array_push($response, $dt);
                        }
                    } else {
                        //member does not exist
                        $dt = [
                            "index" => $request->index[$i],
                            "res" => "Member does not exist"

                        ];
                        array_push($response, $dt);
                    }
                } else {
                    $dt = [
                        "index" => $request->index[$i],
                        "res" => "Corporate does not exist"
                    ];
                    array_push($response, $dt);
                }
            }
        }

        //fetch corporate information
        if (is_null($corp_id)) {
            $max_anniv = $start_date =  $start_date =  $end_date =  $days =   $agent_id =  $unit_manager = $agent_status = null;
        } else {
            //corporate
            if ($request->category === "1") {
                $max_anniv = CorpAnniversary::where('corp_id', $corp_id)->max('anniv');
                $start_date = CorpAnniversary::select('start_date')->where('corp_id', $corp_id)->where('anniv', $max_anniv)->get();
                $start_date = count($start_date) !== 0 ?  $start_date[0]->start_date : null;
                $end_date = CorpAnniversary::select('end_date')->where('corp_id', $corp_id)->where('anniv', $max_anniv)->get();
                $end_date = count($end_date) !== 0 ?  $end_date[0]->end_date : null;

                $start_date_f = \Carbon\Carbon::parse($start_date);
                $end_date_f = \Carbon\Carbon::parse($end_date);

                $days = $start_date_f->diffInDays($end_date_f, false);
                $days = $days > 0 ? $days + 1 : 0;
                $agent_id = Corporate::select('agent_id')->where('corp_id', $corp_id)->get();
                $agent_id = count($agent_id) !== 0 ?  $agent_id[0]->agent_id : null;
                $agent_status = Agent::select('status')->where('agent_id', $agent_id)->get();
                $agent_status = count($agent_status) !== 0 ?  $agent_status[0]->status : '';

                $unit_manager = Agent::select('unit_manager')->where('agent_id', $agent_id)->get();
                $unit_manager = count($unit_manager) !== 0 ?  $unit_manager[0]->unit_manager : null;
                //retail
            } else {
                $max_anniv = $start_date =  $start_date =  $end_date =  $days =   $agent_id =  $unit_manager = $agent_status = null;
            }
        }
        //calculate total amount payable
        $ttl_amount_payable = $ttl_iiu + $ttl_ira + $ttl_smart_card + $ttl_access_fee + $ttl_stamp_duty + $ttl_net_premium;

        $corp_data = [
            'corp_id' => $corp_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'days' => $days,
            'max_anniv' => $max_anniv,
            'agent_id' => $agent_id,
            'agent_status' => $agent_status,
            'unit_manager' => $unit_manager,
            'iiu' => number_format($ttl_iiu, 2, '.', ''),
            'ira' => number_format($ttl_ira, 2, '.', ''),
            'smart_card' => number_format($ttl_smart_card, 2, '.', ''),
            'access_fee' => number_format($ttl_access_fee, 2, '.', ''),
            'stamp_duty' => number_format($ttl_stamp_duty, 2, '.', ''),
            'net_premium' => number_format($ttl_net_premium, 2, '.', ''),
            'amount_payable' => number_format($ttl_amount_payable, 2, '.', ''),

        ];
        return ['response' => $response, 'corp_data' => $corp_data];
    }

    public function savePremiumDebitingHospitalCash(Request $request)
    {
        // return $request;
        ini_set('max_execution_time', '0');
        $response = [];
        $prorate_days = 0;
        $today = date('Y-m-d');

        //get maximums from tables affected
        $premium_invoice_max_idx = PremiumInvoice::max('idx');
        if (is_null($premium_invoice_max_idx)) {
            $premium_invoice_max_idx = 0;
        }
        $premium_invoice_members_max_idx = PremiumInvoiceMembers::max('idx');
        if (is_null($premium_invoice_members_max_idx)) {
            $premium_invoice_members_max_idx = 0;
        }
        $premium_invoice_details_max_idx = PremiumInvoiceDetails::max('idx');
        if (is_null($premium_invoice_details_max_idx)) {
            $premium_invoice_details_max_idx = 0;
        }
        $premium_invoice_details_sammary_max_idx = PremiumInvoiceDetailsSammary::max('id_key');
        if (is_null($premium_invoice_details_sammary_max_idx)) {
            $premium_invoice_details_sammary_max_idx = 0;
        }

        //get prorate days
        if ($request->days_corp < 365) {
            $prorate_days = $request->days;
        } else if ($request->days_corp >= 365) {
            $prorate_days = null;
        }
        try {

            if (isset($request->index)) {
                 //check if corporate is under corporate or retail
                 $individual = Corporate::select('individual')->where('corp_id', $request->corp_id)->get();
                 $individual = count( $individual) !== 0 ?  $individual[0]->individual : null;
                //corporate debiting
                if ($request->category == "1") {                   
                    // if ($request->corp_id != 658 && $request->corp_id != 14  && $request->corp_id != 763 && $request->corp_id != 775) {
                    if ($individual  == 1) {
                        $relation = null;
                        $premium_invoice_details_sammary_max_idx++;
                        $premium_invoice_max_idx++;
                        $reference_no = 'DBT' . '-' . $premium_invoice_max_idx;
                        DB::transaction(function () use (
                            $request,
                            $relation,
                            $today,
                            $prorate_days,
                            $reference_no,
                            $premium_invoice_details_max_idx,
                            $premium_invoice_max_idx,
                            $premium_invoice_details_sammary_max_idx,
                            $premium_invoice_members_max_idx

                        ) {

                            //set inserted, updated, and deleted items
                            $inserted_item = "invoice_no : $reference_no";
                            $updated_item = null;
                            $deleted_item = null;

                            PremiumInvoice::create([
                                'INVOICE_NO' => $reference_no,
                                'CORP_ID' => $request->corp_id,
                                'INVOICE_DATE' => $today,
                                'INVOICE_TOTAL' => str_replace(',', '', $request->amount_payable_corp),
                                'ANNIV' => $request->anniv_corp,
                                'USER_ID' => $request->user,
                                'DATE_ENTERED' => $today,
                                'premium_type' => 1,
                                'INVOICE_NET' => str_replace(',', '', $request->amount_payable_corp),
                                'AGENT_ID' => $request->agent_id_corp,
                                'PREMIUM_NET' => str_replace(',', '', $request->net_premium_corp),
                                'insurer' => 1,
                                'cover_start_date' => $request->start_date_corp,
                                'new_renewal' => 1,
                                'phcf' => str_replace(',', '', $request->iiu_corp),
                                'tl' => str_replace(',', '', $request->ira_corp),
                                'smart_cost' => str_replace(',', '', $request->smart_card_corp),
                                'access_fee' => str_replace(',', '', $request->access_fee_corp),
                                'stamp_duty' => str_replace(',', '', $request->stamp_duty_corp),
                                'credit_note' => 1,
                                'prorate_days' => $prorate_days,
                                'idx' => $premium_invoice_max_idx,
                                'end_date' => $request->end_date_corp,
                                'days' => $request->days_corp,
                                'agent_rate' => 10,
                                'bdm_rate' => 2.80,
                                'ass_hos_rate' => 1.00,
                                'hos_rate' => 1.50,
                            ]);

                            //save to premium invoice details summary
                            PremiumInvoiceDetailsSammary::create([
                                'id_key' => $premium_invoice_details_sammary_max_idx,
                                'invoice_no' => $reference_no,
                                'class' => 1,
                                'net_premium' => str_replace(',', '', $request->net_premium_corp),
                            ]);


                            //loop and save into two tables - Premium Invoice Details and Premium Invoice Members
                            for ($i = 0; $i < count($request->member_no); $i++) {
                                //increment idx
                                $premium_invoice_details_max_idx++;

                                //get family number for each member number row from requests
                                $family_no = explode('-', $request->member_no[$i]);
                                $family_no = $family_no[0];
                                //fetch member data                                
                                $family_size = PrincipalApplicant::select('family_size')->where('family_no', $family_no)->get();
                                $family_size = count($family_size) !== 0 ? ($family_size[0]->family_size) + 1 : null;
                                $family_title = MemberInfo::select('family_title')->where('member_no', $request->member_no[$i])->get();
                                $family_title = count($family_title) !== 0 ? ($family_title[0]->family_title) + 1 : null;
                                $product_code = ProductName::select('code')->where('product_name', $request->product_name[$i])->get();
                                $product_code = count($product_code) ? $product_code[0]->code : null;
                                //calculate age
                                $dob = MemberInfo::select('dob')->where('member_no', $request->member_no[$i])->get();
                                $dob = count($dob) !== 0 ? $dob[0]->dob : null;
                                $diff = abs(strtotime($dob) - strtotime($today));
                                $age = floor($diff / (365 * 60 * 60 * 24));
                                //member anniversary date difference
                                $start_date_f = \Carbon\Carbon::parse($request->start_date[$i]);
                                $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);
                                //calculating prorata period
                                $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;

                                //getting relation
                                if ($family_title == 3) {
                                    $relation = 24;
                                } else {
                                    $relation = 25;
                                }
                                PremiumInvoiceDetails::create([
                                    'family_no' => $family_no,
                                    'member_no' => $request->member_no[$i],
                                    'premium' => str_replace(',', '', $request->net_premium[$i]),
                                    'invoice_no' => $reference_no,
                                    'family_size' => $family_size,
                                    'total' => str_replace(',', '', $request->net_premium[$i]),
                                    'prorated_prem' => str_replace(',', '', $request->net_premium[$i]),
                                    'discounted_prem' => str_replace(',', '', $request->net_premium[$i]),
                                    'loaded_prem' => str_replace(',', '', $request->net_premium[$i]),
                                    'category' => $request->health_plan[$i],
                                    'idx' => $premium_invoice_details_max_idx,
                                    'member_age' => $age,
                                    'product_name' => $product_code,
                                    'family_title' => $family_title,
                                    'prorata_period' => $ll_pro_unit,
                                    'effective_date' => $request->start_date[$i],
                                    'end_date' => $request->end_date[$i],
                                    'smart_cost' => $request->smart_card[$i],
                                    'access_fee' => $request->access_fee[$i],
                                ]);
                                //increment idx
                                $premium_invoice_members_max_idx++;
                                PremiumInvoiceMembers::create([
                                    'idx' => $premium_invoice_members_max_idx,
                                    'invoice_no' => $reference_no,
                                    'member_no' => $request->member_no[$i],
                                    'anniv' => $request->anniv_corp,
                                    'start_date' => $request->start_date[$i],
                                    'relation' => $relation,
                                    'premium' => str_replace(',', '', $request->net_premium[$i]),
                                ]);
                            }

                            //save access log
                            app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                        });
                        array_push($response, 'Hospital cash corporate Debited successfully. Debit Note: ' . $reference_no);
                    } else {
                        //get corporate name
                        $corporate = Corporate::where('corp_id', $request->corp_id)->pluck('corporate');
                        array_push($response, $corporate[0] . ' is not listed as a corporate but as retail!');
                    }
                    //retail
                } else {
                    //check if corporate is under corporate or retail
                    // if ($request->corp_id == 658 || $request->corp_id == 763 || $request->corp_id == 775) {
                    if ($individual == 2) {
                        $relation = null;
                        for ($i = 0; $i < count($request->member_no); $i++) {
                            $premium_invoice_details_sammary_max_idx++;

                            $premium_invoice_max_idx++;
                            $reference_no = 'DBT' . '-' . $premium_invoice_max_idx;
                            $invoice_total = $request->net_premium[$i] + $request->iiu[$i] + $request->ira[$i] + $request->smart_card[$i] + $request->access_fee[$i] + $request->stamp_duty[$i];
                            //set inserted, updated, and deleted items
                            $inserted_item = "invoice_no : $reference_no";
                            $updated_item = null;
                            $deleted_item = null;


                            //get family number for each member number row from requests
                            $family_no = explode('-', $request->member_no[$i]);
                            $family_no = $family_no[0];
                            //get family count
                            $family_count = MemberInfo::where(DB::raw("isnull(cancelled, 0)"), 0)->where('family_no', $family_no)->count('member_no');
                            $ttl_net_premium = str_replace(',', '', $request->net_premium[$i]) * $family_count;
                            $ttl_iiu = str_replace(',', '', $request->iiu[$i]) * $family_count;
                            $ttl_ira = str_replace(',', '', $request->ira[$i]) * $family_count;
                            $ttl_smart_card = str_replace(',', '', is_null($request->smart_card[$i]) ? 0 : $request->smart_card[$i]) * $family_count;
                            $ttl_access_fee = str_replace(',', '', is_null($request->access_fee[$i]) ? 0 : $request->access_fee[$i]) * $family_count;
                            $ttl_stamp_duty = str_replace(',', '', is_null($request->stamp_duty[$i]) ? 0 : $request->stamp_duty[$i]);
                            $ttl_invoice = $ttl_net_premium + $ttl_iiu + $ttl_ira + $ttl_smart_card + $ttl_access_fee + $ttl_stamp_duty;
                                    DB::transaction(
                                        function () use (
                                            $request,
                                            $relation,
                                            $today,
                                            $premium_invoice_details_max_idx,
                                            $premium_invoice_max_idx,
                                            $premium_invoice_details_sammary_max_idx,
                                            $premium_invoice_members_max_idx,
                                            $reference_no,
                                            $invoice_total,
                                            $inserted_item,
                                            $updated_item,
                                            $deleted_item,
                                            $family_no, 
                                            $family_count,
                                            $ttl_net_premium,
                                            $ttl_iiu,
                                            $ttl_ira,
                                            $ttl_smart_card,
                                            $ttl_access_fee,
                                            $ttl_stamp_duty,
                                            $ttl_invoice,
                                            $i
                                        ) {

                                    // PremiumInvoice::create([
                                    //     'INVOICE_NO' => $reference_no,
                                    //     'MEMBER_NO' => $request->member_no[$i],
                                    //     'INVOICE_DATE' => $today,
                                    //     'INVOICE_TOTAL' => str_replace(',', '', $invoice_total),
                                    //     'ANNIV' => 1,
                                    //     'USER_ID' => $request->user,
                                    //     'DATE_ENTERED' => $today,
                                    //     'premium_type' => 1,
                                    //     'INVOICE_NET' => str_replace(',', '', $invoice_total),
                                    //     'AGENT_ID' => $request->agent_id_corp,
                                    //     'PREMIUM_NET' => str_replace(',', '', $request->net_premium[$i]),
                                    //     'insurer' => 1,
                                    //     'cover_start_date' => $request->start_date[$i],
                                    //     'new_renewal' => 1,
                                    //     'phcf' => str_replace(',', '', $request->iiu[$i]),
                                    //     'tl' => str_replace(',', '', $request->ira[$i]),
                                    //     'smart_cost' => str_replace(',', '', $request->smart_card[$i]),
                                    //     'access_fee' => str_replace(',', '', $request->access_fee[$i]),
                                    //     'stamp_duty' => str_replace(',', '', $request->stamp_duty[$i]),
                                    //     'credit_note' => 1,
                                    //     'prorate_days' => 365,
                                    //     'idx' => $premium_invoice_max_idx,
                                    //     'end_date' => $request->end_date[$i],
                                    //     'days' => 365,
                                    //     'agent_rate' => 10,
                                    //     'bdm_rate' => 2.80,
                                    //     'ass_hos_rate' => 1.00,
                                    //     'hos_rate' => 1.50,
                                    // ]);
                                    PremiumInvoice::create([
                                        'INVOICE_NO' => $reference_no,
                                        'MEMBER_NO' => $request->member_no[$i],
                                        'INVOICE_DATE' => $today,
                                        'INVOICE_TOTAL' => $ttl_invoice,
                                        'ANNIV' => 1,
                                        'USER_ID' => $request->user,
                                        'DATE_ENTERED' => $today,
                                        'premium_type' => 1,
                                        'INVOICE_NET' => $ttl_invoice,
                                        'AGENT_ID' => $request->agent_id_corp,
                                        'PREMIUM_NET' => $ttl_net_premium,
                                        'insurer' => 1,
                                        'cover_start_date' => $request->start_date[$i],
                                        'new_renewal' => 1,
                                        'phcf' => $ttl_iiu,
                                        'tl' => $ttl_ira,
                                        'smart_cost' => $ttl_smart_card,
                                        'access_fee' => $ttl_access_fee,
                                        'stamp_duty' => $ttl_stamp_duty,
                                        'credit_note' => 1,
                                        'prorate_days' => 365,
                                        'idx' => $premium_invoice_max_idx,
                                        'end_date' => $request->end_date[$i],
                                        'days' => 365,
                                        'agent_rate' => 10,
                                        'bdm_rate' => 2.80,
                                        'ass_hos_rate' => 1.00,
                                        'hos_rate' => 1.50,
                                    ]);

                                    //save to premium invoice details summary
                                    // PremiumInvoiceDetailsSammary::create([
                                    //     'id_key' => $premium_invoice_details_sammary_max_idx,
                                    //     'invoice_no' => $reference_no,
                                    //     'class' => 1,
                                    //     'net_premium' => str_replace(',', '', $request->net_premium[$i]),
                                    // ]);
                                    PremiumInvoiceDetailsSammary::create([
                                        'id_key' => $premium_invoice_details_sammary_max_idx,
                                        'invoice_no' => $reference_no,
                                        'class' => 1,
                                        'net_premium' => $ttl_net_premium
                                    ]);

                                    //fetch member data                                    
                                    $family_size = PrincipalApplicant::select('family_size')->where('family_no', $family_no)->get();
                                    $family_size = count($family_size) !== 0 ? ($family_size[0]->family_size) + 1 : null;
                                    $family_title = MemberInfo::select('family_title')->where('member_no', $request->member_no[$i])->get();
                                    $family_title = count($family_title) !== 0 ? ($family_title[0]->family_title) + 1 : null;
                                    $product_code = ProductName::select('code')->where('product_name', $request->product_name[$i])->get();
                                    $product_code = count($product_code) ? $product_code[0]->code : null;
                                    //calculate age
                                    $dob = MemberInfo::select('dob')->where('member_no', $request->member_no[$i])->get();
                                    $dob = count($dob) !== 0 ? $dob[0]->dob : null;
                                    $diff = abs(strtotime($dob) - strtotime($today));
                                    $age = floor($diff / (365 * 60 * 60 * 24));
                                    //member anniversary date difference
                                    $start_date_f = \Carbon\Carbon::parse($request->start_date[$i]);
                                    $end_date_f = \Carbon\Carbon::parse($request->end_date[$i]);
                                    //calculating prorata period
                                    $ll_pro_unit = $start_date_f->diffInDays($end_date_f, false) + 1;

                                    //getting relation
                                    if ($family_title == 3) {
                                        $relation = 24;
                                    } else {
                                        $relation = 25;
                                    }

                                    $mem_nos_for_family = MemberInfo::select('member_no')->where(DB::raw("isnull(cancelled, 0)"), 0)->where('family_no', $family_no)->get();

                                    foreach ($mem_nos_for_family as $mem_no) {
                                        $premium_invoice_members_max_idx++;
                                        $premium_invoice_details_max_idx++;
                                        PremiumInvoiceDetails::create([
                                            'idx' => $premium_invoice_details_max_idx,
                                            'family_no' => $family_no,
                                            'member_no' => $mem_no->member_no,
                                            'premium' => str_replace(',', '', $request->net_premium[$i]),
                                            'invoice_no' => $reference_no,
                                            'family_size' => $family_size,
                                            'total' => str_replace(',', '', $request->net_premium[$i]),
                                            'prorated_prem' => str_replace(',', '', $request->net_premium[$i]),
                                            'discounted_prem' => str_replace(',', '', $request->net_premium[$i]),
                                            'loaded_prem' => str_replace(',', '', $request->net_premium[$i]),
                                            'category' => $request->health_plan[$i],
                                            'member_age' => $age,
                                            'product_name' => $product_code,
                                            'family_title' => $family_title,
                                            'prorata_period' => $ll_pro_unit,
                                            'effective_date' => $request->start_date[$i],
                                            'end_date' => $request->end_date[$i],
                                            'smart_cost' => $request->smart_card[$i],
                                            'access_fee' => $request->access_fee[$i],
                                        ]);

                                        PremiumInvoiceMembers::create([
                                            'idx' => $premium_invoice_members_max_idx,
                                            'invoice_no' => $reference_no,
                                            'member_no' => $mem_no->member_no,
                                            'anniv' => $request->anniv_corp,
                                            'start_date' => $request->start_date[$i],
                                            'relation' => $relation,
                                            'premium' => str_replace(',', '', $request->net_premium[$i]),
                                        ]);
                                    }

                                    //save access log
                                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                                });
                                }
                        array_push($response, 'Retail members debited successfully');
                    } else {
                        array_push($response, 'Members must belong to retail corporate groups!');
                    }
                }
            }
        } catch (Exception $e) {
            array_push($response, 'Error ' . $e->getMessage());
        }

        return $response;
    }
}
