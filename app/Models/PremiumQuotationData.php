<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumQuotationData extends Model
{
    use HasFactory;
    protected $table = 'premium_quotation_data';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'corporate',
        'quote_id',
        'quote_no',
        'females',
        'males',
        'inflation_discount',
        'ug_illness',
        'hp_maternity',
        'hp_outpatient',
        'hp_dental',
        'dental_avg',
        'maternity_avg',
        'optical',
        'commission_rate',
        'admin_rate',
        'profit_rate',
        'smart_cost',
        'active_lives',
        'start_date',
        'end_date'       
    ];
}
