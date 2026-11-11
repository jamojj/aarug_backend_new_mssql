<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumReceipt extends Model
{
    use HasFactory;
    protected $table = 'premium_receipt';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'receipt_no',
        'invoice_no',
        'receipt_amount',
        'receipt_date',
        'payment_mode',
        'payment_terms',
        'cheque_no',
        'financer',
        'unallocated_amt',
        'corp_id',
        'member_no',
        'user_id',
        'date_entered',
        'receipt_bank',
        'fund_receipt',
        'agent_id',
        'commis_paid',
        'commis_vno',
        'commis_payno',
        'commis_rate',
        'commis_amt',
        'commis_whtax_rate',
        'commis_whtax_amt',
        'commis_net',
        'commis_excempt',
        'allocated',
        'account_toallocate',
        'integ',
        'integ_date',
        'sys_rate',
        'user_rate',
        'currency',
        'trans_amt',
        'reversed'
    ];
}
