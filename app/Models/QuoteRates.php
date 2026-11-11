<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteRates extends Model
{
    use HasFactory;
    protected $table = 'quote_rates';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'benefit',
        'product_name',
        'family_size',
        'limit',
        'premium',
        'min_age',
        'max_age',
        're_rate',
        'individual',
        'insurer',
        'corp_id',
        'family_title',
        'commis_rate',
        'withholding_tax_rate',
        'premium_type',
        'health_plan',
        'family_no',
        'quote_no',
        'family_count',
        'total',
        'adjusted'
    ];
}
