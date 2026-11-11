<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HaisTriggers extends Model
{
    use HasFactory;
    protected $table = 't_hais_triggers';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'descr'
    ];
}
