<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletedLog extends Model
{
    use HasFactory;
    protected $table = 'deleted_log';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'deleted_info',
        'user_id',
        'date_deleted',
        'deleted_class',
    ];
}
