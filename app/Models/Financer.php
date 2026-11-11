<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Financer extends Model
{
    use HasFactory;

    protected $table = 'financer';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'fin_code',
        'financer',
        'rate'
    ];
}
