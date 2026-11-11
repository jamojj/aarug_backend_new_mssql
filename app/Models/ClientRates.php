<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRates extends Model
{
    use HasFactory;
    protected $table = 'client_rates';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = [
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
        'idx',
        'corp_id',
        'family_title',
        'commis_rate',
        'withholding_tax_rate',
        'premium_type',
        'health_plan',
        'family_no'
    ];
}
