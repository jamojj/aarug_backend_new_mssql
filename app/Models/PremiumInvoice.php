<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumInvoice extends Model
{
    use HasFactory;
    protected $table = 'premium_invoice';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'INVOICE_NO',
        'MEMBER_NO',
        'CORP_ID',
        'INVOICE_DATE',
        'INVOICE_TOTAL',
        'ANNIV',
        'USER_ID',
        'DATE_ENTERED',
        'premium_type',
        'INVOICE_NET',
        'DISCOUNT_AMT',
        'AGENT_ID',
        'PREMIUM_NET',
        'DISCOUNT_ALLOWED',
        'FUND_INVOICE',
        'PRORATED',
        'insurer',
        'allocated',
        'allocated_amt',
        'cover_start_date',
        'load',
        'new_renewal',
        'stamp_duty',
        'phcf',
        'tl',
        'unit_manager',
        'nullify',
        'credit_note',
        'prorate_days',
        'disc_ip',
        'disc_op',
        'load_ip',
        'load_op',
        'idx',
        'disc_dent',
        'disc_opt',
        'disc_fun',
        'disc_mater',
        'disc_all',
        'disc_pa',
        'prorate_days_month',
        'notes',
        'end_date',
        'days',
        'contigency',
        'sync',
        'integ_date',
        'smart_cost',
        'access_fee',
        'gift_items',
        'first_aid',
        'retained',
        'ceded',
        'other',
        'agent_rate',
        'bdm_rate',
        'ass_hos_rate',
        'hos_rate',
        'reversed',
    ];
}
