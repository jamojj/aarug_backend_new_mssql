<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReinsurerExcessOfLoss extends Model
{
    use HasFactory;
    protected $table = 're_insurer_excess_of_loss';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'code',
        're_insurer',
        'upper_limit',
        'rate_period'       
    ];
}
