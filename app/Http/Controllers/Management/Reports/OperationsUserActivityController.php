<?php

namespace App\Http\Controllers\Management\Reports;

use App\Http\Controllers\Controller;
use App\Models\Bills;
use App\Models\BillsPayment;
use App\Models\BillVet;
use App\Models\Corporate\Corporate;
use App\Models\Registration\MemberInfo;
use App\Models\Registration\PrincipalApplicant;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsUserActivityController extends Controller
{
    //operations - fetch user activity
    public function fetchUserActivity(Request $request)
    {
        //return $request;
        ini_set('max_execution_time', '0');

        $data_arr = array();
        $date_from = $request->date_from;
        $date_from = date("Ymd", strtotime($date_from));
        $date_to = $request->date_to;
        $date_to = date("Ymd", strtotime($date_to));


        $users = Users::select('user_name')->get();

        foreach ($users as $user) {
            $ll_corp_cnt = Corporate::where('user_id', $user->user_name)
                ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyyMMdd')"), [$date_from, $date_to])
                ->count();

            $ll_princ_cnt = PrincipalApplicant::where('user_id', $user->user_name)
                ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyyMMdd')"), [$date_from, $date_to])
                ->count();

            $ll_mem_cnt = MemberInfo::where('user_id', $user->user_name)
                ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyyMMdd')"), [$date_from, $date_to])
                ->count();

            $ll_bills_cnt = Bills::where('user_id', $user->user_name)
                ->distinct('claim_no')
                ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyyMMdd')"), [$date_from, $date_to])
                ->count();

            $ll_vet_bills_cnt = BillVet::where('user_id', $user->user_name)
                ->distinct('claim_no')
                ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyyMMdd')"), [$date_from, $date_to])
                ->count();

            $ll_voucher_cnt = Bills::where('voucher_user', $user->user_name)
                ->whereNotNull('voucher_no')
                ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyyMMdd')"), [$date_from, $date_to])
                ->count();

            $ll_chq_cnt = BillsPayment::where('user_id', $user->user_name)
                ->whereBetween(DB::raw("FORMAT(date_entered, 'yyyyMMdd')"), [$date_from, $date_to])
                ->count();
            $data = [
                'user_name' => $user->user_name,
                'corporate' => $ll_corp_cnt,
                'principals' => $ll_princ_cnt,
                'dependants' => $ll_mem_cnt,
                'bills' => $ll_bills_cnt,
                'vetted' => $ll_vet_bills_cnt,
                'vouchers' => $ll_voucher_cnt,
                'paid' => $ll_chq_cnt,
            ];
            array_push($data_arr, $data);
        }
        return ['user_data' => $data_arr];
    }
}
