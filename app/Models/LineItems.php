<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineItems extends Model
{
    use HasFactory;
    protected $table = 'line_items';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'item_code',
        'invoice_no',
        'service',
        'provider',
        'idx',
        'category',
        'service_items',
        'unit_of_measure',
        'invoiced_amt',
        'date_entered',
        'item_class',
        'user_id',
        'year',
        'quantity',
        'recomm_amt',
        'variation',
        'claim_no',
        'smart_bill_id',
        'auto_vetted',
        'adjusted_amt',
        'parent_id'
    ];
}
