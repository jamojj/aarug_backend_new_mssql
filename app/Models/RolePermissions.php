<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermissions extends Model
{
    use HasFactory;
      protected $table = 'role_permissions';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'id',
        'role_code',
        'permission_id'
    ];
}
