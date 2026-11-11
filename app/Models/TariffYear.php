<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TariffYear extends Model
{
    use HasFactory;
    protected $table = 't_tariff_year';
    public $timestamps = false;
    public $incrementing = false;
    
    protected $fillable = [
        'code',
        'year'
    ];
}
