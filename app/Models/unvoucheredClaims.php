<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class unvoucheredClaims extends Model
{
    use HasFactory;
    protected $table = 'unvouchered_claims';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'claim_no',
        'provider',
        'batch_no',
        'voucher_no',
        'voucher_user',
        'voucher_date',
        'unvoucher_user',
        'unvoucher_date'      
    ];
}
