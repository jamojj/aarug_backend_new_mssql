<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PremiumInvoicingCorporates extends Model
{
    use HasFactory;
    protected $table = 'premium_invoicing_corporates';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'corporate',
        'date_entered',
        'user_id',
    ];
}
