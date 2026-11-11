<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CignaPremiumInvoiceDetailsSummary extends Model
{
    use HasFactory;
    protected $table = 'cigna_premium_invoice_details_summary';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'family_no',
        'person_no',
        'plan',
        'dental',
        'dob',
        'name',
        'age',
        'AOC',
        'payment_frequency',
        'invoice_no',
        'plan_premium',
        'dental_premium',
        'loading',
        'deduction',
        'date_entered',
        'user_id'
    ];
}
