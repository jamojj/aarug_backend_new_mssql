<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CignaUsdExchangeRate extends Model
{
    use HasFactory;
    protected $table = 'cigna_usd_exchange_rate';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'rate_from',
        'rate_to',
        'ugx_to_usd_rate',
        'user_id',
        'date_entered'
    ];
}
