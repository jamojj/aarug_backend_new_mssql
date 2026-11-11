<?php

namespace App\Http\Controllers\AccessLogs;

use App\Http\Controllers\Controller;
use App\Models\NewUserAccessLog;
use App\Models\UserAccessLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessLogController extends Controller
{



    public function fetchNewAccessLogs(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        if (!$request->options) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $query = NewUserAccessLog::select(
            'new_user_access_log.idx',
            DB::raw("FORMAT(new_user_access_log.log_date , 'yyyy-mm-dd') as log_date"),
            'new_user_access_log.log_time',
            'new_user_access_log.user_name',
            'new_t_accessed.accessed',
            'new_user_access_log.inserted_item',
            'new_user_access_log.updated_item',
            'new_user_access_log.deleted_item',
            'new_user_access_log.browser_type',
            'new_user_access_log.os_type'
        )
            ->leftJoin('new_t_accessed', 'new_t_accessed.code', '=', 'new_user_access_log.accessed_item');

        switch ($request->options) {

            // USER + DATE RANGE
            case '1':

                $from = date("Ymd", strtotime($request->from));
                $to   = date("Ymd", strtotime($request->to));

                $query->whereBetween(
                    DB::raw("FORMAT(new_user_access_log.log_date, 'yyyyMMdd')"),
                    [$from, $to]
                )
                    ->where('new_user_access_log.user_name', $request->username);

                break;


            // DATE RANGE ONLY
            case '2':

                $from = date("Ymd", strtotime($request->from));
                $to   = date("Ymd", strtotime($request->to));

                $query->whereBetween(
                    DB::raw("FORMAT(new_user_access_log.log_date, 'yyyyMMdd')"),
                    [$from, $to]
                );

                break;


            // ALL RECORDS
            case '3':

                // no additional filters

                break;

            default:
                return response()->json(['error' => 'Invalid option'], 400);
        }

        $query->orderBy('new_user_access_log.idx');

        return response()->streamDownload(function () use ($query) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Log Date',
                'Log Time',
                'User Name',
                'Accessed',
                'Inserted Item',
                'Updated Item',
                'Deleted Item',
                'Browser Type',
                'OS Type'
            ]);

            foreach ($query->cursor() as $log) { // <-- use cursor instead of chunkById
                fputcsv($handle, [
                    $log->log_date,
                    $log->log_time,
                    $log->user_name,
                    $log->accessed,
                    $log->inserted_item,
                    $log->updated_item,
                    $log->deleted_item,
                    $log->browser_type,
                    $log->os_type
                ]);
            }

            fclose($handle);
        }, 'access_logs.csv', [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function fetchNewAccessLogs2(Request $request)
    {
        // return $request;
        ini_set('max_execution_time', '0');
        $data = [];
        if (isset($request->options)) {
            $from = date("Ymd", strtotime($request->from));
            $to = date("Ymd", strtotime($request->to));

            switch ($request->options) {
                //user
                case '1':
                    $data = NewUserAccessLog::select(
                        DB::raw("FORMAT(new_user_access_log.log_date , 'yyyy-mm-dd') as log_date"),
                        'new_user_access_log.log_time',
                        'new_user_access_log.user_name',
                        'new_t_accessed.accessed',
                        'new_user_access_log.inserted_item',
                        'new_user_access_log.updated_item',
                        'new_user_access_log.deleted_item',
                        'new_user_access_log.browser_type',
                        'new_user_access_log.os_type'
                    )
                        ->leftJoin('new_t_accessed', 'new_t_accessed.code', '=', 'new_user_access_log.accessed_item')
                        ->orderBy('new_user_access_log.log_date', 'DESC')
                        ->orderBy('new_user_access_log.log_time', 'DESC')
                        ->whereBetween(DB::raw("FORMAT(new_user_access_log.log_date, 'yyyyMMdd')"), [$from, $to])
                        ->where('new_user_access_log.user_name', $request->username)
                        ->get();

                    break;
                //dates
                case '2':

                    $data = NewUserAccessLog::select(
                        DB::raw("FORMAT(new_user_access_log.log_date , 'yyyy-mm-dd') as log_date"),
                        'new_user_access_log.log_time',
                        'new_user_access_log.user_name',
                        'new_t_accessed.accessed',
                        'new_user_access_log.inserted_item',
                        'new_user_access_log.updated_item',
                        'new_user_access_log.deleted_item',
                        'new_user_access_log.browser_type',
                        'new_user_access_log.os_type'
                    )
                        ->leftJoin('new_t_accessed', 'new_t_accessed.code', '=', 'new_user_access_log.accessed_item')
                        ->orderBy('new_user_access_log.log_date', 'DESC')
                        ->orderBy('new_user_access_log.log_time', 'DESC')
                        ->whereBetween(DB::raw("FORMAT(new_user_access_log.log_date, 'yyyyMMdd')"), [$from, $to])
                        ->get();
                    break;
                //all
                case '3':
                    $data = NewUserAccessLog::select(
                        DB::raw("FORMAT(new_user_access_log.log_date , 'yyyy-mm-dd') as log_date"),
                        'new_user_access_log.log_time',
                        'new_user_access_log.user_name',
                        'new_t_accessed.accessed',
                        'new_user_access_log.inserted_item',
                        'new_user_access_log.updated_item',
                        'new_user_access_log.deleted_item',
                        'new_user_access_log.browser_type',
                        'new_user_access_log.os_type'
                    )
                        ->leftJoin('new_t_accessed', 'new_t_accessed.code', '=', 'new_user_access_log.accessed_item')
                        ->orderBy('new_user_access_log.log_date', 'DESC')
                        ->orderBy('new_user_access_log.log_time', 'DESC')
                        ->get();
                    break;
            }
            return json_encode($data, JSON_INVALID_UTF8_IGNORE);
        }
    }

    public function saveAccessLogs(Request $request)
    {
        // date_default_timezone_set('Africa/Kampala');
        $response = [];
        $max_idx = UserAccessLog::max('idx') + 1;
        $access_date = date("Y-m-d");
        $accessed_time = date("H:i:s");
        $save_user_access_log = UserAccessLog::create([
            'idx' => $max_idx,
            'user_name' => $request->username,
            'accessed' => $request->accessed,
            'accessed_date' => $access_date,
            'accessed_time' => $accessed_time,
        ]);

        if ($save_user_access_log->wasRecentlyCreated == "true") {
            array_push($response, "log for $request->accessed on $access_date at $accessed_time created successfully");
        } else {
            array_push($response, "Error creating log for $request->accessed on $access_date at $accessed_time");
        }

        return $response;
    }

    public function saveNewAccessLogs(Request $request)
    {
        $max_idx = NewUserAccessLog::max('idx');
        if (is_null($max_idx)) {
            $max_idx = 0;
        }
        $max_idx++;
        $log_date = date("Y-m-d");
        $log_time = date("H:i:s");
        try {
            DB::transaction(function () use ($request, $log_date, $log_time, $max_idx) {
                NewUserAccessLog::create([
                    'idx' => $max_idx,
                    'user_name' => $request->user_id,
                    'accessed_item' => $request->accessed_item,
                    'browser_type' => $request->browser_type,
                    'os_type' => $request->os_type,
                    'log_date' => $log_date,
                    'log_time' => $log_time,
                ]);
                //check if user already accessed module same day
                // $user_already_logged = NewUserAccessLog::where('log_date', $log_date)
                //     ->where('accessed_item', $request->accessed_item)
                //     ->where('user_name', $request->user_id)
                //     ->get();
                // if (count($user_already_logged) == 0) {
                //     NewUserAccessLog::create([
                //         'idx' => $max_idx,
                //         'user_name' => $request->user_id,
                //         'accessed_item' => $request->accessed_item,
                //         'browser_type' => $request->browser_type,
                //         'os_type' => $request->os_type,
                //         'log_date' => $log_date,
                //         'log_time' => $log_time,
                //     ]);
                // } else {
                //     NewUserAccessLog::where('log_date', $log_date)
                //         ->where('accessed_item', $request->accessed_item)
                //         ->where('user_name', $request->user_id)
                //         ->update([
                //             'log_time' => $log_time,
                //             'browser_type' => $request->browser_type,
                //             'os_type' => $request->os_type,
                //         ]);
                // }
            });
            return ["Log created successfully"];
        } catch (Exception $e) {
            return ["Error " . $e->getMessage()];
        }
    }

    public function saveNewAccessLogsFunc($user_id, $accessed_item, $inserted_item, $updated_item, $deleted_item, $browser_type, $os_type)
    {
        $max_idx = NewUserAccessLog::max('idx');
        if (is_null($max_idx)) {
            $max_idx = 0;
        }
        $max_idx++;
        $log_date = date("Y-m-d");
        $log_time = date("H:i:s");
        try {
            DB::transaction(function () use ($user_id, $accessed_item, $inserted_item, $updated_item, $deleted_item, $browser_type, $os_type, $log_date, $log_time, $max_idx) {
                NewUserAccessLog::create([
                    'idx' => $max_idx,
                    'user_name' => $user_id,
                    'accessed_item' => $accessed_item,
                    'inserted_item' => $inserted_item,
                    'updated_item' => $updated_item,
                    'deleted_item' => $deleted_item,
                    'browser_type' => $browser_type,
                    'os_type' => $os_type,
                    'log_date' => $log_date,
                    'log_time' => $log_time,
                ]);
            });
            return ["Log created successfully"];
        } catch (Exception $e) {
            return ["Error " . $e->getMessage()];
        }
    }

    public function checkUndefined($val)
    {
        $val = $val == "undefined" ? null : $val;
        return $val;
    }
}
