<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banks extends Model
{
    use HasFactory;
    protected $table = 'BANKS';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'bank'
    ];
}
