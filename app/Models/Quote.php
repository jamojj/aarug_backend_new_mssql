<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;
    protected $table = 'quote';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'quote_no',
        'prospect',
        'agent_id',
        'load_amt',
        'discount_amt',
        'prorate_days',
        'sd',
        'tl',
        'phcf',
        'quote_total',
        'premium_net',
        'quote_net',
        'quote_date',
        'physical_location',
        'disc_ip',
        'disc_op',
        'load_ip',
        'load_op',
        'individual',
        'idx',
        'date_entered',
        'user_id',
        'ira',
        'iiu',
        'address'
    ];
}
