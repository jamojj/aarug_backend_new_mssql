<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\UnresolvedClaims;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnresolvedClaimsController extends Controller
{
    public function saveUnresolvedClaims(Request $request)
    {
        try {
            if ($request->task == "1") {
                $exists = UnresolvedClaims::where('invoice_no', $request->invoice_no)->get();
                if (count($exists) == 0) {                
                    DB::transaction(function () use ($request) {
                        UnresolvedClaims::create([
                            'invoice_no' => $request->invoice_no,
                            'member_no' => $request->member_no,
                            'member_name' => $request->member_name,
                            'corporate' => $request->corporate,
                            'provider' => $request->provider,
                            'dos' => $request->dos,
                            'claimed_amt' => (float)$request->claimed_amt,
                            'deduction_amt' => (float)$request->deduction_amt,
                            'amount_payable' => (float)$request->amount_payable,
                            'issue' => $request->issue,
                            'report' => $request->report,
                            'batch_no' => $request->batch_no,        
                        ]);
                    });
                    return ['successfully saved'];
                }else{
                    return ['Error Invoice no already exists!'];
    
                }               
            }else{
                DB::transaction(function () use ($request) {
                    UnresolvedClaims::where('invoice_no', $request->invoice_no)->update([
                        'member_no' => $request->member_no,
                        'member_name' => $request->member_name,
                        'corporate' => $request->corporate,
                        'provider' => $request->provider,
                        'dos' => $request->dos,
                        'claimed_amt' => (float)$request->claimed_amt,
                        'deduction_amt' => (float)$request->deduction_amt,
                        'amount_payable' => (float)$request->amount_payable,
                        'issue' => $request->issue,
                        'report' => $request->report,
                        'batch_no' => $request->batch_no,
                    ]);
                });
            }
            return ['successfully updated'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }

    public function fetchUnresolvedClaims(Request $request)
    {
        $data = [];
        $from = date("Ymd", strtotime($request->from));
        $to = date("Ymd", strtotime($request->to));
        $ttl_claimed = $ttl_deducted = $ttl_payable = 0;
        switch ($request->options) {
            case '1':
                //corporate
                $data = UnresolvedClaims::select(
                    'unresolved_claims.invoice_no',
                    'unresolved_claims.member_no',
                    'unresolved_claims.member_name',
                    'provider.provider',
                    'corporate.corporate',
                    'unresolved_claims.dos',
                    'unresolved_claims.claimed_amt',
                    'unresolved_claims.deduction_amt',
                    'unresolved_claims.amount_payable',
                    'unresolved_claims.issue',
                    'unresolved_claims.report',
                    'unresolved_claims.batch_no',

                )
                    ->leftJoin('provider', 'provider.code', 'unresolved_claims.provider')
                    ->leftJoin('corporate', 'corporate.corp_id', 'unresolved_claims.corporate')
                    ->where('unresolved_claims.corporate', $request->corporate)
                    ->get();
                break;
            case '2':
                //provider
                $data = UnresolvedClaims::select(
                    'unresolved_claims.invoice_no',
                    'unresolved_claims.member_no',
                    'unresolved_claims.member_name',
                    'provider.provider',
                    'corporate.corporate',
                    'unresolved_claims.dos',
                    'unresolved_claims.claimed_amt',
                    'unresolved_claims.deduction_amt',
                    'unresolved_claims.amount_payable',
                    'unresolved_claims.issue',
                    'unresolved_claims.report',
                    'unresolved_claims.batch_no',

                )
                    ->leftJoin('provider', 'provider.code', 'unresolved_claims.provider')
                    ->leftJoin('corporate', 'corporate.corp_id', 'unresolved_claims.corporate')
                    ->where('unresolved_claims.batch_no', $request->batch_no)
                    ->where('unresolved_claims.provider', $request->provider)
                    ->get();
                break;
            case '3':
                //member
                $data = UnresolvedClaims::select(
                    'unresolved_claims.invoice_no',
                    'unresolved_claims.member_no',
                    'unresolved_claims.member_name',
                    'provider.provider',
                    'corporate.corporate',
                    'unresolved_claims.dos',
                    'unresolved_claims.claimed_amt',
                    'unresolved_claims.deduction_amt',
                    'unresolved_claims.amount_payable',
                    'unresolved_claims.issue',
                    'unresolved_claims.report',
                    'unresolved_claims.batch_no',

                )
                    ->leftJoin('provider', 'provider.code', 'unresolved_claims.provider')
                    ->leftJoin('corporate', 'corporate.corp_id', 'unresolved_claims.corporate')
                    ->where('unresolved_claims.member_no', $request->member_no)
                    ->get();
                break;
            case '4':
                //invoice no
                $data = UnresolvedClaims::select(
                    'unresolved_claims.invoice_no',
                    'unresolved_claims.member_no',
                    'unresolved_claims.member_name',
                    'provider.provider',
                    'provider.code as provider_code',
                    'corporate.corporate',
                    'corporate.corp_id',
                    'unresolved_claims.dos',
                    'unresolved_claims.claimed_amt',
                    'unresolved_claims.deduction_amt',
                    'unresolved_claims.amount_payable',
                    'unresolved_claims.issue',
                    'unresolved_claims.report',
                    'unresolved_claims.batch_no',

                )
                    ->leftJoin('provider', 'provider.code', 'unresolved_claims.provider')
                    ->leftJoin('corporate', 'corporate.corp_id', 'unresolved_claims.corporate')
                    ->where('unresolved_claims.invoice_no', $request->invoice_no)
                    ->get();
                break;
            case '5':
                //dos
                $data = UnresolvedClaims::select(
                    'unresolved_claims.invoice_no',
                    'unresolved_claims.member_no',
                    'unresolved_claims.member_name',
                    'provider.provider',
                    'corporate.corporate',
                    'unresolved_claims.dos',
                    'unresolved_claims.claimed_amt',
                    'unresolved_claims.deduction_amt',
                    'unresolved_claims.amount_payable',
                    'unresolved_claims.issue',
                    'unresolved_claims.report',
                    'unresolved_claims.batch_no',

                )
                    ->leftJoin('provider', 'provider.code', 'unresolved_claims.provider')
                    ->leftJoin('corporate', 'corporate.corp_id', 'unresolved_claims.corporate')
                    ->whereBetween(DB::raw("FORMAT(unresolved_claims.dos, 'yyyyMMdd')"), [$from, $to])
                    ->get();
                break;
        }

        foreach ($data as $datum) {
            $ttl_claimed += $datum->claimed_amt;
            $ttl_deducted += $datum->deduction_amt;
            $ttl_payable += $datum->amount_payable;
        }

        $totals = [
            "claimed"=> number_format($ttl_claimed, 2 ),
            "deducted"=> number_format($ttl_deducted, 2),
            "payable"=> number_format($ttl_payable, 2)
        ];

        return ["unresolved"=>$data, "totals"=>$totals];
    }
}
