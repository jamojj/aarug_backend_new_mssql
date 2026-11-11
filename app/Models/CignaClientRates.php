<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CignaClientRates extends Model
{
    use HasFactory;
     protected $table = 'cigna_client_rates';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'idx',
        'premium',
        'min_age',
        'max_age',
        'age',
        'plan',
        'AOC',
        'new_or_renewal',
        'date_entered',
        'user_id'
    ];
}
