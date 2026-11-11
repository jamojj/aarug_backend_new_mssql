<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Users;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChangePasswordController extends Controller
{
    //fetch usernames
    public function fetchUsernames()
    {
        $users = Users::select('user_name')
            ->orderBy('user_name', 'ASC')
            ->get();
        return $users;
    }
    //save password change by the admin
    public function saveChangePassword(Request $request)
    {
        // return $request;
        $response = [];
        $today = date('Y-m-d');
        $password = hash('sha256', $request->new_password);
        if ($request->new_password == $request->confirm_new_password) {
            Users::where([
                'user_name' => $request->username
            ])->update(
                [
                    'user_pass' => $password,
                    'date_entered' => $today
                ]
            );
            array_push($response, "Password Changed...New password applies in the next log in");
        } else {
            array_push($response, "New password and password confirmation differs!");
        }
        return $response;
    }

    //save password change by the user
    public function saveProfileChangePassword(Request $request)
    {
        $today = date('Y-m-d');
        $existing_pass = hash('sha256', $request->existing_password);
        $new_password = hash('sha256', $request->new_password);
        $user_exists = Users::where('user_name', $request->username)
            ->where('user_pass', $existing_pass)
            ->get();
        if (count($user_exists) !== 0) {
            try {
                DB::transaction(function () use ($request, $existing_pass, $new_password, $today) {

                    Users::where(['user_name' => $request->username, 'user_pass' => $existing_pass])
                        ->update([
                            'user_pass' => $new_password,
                            'date_entered' => $today
                        ]);
                });
                return ['Password updated successfully'];
            } catch (Exception $e) {
                return ['Error ' . $e->getMessage()];
            }
        } else {
            return ['Existing password or username is not correct!'];
        }
    }
}
