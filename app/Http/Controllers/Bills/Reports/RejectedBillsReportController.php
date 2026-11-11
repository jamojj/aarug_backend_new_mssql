<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillVet;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RejectedBillsReportController extends Controller
{
    //fetch rejected bills report
    public function fetchRejectedBillsReport(Request $request)
    {

        // ini_set('memory_limit', '1524M');
        ini_set('max_execution_time', '0');

        //return $request;
        $ttl_invoiced_amt = 0;
        switch ($request->option) {
            case '1':
                $prov_data = BillVet::select(
                    'bill_vet.claim_no',
                    'bills.member_no',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'provider.provider',
                    'bill_vet.invoice_no',
                    'service.service',
                    'reject_reason.reject_reason',
                    'bill_vet.remarks',
                    'bills.invoice_date',
                    'bills.invoiced_amount',
                    'bills.reject_reason',
                    'bills.import'

                )
                    ->distinct('bill_vet.claim_no')
                    ->Join('bills', 'bills.claim_no', '=', 'bill_vet.claim_no')
                    ->Join('member_info', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('provider', 'provider.code', 'bills.provider')
                    ->leftJoin('service', 'service.code', '=', 'bill_vet.service')
                    ->leftJoin('reject_reason', 'reject_reason.code', '=', 'bill_vet.reasons')
                    ->where('bills.provider', $request->provider)
                    ->where('bill_vet.date_entered', '>=', $request->date_from)
                    ->where('bill_vet.date_entered', '<=', $request->date_to)
                    ->where('bill_vet.vet_status', 2)
                    ->get();
                // $prov_data = Bills::select(
                //     'bills.claim_no',
                //     'bills.invoice_no',
                //     'service.service',
                //     'bills.service as service_code',
                //     'bills.invoice_date',
                //     'bills.invoiced_amount',
                //     'bills.reject_reason',
                //     'bills.import',
                //     'bills.member_no',
                //     'provider.provider',
                //     DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                // )
                //     ->leftJoin('member_info', 'member_info.member_no', '=', 'bills.member_no')
                //     ->leftJoin('service', 'service.code', '=', 'bills.service')
                //     ->leftJoin('provider', 'provider.code', '=', 'bills.provider')
                //     ->where('bills.provider', $request->provider)
                //     ->where('bills.invoice_date', '>=', $request->date_from)
                //     ->where('bills.invoice_date', '<=', $request->date_to)
                //     ->where('bills.vet_status', 2)
                //     ->get();

                foreach ($prov_data as $data) {
                    // $ll_reason = BillVet::select('bill_vet.remarks', 'reject_reason.reject_reason')
                    //     ->leftJoin('reject_reason', 'reject_reason.code', '=', 'bill_vet.reasons')
                    //     ->where('bill_vet.service', $data->service_code)
                    //     ->where('bill_vet.invoice_no', $data->invoice_no)
                    //     ->where('bill_vet.claim_no', $data->claim_no)
                    //     ->get();

                    // $data->remarks = $ll_reason[0]['remarks'];
                    // $data->reject_reason = $ll_reason[0]['reject_reason'];

                    $ttl_invoiced_amt += $data->invoiced_amount;
                }
                return ['claims' => $prov_data, 'ttl_invoiced_amount' => $ttl_invoiced_amt];
                break;
            case '2':

                $corp_data = BillVet::select(
                    'bill_vet.claim_no',
                    'bills.member_no',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'provider.provider',
                    'bill_vet.invoice_no',
                    'service.service',
                    'reject_reason.reject_reason',
                    'bill_vet.remarks',
                    'bills.invoice_date',
                    'bills.invoiced_amount',
                    'bills.reject_reason',
                    'bills.import'

                )
                    ->distinct('bill_vet.claim_no')
                    ->Join('bills', 'bills.claim_no', '=', 'bill_vet.claim_no')
                    ->Join('member_info', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('provider', 'provider.code', 'bills.provider')
                    ->leftJoin('service', 'service.code', '=', 'bill_vet.service')
                    ->leftJoin('reject_reason', 'reject_reason.code', '=', 'bill_vet.reasons')
                    ->where('bills.corp_id', $request->corporate)
                    ->where('bill_vet.date_entered', '>=', $request->date_from)
                    ->where('bill_vet.date_entered', '<=', $request->date_to)
                    ->where('bill_vet.vet_status', 2)
                    ->get();
                // $corp_data = BillVet::select('bill_vet.claim_no', 'bill_vet.provider as prov_code')
                //     ->distinct()
                //     ->leftJoin('bills', 'bills.id', '=', 'bill_vet.id')
                //     ->where('bills.corp_id', $request->corporate)
                //     ->where('bill_vet.date_entered', '>=', $request->date_from)
                //     ->where('bill_vet.date_entered', '<=', $request->date_to)
                //     ->where('bill_vet.vet_status', 2)
                //     ->get();
                //return $corp_data;
                foreach ($corp_data as $data) {
                    // $ls_memno = Bills::select('member_no')->where('claim_no', $data->claim_no)->get();
                    // $ls_memnames = MemberInfo::select(DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),)
                    //     ->where('member_no', $ls_memno[0]['member_no'])
                    //     ->get();
                    // $ls_invno = BillVet::select('bill_vet.invoice_no')
                    //     ->where('vet_status', 2)
                    //     ->where('claim_no', $data->claim_no)
                    //     ->get();
                    // $ls_service_data = BillVet::select(
                    //     'service.service',
                    //     'reject_reason.reject_reason',
                    //     'provider.provider',
                    //     'bill_vet.remarks'
                    // )
                    //     ->leftJoin('service', 'service.code', '=', 'bill_vet.service')
                    //     ->leftJoin('provider', 'provider.code', '=', 'bill_vet.provider')
                    //     ->leftJoin('reject_reason', 'reject_reason.code', '=', 'bill_vet.reasons')
                    //     ->where('bill_vet.claim_no', $data->claim_no)
                    //     ->where('bill_vet.invoice_no', $ls_invno[0]['invoice_no'])
                    //     ->where('bill_vet.vet_status', 2)
                    //     ->get();
                    // $bills_data = Bills::select('invoice_date', 'invoiced_amount', 'reject_reason', 'import')
                    //     ->where('vet_status', 2)
                    //     ->where('invoice_no', $ls_invno[0]['invoice_no'])
                    //     ->where('claim_no', $data->claim_no)
                    //     ->get();
                    $ttl_invoiced_amt += $data->invoiced_amount;


                    // $data->member_no = $ls_memno[0]['member_no'];
                    // $data->member_name = $ls_memnames[0]['member_name'];
                    // $data->invoice_no = $ls_invno[0]['invoice_no'];
                    // $data->service = $ls_service_data[0]['service'];
                    // $data->reject_reason = $ls_service_data[0]['reject_reason'];
                    // $data->provider = $ls_service_data[0]['provider'];
                    // $data->remarks = $ls_service_data[0]['remarks'];
                    // $data->invoice_date = $bills_data[0]['invoice_date'];
                    // $data->invoiced_amount = $bills_data[0]['invoiced_amount'];
                }
                return ['claims' => $corp_data, 'ttl_invoiced_amount' => $ttl_invoiced_amt];
                break;
            case '3':
                $all_prov_data = BillVet::select(
                    'bill_vet.claim_no',
                    'bills.member_no',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                    'provider.provider',
                    'bill_vet.invoice_no',
                    'service.service',
                    'reject_reason.reject_reason',
                    'bill_vet.remarks',
                    'bills.invoice_date',
                    'bills.invoiced_amount',
                    'bills.reject_reason',
                    'bills.import'

                )
                    ->distinct('bill_vet.claim_no')
                    ->Join('bills', 'bills.claim_no', '=', 'bill_vet.claim_no')
                    ->Join('member_info', 'bills.member_no', '=', 'member_info.member_no')
                    ->leftJoin('provider', 'provider.code', 'bills.provider')
                    ->leftJoin('service', 'service.code', '=', 'bill_vet.service')
                    ->leftJoin('reject_reason', 'reject_reason.code', '=', 'bill_vet.reasons')
                    ->where('bill_vet.date_entered', '>=', $request->date_from)
                    ->where('bill_vet.date_entered', '<=', $request->date_to)
                    ->where('bill_vet.vet_status', 2)
                    ->get();



                // $all_prov_data = BillVet::select('bill_vet.claim_no')
                //     ->distinct()
                //     ->leftJoin('bills', 'bills.claim_no', '=', 'bill_vet.claim_no')
                //     ->where('bill_vet.date_entered', '>=', $request->date_from)
                //     ->where('bill_vet.date_entered', '<=', $request->date_to)
                //     ->where('bill_vet.vet_status', 2)
                //     ->get();


                foreach ($all_prov_data as $data) {
                    // $ls_memno = Bills::select('member_no')->where('claim_no', $data->claim_no)->get();
                    // $ls_memno = count($ls_memno) === 0 ? $ls_memno[0]->member_no : "";
                    // $ls_memnames = MemberInfo::select(DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),)
                    // ->where('member_no',  $ls_memno)
                    // ->get();
                    // $provider = Bills::select('provider.provider')
                    // ->leftJoin('provider', 'provider.code', 'bills.provider')
                    // ->where('bills.claim_no', $data->claim_no)
                    // ->get();
                    // $provider = count($provider) !== 0 ? $provider[0]->provider : "";
                    // $ls_invno = BillVet::select('bill_vet.invoice_no')
                    //     ->where('vet_status', 2)
                    //     ->where('claim_no', $data->claim_no)
                    //     ->get();
                    // $ls_invno = count($ls_invno) !== 0 ? $ls_invno[0]->invoice_no : "";
                    // $ls_service_data = BillVet::select(
                    //     'service.service',
                    //     'reject_reason.reject_reason',
                    //     'provider.provider',
                    //     'bill_vet.remarks'
                    // )
                    //     ->leftJoin('service', 'service.code', '=', 'bill_vet.service')
                    //     ->leftJoin('provider', 'provider.code', '=', 'bill_vet.provider')
                    //     ->leftJoin('reject_reason', 'reject_reason.code', '=', 'bill_vet.reasons')
                    //     ->where('bill_vet.claim_no', $data->claim_no)
                    //     ->where('bill_vet.invoice_no', $ls_invno)
                    //     ->where('bill_vet.vet_status', 2)
                    //     ->get();
                    //     $service = count($ls_service_data) !== 0 ? $ls_service_data[0]->service : "";
                    //     $reject_reason = count($ls_service_data) !== 0 ? $ls_service_data[0]->reject_reason : "";
                    //     $provider_service = count($ls_service_data) !== 0 ? $ls_service_data[0]->provider : "";
                    //     $remarks = count($ls_service_data) !== 0 ? $ls_service_data[0]->remarks : "";

                    // $bills_data = Bills::select('invoice_date', 'invoiced_amount', 'reject_reason', 'import')
                    //     ->where('vet_status', 2)
                    //     ->where('invoice_no', $ls_invno)
                    //     ->where('claim_no', $data->claim_no)
                    //     ->get();
                    // $invoice_date = count($bills_data) !== 0 ? $bills_data[0]->invoice_date : "";
                    // $invoiced_amount = count($bills_data) !== 0 ? $bills_data[0]->invoiced_amount : 0;
                    // $reject_reason = count($bills_data) !== 0 ? $bills_data[0]->reject_reason : "";
                    // $import = count($bills_data) !== 0 ? $bills_data[0]->import : "";

                    $ttl_invoiced_amt += $data->invoiced_amount;

                    // $data->member_no = $ls_memno;
                    // $data->member_name = $ls_memnames[0]['member_name'];
                    // $data->provider = $provider;
                    // $data->invoice_no = $ls_invno;
                    // $data->service = $service;
                    // $data->reject_reason = $reject_reason;
                    // $data->provider2 = $provider_service;
                    // $data->remarks = $remarks;
                    // $data->invoice_date = $invoice_date;
                    // $data->invoiced_amount = $invoiced_amount;
                }
                return ['claims' => $all_prov_data, 'ttl_invoiced_amount' => $ttl_invoiced_amt];
                break;
            case '4':
                break;
            default:
                break;
        }
    }
}
