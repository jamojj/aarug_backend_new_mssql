<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoleNew extends Model
{
    use HasFactory;
    protected $table = 'user_role_new';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'id',
        'user_name',
        'user_role'
    ];
}
