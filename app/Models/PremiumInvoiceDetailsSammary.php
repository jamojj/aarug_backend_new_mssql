<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumInvoiceDetailsSammary extends Model
{
    use HasFactory;
    protected $table = 'premium_invoice_details_sammary';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id_key',
        'invoice_no',
        'class',
        'override_paid',
        'override_vno',
        'override_payno',
        'override_wh_tax',
        'net_premium',
        'allocated',
        'allocated_amt',
        'paid',
        'commission',
        'commis_vno',
        'commis_payno',
        'override_amt',
        'override_wh_tax_amt',
        'override_rate',
        'levied',
        'wh_tax',
        'commis_rate',
        'wh_tax_rate',
        'receipt_no',
        'contigency',
        'bdm_paid',
        'bdm_vno',
        'bdm_payno',
        'bdm_amt',
        'bdm_rate',
        'hos_paid',
        'hos_rate',
        'hos_amt',
        'hos_vno',
        'hos_payno',
        'hos_whtax',
        'first_aid',
        'other_costs',
        'gift_items',
        'smart_cost'
    ];
}
