<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRates extends Model
{
    use HasFactory;

    protected $table = 't_exchange_rate';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'fromdt',
        'todt',
        'rate',
        'currency'
    ];
}
