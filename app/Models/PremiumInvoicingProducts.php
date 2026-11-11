<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumInvoicingProducts extends Model
{
    use HasFactory;
    protected $table = 'premium_invoicing_products';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'product_name',
        'date_entered',
        'user_id',
    ];
}
