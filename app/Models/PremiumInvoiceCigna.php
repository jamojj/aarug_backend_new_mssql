<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumInvoiceCigna extends Model
{
    use HasFactory;
    protected $table = 'premium_invoice_cigna';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'invoice_no',
        'policy_no',
        'family_no',
        'person_no',
        'stamp_duty',
        'iiu',
        'premium_net',
        'total_premium',
        'date_entered',
        'user_id'
    ];
}
