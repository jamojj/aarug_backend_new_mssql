<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteDetails extends Model
{
    use HasFactory;
    protected $table = 'quote_details';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'key_id',
        'quote_no',
        'benefit',
        'family_size',
        'family_count',
        'limit',
        'premium',
        'total',
        'category',
        'quote_option',
        'discounted_prem',
        'disc_rate',
        'loaded_prem',
        'load_rate',
        'product_name',
        'corp_id',
        'family_no',
        'sharing',
        'sub_limit_of',       
    ];
}
