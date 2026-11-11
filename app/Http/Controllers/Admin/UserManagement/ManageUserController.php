<?php

namespace App\Http\Controllers\Admin\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\RolePermissions;
use App\Models\UserGroup;
use App\Models\UserGroups;
use App\Models\UserPermission;
use App\Models\UserRoleNew;
use App\Models\Users;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManageUserController extends Controller
{
    public function fetchUsers()
    {
        $users = Users::select('*')
            ->orderBy('user_name', 'ASC')
            ->get();
        return $users;
    }

    public function fetchUserGroup($user_name)
    {
        $user_group = UserGroups::select('*')
            ->where('user_name', $user_name)
            ->get();
        return $user_group;
    }
    public function fetchUserRole($user_name)
    {
        $user_role = UserRoleNew::select('*')
            ->where('user_name', $user_name)
            ->get();
        return $user_role;
    }
    public function fetchUserPermissions($user_name)
    {
        $user_role = UserRoleNew::where('user_name', $user_name)
            ->pluck('user_role');

        $user_permissions = UserPermission::where('user_name', $user_name)
            ->get()
            ->keyBy('permission_id');

        // 🚨 If user has NO roles → return only user permissions
        if ($user_role->isEmpty()) {
            return $user_permissions->map(function ($p) use ($user_name) {
                return [
                    'user_name' => $user_name,
                    'permission_id' => $p->permission_id,
                    'type_code' => $p->type_code,
                    'category' => 'user'
                ];
            })->values();
        }

        // Continue normal flow if roles exist
        $role_permissions = RolePermissions::whereIn('role_code', $user_role)
            ->pluck('permission_id');

        $result = $role_permissions->map(function ($perm) use ($user_permissions, $user_name) {

            if (isset($user_permissions[$perm])) {
                return [
                    'user_name' => $user_name,
                    'permission_id' => $perm,
                    'type_code' => $user_permissions[$perm]->type_code,
                    'category' => 'both'
                ];
            }

            return [
                'user_name' => $user_name,
                'permission_id' => $perm,
                'type_code' => 2,
                'category' => 'role'
            ];
        });

        $extra = $user_permissions
            ->filter(fn($p) => !$role_permissions->contains($p->permission_id))
            ->map(function ($p) use ($user_name) {
                return [
                    'user_name' => $user_name,
                    'permission_id' => $p->permission_id,
                    'type_code' => $p->type_code,
                    'category' => 'user'
                ];
            });

        return $result->merge($extra)->values();
    }
    public function fetchUserPermissions3($user_name)
    {
        $user_role = UserRoleNew::where('user_name', $user_name)
            ->pluck('user_role');

        $role_permissions = RolePermissions::whereIn('role_code', $user_role)
            ->pluck('permission_id');

        $user_permissions = UserPermission::where('user_name', $user_name)
            ->get()
            ->keyBy('permission_id'); // 🔥 user overrides

        // Start with role permissions
        $result = $role_permissions->map(function ($perm) use ($user_permissions, $user_name) {

            // If user has it → use user record
            if (isset($user_permissions[$perm])) {
                return [
                    'user_name' => $user_name,
                    'permission_id' => $perm,
                    'type_code' => $user_permissions[$perm]->type_code,
                    'category' => 'both' // override
                ];
            }

            // Otherwise → default from role
            return [
                'user_name' => $user_name,
                'permission_id' => $perm,
                'type_code' => 2,
                'category' => 'role'


            ];
        });

        // Add user permissions that are NOT in role
        $extra = $user_permissions
            ->filter(fn($p) => !$role_permissions->contains($p->permission_id))
            ->map(function ($p) use ($user_name) {
                return [
                    'user_name' => $user_name,
                    'permission_id' => $p->permission_id,
                    'type_code' => $p->type_code,
                    'category' => 'user' // extra
                ];
            });

        // Merge everything
        $result = $result->merge($extra)->values();
        return $result;
    }
    public function fetchUserPermissions2($user_name)
    {
        $user_permission = UserPermission::select('*')
            ->where('user_name', $user_name)
            ->get();
        return $user_permission;
    }

    public function setDefaultPassword()
    {
        $password = hash('sha256', 'Pass123');
        return ['password' => $password];
    }
    public function saveUserManagement(Request $request)
    {
        // return $request;
        try {
                $max_idx = UserGroups::max('idx');
                $today = date('Y-m-d');

                //update users
                if (isset($request->user_name)) {
                    $count = count($request->user_name);
                    for ($i = 0; $i < $count; $i++) {
                        DB::transaction(function () use ($request, $i) {
                            Users::where([
                                'user_name' => $request->user_name[$i]
                            ])->update([
                                // 'user_name' => $request->user_name[$i],
                                'full_names' => $request->full_names[$i],
                                'department' => $request->department[$i],
                                'status' => $request->status[$i],
                                'blocked' => $request->blocked[$i],
                                'created_by' => $request->created_by[$i]
                            ]);
                        });
                    }
                }
                //save new user new
                if (isset($request->user_name_added)) {
                        $existing_user = Users::where('user_name', $request->user_name_added)
                            ->pluck('user_name');
                        if (count($existing_user) == 0) {
                            DB::transaction(function () use ($request, $today) {
                                Users::create([
                                    'user_name' => $request->user_name_added,
                                    'user_pass' => $request->user_pass_added,
                                    'full_names' => $request->full_names_added,
                                    'department' => $request->department_added,
                                    'status' => $request->status_added,
                                    'blocked' => $request->blocked_added,
                                    'created_by' => $request->created_by_added,
                                    'date_entered' => $today,
                                    'create_date' => $today,
                                ]);
                            });
                        }
                }
                //save new user old
                // if (isset($request->user_name_added)) {
                //     $count = count($request->user_name_added);
                //     for ($i = 0; $i < $count; $i++) {
                //         $existing_user = Users::where('user_name', $request->user_name_added[$i])
                //             ->pluck('user_name');
                //         if (count($existing_user) == 0) {
                //             DB::transaction(function () use ($request, $i, $today) {
                //                 Users::create([
                //                     'user_name' => $request->user_name_added[$i],
                //                     'user_pass' => $request->user_pass_added[$i],
                //                     'full_names' => $request->full_names_added[$i],
                //                     'department' => $request->department_added[$i],
                //                     'status' => $request->status_added[$i],
                //                     'blocked' => $request->blocked_added[$i],
                //                     'created_by' => $request->created_by_added[$i],
                //                     'date_entered' => $today,
                //                     'create_date' => $today,
                //                 ]);
                //             });
                //         }
                //     }
                // }
                //update user_groups
                // if (isset($request->user_group_idx)) {
                //     $count = count($request->user_group_idx);
                //     for ($i = 0; $i < $count; $i++) {
                //         $update_user_group = UserGroups::where([
                //             'idx' => $request->user_group_idx[$i]
                //         ])->update([
                //             'user_group' => $request->user_group_row[$i]
                //         ]);

                //         if ($update_user_group == 0) {
                //             $errors_update++;
                //         }
                //     }
                // }
                //save user_groups
                // if (isset($request->user_name_appended)) {
                //     $count = count($request->user_name_appended);
                //     for ($i = 0; $i < $count; $i++) {
                //         $max_idx++;
                //         $existing_user_group = UserGroups::where('user_group', $request->user_group_appended[$i])
                //             ->where('user_name', $request->user_name_appended[$i])
                //             ->pluck('user_group');
                //         if (count($existing_user_group) == 0) {
                //             $save_user_groups = UserGroups::create([
                //                 'idx' => $max_idx,
                //                 'user_name' => $request->user_name_appended[$i],
                //                 'user_group' => $request->user_group_appended[$i]
                //             ]);

                //             if ($save_user_groups->wasRecentlyCreated !== true) {
                //                 $errors_save++;
                //             }
                //         } else {
                //             array_push($response, "That UserGroup Already Exists For This User");
                //         }
                //     }
                // }

                //update user role
                // if (isset($request->user_role_id)) {
                //     $count = count($request->user_role_id);
                //     for ($i = 0; $i < $count; $i++) {
                //         $update_user_role = UserRoleNew::where([
                //             'id' => $request->user_role_id[$i]
                //         ])->update([
                //             'user_role' => $request->user_role_row[$i]
                //         ]);

                //         if ($update_user_role == 0) {
                //             $errors_update++;
                //         }
                //     }
                // }

                //save user roles
                if (isset($request->user_name_role_appended)) {
                    $count = count($request->user_name_role_appended);
                    for ($i = 0; $i < $count; $i++) {
                        $existing_user_role = UserRoleNew::where('user_role', $request->user_role_appended[$i])
                            ->where('user_name', $request->user_name_role_appended[$i])
                            ->pluck('user_role');
                        if (count($existing_user_role) == 0) {
                            DB::transaction(function () use ($request, $i) {
                                UserRoleNew::create([
                                    'user_name' => $request->user_name_role_appended[$i],
                                    'user_role' => $request->user_role_appended[$i]
                                ]);
                            });
                        }
                    }
                }

                //update user permissions
                if (isset($request->permission_id)) {
                    $count = count($request->permission_id);
                    for ($i = 0; $i < $count; $i++) {
                        //check where permission exists
                        if ($request->permission_category[$i] == 'role') { // exists in role alone
                            //only create those that have type code deny or 1
                            if ($request->permission_type[$i] == 1) {
                                DB::transaction(function () use ($request, $i) {
                                    UserPermission::create([
                                        'user_name' => $request->user,
                                        'permission_id' => $request->permission_id[$i],
                                        'type_code' => $request->permission_type[$i]
                                    ]);
                                });
                            }
                        } else { //exists in both or in user permission alone
                            DB::transaction(function () use ($request, $i) {
                                UserPermission::where([
                                    'user_name' => $request->user,
                                    'permission_id' => $request->permission_id[$i]
                                ])->update([
                                    'type_code' => $request->permission_type[$i]
                                ]);
                            });
                        }
                    }
                }
                //save user permissions
                if (isset($request->permission_id_appended)) {
                    $count = count($request->permission_id_appended);
                    for ($i = 0; $i < $count; $i++) {
                        $existing_user_permission = UserPermission::where('permission_id', $request->permission_id_appended[$i])
                            ->where('user_name', $request->user)
                            ->pluck('permission_id');
                        if (count($existing_user_permission) == 0) {
                            DB::transaction(function () use ($request, $i) {
                                UserPermission::create([
                                    'user_name' => $request->user,
                                    'permission_id' => $request->permission_id_appended[$i],
                                    'type_code' => $request->permission_type_appended[$i]
                                ]);
                            });
                        }
                    }
                }
            return ['success'];
        } catch (\Throwable $th) {
            return ['Error ' . $th->getMessage()];
        }
    }

    public function saveUserManagement2(Request $request)
    {
        // return $request;
        $response = [];
        $errors_save = $errors_update = 0;
        $max_idx = UserGroups::max('idx');
        $today = date('Y-m-d');

        //update users
        if (isset($request->user_name)) {
            $count = count($request->user_name);
            for ($i = 0; $i < $count; $i++) {
                $update_users = Users::where([
                    'user_name' => $request->user_name[$i]
                ])->update([
                    'user_name' => $request->user_name[$i],
                    'full_names' => $request->full_names[$i],
                    'department' => $request->department[$i],
                    'status' => $request->status[$i],
                    'blocked' => $request->blocked[$i],
                    'created_by' => $request->created_by[$i]
                ]);

                if ($update_users == 0) {
                    $errors_update++;
                }
            }
        }
        //save users
        if (isset($request->user_name_added)) {
            $count = count($request->user_name_added);
            for ($i = 0; $i < $count; $i++) {
                $existing_user = Users::where('user_name', $request->user_name_added[$i])
                    ->pluck('user_name');
                if (count($existing_user) == 0) {
                    $save_users = Users::create([
                        'user_name' => $request->user_name_added[$i],
                        'user_pass' => $request->user_pass_added[$i],
                        'full_names' => $request->full_names_added[$i],
                        'department' => $request->department_added[$i],
                        'status' => $request->status_added[$i],
                        'blocked' => $request->blocked_added[$i],
                        'created_by' => $request->created_by_added[$i],
                        'date_entered' => $today,
                        'create_date' => $today,
                    ]);

                    if ($save_users->wasRecentlyCreated !== true) {
                        $errors_save++;
                    }
                } else {
                    array_push($response, "That Username Already Exists");
                }
            }
        }
        //update user_groups
        // if (isset($request->user_group_idx)) {
        //     $count = count($request->user_group_idx);
        //     for ($i = 0; $i < $count; $i++) {
        //         $update_user_group = UserGroups::where([
        //             'idx' => $request->user_group_idx[$i]
        //         ])->update([
        //             'user_group' => $request->user_group_row[$i]
        //         ]);

        //         if ($update_user_group == 0) {
        //             $errors_update++;
        //         }
        //     }
        // }
        //save user_groups
        // if (isset($request->user_name_appended)) {
        //     $count = count($request->user_name_appended);
        //     for ($i = 0; $i < $count; $i++) {
        //         $max_idx++;
        //         $existing_user_group = UserGroups::where('user_group', $request->user_group_appended[$i])
        //             ->where('user_name', $request->user_name_appended[$i])
        //             ->pluck('user_group');
        //         if (count($existing_user_group) == 0) {
        //             $save_user_groups = UserGroups::create([
        //                 'idx' => $max_idx,
        //                 'user_name' => $request->user_name_appended[$i],
        //                 'user_group' => $request->user_group_appended[$i]
        //             ]);

        //             if ($save_user_groups->wasRecentlyCreated !== true) {
        //                 $errors_save++;
        //             }
        //         } else {
        //             array_push($response, "That UserGroup Already Exists For This User");
        //         }
        //     }
        // }

        //update user role
        // if (isset($request->user_role_id)) {
        //     $count = count($request->user_role_id);
        //     for ($i = 0; $i < $count; $i++) {
        //         $update_user_role = UserRoleNew::where([
        //             'id' => $request->user_role_id[$i]
        //         ])->update([
        //             'user_role' => $request->user_role_row[$i]
        //         ]);

        //         if ($update_user_role == 0) {
        //             $errors_update++;
        //         }
        //     }
        // }

        //save user roles
        if (isset($request->user_name_role_appended)) {
            $count = count($request->user_name_role_appended);
            for ($i = 0; $i < $count; $i++) {
                $existing_user_role = UserRoleNew::where('user_role', $request->user_role_appended[$i])
                    ->where('user_name', $request->user_name_role_appended[$i])
                    ->pluck('user_role');
                if (count($existing_user_role) == 0) {
                    $save_user_role = UserRoleNew::create([
                        'user_name' => $request->user_name_role_appended[$i],
                        'user_role' => $request->user_role_appended[$i]
                    ]);

                    if ($save_user_role->wasRecentlyCreated !== true) {
                        $errors_save++;
                    }
                } else {
                    array_push($response, "That User role Already Exists For This User");
                }
            }
        }

        //update user permissions
        if (isset($request->permission_id)) {
            $count = count($request->permission_id);
            for ($i = 0; $i < $count; $i++) {
                $update_user_permission = UserPermission::where([
                    'user_name' => $request->user,
                    'permission_id' => $request->permission_id[$i]
                ])->update([
                    'type_code' => $request->permission_type[$i]
                ]);

                if ($update_user_permission == 0) {
                    $errors_update++;
                }
            }
        }
        //save user permissions
        if (isset($request->permission_id_appended)) {
            $count = count($request->permission_id_appended);
            for ($i = 0; $i < $count; $i++) {
                $existing_user_permission = UserPermission::where('permission_id', $request->permission_id_appended[$i])
                    ->where('user_name', $request->user)
                    ->pluck('permission_id');
                if (count($existing_user_permission) == 0) {
                    $save_user_permission = UserPermission::create([
                        'user_name' => $request->user,
                        'user_role' => $request->permission_id_appended[$i],
                        'type_code' => $request->permission_type_appended[$i]
                    ]);

                    if ($save_user_permission->wasRecentlyCreated !== true) {
                        $errors_save++;
                    }
                } else {
                    array_push($response, "That User permission Already Exists For This User");
                }
            }
        }

        if ($errors_save == 0 && $errors_update == 0) {
            array_push($response, "User saved successfully.");
        } else {
            if ($errors_save > 0) {
                array_push($response, "Error saving User");
            } else if ($errors_update > 0) {
                array_push($response, "Error updating User");
            }
        }


        return $response;
    }

    public function deleteUserGroup(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                UserGroups::where('idx', $request->idx)->delete();
            });
            return ['User group removed successfully!'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
    public function deleteUserRole(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                UserRoleNew::where('user_role', $request->user_role)->where('user_name', $request->user)->delete();
            });
            return ['User role removed successfully!'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
    // for permissions that only exist in user permission table and not in user role
    public function deleteUserPermission(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                UserPermission::where('permission_id', $request->permission_id)->where('user_name', $request->user)->delete();
            });
            return ['User permission removed successfully!'];
        } catch (Exception $e) {
            return ['Error ' . $e->getMessage()];
        }
    }
}
