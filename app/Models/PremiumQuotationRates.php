<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumQuotationRates extends Model
{
    use HasFactory;
    protected $table = 'premium_quotation_rates';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'category',
        'overall_annual_limit',
        'amount',
    ];
}
