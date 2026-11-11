<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumPaymentSchedule extends Model
{
    use HasFactory;
    protected $table = 'premium_payment_schedule';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'invoice_no',
        'payable_date',
        'payable_amt',
        'paid',
        'paid_date',
        'paid_amt',
    ];
}
