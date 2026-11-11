<?php

namespace App\Http\Controllers\Admin\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\DeletedLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeleteLogListController extends Controller
{
    //fetch all user delete logs
    public function fetchUserDeleteLogList(Request $request)
    {
        //return $request;
        $date_from = $request->date_from;
        $date_to = $request->date_to;
        $data = DeletedLog::select(
            'deleted_log.deleted_info',
            'deleted_log.user_id',
            DB::raw("FORMAT(deleted_log.date_deleted, 'yyyy-mm-dd')"),
            'deleted_log.deleted_class',
            't_misc_deletion_reason.delete_reason'
        )
            ->leftJoin('t_misc_deletion_reason', 't_misc_deletion_reason.code', '=', 'deleted_log.deleted_class')
            ->whereBetween('date_deleted', [$date_from, $date_to])
            ->get();

        return ['data' => $data];
    }
}
