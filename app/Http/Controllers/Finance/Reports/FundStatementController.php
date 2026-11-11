<?php

namespace App\Http\Controllers\Finance\Reports;

use App\Http\Controllers\Controller;
use App\Models\BillsPayment;
use App\Models\Corporate\Corporate;
use App\Models\CreditedFund;
use App\Models\Provider;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FundStatementController extends Controller
{
    //fetch fund statement
    public function fetchFundStatement($corp_id){
        $credits = CreditedFund::select('date_credited', 'fund_amount', 'cheque_no')
            ->where('corp_id', $corp_id)
            ->get();
          
        foreach($credits as $credit){
            if($credit->fund_amount > 0){
                $credited = 'Replenishment';
                $credit->transaction = $credited;
                
            } elseif($credit->fund_amount < 0){
                $credited = 'Photo Card Debit';
                $credit->transaction = $credited;
            }
        }
    
        $debits = BillsPayment::select('cheque_date', 'cheque_no', 'cheque_amount', 
                'provider as prov_code', 'member_no', 'corp_id', 'admin_fee')
                ->where('dispatched', 1)
                ->where('self_fund', 1)
                ->where('corp_id', $corp_id)
                ->get();
                // return $debits;
                foreach($debits as $debit){
                    if(!is_null($debit->prov_code)){
                        $provider = Provider::select('provider')
                            ->where('code', $debit->prov_code)
                            ->get();
                            $prov = $provider;
                            $debit->provider = $prov;

                            $debit->payee = 'Re-imbursment. '.$debit->provider;

                    } elseif(!is_null($debit->member_no)){
                        $member_name = MemberInfo::select(
                            DB::raw("CONCAT(member_info.surname,' ',member_info.first_name,' ',member_info.other_names) as names"),
                        )
                        ->where('member_no', $debit->member_no)
                        ->get();
                        $mem_no = $member_name;
                        $debit->names = $mem_no;

                        $debit->payee = 'Re-imbursment. '.$debit->names;

                    } elseif(!is_null($debit->corp_id)){
                        $corporate = Corporate::select('corporate')
                            ->where('corp_id', $debit->corp_id)
                            ->get();
                            $corp = $corporate;
                            $debit->corporate = $corp;

                            $debit->payee = 'Re-imbursment. '.$debit->corporate;
                    }
                }

                $dt = [
                    'credits' => $credits,
                    'debits' => $debits,
                ];
                return $dt;

    }
}
