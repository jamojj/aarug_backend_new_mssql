<?php

namespace App\Http\Controllers;

use App\Models\RolePermissions;
use App\Models\UserGroups;
use App\Models\UserPermission;
use App\Models\UserRoleNew;
use Illuminate\Http\Request;
use App\Models\Users;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    public function fetchUsers(Request $request)
    {
        $password_count = strlen($request->password);
        $password = hash('sha256', $request->password);

        $user = Users::select('user_name', 'full_names', 'blocked')
            ->where('user_name', $request->username)
            ->where('user_pass', $password)
            ->get();
        $user_role =  UserRoleNew::where('user_name', $request->username)->pluck('user_role');
        $role_permissions = RolePermissions::whereIn('role_code', $user_role)->pluck('permission_id');
        $user_permission = UserPermission::where('user_name', $request->username)->get();

        // split into allow & deny
        $allow = $user_permission
            ->where('type_code', 2) //2 is allow
            ->pluck('permission_id');

        $deny = $user_permission
            ->where('type_code', 1) //1 is deny
            ->pluck('permission_id');

        // final permissions
        $permissions = $role_permissions
            ->merge($allow)
            ->diff($deny)
            ->unique()
            ->values();

        $data = [
            'user' => $user,
            'user_role' => $user_role,
            'permissions' => $permissions->toArray(),
            'pass_count' => $password_count
        ];

        return $data;
    }
    public function fetchUsers3(Request $request)
    {
        $password_count = strlen($request->password);
        $password = hash('sha256', $request->password);

        $user = Users::select('user_name', 'full_names', 'blocked')
            ->where('user_name', $request->username)
            ->where('user_pass', $password)
            ->get();
        $user_role =  UserRoleNew::where('user_name', $request->username)->pluck('user_role');

        $data = [
            'user' => $user,
            'user_role' => $user_role,
            'pass_count' => $password_count
        ];

        return $data;
    }
    public function fetchUsers2(Request $request)
    {
        $password_count = strlen($request->password);
        $password = hash('sha256', $request->password);

        $user = Users::select('user_name', 'full_names', 'blocked')
            ->where('user_name', $request->username)
            ->where('user_pass', $password)
            ->get();
        $user_groups =  UserGroups::where('user_name', $request->username)->pluck('user_group');

        $data = [
            'user' => $user,
            'user_group' => $user_groups,
            'pass_count' => $password_count
        ];

        return $data;
    }


    //generate code and send email and save on db
    public function generateMfaToken(Request $request)
    {
        try {
            $code = strtoupper(Str::random(6));

            $payload = [
                'user_id' => $request->username,
                'code' => $code,
                'expires_short_term' => now()->addMinutes(10)->timestamp,
                'expires_long_term' => now()->addDay()->timestamp
            ];


            //Encrypt token
            $token = encrypt(json_encode($payload));


            //send email
            $sendEmail = $this->sendEmailWithCode($request->username, $code, now()->addMinutes(10)->timestamp);
            if ($sendEmail["message"] == 'email sent') {
                //update  db
                DB::transaction(function () use ($request, $token) {
                    Users::where('user_name', $request->username)
                        ->update([
                            'mfa_token' => $token,
                            'token_verified' => null
                        ]);
                });
            }

            return $sendEmail;
        } catch (\Throwable $th) {
            return ['message' => 'Error ' . $th->getMessage()];
        }
    }
    //verify code automatically
    public function verifyMfaCodeAutomatic(Request $request)
    {
        try {
            $mfa = Users::select('mfa_token', 'token_verified')->where('user_name', $request->username)->get();
            $mfa_token = count($mfa) > 0 ? $mfa[0]->mfa_token : null;
            $token_verified = count($mfa) > 0 ? $mfa[0]->token_verified : null;
            //check when no code exist
            if (empty($mfa_token)) {
                return ['Token invalid'];
            }

            $data = json_decode(decrypt($mfa_token), true);

            if (
                now()->timestamp > $data['expires_long_term'] || $token_verified != 1
            ) {
                return ['Token invalid'];
            }
            return ['token is valid'];
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }

    //verify code first time manually
    public function verifyMfaCodeManual(Request $request)
    {
        try {
            $mfa_token = Users::select('mfa_token')->where('user_name', $request->username)->get();
            $mfa_token = count($mfa_token) > 0 ? $mfa_token[0]->mfa_token : null;

            //check when no code exist
            if (empty($mfa_token)) {
                return ['message' => 'Token invalid'];
            }
            //decrypt data
            $data = json_decode(decrypt($mfa_token), true);
            // Check if token expired or invalid
            if ($data['code'] != $request->code || now()->timestamp > $data['expires_long_term'] || now()->timestamp > $data['expires_short_term']) {
                return ['message' => 'Token invalid'];
            } else {
                //update token verified
                DB::transaction(function () use ($request) {
                    Users::where('user_name', $request->username)
                        ->update([
                            'token_verified' => 1
                        ]);
                });
            }

            return ['message' => 'token is valid', 'short_expiry' => $data['expires_short_term']];
        } catch (\Throwable $th) {
            return ['message' => 'Error ' . $th->getMessage()];
        }
    }


    // send email wth code 
    private function sendEmailWithCode($username, $code, $short_expiry)
    {
        try {
            $user_email = Users::select('email')->where('user_name', $username)->get();
            if (count($user_email) > 0) {
                $user_email = $user_email[0]->email;
                //send email
                Mail::html(
                    "
                        <h3>Your Login Code</h3>
                        <p>Your verification code is:</p>
                        <h2>{$code}</h2>
                        <p>This code expires in 10 minutes.</p>",
                    function ($message) use ($user_email) {
                        $message->to($user_email)
                            ->subject('Login Verification Code');
                    }
                );
                return ['message' => 'email sent', 'email' => $user_email, 'short_expiry' => $short_expiry];
            } else {
                return ['message' => 'User has no email'];
            }
            return ['message' => 'email not sent'];
        } catch (\Throwable $th) {
            return ['message' => 'Error ' . $th->getMessage()];
        }
    }
    // send email wth code 
    public function getShortExpiryTime(Request $request)
    {
        try {
            $mfa_token = Users::select('mfa_token')->where('user_name', $request->username)->get();
            $mfa_token = count($mfa_token) > 0 ? $mfa_token[0]->mfa_token : null;

            //check when no code exist
            if (empty($mfa_token)) {
                return ['message' => 'Token invalid'];
            }
            //decrypt data
            $data = json_decode(decrypt($mfa_token), true);

            if (now()->timestamp > $data['expires_short_term']) {
                return ['message' => 'Token invalid'];
            }

            return ['message' => 'Valid Token', 'short_expiry' => $data['expires_short_term']];
        } catch (\Throwable $th) {
            return ['message' => 'Error ' . $th->getMessage()];
        }
    }



    public function updateUserPassword(Request $request)
    {
        $today = date('Y-m-d');
        $old_password = hash('sha256', $request->old_password);
        $new_password = hash('sha256', $request->new_password);
        $user_exists = Users::where('user_name', $request->username)
            ->where('user_pass', $old_password)
            ->get();
        if (count($user_exists) !== 0) {
            try {
                DB::transaction(function () use ($request, $old_password, $new_password, $today) {
                    //set inserted, updated, and deleted items
                    $inserted_item = null;
                    $updated_item = "password : password";
                    $deleted_item = null;

                    Users::where(['user_name' => $request->username, 'user_pass' => $old_password])
                        ->update([
                            'user_pass' => $new_password,
                            'date_entered' => $today
                        ]);

                    //save access log
                    app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
                });
                return ['Password updated successfully'];
            } catch (Exception $e) {
                return ['Error ' . $e->getMessage()];
            }
        } else {
            return ['Existing password or username is not correct!'];
        }
    }

    public function checkLastPasswordChange(Request $request)
    {
        $today = date('Y-m-d');
        $date_entered = Users::select('date_entered')->where('user_name', $request->username)->where('full_names', $request->fullname)->get();
        $date_entered = count($date_entered) !== 0 ? $date_entered[0]->date_entered : '1970-01-01';
        $date_entered = \Carbon\Carbon::parse($date_entered);
        $today = \Carbon\Carbon::parse($today);
        $period = $date_entered->diffInDays($today, false);
        return $period;
    }

    public function checkIfActiveOrBlocked(Request $request)
    {
        $status = Users::select(
            DB::raw("CASE WHEN status = 1 or blocked = 1 THEN 'inactive' else 'active' END AS status")
        )
            ->where('user_name', $request->username)
            ->where('full_names', $request->fullname)
            ->get();
        $status = $status[0]->status;
        return [$status];
    }

    public function checkEmailExists($user)
    {
        $email = Users::select('email')->where('user_name', $user)->get();
        $email = count($email) != 0 ? $email[0]->email : null;
        if (is_null($email) || $email == '') {
            return [];
        } else {
            return [$email];
        }
    }

    public function updateUserEmail(Request $request)
    {
        try {
            $email = $request->email . '@aar-insurance.com';
            DB::transaction(function () use ($request, $email) {
                //set inserted, updated, and deleted items
                $inserted_item = null;
                $updated_item = "email : $email";
                $deleted_item = null;
                Users::where('user_name', $request->user)->update([
                    'email' => $email
                ]);

                //save access log
                app('App\Http\Controllers\AccessLogs\AccessLogController')->saveNewAccessLogsFunc($request->user_id, $request->accessed_item, $inserted_item, $updated_item, $deleted_item, $request->browser_type, $request->os_type, $request->ip_address, $request->isp, $request->city, $request->country);
            });
            return ['Email updated successfully.'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
