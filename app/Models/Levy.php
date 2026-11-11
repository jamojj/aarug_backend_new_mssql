<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Levy extends Model
{
    use HasFactory;
    protected $table = 't_levy';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'rate',
        'levy',
    ];
}
