<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermissionTypes extends Model
{
    use HasFactory;
        protected $table = 'user_permission_types';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'code',
        'type'
    ];
}
