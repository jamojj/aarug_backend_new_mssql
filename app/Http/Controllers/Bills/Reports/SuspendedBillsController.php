<?php

namespace App\Http\Controllers\Bills\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillVet;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isNull;

class SuspendedBillsController extends Controller
{
    public function fetchSuspendedBills(Request $request){
        // return $request;
        $bills_array = array();
        switch ($request->option) {
            case '1':
                $data = Bills::select(
                    'claim_no', 'invoice_no', 'bills.service as service', 'service.service as service_name',
                    'invoice_date', 'invoiced_amount', 'reject_reason','import', 'bills.member_no', 'provider.provider',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                )
                ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                ->leftJoin('service', 'bills.service', '=', 'service.code')
                ->leftJoin('provider', 'bills.provider', 'provider.code')
                ->where('bills.vet_status', 3)
                ->where('bills.provider', $request->provider)
                ->where('invoice_date', '>=', $request->from)
                ->where('invoice_date', '<=', $request->to)
                ->orderBy('invoice_date', 'ASC')
                ->get();
        
                foreach($data as $dt){
                    $ll_reason = BillVet::select('reject_reason.reject_reason as reason')
                                ->leftJoin('reject_reason', 'bill_vet.reasons', '=', 'reject_reason.code')
                                ->where('service', $dt->service)
                                ->where('invoice_no', $dt->invoice_no)
                                ->where('claim_no', $dt->claim_no)
                                ->pluck('reason');
                    $ll_remarks = BillVet::select('bill_vet.remarks')
                                ->where('service', $dt->service)
                                ->where('invoice_no', $dt->invoice_no)
                                ->where('claim_no', $dt->claim_no)
                                ->pluck('remarks');
                               
                                $datas = [
                                    'member_no' => $dt->member_no,
                                    'member_name' => $dt->member_name,
                                    'provider' => $dt->provider,
                                    'claim_no' => $dt->claim_no,
                                    'invoice_no' => $dt->invoice_no,
                                    'service' => $dt->service_name,
                                    'invoice_date' => $dt->invoice_date,
                                    'invoiced_amount' => $dt->invoiced_amount,
                                    'reason' => $ll_reason,
                                    'remarks' => $ll_remarks
                                ];
                                array_push($bills_array, $datas);
                }
                break;
            case '2':
                $data = BillVet::select('bill_vet.claim_no', 'bill_vet.provider')
                            ->leftJoin('bills', 'bill_vet.id', '=', 'bills.id')
                            ->where('bill_vet.vet_status',3)
                            ->where('bills.corp_id', $request->corp_id)
                            ->where('bills.invoice_date', '>=', $request->from)
                            ->where('bills.invoice_date', '<=', $request->to)
                            ->distinct()
                            ->get();
                            // return $data;
                        foreach($data as $dt){
                            $mem_no = Bills::select('member_no')
                                    ->where('claim_no', $dt->claim_no)
                                    ->distinct()
                                    ->pluck('member_no');
                                   
                            $mem_name = MemberInfo::select(
                                DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                            )
                                ->where('member_no', $mem_no[0])
                                ->pluck('member_name');
                                
                            $inv_no = BillVet::select('invoice_no')
                                ->where('vet_status', 2)
                                ->where('claim_no', $dt->claim_no)
                                ->distinct()
                                ->get();
                                if(isNull($inv_no)){
                                    $inv_no = '';
                                   
                                }else{
                                    $inv_no = $inv_no[0];
                                }
                            $service = BillVet::select('service.service')
                            ->leftJoin('service', 'bill_vet.service', '=', 'service.code')
                            ->where('vet_status', 2)
                            ->where('invoice_no', $inv_no)
                            ->where('claim_no', $dt->claim_no)
                            ->pluck('service');
                            if(isNull($service)){
                                $service = '';
                               
                            }else{
                                $service = $service[0];
                            }
                            
                            $reason = BillVet::select('reject_reason.reject_reason as reason')
                            ->leftJoin('reject_reason', 'bill_vet.reasons', '=', 'reject_reason.code')
                            ->where('vet_status', 2)
                            ->where('invoice_no', $inv_no)
                            ->where('claim_no', $dt->claim_no)
                            ->pluck('reason');
                            if(isNull($reason)){
                                $reason = '';
                               
                            }else{
                                $reason = $reason[0];
                            }

                            $remarks = BillVet::select('remarks')
                            ->where('vet_status', 2)
                            ->where('invoice_no', $inv_no)
                            ->where('claim_no', $dt->claim_no)
                            ->pluck('remarks');
                            if(isNull($remarks)){
                                $remarks = '';
                               
                            }else{
                                $remarks = $remarks[0];
                            }
                            
                            $invoice_date = Bills::select('invoice_date')
                            ->where('vet_status', 2)
                            ->where('invoice_no', $inv_no)
                            ->where('claim_no', $dt->claim_no)
                            ->pluck('invoice_date');
                            if(isNull($invoice_date)){
                                $invoice_date = '';
                               
                            }else{
                                $invoice_date = $invoice_date[0];
                            }
                            
                            $invoiced_amount = Bills::select('invoiced_amount')
                            ->where('vet_status', 2)
                            ->where('invoice_no', $inv_no)
                            ->where('claim_no', $dt->claim_no)
                            ->pluck('invoiced_amount');
                            if(isNull($invoiced_amount)){
                                $invoiced_amount = '';
                               
                            }else{
                                $invoiced_amount = $invoiced_amount[0];
                            }

                            $datas = [
                                'member_no' => $mem_no[0],
                                'member_name' => $mem_name[0],
                                'provider' => $dt->provider,
                                'claim_no' => $dt->claim_no,
                                'invoice_no' => $inv_no,
                                'service' => $service,
                                'invoice_date' => $invoice_date,
                                'invoiced_amount' => $invoiced_amount,
                                'reason' => $reason,
                                'remarks' => $remarks
                            ];
                            array_push($bills_array, $datas);
                        }
                break;
            case '3':
                $data = Bills::select(
                    'claim_no', 'invoice_no', 'bills.service as service', 'service.service as service_name',
                    'invoice_date', 'invoiced_amount', 'reject_reason','import', 'bills.member_no', 'provider.provider',
                    DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as member_name"),
                )
                ->leftJoin('member_info', 'bills.member_no', '=', 'member_info.member_no')
                ->leftJoin('service', 'bills.service', '=', 'service.code')
                ->leftJoin('provider', 'bills.provider', 'provider.code')
                ->where('bills.vet_status', 3)
                ->where('invoice_date', '>=', $request->from)
                ->where('invoice_date', '<=', $request->to)
                ->orderBy('invoice_date', 'ASC')
                ->get();
        
                foreach($data as $dt){
                    $ll_reason = BillVet::select('reject_reason.reject_reason as reason')
                                ->leftJoin('reject_reason', 'bill_vet.reasons', '=', 'reject_reason.code')
                                ->where('service', $dt->service)
                                ->where('invoice_no', $dt->invoice_no)
                                ->where('claim_no', $dt->claim_no)
                                ->pluck('reason');
                    $ll_remarks = BillVet::select('bill_vet.remarks')
                                ->where('service', $dt->service)
                                ->where('invoice_no', $dt->invoice_no)
                                ->where('claim_no', $dt->claim_no)
                                ->pluck('remarks');
                               
                                $datas = [
                                    'member_no' => $dt->member_no,
                                    'member_name' => $dt->member_name,
                                    'provider' => $dt->provider,
                                    'claim_no' => $dt->claim_no,
                                    'invoice_no' => $dt->invoice_no,
                                    'service' => $dt->service_name,
                                    'invoice_date' => $dt->invoice_date,
                                    'invoiced_amount' => $dt->invoiced_amount,
                                    'reason' => $ll_reason,
                                    'remarks' => $ll_remarks
                                ];
                                array_push($bills_array, $datas);
                }
                break;
        }
        return $bills_array;
       
    }
}
