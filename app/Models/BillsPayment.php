<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillsPayment extends Model
{
    use HasFactory;
    protected $table = 'bills_payment';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
        'PAYMENT_NO',
        'CHEQUE_NO',
        'CHEQUE_DATE',
        'CHEQUE_AMOUNT',
        'PROVIDER',
        'MEMBER_NO',
        'CORP_ID',
        'DISPATCHED',
        'DATE_DISPATCHED',
        'USER_ID',
        'DATE_ENTERED',
        'admin_fee',
        'corp_paid_for',
        'acct_no',
        'bank',
        'self_fund',
        'notes',
        'payrun_date',
        'ccapay_no',
        'amount_payable',
        'discounts',
        'wtax',
        'wtax_rate',
        'bank_account',
        'integ',
        'tax',
        'payment_mode',
        'integ_date'
    ];
}
