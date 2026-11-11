<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LastNumber extends Model
{
    use HasFactory;

    protected $table = 'LAST_NUMBER';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'TASK',
        'TASK_DESK',
        'LAST_NO'
    ];
}
