<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequePayment extends Model
{
    use HasFactory;
    protected $table = 'cheque_payment';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'payment_no',
        'cheque_no',
        'cheque_date',
        'cheque_amount',
        'payee',
        'payee_type',
        'dispatched',
        'date_dispatched',
        'user_id',
        'date_entered',
        'deduction',
        'vno_ovrd',
        'pno_ovrd',
        'pay_ovrd',
        'override_amt',
        'vno_ovrd_exec',
        'pno_ovrd_exec',
        'pay_ovrd_exec',
        'override_amt_exec',
        'total_commission',
        'wh_tax_rate',
        'wh_tax_amt',
        'override_rate',
        'commis_deduction',
        'bank_account',
        'vend',
        'integ',
        'payment_mode',
        'integ_date',
    ];
}
