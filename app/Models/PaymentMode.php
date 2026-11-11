<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{
    use HasFactory;

    protected $table = 't_payment_mode';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        'payment_mode'
    ];

}


