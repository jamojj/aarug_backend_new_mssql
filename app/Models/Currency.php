<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $table = 't_currency';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'currency',
        'description',
        'corg'
    ];
}
