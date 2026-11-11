<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumInvoicingCorporateInvoices extends Model
{
    use HasFactory;
    protected $table = 'premium_invoicing_corporate_invoices';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'corporate',
        'inv_no',
        'date_entered',
        'user_id',
    ];
}
