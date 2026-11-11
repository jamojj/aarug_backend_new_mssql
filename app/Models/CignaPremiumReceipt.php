<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CignaPremiumReceipt extends Model
{
    use HasFactory;
    protected $table = 'cigna_premium_receipt';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'receipt_no',
        'family_no',
        'receipt_amount',
        'payment_mode',
        'cheque_no',
        'bank',
        'bank_account',
        'user_id',
        'date_entered',
        'receipt_date'      
    ];
}
