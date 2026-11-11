<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccessLog extends Model
{
    use HasFactory;
      protected $table = 'user_access_log';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'user_name',
        'accessed',
        'accessed_date',
        'accessed_time',
    ];
}
