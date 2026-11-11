<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRightsController extends Controller
{
    public function fetchUserRightsReport($user)
    {
        $report = [];
        if ($user == '1') {
            $report = User::select(
                'users.user_name',
                'users.full_names',
                'users.status as status_code',
                // 'users.date_entered',
                'users.created_by',
                'users.blocked as blocked_code',
                DB::raw("CONVERT(varchar, users.date_entered, 1) as date_entered"),
                DB::raw("CONVERT(varchar, users.create_date, 1) as create_date"),
                // 'users.create_date',
                'users.email',
                't_user_group.user_group',
                DB::raw("CASE WHEN isnull(users.status, 0) = 0 THEN (CASE WHEN isnull(users.blocked, 0) = 0 THEN 'ACTIVE' ELSE 'BLOCKED' END) ELSE 'CANCELLED' END AS status")


            )
                ->leftJoin('user_groups', 'user_groups.user_name', '=', 'users.user_name')
                ->leftJoin('t_user_group', 'user_groups.user_group', '=', 't_user_group.code')
                ->orderBy('users.user_name', 'asc')
                ->get();
        } else {
            $report = User::select(
                'users.user_name',
                'users.full_names',
                'users.status as status_code',
                // 'users.date_entered',
                'users.created_by',
                'users.blocked as blocked_code',
                // 'users.create_date',
                'users.email',
                't_user_group.user_group',
                DB::raw("CONVERT(varchar, users.date_entered, 1) as date_entered"),
                DB::raw("CONVERT(varchar, users.create_date, 1) as create_date"),
                DB::raw("CASE WHEN isnull(users.status, 0) = 0 THEN (CASE WHEN isnull(users.blocked, 0) = 0 THEN 'ACTIVE' ELSE 'BLOCKED' END) ELSE 'CANCELLED' END AS status")


            )
                ->leftJoin('user_groups', 'user_groups.user_name', '=', 'users.user_name')
                ->leftJoin('t_user_group', 'user_groups.user_group', '=', 't_user_group.code')
                ->where('users.user_name', $user)
                ->orderBy('t_user_group.user_group', 'asc')
                ->get();
           

        }
        return $report;
    }
}
