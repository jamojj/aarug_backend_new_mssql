<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumInvoiceMembers extends Model
{
    use HasFactory;

    protected $table = 'premium_invoice_members';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'invoice_no',
        'member_no',
        'anniv',
        'start_date',
        'relation',
        'premium',
        'allocated_amount',
        'receipt_no',
        'date_allocated'
    ];
}
