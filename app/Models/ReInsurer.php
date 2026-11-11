<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReInsurer extends Model
{
    use HasFactory;
    protected $table = 're_insurer';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        're_insurer',
        'address',
        'physical_location',
        'ip_rate',
        'commis_rate',
        'rate_period',
        're_broker',
        'tax_rate',
        'retain_rate',
    ];
}
