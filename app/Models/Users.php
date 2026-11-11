<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    use HasFactory;
    protected $table = 'users';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'user_name', 'user_pass', 'full_names', 'department', 'status',
        'date_entered', 'created_by', 'blocked', 'super_user', 'create_date', 'email', 'mfa_token','token_verified'
    ];
}
