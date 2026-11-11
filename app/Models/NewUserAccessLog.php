<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewUserAccessLog extends Model
{
    use HasFactory;
    protected $table = 'new_user_access_log';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'user_name',
        'accessed_item',
        'inserted_item',
        'updated_item',
        'deleted_item',
        'browser_type',
        'os_type',      
        'log_date',
        'log_time',
    ];
}
