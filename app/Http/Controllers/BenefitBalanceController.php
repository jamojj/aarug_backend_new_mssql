<?php

namespace App\Http\Controllers;

use App\Models\Bills;
use App\Models\ClaimsReserve;
use App\Models\Registration\MemberBenefits;
use App\Models\Registration\MemberInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BenefitBalanceController extends Controller
{
    public function fetchBenefitBalanceBills(Request $request)
    {

        $data = [];

        $fam_no = MemberInfo::where('member_no', $request->member_no)->pluck('family_no');
        if (count($fam_no) === 0) {
            return [];
        }
        $child_sharing = MemberBenefits::where('member_benefits.benefit', $request->benefit)
            ->where('member_benefits.anniv', $request->anniv)
            ->where('member_benefits.member_no', $request->member_no)
            ->pluck('sharing');

        $child_limit = MemberBenefits::where('benefit', $request->benefit)
            ->where('anniv', $request->anniv)
            ->where('member_no', $request->member_no)
            ->pluck('limit');

        $child_limit = count($child_limit) === 0  ? 0.00 : $child_limit[0];

        //getting the main benefit
        $bene = MemberBenefits::where('benefit', $request->benefit)
            ->where('anniv', $request->anniv)
            ->where('member_no', $request->member_no)
            ->pluck('sub_limit_of');



        //getting sharing for the main benefit
        if (empty($bene[0])) {
            $bene = $request->benefit;
            $parent_sharing = MemberBenefits::where('anniv', $request->anniv)
                ->where('benefit', $bene)
                ->where('member_no', $request->member_no)
                ->pluck('sharing');
        } else {
            $bene = $bene[0];
            $parent_sharing = MemberBenefits::where('anniv', $request->anniv)
                ->where('benefit', $bene)
                ->where('member_no', $request->member_no)
                ->pluck('sharing');
        }


        $parent_sharing = count($parent_sharing) === 0 ? 0 : $parent_sharing[0];

        //getting limit 
        $parent_limit = MemberBenefits::where('anniv', $request->anniv)
            ->where('benefit', $bene)
            ->where('member_no', $request->member_no)
            ->pluck('limit');

        $parent_limit = count($parent_limit) === 0 ? 0.00 : $parent_limit[0];

        $member_nos = MemberInfo::where('family_no', $fam_no)->pluck('member_no');


        //child calculations 
        if (!is_null($child_sharing[0])) {

            if ($child_sharing[0] == 1) {

                $child_consumption = Bills::select(
                    DB::raw("SUM(ISNULL(bills.amount_payable, 0)) as claims_amt")
                )
                    ->where('bills.vet_status', 1)
                    ->where('claim_nature', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->whereIn('member_no', $member_nos)
                    ->get();

                $child_consumption = count($child_consumption) === 0 ? 0.00 :  $child_consumption[0]->claims_amt;

                $child_consumption_inv = Bills::select(
                    DB::raw("SUM(ISNULL(bills.invoiced_amount, 0)) as inv_amt")
                )
                    ->where('claim_nature', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->whereIn('member_no', $member_nos)
                    ->get();
                $child_consumption_inv = count($child_consumption_inv) === 0 ? 0.00 :  $child_consumption_inv[0]->inv_amt;

                $child_reserves = ClaimsReserve::select(
                    DB::raw("sum(isnull(claims_reserve.credit,0)) - sum(isnull(claims_reserve.debit,0)) as reserves"),
                )
                    ->where('benefit', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->whereIn('member_no', $member_nos)
                    ->get();
                $child_reserves = count($child_reserves) === 0 ? 0 :  $child_reserves[0]->reserves;

                //calculating balance we don't include reserves
                $child_balance = $child_limit - $child_consumption;
                $child_balance_inv = $child_limit - $child_consumption_inv;
                $child_balance_with_reserves = $child_limit - ($child_consumption + $child_reserves);
                $child_balance_with_reserves_inv = $child_limit - ($child_consumption_inv + $child_reserves);
            } else {
                $child_consumption = Bills::select(
                    DB::raw("SUM(ISNULL(bills.amount_payable, 0)) as claims_amt")
                )
                    ->where('bills.vet_status', 1)
                    ->where('claim_nature', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->where('member_no', $request->member_no)
                    ->get();

                $child_consumption = count($child_consumption) === 0 ? 0.00 :  $child_consumption[0]->claims_amt;

                $child_consumption_inv = Bills::select(
                    DB::raw("SUM(ISNULL(bills.invoiced_amount, 0)) as inv_amt")
                )
                    ->where('claim_nature', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->where('member_no', $request->member_no)
                    ->get();
                $child_consumption_inv = count($child_consumption_inv) === 0 ? 0.00 :  $child_consumption_inv[0]->inv_amt;


                $child_reserves = ClaimsReserve::select(
                    DB::raw("sum(isnull(claims_reserve.credit,0)) - sum(isnull(claims_reserve.debit,0)) as reserves"),
                )
                    ->where('benefit', $request->benefit)
                    ->where('anniv', $request->anniv)
                    ->where('member_no', $request->member_no)
                    ->get();


                $child_reserves = count($child_reserves) === 0 ? 0 :  $child_reserves[0]->reserves;


                //calculating balance we don't include reserves
                $child_balance = $child_limit - $child_consumption;
                 $child_balance_inv = $child_limit - $child_consumption_inv;
                $child_balance_with_reserves = $child_limit - ($child_consumption + $child_reserves);
                 $child_balance_with_reserves_inv = $child_limit - ($child_consumption_inv + $child_reserves);
            }
        }

        // Parent benefit chain (parents only)
        $benefitChain = [];

        // Track visited benefits to prevent circular loops or infinite loops
        $visited = [];

        $currentBenefit = $request->benefit;

        while ($currentBenefit && !in_array($currentBenefit, $visited)) {

            // Mark current benefit as visited
            $visited[] = $currentBenefit;

            // Fetch parent benefit
            $parent = MemberBenefits::where('benefit', $currentBenefit)
                ->where('anniv', $request->anniv)
                ->where('member_no', $request->member_no)
                ->pluck('sub_limit_of')
                ->first();


            // Store parent only
            if (!empty($parent)) {
                $benefitChain[] = $parent;
            }
            // assign current benefit afresh
            $currentBenefit = $parent;
        }

        // If no parent was found, the benefit itself is the main benefit
        if (empty($benefitChain)) {
            $benefitChain[] = $request->benefit;
        }


        //parent calculations
        if ($parent_sharing == 1) {

            $parent_consumption = Bills::select(
                DB::raw("SUM(ISNULL(bills.amount_payable, 0))  as claims_amt")
            )
                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'bills.member_no')
                        ->on('member_benefits.benefit', '=', 'bills.claim_nature')
                        ->on('member_benefits.anniv', '=', 'bills.anniv');
                })
                ->where('bills.anniv', $request->anniv)
                ->where(function ($q) use ($bene, $benefitChain) {
                    $q->whereIn('member_benefits.sub_limit_of', $benefitChain)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->where('bills.vet_status', 1)
                ->whereIn('bills.member_no', $member_nos)
                ->get();


            $parent_consumption = count($parent_consumption) !== 0 ? $parent_consumption[0]->claims_amt : 0.00;

             $parent_consumption_inv = Bills::select(
                DB::raw("SUM(ISNULL(bills.invoiced_amount, 0))as inv_amt")
            )
                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'bills.member_no')
                        ->on('member_benefits.benefit', '=', 'bills.claim_nature')
                        ->on('member_benefits.anniv', '=', 'bills.anniv');
                })
                ->where('bills.anniv', $request->anniv)
                ->where(function ($q) use ($bene, $benefitChain) {
                    $q->whereIn('member_benefits.sub_limit_of', $benefitChain)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->whereIn('bills.member_no', $member_nos)
                ->get();
            $parent_consumption_inv = count($parent_consumption_inv) !== 0 ? $parent_consumption_inv[0]->inv_amt : 0.00;


            $parent_reserves = ClaimsReserve::select(
                DB::raw("sum(isnull(claims_reserve.credit,0))- sum(isnull(claims_reserve.debit,0)) as reserves")
            )

                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'claims_reserve.member_no')
                        ->on('member_benefits.benefit', '=', 'claims_reserve.benefit')
                        ->on('member_benefits.anniv', '=', 'claims_reserve.anniv');
                })
                ->where('claims_reserve.anniv', $request->anniv)
                ->where(function ($q) use ($bene, $benefitChain) {
                    $q->whereIn('member_benefits.sub_limit_of', $benefitChain)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->whereIn('claims_reserve.member_no', $member_nos)
                ->get();

            $parent_reserves = count($parent_reserves) !== 0 ? $parent_reserves[0]->reserves : 0.00;


            //calculating balance we don't include reserves
            $parent_balance = $parent_limit - $parent_consumption;
            $parent_balance_inv = $parent_limit - $parent_consumption_inv;
            $parent_balance_with_reserves = $parent_limit - ($parent_consumption + $parent_reserves);
            $parent_balance_with_reserves_inv = $parent_limit - ($parent_consumption_inv + $parent_reserves);
        } else {
            $parent_consumption = Bills::select(
                DB::raw("SUM(ISNULL(bills.amount_payable, 0))as claims_amt")
            )
                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'bills.member_no')
                        ->on('member_benefits.benefit', '=', 'bills.claim_nature')
                        ->on('member_benefits.anniv', '=', 'bills.anniv');
                })
                ->where('bills.anniv', $request->anniv)
                ->where(function ($q) use ($bene, $benefitChain) {
                    $q->whereIn('member_benefits.sub_limit_of', $benefitChain)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->where('bills.vet_status', 1)
                ->where('bills.member_no', $request->member_no)
                ->get();

            $parent_consumption = count($parent_consumption) !== 0 ? $parent_consumption[0]->claims_amt : 0.00;

             $parent_consumption_inv = Bills::select(
                DB::raw("SUM(ISNULL(bills.invoiced_amount, 0))as inv_amt")
            )
                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'bills.member_no')
                        ->on('member_benefits.benefit', '=', 'bills.claim_nature')
                        ->on('member_benefits.anniv', '=', 'bills.anniv');
                })
                ->where('bills.anniv', $request->anniv)
                ->where(function ($q) use ($bene, $benefitChain) {
                    $q->whereIn('member_benefits.sub_limit_of', $benefitChain)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->where('bills.member_no', $request->member_no)
                ->get();
            $parent_consumption_inv = count($parent_consumption_inv) !== 0 ? $parent_consumption_inv[0]->inv_amt : 0.00;


            $parent_reserves = ClaimsReserve::select(
                DB::raw("sum(isnull(claims_reserve.credit,0)) - sum(isnull(claims_reserve.debit,0)) as reserves")
            )
                ->join('member_benefits', function ($join) {
                    $join->on('member_benefits.member_no', '=', 'claims_reserve.member_no')
                        ->on('member_benefits.benefit', '=', 'claims_reserve.benefit')
                        ->on('member_benefits.anniv', '=', 'claims_reserve.anniv');
                })
                ->where('claims_reserve.anniv', $request->anniv)
                ->where(function ($q) use ($bene, $benefitChain) {
                    $q->whereIn('member_benefits.sub_limit_of', $benefitChain)
                        ->orWhere('member_benefits.benefit', $bene);
                })
                ->where('claims_reserve.member_no', $request->member_no)
                ->get();

            $parent_reserves = count($parent_reserves) !== 0 ? $parent_reserves[0]->reserves : 0.00;


            //no longer using reserve to calculate balance
            $parent_balance = $parent_limit - $parent_consumption;
            $parent_balance_inv = $parent_limit - $parent_consumption_inv;
            $parent_balance_with_reserves = $parent_limit - ($parent_consumption + $parent_reserves);
            $parent_balance_with_reserves_inv = $parent_limit - ($parent_consumption_inv + $parent_reserves);
        }
        //final child balance
        $child_balance = $parent_balance < $child_balance ? $parent_balance : $child_balance;
        $child_balance_inv = $parent_balance_inv < $child_balance_inv ? $parent_balance_inv : $child_balance_inv;
        //final child balance with reserves
        $child_balance_with_reserves = $parent_balance_with_reserves < $child_balance_with_reserves ? $parent_balance_with_reserves : $child_balance_with_reserves;
        $child_balance_with_reserves_inv = $parent_balance_with_reserves_inv < $child_balance_with_reserves_inv ? $parent_balance_with_reserves_inv : $child_balance_with_reserves_inv;

        $dt = [
            'child_limit' => $child_limit,
            'child_consumption' => $child_consumption,
            'child_consumption_inv' => $child_consumption_inv,
            'child_reserves' => $child_reserves,
            'child_balance' => $child_balance,
            'child_balance_inv' => $child_balance_inv,
            'child_balance_with_reserves' => $child_balance_with_reserves,
            'child_balance_with_reserves_inv' => $child_balance_with_reserves_inv, //for care module
            'parent_limit' => $parent_limit,
            'parent_consumption' => $parent_consumption,
            'parent_consumption_inv' => $parent_consumption_inv,
            'parent_reserves' => $parent_reserves,
            'parent_balance' => $parent_balance,
            'parent_balance_inv' => $parent_balance_inv,
            'parent_balance_with_reserves' => $parent_balance_with_reserves,
            'parent_balance_with_reserves_inv' => $parent_balance_with_reserves_inv, //for care module
        ];

        array_push($data, $dt);


        return $data;
    }
}
