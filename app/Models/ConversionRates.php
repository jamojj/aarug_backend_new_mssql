<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversionRates extends Model
{
    use HasFactory;
    protected $table = 'conversion_rates';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'currency',
        'value',
        'operation',
    ];
}
