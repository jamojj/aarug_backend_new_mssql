<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumInvoiceDetails extends Model
{
    use HasFactory;

    protected $table = 'premium_invoice_details';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'family_no',
        'member_no',
        'benefit',
        'limit',
        'premium',
        'invoice_no',
        'sharing',
        'family_size',
        'family_age',
        'total',
        'prorated_prem',
        'discounted_prem',
        'loaded_prem',
        'disc_amt',
        'load_amt',
        'category',
        'idx',
        'member_age',
        'product_name',
        'family_title',
        'prorata',
        'join_date',
        'prorata_period',
        'effective_date',
        'end_date',
        'smart_cost',
        'access_fee'
    ];
}
